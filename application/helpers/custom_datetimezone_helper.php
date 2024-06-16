<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class CustomDateTimeZone extends DateTimeZone {
    private static $customTimezones = [
        'S1' => 'UTC', //'Asia/Jakarta', //'+07:00', 
        'S2' => 'UTC', //'Africa/Algiers', //'+01:00', 
        'S3' => 'UTC', //'America/Jamaica', //'-05:00', 
        'S4' => 'UTC', //'Pacific/Niue', //'-11:00', 
    ];

    public function __construct($timezone) {
        if (isset(self::$customTimezones[$timezone])) {
            $timezone = self::$customTimezones[$timezone];
        }
        parent::__construct($timezone);
    }
}

// Define a global function to create CustomDateTimeZone instances
function create_custom_datetimezone($timezone) {
    return new CustomDateTimeZone($timezone);
}

