<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tools extends EA_Controller {

    public function __construct()
    {
        parent::__construct();
        // Ensure the script is being run in the command line
        if (!$this->input->is_cli_request()) {
		echo "quitting...";
            exit('Direct access is not allowed');
        }
    }

    public function gsync()
    {
     $providers= $this->providers_model->get_available_providers();
     echo "Sending appointment reminders...\n";
     log_message('debug', print_r($providers));
    }
}
?>

