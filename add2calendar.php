<?php
// ----------------------------------------------------------------
// Rewrite of https://github.com/spatie/calendar-links to a single page script
// ----------------------------------------------------------------
// Meeting calendar event data -
require_once('./utils.php');
$title = filter_var($_GET["title"], FILTER_SANITIZE_STRING);
$title = urldecode($title);
$description = filter_var($_GET["desc"], FILTER_SANITIZE_STRING);
$description = urldecode($description);
$address = filter_var($_GET["address"], FILTER_SANITIZE_STRING);
$address = urldecode($address);
$allDay = filter_var($_GET["allday"], FILTER_SANITIZE_STRING); //yes or no (do not use boolean)
$startDateInput = filter_var($_GET["from"], FILTER_SANITIZE_NUMBER_INT); // see: $inputDateFormat
$endDateInput = false;
// see: $inputDateFormat --> MUST be a time AFTER $startDate
if (isset($_GET["to"])) {
	$endDateInput = filter_var($_GET["to"], FILTER_SANITIZE_NUMBER_INT);
}
$duration = false;
// DateInterval format, e.g. PT1H
if (isset($_GET["duration"])) {
	$duration = filter_var($_GET["duration"], FILTER_SANITIZE_STRING);
}
if (
    $title === false || $description === false || $address === false || $startDateInput === false ||
    $allDay === false || ($endDateInput === false && $duration === false)
) {
    redirect_to_page('index.html');
}
$inputDateFormat = 'U'; // input should be UNIX timestamp formatted. e.g. 1612612219
$durationInterval = null;
try {
    $startDate = DateTime::createFromFormat($inputDateFormat, $startDateInput);
} catch (Exception $e) {
    echo 'from input date (' . $startDateInput . ') wrong format, please use a unix-timestamp, e.g. 1612612219' . PHP_EOL;
    exit(1);
}
if ($endDateInput) {
    try {
        $endDate = DateTime::createFromFormat($inputDateFormat, $endDateInput);
    } catch (Exception $e) {
        echo 'to input date (' . $endDateInput . ') wrong format, please use a unix-timestamp, e.g. 1612612219' . PHP_EOL;
        exit(1);
    }
} else {
    if ($duration) {
        try {
            $durationInterval = new DateInterval($duration);
        } catch (Exception $e) {
            echo 'duration input (' . $duration . ') wrong format, please use PHP DateInterval format, e.g. PT1H for +1 hour' . PHP_EOL;
            exit(1);
        }
        $endDate = (clone $startDate)->add($durationInterval);
    }
}
if ($endDate <= $startDate) {
    echo 'the end date/time must be after start date/time' . PHP_EOL;
    exit(1);
}
if ($allDay != 'yes' && $allDay != 'no') {
    echo 'allday input (' . $allDay . ') wrong format, please use either "yes" or "no" as value' . PHP_EOL;
    exit(1);
}
$allDay = $allDay == 'yes' ? true : false;
// ----------------------------------------------------------------
$dateFormat = 'Ymd';
$dateTimeFormat = 'Ymd\THis\Z';
$outlookDateFormat = 'Y-m-d';
$outlookDateTimeFormat = 'Y-m-d\TH:i:s\Z';
$IcsDateTimeFormat = 'e:Ymd\THis';
$dateTimeFormat = $allDay ? $dateFormat : $dateTimeFormat;
// ----------------------------------------------------------------
// Google Calendar
$googleCalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE';
$googleCalUrl .= '&dates=' . $startDate->format($dateTimeFormat) . '/' . $endDate->format($dateTimeFormat);
$googleCalUrl .= '&text=' . rawurlencode($title);
if ($description) $googleCalUrl .= '&details=' . rawurlencode($description);
if ($address) $googleCalUrl .= '&location=' . rawurlencode($address);
// ----------------------------------------------------------------
// Outlook365 Calendar
$outlook365Url = 'https://outlook.live.com/calendar/deeplink/compose?path=/calendar/action/compose'; //&rru=addevent causes + signs
$outlook365Url .= '&startdt=' . $startDate->format($outlookDateTimeFormat);
$outlook365Url .= '&enddt=' . $endDate->format($outlookDateTimeFormat);
if ($allDay) $outlook365Url .= '&allday=true';
$outlook365Url .= '&subject=' . rawurlencode($title);
if ($description) $outlook365Url .= '&body=' . rawurlencode($description);
if ($address) $outlook365Url .= '&location=' . rawurlencode($address);
// ----------------------------------------------------------------
// Yahoo! Calendar
$yahooUrl = 'https://calendar.yahoo.com/?v=60&view=d&type=20';
if ($allDay && $startDate->diff($endDate)->days === 1) {
    $yahooUrl .= '&st=' . $startDate->format($dateTimeFormat);
    $yahooUrl .= '&dur=allday';
} else {
    $yahooUrl .= '&st=' . $startDate->format($dateTimeFormat);
    /**
     * Yahoo has a bug on parsing end date parameter: it ignores timezone, assuming
     * that it's specified in user's tz. In order to bypass it, we can use duration ("dur")
     * parameter instead of "et", but this parameter has a limitation cause by it's format HHmm:
     * the max duration is 99hours and 59 minutes (dur=9959).
     */
    $maxDurationInSecs = (59 * 60 * 60) + (59 * 60);
    $canUseDuration = $maxDurationInSecs > ($endDate->getTimestamp() - $startDate->getTimestamp());
    if ($canUseDuration) {
        $dateDiff = $startDate->diff($endDate);
        $yahooUrl .= '&dur=' . $dateDiff->format('%H%I');
    } else {
        $yahooUrl .= '&et=' . $endDate->format($dateTimeFormat);
    }
}
$yahooUrl .= '&title=' . rawurlencode($title);
if ($description) $yahooUrl .= '&desc=' . rawurlencode($description);
if ($address) $yahooUrl .= '&in_loc=' . rawurlencode($address);
// ----------------------------------------------------------------
// ICS Download
// See: https://tools.ietf.org/html/rfc5545#section-3.8.4.7
$eventUuid = md5(sprintf(
    '%s%s%s%s',
    $startDate->format(\DateTimeInterface::ATOM),
    $endDate->format(\DateTimeInterface::ATOM),
    $title,
    $address
));
// See: https://tools.ietf.org/html/rfc5545.html#section-3.3.11
$ecapedTitle = addcslashes($title, "\r\n,;");
$IcsUrlParts = array(
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'BEGIN:VEVENT',
    'UID:' . $eventUuid,
    'SUMMARY:' . $ecapedTitle,
);
$dateTimeFormat2Use = $allDay ? $dateFormat : $IcsDateTimeFormat;
if ($allDay) {
    $IcsUrlParts[] = 'DTSTART:' . $startDate->format($dateTimeFormat2Use);
    $IcsUrlParts[] = 'DURATION:P1D';
} else {
    $IcsUrlParts[] = 'DTSTART;TZID=' . $startDate->format($dateTimeFormat2Use);
    $IcsUrlParts[] = 'DTEND;TZID=' . $endDate->format($dateTimeFormat2Use);
}
if ($description) $IcsUrlParts[] = 'DESCRIPTION:' . addcslashes($description, "\r\n,;");
if ($address) $IcsUrlParts[] = 'LOCATION:' . addcslashes($address, "\r\n,;");
$IcsUrlParts[] = 'END:VEVENT';
$IcsUrlParts[] = 'END:VCALENDAR';
$IcsUrl = 'data:text/calendar;charset=utf8;base64,' . base64_encode(implode("\r\n", $IcsUrlParts));

