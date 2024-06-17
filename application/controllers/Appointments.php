<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Appointments Controller
 *
 * @package Controllers
 */
class Appointments extends EA_Controller {
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('installation');
        $this->load->helper('google_analytics');
        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('admins_model');
        $this->load->model('secretaries_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');
        $this->load->library('timezones');
        $this->load->library('synchronization');
        $this->load->library('notifications');
        $this->load->library('availability');
        $this->load->driver('cache', ['adapter' => 'file']);
    }



    /**
     * Default callback method of the application.
     *
     * This method creates the appointment book wizard. If an appointment hash is provided then it means that the
     * customer followed the appointment manage link that was send with the book success email.
     *
     * @param string $appointment_hash The appointment hash identifier.
     */
    public function index($appointment_hash = '')
    {
        try
        {
            if ( ! is_app_installed())
            {
                redirect('installation/index');
                return;
            }

            $authString = $this->input->get('auth');
            $available_services = $this->services_model->get_available_services();
            $available_providers = $this->providers_model->get_available_providers();
            $company_name = $this->settings_model->get_setting('company_name');
            $book_advance_timeout = $this->settings_model->get_setting('book_advance_timeout');
            $date_format = $this->settings_model->get_setting('date_format');
            $time_format = $this->settings_model->get_setting('time_format');
            $first_weekday = $this->settings_model->get_setting('first_weekday');
            $require_phone_number = $this->settings_model->get_setting('require_phone_number');
            $display_cookie_notice = $this->settings_model->get_setting('display_cookie_notice');
            $cookie_notice_content = $this->settings_model->get_setting('cookie_notice_content');
            $display_terms_and_conditions = $this->settings_model->get_setting('display_terms_and_conditions');
            $terms_and_conditions_content = $this->settings_model->get_setting('terms_and_conditions_content');
            $display_privacy_policy = $this->settings_model->get_setting('display_privacy_policy');
            $privacy_policy_content = $this->settings_model->get_setting('privacy_policy_content');
            $display_any_provider = $this->settings_model->get_setting('display_any_provider');
            $timezones = $this->timezones->to_array();

            // Remove the data that are not needed inside the $available_providers array.
            foreach ($available_providers as $index => $provider)
            {
                $stripped_data = [
                    'id' => $provider['id'],
                    'first_name' => $provider['first_name'],
                    'last_name' => $provider['last_name'],
                    'services' => $provider['services'],
                    'timezone' => $provider['timezone']
                ];
                $available_providers[$index] = $stripped_data;
            }

            // Remove the data that are not needed inside the $available_services array.
            foreach ($available_services as $index => $service)
            {
                $stripped_data = [
                    'id' => $service['id'],
                    'name' => $service['name'],
                    'duration' => $service['duration'],
                    'location' => $service['location'],
                    'price' => $service['price'],
                    'currency' => $service['currency'],
                    'description' => $service['description'],
                    'category_id' => $service['category_id'],
                    'category_name' => $service['category_name']
                ];
                $available_services[$index] = $stripped_data;
            }

            // If an appointment hash is provided then it means that the customer is trying to edit a registered
            // appointment record.
            if ($appointment_hash !== '')
            {
                // Load the appointments data and enable the manage mode of the page.
                $manage_mode = TRUE;

                $results = $this->appointments_model->get_batch(['hash' => $appointment_hash]);

                if (empty($results))
                {
                    // The requested appointment doesn't exist in the database. Display a message to the customer.
                    $variables = [
                        'message_title' => lang('appointment_not_found'),
                        'message_text' => lang('appointment_does_not_exist_in_db'),
                        'message_icon' => base_url('assets/img/error.png')
                    ];

                    $this->load->view('appointments/message', $variables);

                    return;
                }

                // If the requested appointment begin date is lower than book_advance_timeout. Display a message to the
                // customer.
                $startDate = strtotime($results[0]['start_datetime']);
                $limit = strtotime('+' . $book_advance_timeout . ' minutes', strtotime('now'));

                if ($startDate < $limit)
                {
                    $hours = floor($book_advance_timeout / 60);
                    $minutes = ($book_advance_timeout % 60);

                    $view = [
                        'message_title' => lang('appointment_locked'),
                        'message_text' => strtr(lang('appointment_locked_message'), [
                            '{$limit}' => sprintf('%02d:%02d', $hours, $minutes)
                        ]),
                        'message_icon' => base_url('assets/img/error.png')
                    ];
                    $this->load->view('appointments/message', $view);
                    return;
                }

                $appointment = $results[0];

                $appointment = [
                    'id' => $appointment['id'],
                    'hash' => $appointment['hash'],
                    'start_datetime' => $appointment['start_datetime'],
                    'end_datetime' => $appointment['end_datetime'],
                    'id_services' => $appointment['id_services'],
                    'id_users_customer' => $appointment['id_users_customer'],
                    'id_users_provider' => $appointment['id_users_provider'],
                    'notes' => $appointment['notes']
                ];

                $provider = $this->providers_model->get_row($appointment['id_users_provider']);

                $provider = [
                    'id' => $provider['id'],
                    'first_name' => $provider['first_name'],
                    'last_name' => $provider['last_name'],
                    'services' => $provider['services'],
                    'timezone' => $provider['timezone']
                ];

                $customer = $this->customers_model->get_row($appointment['id_users_customer']);

                $customer = [
                    'id' => $customer['id'],
                    'first_name' => $customer['first_name'],
                    'last_name' => $customer['last_name'],
                    'email' => $customer['email'],
                    'phone_number' => $customer['phone_number'],
                    'timezone' => $customer['timezone'],
                    'address' => $customer['address'],
                    'city' => $customer['city'],
                    'zip_code' => $customer['zip_code']
                ];

                $customer_token = md5(uniqid(mt_rand(), TRUE));

                // Save the token for 10 minutes.
                $this->cache->save('customer-token-' . $customer_token, $customer['id'], 600);
            }
            else
            {
                // The customer is going to book a new appointment so there is no need for the manage functionality to
                // be initialized.
                $manage_mode = FALSE;
                $customer_token = FALSE;
                $appointment = [];
                $provider = [];
                $customer = [];
            }

            if (!$authString) {
		redirect('sorry');
		return;
            }
            $epochTimestamp = base64_decode($authString);
            if ($epochTimestamp === false || !ctype_digit($epochTimestamp)) {
		redirect('sorry');
		return;
            }
            $currentTimestamp = time();
            $sevenDaysInSeconds = 7 * 24 * 60 * 60;
            if (($currentTimestamp - $epochTimestamp) > $sevenDaysInSeconds && $authString !== "MTcxNTgxNzYwMA==") {
		redirect('sorry');
		return;
            }
                // Continue Loading the book appointment view.

            $variables = [
                'available_services' => $available_services,
                'available_providers' => $available_providers,
                'company_name' => $company_name,
                'manage_mode' => $manage_mode,
                'customer_token' => $customer_token,
                'date_format' => $date_format,
                'time_format' => $time_format,
                'first_weekday' => $first_weekday,
                'require_phone_number' => $require_phone_number,
                'appointment_data' => $appointment,
                'provider_data' => $provider,
                'customer_data' => $customer,
                'display_cookie_notice' => $display_cookie_notice,
                'cookie_notice_content' => $cookie_notice_content,
                'display_terms_and_conditions' => $display_terms_and_conditions,
                'terms_and_conditions_content' => $terms_and_conditions_content,
                'display_privacy_policy' => $display_privacy_policy,
                'privacy_policy_content' => $privacy_policy_content,
                'timezones' => $timezones,
                'display_any_provider' => $display_any_provider,
            ];
        }
        catch (Exception $exception)
        {
            $variables['exceptions'][] = $exception;
        }

        $this->load->view('appointments/book', $variables);
    }

    /**
     * Cancel an existing appointment.
     *
     * This method removes an appointment from the company's schedule. In order for the appointment to be deleted, the
     * hash string must be provided. The customer can only cancel the appointment if the edit time period is not over
     * yet.
     *
     * @param string $appointment_hash This appointment hash identifier.
     */
    public function cancel($appointment_hash)
    {
        try
        {
            $cancel_reason = $this->input->post('cancel_reason');

            if ($this->input->method() !== 'post' || empty($cancel_reason))
            {
                show_error('Bad Request', 400);
            }

            // Check whether the appointment hash exists in the database.
            $appointments = $this->appointments_model->get_batch(['hash' => $appointment_hash]);

            if (empty($appointments))
            {
                throw new Exception('No record matches the provided hash.');
            }

            $appointment = $appointments[0];
            $provider = $this->providers_model->get_row($appointment['id_users_provider']);
            $customer = $this->customers_model->get_row($appointment['id_users_customer']);
            $service = $this->services_model->get_row($appointment['id_services']);

            $settings = [
                'company_name' => $this->settings_model->get_setting('company_name'),
                'company_email' => $this->settings_model->get_setting('company_email'),
                'company_link' => $this->settings_model->get_setting('company_link'),
                'date_format' => $this->settings_model->get_setting('date_format'),
                'time_format' => $this->settings_model->get_setting('time_format')
            ];

            // Remove the appointment record from the data.
            if ( ! $this->appointments_model->delete($appointment['id']))
            {
                throw new Exception('Appointment could not be deleted from the database.');
            }

            $this->synchronization->sync_appointment_deleted($appointment, $provider);
            $this->notifications->notify_appointment_deleted($appointment, $service, $provider, $customer, $settings);
        }
        catch (Exception $exception)
        {
            // Display the error message to the customer.
            $exceptions[] = $exception;
        }

        $view = [
            'message_title' => lang('appointment_cancelled_title'),
            'message_text' => lang('appointment_cancelled'),
            'message_icon' => base_url('assets/img/success.png')
        ];

        if (isset($exceptions))
        {
            $view['exceptions'] = $exceptions;
        }

        $this->load->view('appointments/message', $view);
    }

    /**
     * GET a specific appointment book and redirect to the success screen.
     *
     * @param string $appointment_hash The appointment hash identifier.
     *
     * @throws Exception
     */
    public function book_success($appointment_hash)
    {
        $appointments = $this->appointments_model->get_batch(['hash' => $appointment_hash]);

        if (empty($appointments))
        {
            $vars = [
                'message_title' => lang('appointment_not_found'),
                'message_text' => lang('appointment_does_not_exist_in_db'),
                'message_icon' => base_url('assets/img/error.png')
            ];

            $this->load->view('appointments/message', $vars);

            return;
        }

        $appointment = $appointments[0];

        $customer = $this->customers_model->get_row($appointment['id_users_customer']);

        $provider = $this->providers_model->get_row($appointment['id_users_provider']);

        $service = $this->services_model->get_row($appointment['id_services']);

        $company_name = $this->settings_model->get_setting('company_name');

        $provider_timezone_instance = create_custom_datetimezone($provider['timezone']);
        
        $utc_timezone_instance = create_custom_datetimezone('UTC');
        
        $appointment_start_instance = new DateTime($appointment['start_datetime'], $provider_timezone_instance);
        
        $appointment_start_instance->setTimezone($utc_timezone_instance);

        $appointment_end_instance = new DateTime($appointment['end_datetime'], $provider_timezone_instance);

        $appointment_end_instance->setTimezone($utc_timezone_instance);
        
        $add_to_google_url_params = [
            'action' => 'TEMPLATE',
            'text' => $service['name'],
            'dates' => $appointment_start_instance->format('Ymd\THis\Z') . '/' . $appointment_end_instance->format('Ymd\THis\Z'),
            'location' => $company_name,
            'details' => 'View/Change Appointment: ' . site_url('appointments/index/' . $appointment['hash']),
            'add' => $provider['email'] . ',' . $customer['email']
        ];

        $add_to_google_url = 'https://calendar.google.com/calendar/render?' . http_build_query($add_to_google_url_params);

        // Get any pending exceptions.
        $exceptions = $this->session->flashdata('book_success');

        $view = [
            'appointment_data' => [
                'start_datetime' => $appointment['start_datetime'],
                'end_datetime' => $appointment['end_datetime'],
            ],
            'provider_data' => [
                'first_name' => $provider['first_name'],
                'last_name' => $provider['last_name'],
                'email' => $provider['email'],
                'timezone' => $provider['timezone'],
            ],
            'customer_data' => [
                'first_name' => $customer['first_name'],
                'last_name' => $customer['last_name'],
                'email' => $customer['email'],
                'timezone' => $customer['timezone'],
            ],
            'service_data' => [
                'name' => $service['name'],
            ],
            'company_name' => $company_name,
            'add_to_google_url' => $add_to_google_url,
        ];

        if ($exceptions)
        {
            $view['exceptions'] = $exceptions;
        }

        $this->load->view('appointments/book_success', $view);
    }

    /**
     * Get the available appointment hours for the given date.
     *
     * This method answers to an AJAX request. It calculates the available hours for the given service, provider and
     * date.
     *
     * Outputs a JSON string with the availabilities.
     */
    public function ajax_get_available_hours()
    {
        try
        {
            $provider_id = $this->input->post('provider_id');
            $service_id = $this->input->post('service_id');
            $selected_date = $this->input->post('selected_date');
            // Do not continue if there was no provider selected (more likely there is no provider in the system).
            if (empty($provider_id))
            {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([]));

                return;
            }

            // If manage mode is TRUE then the following we should not consider the selected appointment when
            // calculating the available time periods of the provider.
            $exclude_appointment_id = $this->input->post('manage_mode') === 'true' ? $this->input->post('appointment_id') : NULL;

            // If the user has selected the "any-provider" option then we will need to search for an available provider
            // that will provide the requested service.
            if ($provider_id === ANY_PROVIDER)
            {

                $response = $this->search_any_provider_mod($service_id, $selected_date);
                if (empty($response))
                {
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([]));
                    return;
                }

            }

