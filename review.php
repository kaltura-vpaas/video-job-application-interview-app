<?php
require_once('./config.php');
require_once('./utils.php');
$entryId = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
$ks = filter_var($_GET['ks'], FILTER_SANITIZE_STRING);
$applicantEmail = filter_var($_GET['uid'], FILTER_SANITIZE_EMAIL);
$userDisplayName = filter_var($_GET['name'], FILTER_SANITIZE_STRING);
$userDisplayName = urldecode($userDisplayName);
if ($ks === false || $entryId === false || $applicantEmail === false) {
    redirect_to_page('index.html');
}
$mediaGetUrl = 'https://cdnapisec.kaltura.com/api_v3/service/media/action/get/format/1/entryId/' . $entryId . '/ks/' . $ks;
try {
    $mediaEntry = json_decode(file_get_contents($mediaGetUrl));
} catch (Exception $e) {
    var_dump($e);
    exit(1);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Kaltura Job Application Review</title>
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/theme.css" type="text/css" media="all" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="32x32" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="192x192" />
    <link rel="apple-touch-icon" href="./css/images/fav_icon.png" />
    <meta name="msapplication-TileImage" content="./css/images/fav_icon.png" />
    <script type="text/javascript" src="https://cdnapisec.kaltura.com/p/<?php echo $partnerId; ?>/embedPlaykitJs/uiconf_id/<?php echo $recorderPlayerId; ?>"></script>
    <script type="text/javascript" src="./js/playkit-js-hotspots.js"></script>
</head>

<body>
    <div class="main">
        <h1>Kaltura Video Job Application</h1>
        <p id="picktimeloading" style="background-color: aqua;"><span class="icon-calendar"></span>&nbsp;Fetching available meeting times...</p>
        <div id="picktimeblock" style="display: none; margin-bottom: 3em;">
            <p style="font-weight: bold;"><span class="icon-calendar"></span>Click to schedule your 1 hour interview:</p>
            <div class="add2calendar">
                <ul id="availabletimes">
                </ul>
            </div>
        </div>
        <div class="well embed-wrap">
            <div class="kaltura-player-embed-wrap">
                <div class="embed-ratio"></div>
                <div id="kaltura_player1" class="kaltura-player-embed">
                </div>
            </div>
            <div style="margin-top: 2em;">
                <p class="left-align-block" style="border: 1px dashed gray; padding: 10px;">
                    <span style="background-color: aqua;">Your application ID: <?php echo $mediaEntry->id; ?></span><br />
                    <?php echo nl2br($mediaEntry->description); ?>
                </p>
            </div>
            <script type="text/javascript">
                try {
                    var kalturaPlayer = KalturaPlayer.setup({
                        targetId: "kaltura_player1",
                        provider: {
                            partnerId: <?php echo $partnerId; ?>,
                            uiConfId: <?php echo $recorderPlayerId; ?>,
                            ks: "<?php echo $ks; ?>"
                        },
                        plugins: {
                            hotspots: {}
                        },
                        ui: {
                            components: {}
                        }
                    });
                    kalturaPlayer.loadMedia({
                        entryId: '<?php echo $entryId; ?>'
                    });
                } catch (e) {
                    console.error(e.message)
                }

                window.addEventListener('DOMContentLoaded', (event) => {
                    var picktimeloading = document.getElementById("picktimeloading");
                    var picktimeblock = document.getElementById("picktimeblock");
                    var availabletimeslist = document.getElementById("availabletimes");

                    //---
                    //integrate with your calendar of choice and populate availabletimeslist (ul) with li options
                    // replace this mock date logic with your calendar availability APIs:
                    var nowdate = new Date();
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
                    nowdate.setDate(nowdate.getDate() + 7); //start in a week form now
                    for (var i = 0; i < 4; ++i) {
                        nowdate.setDate(nowdate.getDate() + 1); //add a day
                        var dateTimeStr = nowdate.toLocaleString("en-US", options);
                        var unixtimestamp = parseInt((nowdate.getTime() / 1000).toFixed(0));
                        var li = document.createElement("li");
                        var a = document.createElement('a');
                        var linkTxt = document.createTextNode(dateTimeStr);
                        a.title = "Book an interview on: " + dateTimeStr;
                        a.href = "./bookmeeting.php?from=" + unixtimestamp + "&id=<?php echo $entryId; ?>&uid=<?php echo $applicantEmail; ?>&name=<?php echo $userDisplayName; ?>&ks=<?php echo $ks; ?>";
                        a.appendChild(linkTxt);
                        li.appendChild(a);
                        availabletimeslist.appendChild(li);
                    }
                    //---

                    picktimeloading.style.display = 'none';
                    picktimeblock.style.display = 'block';
                });
            </script>
        </div>
    </div>
</body>

</html>