function echoLink($url, $name, $filename = '')
{
    echo '<a href="' . $url . '" target="_blank"' . ($filename != '' ? 'download="' . $filename . '"' : '') . '>' . $name . '</a>' . PHP_EOL;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Kaltura Job Application</title>
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./css/theme.css" type="text/css" media="all" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="32x32" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="192x192" />
    <link rel="apple-touch-icon" href="./css/images/fav_icon.png" />
    <meta name="msapplication-TileImage" content="./css/images/fav_icon.png" />
</head>

<body>
    <div id="maincontainer" class="main">
        <h1>Add the meeting to your calendar</h1>
        <div class="add2calendar">
            <ul>
                <li><span class="icon-google"></span>&nbsp;<?php echoLink($googleCalUrl, 'Google'); ?></li>
                <li><span class="icon-microsoftoutlook"></span>&nbsp;<?php echoLink($outlook365Url, 'Outlook365'); ?></li>
                <li><span class="icon-yahoo"></span>&nbsp;<?php echoLink($yahooUrl, 'Yahoo!'); ?></li>
                <li><span class="icon-calendar"></span>&nbsp;<?php echoLink($IcsUrl, 'Download ICS file', 'meetingCal.ics'); ?></li>
            </ul>
        </div>
    </div>
</body>

</html>
