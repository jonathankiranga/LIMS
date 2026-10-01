<?php
$application_creds = 'calendar/client_secret.json';  
//the Service Account generated cred in JSON
$credentials_file = file_exists($application_creds) ? $application_creds : false;

define("APP_NAME","Google Calendar API PHP");
define("emailtoimpersonate",$_SESSION['UserEmail']);
$client = new Google_Client();
$client->setAuthConfig($credentials_file);
$client->setApplicationName(APP_NAME);
$client->addScope(Google_Service_Calendar::CALENDAR);
$client->addScope(Google_Service_Calendar::CALENDAR_READONLY);
$client->setSubject(emailtoimpersonate);

