This is a fork of https://github.com/alextselegidis/easyappointments 
More information at https://easyappointments.org

## Features

* Generate booking url for a Product(Service in EasyAppointment parlance) with timeslots that are round-robined.
* Nonce parameter in the booking url that expires in 7 days
* Sync users and products via scripts from [Latest product agents list - Google Sheets](https://docs.google.com/spreadsheets/d/1v9lYDQvkN65S1-_UeE10XTgxWvfTi4Vz2G1qrPuUUw8/edit?gid=1999904100#gid=1999904100) 
* Customers and appointments management.
* Agent schedules/working plans.
* Google Calendar synchronization.
* Email notifications system.
* Self hosted installation.

## Setup

To clone and run this application, you'll need [Git](https://git-scm.com), [Node.js](https://nodejs.org/en/download/) (which comes with [npm](http://npmjs.com)) and [Composer](https://getcomposer.org) installed on your computer. From your command line:

```bash
# Clone this repository
$ git clone https://github.com/trilogy-group/meeting-scheduler

# Go into the repository
$ cd meeting-scheduler 

# Install dependencies
$ npm install && composer install

# Start the file watcher
$ npm start
```
* For getting a new list of products and users cd ./assets/js/trilogyfetch
* Run `node fetch.js` script to populate a fresh / new output.json. 


## Installation

You will need to perform the following steps to install the application on your server:

* ssh to AWS EC-2 instance where you intend to host meeting-scheduler
* Make sure that your server has Apache, PHP and MySQL installed.
    * sudo dnf install -y mariadb105-server
    * sudo dnf install -y httpd
    * sudo systemctl start mariadb
    * sudo systemctl enable mariadb
    * sudo systemctl start httpd.service
    * sudo systemctl enable httpd.service

* Create a new database (or use an existing one).
    * sudo mysql_secure_installation
    * sudo mysql -u root -p

* Copy the "meeting-scheduler" source folder on your server via scp 
    * scp -i <key_path.pem> ./meeting-scheduler.zip ec2-user@<ip-address>:/var/www/html
    * unzip the folder into /var/www/html

* IMPORTANT!: Make sure that the "storage" directory is writable. chmod -666 ./storage
* Rename the "config-sample.php" file to "config.php" and update its contents based on your environment.
* Open the browser on the Meeting-Scheduler URL and follow the installation guide.

* To import Products from output.json:
    * In the ec-2 instance cd ./application/config/config.php, make sure rate limiting is disabled.  
    * In your web browser go to ip-address/index.php/backend. Login as Admin. 
    * Go to Services tab.
    * Open Developer Console, and enable the 'Sync Trilogy Products' button.
    * Click on the button.

* To synchronize Agent - Product mappings from the Latest Google Sheet
    * In your web browser go to ip-address/index.php/backend. Login as Admin. 
    * Open Developer Console, and enable the 'Sync Trilogy Agents' button.
    * Click on said button, and it should update the agents. 

## NOTES on customizations

Changes have been made to the following:
* ./application/libraries/Availability.php - Applied a function to round to nearest half hour when pulling periods. Round up or down depending on end or start of shift/appointment/break. Used in get_available_hours
* ./application/controllers/Appointments.php ajax_get_unavailable_dates and ajax_get_available_hours (sub function "search_any_provider_mod") - now returns yesterday, today, tomorrow for all providers instead of only that day for a single provider.
* Round Robin logic inserted in - ./assets/js/frontend_book_api.js (Data from ajax_get_available_hours POST endpoint is parsed, and round robin logic applied here. 
* Exclude Step #1 on booking page so "any-provider" always selected - ./application/views/appointments/book.php AND ./assets/css/frontend.css
* ./application/libraries/Timezones.php to insert shifts as timezones that can be selected for providers
* ./application/helpers/custom_datetimezone_helper.php - helper function to stop using new DateTimeZone() constructor which would not recognize custom shifts. 
* In all instances where new DateTimeZone() was used, instead used custom_datetimezone_helper.php
* ./application/views/backend/services.php - The following options are pre-selected and readonly when creating a service because the code amendments only cater to this set of options: 
- Availability Types = Fixed
- Attendants number = 1
* Modified ./assets/js/backend_services_helper.js to add the logic for generating links that will die after 7 days. 
- this is simply at time of creation a time epoch string base64 encoded. 
- It is reverse encoded when loading the booking page to optionally deny bookings.
* Modified controller ./application/controllers/Appointments.php - to throw an error instead of loading booking view if b64 time epoch string is not included.
