<?php
require_once('./config.php');
require_once('./utils.php');
$recruiterKs = false;
if (isset($_POST['ks'])) {
    $recruiterKs = filter_var($_POST['ks'], FILTER_SANITIZE_STRING);
}
$uid = false;
if (isset($_POST['uid'])) {
    $uid = filter_var($_POST['uid'], FILTER_SANITIZE_EMAIL);
}
if ($recruiterKs === false || $uid === false) {
    redirect_to_page('recruiterlogin.php');
}
$userName = substr($uid, 0, strrpos($uid, '@'));

$scheduledEventsListUrl = 'https://www.kaltura.com/api_v3/service/schedule_scheduleevent/action/list/format/1?filter[objectType]=KalturaRecordScheduleEventFilter';
$scheduledEventsListUrl .= '&filter[tagsLike]=job-application';
$scheduledEventsListUrl .= '&filter[statusEqual]=2';
$scheduledEventsListUrl .= '&filter[orderBy]=+startDate';
$scheduledEventsListUrl .= '&pager[objectType]=KalturaFilterPager';
$scheduledEventsListUrl .= '&pager[pageIndex]=1';
$scheduledEventsListUrl .= '&pager[pageSize]=500';
$scheduledEventsListUrl .= '&ks=' . $recruiterKs;
try {
    $scheduledEventsList = json_decode(file_get_contents($scheduledEventsListUrl));
} catch (Exception $e) {
    var_dump($e);
    exit(1);
}
$events = array();
foreach ($scheduledEventsList->objects as $scheduledEvent) {
    if (verify_entry_exist($scheduledEvent->entryIds, $recruiterKs) == true) {
        $roomlink = create_room_link($scheduledEvent, $uid, $userName, $apiAdminSecret, $partnerId, $sessionType);
        $events[] = array(
            'Video' => null,
            'Applicantion ID' => $scheduledEvent->entryIds,
            'Details' => nl2br($scheduledEvent->summary),
            'Start Date' => $scheduledEvent->startDate,
            'Room Url' => wrap_as_link($roomlink, '👩‍💻 <span style="font-weight:bold;">Enter interview room</span>')
        );
    }
}

function verify_entry_exist($eid, $ks)
{
    $mediaGetUrl = 'https://cdnapisec.kaltura.com/api_v3/service/media/action/get/format/1/entryId/' . $eid . '/ks/' . $ks;
    try {
        $mediaEntry = json_decode(file_get_contents($mediaGetUrl));
    } catch (Exception $e) {
        var_dump($e);
        exit(1);
    }
    return isset($mediaEntry->id);
}

function wrap_as_link($link, $txt)
{
    return '<a href="' . $link . '" target="_blank">' . $txt . '</a>';
}

