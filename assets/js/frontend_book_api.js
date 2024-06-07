/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

window.FrontendBookApi = window.FrontendBookApi || {};

/**
 * Frontend Book API
 *
 * This module serves as the API consumer for the booking wizard of the app.
 *
 * @module FrontendBookApi
 */
(function (exports) {

    'use strict';

    var unavailableDatesBackup;
    var selectedDateStringBackup;
    var processingUnavailabilities = false;

    /**
     * Get Available Hours
     *
     * This function makes an AJAX call and returns the available hours for the selected service,
     * provider and date.
     *
     * @param {String} selectedDate The selected date of the available hours we need.
     */
    exports.getAvailableHours = function (selectedDate) {
        $('#available-hours').empty();

        // Find the selected service duration (it is going to be send within the "data" object).
        var serviceId = $('#select-service').val();

        // Default value of duration (in minutes).
        var serviceDuration = 30;

        var service = GlobalVariables.availableServices.find(function (availableService) {
            return Number(availableService.id) === Number(serviceId);
        });

        if (service) {
            serviceDuration = service.duration;
        }

        // If the manage mode is true then the appointment's start date should return as available too.
        var appointmentId = FrontendBook.manageMode ? GlobalVariables.appointmentData.id : null;

        // Make ajax post request and get the available hours.
        var url = GlobalVariables.baseUrl + '/index.php/appointments/ajax_get_available_hours';
        var data = {
            csrfToken: GlobalVariables.csrfToken,
            service_id: $('#select-service').val(),
            provider_id: $('#select-provider').val(), //What does Any-Provider give back from ajax_get_available_hours?
            selected_date: selectedDate,
            service_duration: serviceDuration,
            manage_mode: FrontendBook.manageMode,
            appointment_id: appointmentId
        };

        $.post(url, data)
            .done(function (response) {
                let collectedProviders = []; //Providers of that service will be collected here.
                // The response contains the available hours for all providers and
                // service. Fill the available hours div with response data.
                if (Object.keys(response).length > 0) {
                    var providerId = $('#select-provider').val(); 
                    let tempProviderCollection = [];
                    if (providerId === 'any-provider') {
                        for (var availableProvider of GlobalVariables.availableProviders) {
                            if (availableProvider.services.indexOf(serviceId) !== -1) {
                                tempProviderCollection.push(availableProvider);
                            }
                        }
                    }
                    collectedProviders = tempProviderCollection;
                }
                
                // console.log("Printing available providers", JSON.stringify(collectedProviders, null,2));
                        // [
                        //  {
                        //    "id": "4",
                        //    "first_name": "John",
                        //    "last_name": "Doe_S2",
                        //    "services": [
                        //      "2",
                        //      "3"
                        //    ],
                        //    "timezone": "S2"
                        //  },
                        //  {
                        //    "id": "2",
                        //    "first_name": "Munawar",
                        //    "last_name": "Shah_S3",
                        //    "services": [
                        //      "2",
                        //      "3"
                        //    ],
                        //    "timezone": "S3"
                        //  }
                        //]
                                

                       // ORIGINAL LOGIC
                       // for (var availableProvider of GlobalVariables.availableProviders) {
                       //     if (availableProvider.services.indexOf(serviceId) !== -1) {
                       //         providerId = availableProvider.id; // Use first available provider.
                       //         break;
                       //     }
                       // }
                    
                //CUSTOM following 3 lines check to see if the provider selected still exists / is not deleted. 
                //only relevant if a particular provider is selected or their link used 
                //NOT relevant for us as we are forcing any-provider.
                    
                    //var provider = GlobalVariables.availableProviders.find(function (availableProvider) {
                    //    return Number(providerId) === Number(availableProvider.id);
                    //});

                    //if (!provider) {
                    //    throw new Error('Could not find provider.');
                    //}
                
                //To collect REAL HOUR (in provider timezone) mapped to the HOUR/SLOT in the customer's timezone. Used to append value to #available-hours for booking appointment
                const providerSlotsMap = {}; 
                let hourSet = new Set();
                let hourList = [];
                for (const provider of collectedProviders) {
                    providerId = provider.id; 
                    var providerName = provider.first_name + ' ' + provider.last_name;
                    var providerTimezone = provider.timezone;
                    var selectedTimezone = $('#select-timezone').val();
                    var timeFormat = GlobalVariables.timeFormat === 'regular' ? 'h:mm a' : 'HH:mm';
                    providerSlotsMap[providerId] = {}; //initialize empty to carry the realHours data

                    
                    //CUSTOM: Mapping that will make 08:00AM to 5:00PM working hours set align with UTC shift on same day (in UTC time) 
                    //To test: https://savvytime.com/converter/utc and move timer to UTC shift start time, then add the 'city' corresponding to offset, and observe it is 08:00am.
                    // S1 : 0100 - 1000 hrs UTC
                    // S2 : 0700 - 1600 hrs UTC
                    // S3 : 1300 - 2200 hrs UTC
                    // S4 : 1900 - 0400 hrs(next day) UTC

                    const tzMapping = {
                        'S1':'+07:00', //City: Jakarta, Indonesia - UTC+07:00
                        'S2':'+01:00', //City: Algiers, Algeria - UTC+01:00
                        'S3':'-05:00', //City: Kingston, Jamaica - UTC-05:00
                        'S4':'-11:00', //City: Alofi, Niue - UTC-11:00
                    };
                    // Iterate over the hours which are of format 2024-05-14: [10:00,10:30...] 
                    // Extract and conver the hours to timezone of customer viewing this booking page
                    Object.entries(response[providerId]).forEach(([thisDate, hours]) => {
                        //console.log("Using the provider :", providerId);
                        //console.log(JSON.stringify(hours, null, 2));
                        hours.forEach(function (availableHour) {
                            const realHour = thisDate + ' ' + availableHour + ':00';
                            let availableHourMoment;
                            if (Object.keys(tzMapping).includes(providerTimezone)){
                                availableHourMoment = moment
                                    .tz(thisDate + ' ' + availableHour + ':00' + tzMapping[providerTimezone],selectedTimezone);
                                //console.log(availableHourMoment.format('YYYY-MM-DD'));
                            } else {
                                availableHourMoment = moment
                                    .tz(thisDate + ' ' + availableHour + ':00', providerTimezone)
                                    .tz(selectedTimezone);
                            }
                            let tzConvertedDate = availableHourMoment.format('YYYY-MM-DD');
                            if (tzConvertedDate !== selectedDate) {
                                return; // The available hour belongs to another date in customer's timezone and should not be rendered.  
                            }
                            const hourMoment = availableHourMoment.format(timeFormat);
                            //console.log(hourMoment);
                            providerSlotsMap[providerId][hourMoment] = realHour; //Loading trace to realHour vs timezoneConverted map.
                            hourSet.add(hourMoment);
                        });
                    });
                     
                            //From the set create a list of sorted hours
                            hourList = Array.from(hourSet).sort((a,b) => moment(a, timeFormat).valueOf() - moment(b,timeFormat).valueOf());
                }
                //console.log(hourList);
                function getRandomItem(listing) {
                    const randomIndex = Math.floor(Math.random() * listing.length);
                    return listing[randomIndex];
                }
                for (const hour of hourList) {
                    let theProvider;
                    const maxAttempts = 2000; // Define a maximum number of attempts to prevent infinite loop
                    let attempts = 0;
                    if (collectedProviders.length === 0) {
                        console.log('No providers available');
                    } else {
                        while (!theProvider && attempts < maxAttempts) {
                            const randomProvider = getRandomItem(collectedProviders);
                            theProvider = randomProvider.id;
                            if (providerSlotsMap[theProvider][hour] !== undefined) {
                                const realHour = providerSlotsMap[theProvider][hour];  
                                const theProviderObject = collectedProviders.find(provider => provider.id === theProvider); 
                                const theProviderName = theProviderObject.first_name + ' ' + theProviderObject.last_name;
                                console.log(`Printing appended provider ${theProvider} at ${realHour.split(' ')[1].substring(0, 5)}`);
                                $('#available-hours').append(
                                    $('<button/>', {
                                        'class': 'btn btn-outline-secondary btn-block shadow-none available-hour',
                                        'data': {
                                            'value': realHour,
                                            'provider': theProvider
                                        }, 
                                        'text': hour 
                                    })
                                    .attr('data-agentTime', realHour)
                                    .attr('data-id', theProvider)
                                    .attr('data-name', theProviderName)
                                )
                                break;
                                } else {
                                    theProvider = null;
                                    attempts += 1;
                                    continue;
                                }
                            }
                        }
                }
                
                // Set the appointment's start time as the default selection.
                if (FrontendBook.manageMode) {
                    $('.available-hour')
                        .removeClass('selected-hour')
                        .filter(function () {
                            return $(this).text() === Date.parseExact(
                                GlobalVariables.appointmentData.start_datetime,
                                'yyyy-MM-dd HH:mm:ss').toString(timeFormat);
                        })
                        .addClass('selected-hour');
                } else {
                    // Set the first available hour as the default selection.
                    $('.available-hour:eq(0)').addClass('selected-hour');
                }
                FrontendBook.updateConfirmFrame();
                if (!$('.available-hour').length) {
                    $('#available-hours').text(EALang.no_available_hours);
                }
            });

        //var formData = JSON.parse($('input[name="post_data"]').val());
        //console.log(JSON.stringify(formData);
    };

    /**
     * Register an appointment to the database.
     *
     * This method will make an ajax call to the appointments controller that will register
     * the appointment to the database.
     */
    exports.registerAppointment = function () {
        var $captchaText = $('.captcha-text');

        if ($captchaText.length > 0) {
            $captchaText.closest('.form-group').removeClass('has-error');
            if ($captchaText.val() === '') {
                $captchaText.closest('.form-group').addClass('has-error');
                return;
            }
        }

        var formData = JSON.parse($('input[name="post_data"]').val());

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            post_data: formData
        };

        if ($captchaText.length > 0) {
            data.captcha = $captchaText.val();
        }

        if (GlobalVariables.manageMode) {
            data.exclude_appointment_id = GlobalVariables.appointmentData.id;
        }

        var url = GlobalVariables.baseUrl + '/index.php/appointments/ajax_register_appointment';

        var $layer = $('<div/>');

        $.ajax({
            url: url,
            method: 'post',
            data: data,
            dataType: 'json',
            beforeSend: function (jqxhr, settings) {
                $layer
                    .appendTo('body')
                    .css({
                        background: 'white',
                        position: 'fixed',
                        top: '0',
                        left: '0',
                        height: '100vh',
                        width: '100vw',
                        opacity: '0.5'
                    });
            }
        })
            .done(function (response) {
                if (response.captcha_verification === false) {
                    $('#captcha-hint')
                        .text(EALang.captcha_is_wrong)
                        .fadeTo(400, 1);

                    setTimeout(function () {
                        $('#captcha-hint').fadeTo(400, 0);
                    }, 3000);

                    $('.captcha-title button').trigger('click');

                    $captchaText.closest('.form-group').addClass('has-error');

                    return false;
                }

                window.location.href = GlobalVariables.baseUrl
                    + '/index.php/appointments/book_success/' + response.appointment_hash;
            })
            .fail(function (jqxhr, textStatus, errorThrown) {
                $('.captcha-title button').trigger('click');
            })
            .always(function () {
                $layer.remove();
            });
    };

    /**
     * Get the unavailable dates of a provider.
     *
     * This method will fetch the unavailable dates of the selected provider and service and then it will
     * select the first available date (if any). It uses the "FrontendBookApi.getAvailableHours" method to
     * fetch the appointment* hours of the selected date.
     *
     * @param {Number} providerId The selected provider ID.
     * @param {Number} serviceId The selected service ID.
     * @param {String} selectedDateString Y-m-d value of the selected date.
     */
    exports.getUnavailableDates = function (providerId, serviceId, selectedDateString) {
        if (processingUnavailabilities) {
            return;
        }

        if (!providerId || !serviceId) {
            return;
        }

        var appointmentId = FrontendBook.manageMode ? GlobalVariables.appointmentData.id : null;

        var url = GlobalVariables.baseUrl + '/index.php/appointments/ajax_get_unavailable_dates';

        var data = {
            provider_id: providerId,
            service_id: serviceId,
            selected_date: encodeURIComponent(selectedDateString),
            csrfToken: GlobalVariables.csrfToken,
            manage_mode: FrontendBook.manageMode,
            appointment_id: appointmentId
        };

        $.ajax({
            url: url,
            type: 'GET',
            data: data,
            dataType: 'json'
        })
            .done(function (response) {
                unavailableDatesBackup = response;
                selectedDateStringBackup = selectedDateString;
                applyUnavailableDates(response, selectedDateString, true);
            });
    };

    exports.applyPreviousUnavailableDates = function () {
        applyUnavailableDates(unavailableDatesBackup, selectedDateStringBackup);
    };

    function applyUnavailableDates(unavailableDates, selectedDateString, setDate) {
        setDate = setDate || false;

        processingUnavailabilities = true;

        // Select first enabled date.
        var selectedDate = Date.parse(selectedDateString);
        var numberOfDays = moment(selectedDate).daysInMonth();

        if (setDate && !GlobalVariables.manageMode) {
            for (var i = 1; i <= numberOfDays; i++) {
                var currentDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), i);
                if (unavailableDates.indexOf(currentDate.toString('yyyy-MM-dd')) === -1) {
                    $('#select-date').datepicker('setDate', currentDate);
                    FrontendBookApi.getAvailableHours(currentDate.toString('yyyy-MM-dd'));
                    break;
                }
            }
        }

        // If all the days are unavailable then hide the appointments hours.
        if (unavailableDates.length === numberOfDays) {
            $('#available-hours').text(EALang.no_available_hours);
        }

        // Grey out unavailable dates.
        $('#select-date .ui-datepicker-calendar td:not(.ui-datepicker-other-month)').each(function (index, td) {
            selectedDate.set({day: index + 1});
            if (unavailableDates.indexOf(selectedDate.toString('yyyy-MM-dd')) !== -1) {
                $(td).addClass('custom-selectable'); //CUSTOM CODE to enable click on grey
            }
        });

        processingUnavailabilities = false;
    }

    /**
     * Save the user's consent.
     *
     * @param {Object} consent Contains user's consents.
     */
    exports.saveConsent = function (consent) {
        var url = GlobalVariables.baseUrl + '/index.php/consents/ajax_save_consent';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            consent: consent
        };

        $.post(url, data);
    };

    /**
     * Delete personal information.
     *
     * @param {Number} customerToken Customer unique token.
     */
    exports.deletePersonalInformation = function (customerToken) {
        var url = GlobalVariables.baseUrl + '/index.php/privacy/ajax_delete_personal_information';

        var data = {
            csrfToken: GlobalVariables.csrfToken,
            customer_token: customerToken
        };

        $.post(url, data)
            .done(function () {
                window.location.href = GlobalVariables.baseUrl;
            });
    };

})(window.FrontendBookApi);


