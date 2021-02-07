<?php
require_once('./config.php');
require_once('./utils.php');
$startDateInput = filter_var($_GET["from"], FILTER_SANITIZE_NUMBER_INT); // see: $inputDateFormat
$userks = filter_var($_GET['ks'], FILTER_SANITIZE_STRING);
$entryId = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
$userDisplayName = filter_var($_GET['name'], FILTER_SANITIZE_STRING);
$userDisplayName = urldecode($userDisplayName);
$applicantEmail = filter_var($_GET["uid"], FILTER_SANITIZE_EMAIL);
$applicantEmail = urldecode($applicantEmail);
$startDate = null;
$inputDateFormat = 'U';
try {
    $startDate = DateTime::createFromFormat($inputDateFormat, $startDateInput);
} catch (Exception $e) {
    $startDate = null;
}
$eventDescription = null;
$mediaGetUrl = 'https://cdnapisec.kaltura.com/api_v3/service/media/action/get/format/1/entryId/' . $entryId . '/ks/' . $userks;
try {
    $mediaEntry = json_decode(file_get_contents($mediaGetUrl));
    $eventDescription = $mediaEntry->description;
} catch (Exception $e) {
    $eventDescription = null;
}
if (
    $mediaEntry === null || $eventDescription === null ||
    $startDate === null || $startDateInput === false || $userks === false ||
    $entryId === false || $userDisplayName === false || $applicantEmail === false
) {
    redirect_to_page('index.html');
}

// read more: https://github.com/kaltura-vpaas/virtual-meeting-rooms
// We'll create the video meeting experience room by creating:
// 1. scheduled resource (the room the virtual meeting will take place in)
// 2. scheduled event (indicating the date & time the meeting will happen)
// 3. scheduled event resource (an object mapping the two)

$ksType = 2; //admin KS
$sessionStartRESTAPIUrl = 'https://cdnapisec.kaltura.com/api_v3/service/session/action/start/format/1/secret/' . $apiAdminSecret . '/partnerId/' . $partnerId . '/type/' . $ksType . '/expiry/' . $expire . '/userId/' . $applicantEmail . '/privileges/editadmintags:*,appid:' . $appName . '-' . $appDomain . ($privacyContext != null ? ',privacycontext:' . $privacyContext : '');
$adminks = file_get_contents($sessionStartRESTAPIUrl);
$adminks = trim($adminks, '"');

// create the scheduled resource for the meeting:
$scheduledResourceUrl = 'https://www.kaltura.com/api_v3/service/schedule_scheduleresource/action/add/format/1?scheduleResource[objectType]=KalturaLocationScheduleResource';
$scheduledResourceUrl .= '&scheduleResource[name]=' . urlencode('Meeting room for interview with ' . $userDisplayName);
$scheduledResourceUrl .= '&scheduleResource[description]=' . urlencode($eventDescription);
$scheduledResourceUrl .= '&scheduleResource[tags]=vcprovider:newrow,job-application';
$scheduledResourceUrl .= '&scheduleResource[systemName]=job-application-' . $entryId;
$scheduledResourceUrl .= '&ks=' . $adminks;
try {
    $scheduledResource = json_decode(file_get_contents($scheduledResourceUrl));
} catch (Exception $e) {
    var_dump($e);
    exit(1);
}

// create the event for the meeting:
$duration = 'PT1H'; // schedule the event for 1 hour (StartDate was given in the from param, EndDate will be 1 hour from the StartDate)
$durationInterval = new DateInterval($duration);
$endDate = (clone $startDate)->add($durationInterval);