function create_room_link($schedEvent, $userId, $userName, $apiAdminSecret, $partnerId, $sessionType)
{
    $startDate = DateTime::createFromFormat('U', $schedEvent->startDate);
    // create the Kaltura Session for authenticated room participant
    // to make sure we give the session enough time to live, we'll set it to when the meeting is supposed to end + 2 hours extra
    $participantSessionEndDate = (clone $startDate)->add(new DateInterval('PT2H')); // add 2 hours to meeting end time
    $sessionExpiry = $participantSessionEndDate->getTimestamp() - $startDate->getTimestamp(); //unixtimestamps are in seconds
    $participantSessionPrivileges = "eventId:$schedEvent->id,role:viewerRole,userContextualRole:3,firstName:$userName";
    $eventParticipantKsUrl = 'https://cdnapisec.kaltura.com/api_v3/service/session/action/start/format/1/secret/' . $apiAdminSecret . '/partnerId/' . $partnerId . '/type/' . $sessionType . '/expiry/' . $sessionExpiry . '/userId/' . $userId . '/privileges/' . $participantSessionPrivileges;
    $eventParticipantKs = file_get_contents($eventParticipantKsUrl);
    $eventParticipantKs = trim($eventParticipantKs, '"');

    // since the room link is rather lengthy with the secure session, we will create a shortlink for it
    // construct the room link with the KS
    $meetingRoomUrl = get_base_url() . '/meetingroom.php?ks=' . $eventParticipantKs;

    return $meetingRoomUrl;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Kaltura Record Job Application</title>
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./css/theme.css" type="text/css" media="all" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="32x32" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="192x192" />
    <link rel="apple-touch-icon" href="./css/images/fav_icon.png" />
    <meta name="msapplication-TileImage" content="./css/images/fav_icon.png" />
    <script src="./js/array2table.js"></script>
    <script type="text/javascript" src="https://cdnapisec.kaltura.com/p/<?php echo $partnerId; ?>/embedPlaykitJs/uiconf_id/<?php echo $recorderPlayerId; ?>"></script>
    <script type="text/javascript" src="./js/playkit-js-hotspots.js"></script>
</head>

<body>
    <script>
        const upcomingEvents = <?php echo json_encode($events); ?>;
        const partnerId = <?php echo $partnerId; ?>;

        var options = {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour12: false,
            timeZoneName: 'short',
            hour: '2-digit',
            minute: '2-digit'
        };
        const len = upcomingEvents.length;
        for (var i = 0; i < len; i++) {
            var milliseconds = upcomingEvents[i]['Start Date'] * 1000;
            var startDateObj = new Date(milliseconds);
            var startDateStr = startDateObj.toLocaleString("en-US", options);
            upcomingEvents[i]['Start Date'] = startDateStr;

            var entryId = upcomingEvents[i]['Applicantion ID'];
            var thumbnailUrl = 'https://www.kaltura.com/api_v3/service/thumbnail_thumbnail/action/transform/p/' + partnerId + '/transformString/id-' + entryId + ',vidSec_s-1,resize_w-200_h-200_bf-1,rc_x-6_y-6_bg-white';
            upcomingEvents[i]['Video'] = '<div id="thumb_' + entryId + '" data-entry-id="' + entryId + '" class="thumb"><img src="' + thumbnailUrl + '" alt="video thumbnail" style="width:200px;" /></div>';
        }

        var kalturaPlayer = null;

        function renderInlinePlayer(entryId) {
            if (kalturaPlayer == null) {
                try {
                    kalturaPlayer = KalturaPlayer.setup({
                        targetId: "kaltura_player",
                        provider: {
                            partnerId: <?php echo $partnerId; ?>,
                            uiConfId: <?php echo $recorderPlayerId; ?>,
                            ks: "<?php echo $recruiterKs; ?>"
                        },
                        plugins: {
                            hotspots: {}
                        },
                        ui: {
                            components: {}
                        }
                    });

                } catch (e) {
                    console.error(e.message)
                }
            }
            kalturaPlayer.loadMedia({
                entryId: entryId
            });
        }
    </script>
    <div id="maincontainer" class="main well embed-wrap">
        <h1>Available Video Job Applications</h1>
        <div id="playercontainer" class="kaltura-player-embed-wrap" style="display:none;">
            <div class="embed-ratio"></div>
            <div id="kaltura_player" class="kaltura-player-embed">
            </div>
        </div>
        <table id="eventsTable" className="table table-sm">
            <thead id="eventsTableHead"></thead>
            <tbody id="eventsBody"></tbody>
        </table>
    </div>
    <div id="noapplicationsmsg" class="main" style="display:none;">
        <h1>No Applications Available</h1>
        <p>🤩 Looks like you're all caught up!</p>
    </div>
    <script>
        const thumbnailClickHandler = function(event) {
            var img = event.target;
            var entryId = img.parentElement.getAttribute('data-entry-id');
            renderInlinePlayer(entryId);
            var playercontainer = document.getElementById('playercontainer');
            playercontainer.style.display = 'inline-block';
        };
        window.addEventListener('DOMContentLoaded', (event) => {
            if (len == 0) {
                var maincontainer = document.getElementById("maincontainer");
                var noapplicationsmsg = document.getElementById("noapplicationsmsg");
                maincontainer.style.display = 'none';
                noapplicationsmsg.style.display = 'block';
            } else {
                var tblHead = document.getElementById("eventsTableHead");
                var tblBody = document.getElementById("eventsBody");
                var thead = renderTableHeader(upcomingEvents);
                var tbody = renderTableRows(upcomingEvents);
                tblHead.innerHTML = thead;
                tblBody.innerHTML = tbody;

                const thumbs = document.querySelectorAll('.thumb');
                Array.from(thumbs).forEach(thumb => {
                    thumb.addEventListener('click', thumbnailClickHandler);
                });
            }
        });
    </script>
</body>

</html>
