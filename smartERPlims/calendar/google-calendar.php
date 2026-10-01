<?php

$service = new Google_Service_Calendar($client);

$event = new Google_Service_Calendar_Event();  
$event->setSummary('Halloween');
$event->setLocation('The Neighbourhood');
$event->setSummary('Evento creado desde el API');

$start = new Google_Service_Calendar_EventDateTime();
$start->setDateTime(date('c'));
$event->setStart($start);

$end = new Google_Service_Calendar_EventDateTime();
$end->setDateTime(date('c'));
$event->setEnd($end);

$attendee = new Google_Service_Calendar_EventAttendee();
$attendee->setEmail('jonathan.kiranga@quality-bitumen.com');
$attendees = array($attendee);
$event->setAttendees($attendees);

$organizer = new Google_Service_Calendar_EventOrganizer();
$organizer->setEmail('jonathan.kiranga@quality-bitumen.com');
$organizer->getDisplayName("Full Name");

$event->setOrganizer($organizer);

$createdEvent = $service->events->insert('primary', $event);
echo "ID => <br>"; 
echo $createdEvent->getId() . "<br>";
echo "HTML => <br>";
echo $createdEvent->getHtmlLink() . "<br>";

?>