$scheduledEventUrl = 'https://www.kaltura.com/api_v3/service/schedule_scheduleevent/action/add/format/1?scheduleEvent[objectType]=KalturaRecordScheduleEvent&scheduleEvent[recurrenceType]=0';
$scheduledEventUrl .= '&scheduleEvent[tags]=' . 'job-application,custom_rec_auto_start:1,custom_rs_show_participant:0,custom_rs_show_invite:0,custom_rs_show_chat:1,custom_rs_class_mode:virtual_classroom,custom_rs_show_chat_moderators:0,custom_rs_show_chat_questions:0,custom_rs_room_version:nr2';
$scheduledEventUrl .= '&scheduleEvent[summary]=' . urlencode($eventDescription);
$scheduledEventUrl .= '&scheduleEvent[startDate]=' . $startDate->getTimestamp();
$scheduledEventUrl .= '&scheduleEvent[endDate]=' . $endDate->getTimestamp();
$scheduledEventUrl .= '&scheduleEvent[entryIds]=' . $entryId; //associate this event with the job applicant's video
$scheduledEventUrl .= '&ks=' . $adminks;
try {
    $scheduledEvent = json_decode(file_get_contents($scheduledEventUrl));
} catch (Exception $e) {
    var_dump($e);
    exit(1);
}

// associate the resource (room) with the event
$scheduledEventResourceUrl = 'https://www.kaltura.com/api_v3/service/schedule_scheduleeventresource/action/add/format/1?scheduleEventResource[objectType]=KalturaScheduleEventResource';
$scheduledEventResourceUrl .= '&scheduleEventResource[eventId]=' . $scheduledEvent->id;
$scheduledEventResourceUrl .= '&scheduleEventResource[resourceId]=' . $scheduledResource->id;
$scheduledEventResourceUrl .= '&ks=' . $adminks;
try {
    $scheduledEventResource = json_decode(file_get_contents($scheduledEventResourceUrl));
} catch (Exception $e) {
    var_dump($e);
    exit(1);
}

// create the Kaltura Session for authenticated room participant
// to make sure we give the session enough time to live, we'll set it to when the meeting is supposed to end + 2 hours extra
$participantSessionEndDate = (clone $startDate)->add(new DateInterval('PT2H')); // add 2 hours to meeting end time
$sessionExpiry = $participantSessionEndDate->getTimestamp() - $startDate->getTimestamp(); //unixtimestamps are in seconds
$participantSessionPrivileges = "eventId:$scheduledEvent->id,role:viewerRole,userContextualRole:3,firstName:$userDisplayName";
$eventParticipantKsUrl = 'https://cdnapisec.kaltura.com/api_v3/service/session/action/start/format/1/secret/' . $apiAdminSecret . '/partnerId/' . $partnerId . '/type/' . $sessionType . '/expiry/' . $sessionExpiry . '/userId/' . $applicantEmail . '/privileges/' . $participantSessionPrivileges;
$eventParticipantKs = file_get_contents($eventParticipantKsUrl);
$eventParticipantKs = trim($eventParticipantKs, '"');

// since the room link is rather lengthy with the secure session, we will create a shortlink for it
// construct the room link with the KS
$meetingRoomUrl = get_base_url() . '/meetingroom.php?ks=' . $eventParticipantKs;
// create the short link
$shortLinkAddUrl = 'https://www.kaltura.com/api_v3/service/shortlink_shortlink/action/add/format/1/?shortLink[objectType]=KalturaShortLink&shortLink[status]=2';
$shortLinkAddUrl .= '&shortLink[fullUrl]=' . $meetingRoomUrl;
$shortLinkAddUrl .= '&shortLink[systemName]=' . 'roomshortlink' . $scheduledEvent->id;
$shortLinkAddUrl .= '&ks=' . $adminks;
try {
    $shortLink = json_decode(file_get_contents($shortLinkAddUrl));
} catch (Exception $e) {
    var_dump($e);
    exit(1);
}

$participantRoomShortLink = urlencode($kalturaShortLinkBaseUrl . $shortLink->id);

//redirect to the add2calendar page:
$desc = urlencode($eventDescription);
$addToCalendarLink = "add2calendar.php?title=$scheduledResource->name&desc=$desc&address=$participantRoomShortLink&allday=no&from=$scheduledEvent->startDate&to=$scheduledEvent->endDate";
redirect_to_page($addToCalendarLink);
exit(1);