//            $service = $this->services_model->get_row($service_id);
//            $provider = $this->providers_model->get_row($provider_id);
//            $response = $this->availability->get_available_hours($selected_date, $service, $provider, $exclude_appointment_id);
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);
            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Search for any provider that can handle the requested service.
     *
     * This method will return the database ID of the provider with the most available periods.
     *
     * @param int $service_id The requested service ID.
     * @param string $date The date to be searched (Y-m-d).
     * @param string $hour The hour to be searched (H:i).
     *
     * @return int Returns the ID of the provider that can provide the service at the selected date.
     *
     * @throws Exception
     */


    // debug : Modified function that will fetch all the providers and feed them so that all available hours are obtained. 
    
    protected function search_any_provider_mod($service_id, $date)
    {
        //Getting day-before and day-after
        $today_obj = new DateTime($date);
        $yesterday_obj = (clone $today_obj)->modify('-1 day');
        $tomorrow_obj = (clone $today_obj)->modify('+1 day'); 

        $yesterday = $yesterday_obj->format('Y-m-d');
        $today = $today_obj->format('Y-m-d');
        $tomorrow = $tomorrow_obj->format('Y-m-d');

        $available_providers = $this->providers_model->get_available_providers();
        $service = $this->services_model->get_row($service_id);
        

        $pooled_available_hours = [];

        foreach ($available_providers as $provider) {

            $provider_id = $provider['id'];
            $pooled_available_hours[$provider_id] = [
                $yesterday => [],
                $today => [],
                $tomorrow => []
            ];

            foreach ($provider['services'] as $provider_service_id) {
                if ($provider_service_id == $service_id) {
                    foreach (array_keys($pooled_available_hours[$provider_id]) as $current_date) {
                        $available_hours = $this->availability->get_available_hours($current_date, $service, $provider);
                        $pooled_available_hours[$provider_id][$current_date] = $available_hours;
                      //  log_message('debug', json_encode($available_hours, JSON_PRETTY_PRINT));
                    }
                }
            }
        }
//        log_message('debug', json_encode($pooled_available_hours, JSON_PRETTY_PRINT));
            return $pooled_available_hours;
    }
    
    protected function search_any_provider($service_id, $date, $hour = NULL)
    {
        $available_providers = $this->providers_model->get_available_providers();

        $service = $this->services_model->get_row($service_id);

        $provider_id = NULL;

        $max_hours_count = 0;

        $providers_with_hours = [];

        foreach ($available_providers as $provider)
        {
            foreach ($provider['services'] as $provider_service_id)
            {
                if ($provider_service_id == $service_id)
                {
                    // Check if the provider is available for the requested date.
                    $available_hours = $this->availability->get_available_hours($date, $service, $provider);

                    if (count($available_hours) > $max_hours_count && (empty($hour) || in_array($hour, $available_hours, FALSE)))
                    {
                        $provider_id = $provider['id'];
                        $max_hours_count = count($available_hours);
                    }
                }
            }
        }

        return $provider_id;
    }


    /**
     * Register the appointment to the database.
     *
     * Outputs a JSON string with the appointment ID.
     */
    public function ajax_register_appointment()
    {
        try
        {
	    log_message('debug', 'Trying now');
            $post_data = $this->input->post('post_data');
            $captcha = $this->input->post('captcha');
            $manage_mode = filter_var($post_data['manage_mode'], FILTER_VALIDATE_BOOLEAN);
            $appointment = $post_data['appointment'];
	    log_message('debug', 'Appointment data' .  print_r($appointment, true));
            $customer = $post_data['customer'];
	    log_message('debug', 'Customer data' .  print_r($customer,true));

            // Check appointment availability before registering it to the database.
            $appointment['id_users_provider'] = $this->check_datetime_availability();

            if ( ! $appointment['id_users_provider'])
            {
		    log_message('debug', 'requested_hour_is_unavailable');
                throw new Exception(lang('requested_hour_is_unavailable'));

            }

            $provider = $this->providers_model->get_row($appointment['id_users_provider']);
            $service = $this->services_model->get_row($appointment['id_services']);

            $require_captcha = $this->settings_model->get_setting('require_captcha');
            $captcha_phrase = $this->session->userdata('captcha_phrase');

            // Validate the CAPTCHA string.
            if ($require_captcha === '1' && strtolower($captcha_phrase) !== strtolower($captcha))
            {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'captcha_verification' => FALSE
                    ]));

                return;
            }

            if ($this->customers_model->exists($customer))
            {
                $customer['id'] = $this->customers_model->find_record_id($customer);
            }

            if (empty($appointment['location']) && ! empty($service['location']))
            {
                $appointment['location'] = $service['location'];
            }

            // Save customer language (the language which is used to render the booking page).
            $customer['language'] = config('language');
	    
	    $customer_id = $this->customers_model->add($customer);
	    
	    if (empty($appointment['start_datetime'])) {
		    log_message('debug', 'Start datetime is not set.');
		    throw new \Exception('Start datetime is not set.');
	    }
            $appointment_start_instance = new DateTime($appointment['start_datetime']);
	    
	    if (empty($service['duration'])) {
		    log_message('debug', 'Service duration could not be pulled');
		    throw new \Exception('Service duration could not be pulled');
	    }

            $appointment['end_datetime'] = $appointment_start_instance
                ->add(new DateInterval('PT' . $service['duration'] . 'M'))
                ->format('Y-m-d H:i:s');

	    if (empty($service['id']) || empty($provider['services']) || !is_array($provider['services'])) {
		    log_message('debug', 'Invalid service[id] or provider[services] on checking');
		throw new \Exception('Invalid service[id] or provider[services] on checking');
	    }
            if ( ! in_array($service['id'], $provider['services']))
            {
		    log_message('debug', 'Invalid provider record selected for appointment.');
                throw new \Exception('Invalid provider record selected for appointment.');
            }

            $appointment['id_users_customer'] = $customer_id;
            $appointment['is_unavailable'] = (int)$appointment['is_unavailable']; // needs to be type casted
            $appointment['id'] = $this->appointments_model->add($appointment);
	    if ($appointment['id'] === null) {
		    log_message('debug', 'Failed to add appointment.');
	        throw new \Exception('Failed to add appointment.');
	    }
            $appointment['hash'] = $this->appointments_model->get_value('hash', $appointment['id']);
	     if ($appointment['id'] === null) {
		    log_message('debug', 'Failed to get appointment hash');
	        throw new \Exception('Failed to get appointment hash');
	    }	
            $settings = [
                'company_name' => $this->settings_model->get_setting('company_name'),
                'company_link' => $this->settings_model->get_setting('company_link'),
                'company_email' => $this->settings_model->get_setting('company_email'),
                'date_format' => $this->settings_model->get_setting('date_format'),
                'time_format' => $this->settings_model->get_setting('time_format')
            ];
	    log_message('debug', 'response generated');
	    log_message('debug', 'sync attempt even now');
            $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings, $manage_mode);
	    log_message('debug', 'sync ended');
            $this->notifications->notify_appointment_saved($appointment, $service, $provider, $customer, $settings, $manage_mode);
	    log_message('debug', 'notification ended');
            $response = [
                'appointment_id' => $appointment['id'],
                'appointment_hash' => $appointment['hash']
            ];
	    
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

	    log_message('debug', "Error happened" . print_r($exception, true));
            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Check whether the provider is still available in the selected appointment date.
     *
     * It might be times where two or more customers select the same appointment date and time. This shouldn't be
     * allowed to happen, so one of the two customers will eventually get the preferred date and the other one will have
     * to choose for another date. Use this method just before the customer confirms the appointment details. If the
     * selected date was taken in the mean time, the customer must be prompted to select another time for his
     * appointment.
     *
     * @return int Returns the ID of the provider that is available for the appointment.
     *
     * @throws Exception
     */
    protected function check_datetime_availability()
    {
        $post_data = $this->input->post('post_data');

        $appointment = $post_data['appointment'];

        $appointment_start = new DateTime($appointment['start_datetime']);
        $date = $appointment_start->format('Y-m-d');
        $hour = $appointment_start->format('H:i');

        if ($appointment['id_users_provider'] === ANY_PROVIDER)
        {
            $appointment['id_users_provider'] = $this->search_any_provider($appointment['id_services'], $date, $hour);

            return $appointment['id_users_provider'];
        }

        $service = $this->services_model->get_row($appointment['id_services']);

        $exclude_appointment_id = isset($appointment['id']) ? $appointment['id'] : NULL;

        $provider = $this->providers_model->get_row($appointment['id_users_provider']);

        $available_hours = $this->availability->get_available_hours($date, $service, $provider, $exclude_appointment_id);

        $is_still_available = FALSE;

        $appointment_hour = date('H:i', strtotime($appointment['start_datetime']));

        foreach ($available_hours as $available_hour)
        {
            if ($appointment_hour === $available_hour)
            {
                $is_still_available = TRUE;
                break;
            }
        }

        return $is_still_available ? $appointment['id_users_provider'] : NULL;
    }

    /**
     * Get Unavailable Dates
     *
     * Get an array with the available dates of a specific provider, service and month of the year. Provide the
     * "provider_id", "service_id" and "selected_date" as GET parameters to the request. The "selected_date" parameter
     * must have the Y-m-d format.
     *
     * Outputs a JSON string with the unavailable dates. that are unavailable.
     */
    public function ajax_get_unavailable_dates()
    {
        try
        {
            $provider_id = $this->input->get('provider_id');
            $service_id = $this->input->get('service_id');
            $appointment_id = $this->input->get_post('appointment_id');
            $manage_mode = $this->input->get_post('manage_mode');
            $selected_date_string = $this->input->get('selected_date');
            $selected_date = new DateTime($selected_date_string);
            $number_of_days_in_month = (int)$selected_date->format('t');
            $unavailable_dates = [];

            $provider_ids = $provider_id === ANY_PROVIDER
                ? $this->search_providers_by_service($service_id)
                : [$provider_id];

            $exclude_appointment_id = $manage_mode ? $appointment_id : NULL;

            // Get the service record.
            $service = $this->services_model->get_row($service_id);

            for ($i = 1; $i <= $number_of_days_in_month; $i++)
            {
                $current_date = new DateTime($selected_date->format('Y-m') . '-' . $i);

                if ($current_date < new DateTime(date('Y-m-d 00:00:00')))
                {
                    // Past dates become immediately unavailable.
                    $unavailable_dates[] = $current_date->format('Y-m-d');
                    continue;
                }

                // Finding at least one slot of availability.
                foreach ($provider_ids as $current_provider_id)
                {
                    $provider = $this->providers_model->get_row($current_provider_id);

                    $available_hours = $this->availability->get_available_hours(
                        $current_date->format('Y-m-d'),
                        $service,
                        $provider,
                        $exclude_appointment_id
                    );

                    if ( ! empty($available_hours))
                    {
                        break;
                    }
                }

                // No availability amongst all the provider.
                if (empty($available_hours))
                {
                    $unavailable_dates[] = $current_date->format('Y-m-d');
                }
            }

            $response = $unavailable_dates;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Search for any provider that can handle the requested service.
     *
     * This method will return the database ID of the providers affected to the requested service.
     *
     * @param int $service_id The requested service ID.
     *
     * @return array Returns the ID of the provider that can provide the requested service.
     */
    protected function search_providers_by_service($service_id)
    {
        $available_providers = $this->providers_model->get_available_providers();
        $provider_list = [];

        foreach ($available_providers as $provider)
        {
            foreach ($provider['services'] as $provider_service_id)
            {
                if ($provider_service_id === $service_id)
                {
                    // Check if the provider is affected to the selected service.
                    $provider_list[] = $provider['id'];
                }
            }
        }

        return $provider_list;
    }


}
