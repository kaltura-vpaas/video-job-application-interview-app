<?php
require_once('./config.php');
require_once('./utils.php');
$ks = filter_var($_GET['ks'], FILTER_SANITIZE_STRING);
$entryId = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
$userDisplayName = filter_var($_GET['name'], FILTER_SANITIZE_STRING);
$userDisplayName = urldecode($userDisplayName);
$applicantEmail = filter_var($_GET["uid"], FILTER_SANITIZE_EMAIL);
$applicantEmail = urldecode($applicantEmail);
if ($ks === false || $entryId === false || $userDisplayName === false || $applicantEmail === false) {
    redirect_to_page('index.html');
}
$uiConfId = $editorPlayerId;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Kaltura Edit Your Job Application Video</title>
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/theme.css" type="text/css" media="all" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="32x32" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="192x192" />
    <link rel="apple-touch-icon" href="./css/images/fav_icon.png" />
    <meta name="msapplication-TileImage" content="./css/images/fav_icon.png" />
    <script src="https://www.kaltura.com/apps/expressrecorder/latest/express-recorder.js"></script>
    <script src="./js/kaltura-editor.js"></script>
</head>

<body>
    <script>
        const ks = "<?php echo $ks; ?>";
        const partnerId = <?php echo $partnerId; ?>;
        const uiConfId = <?php echo $uiConfId; ?>;
        const entryId = "<?php echo $entryId; ?>";
        const userDisplayName = "<?php echo $userDisplayName; ?>";
        var keaInitParams = getInitParams('https://cdnapisec.kaltura.com', partnerId, ks, entryId, uiConfId, userDisplayName);
    </script>
    <div class="main">
        <h1>Kaltura Video Job Application</h1>
        <p id="instructions">You can use the editor below to trim your video or add hotspots with links to important references you'd like to highlight for us.</p>
        <p><span style="font-weight:bold;background-color:yellow;">Next step</span>: <a style="font-weight:bold;" href="./review.php?id=<?php echo $entryId; ?>&uid=<?php echo urlencode($applicantEmail); ?>&name=<?php echo urlencode($userDisplayName); ?>&ks=<?php echo $ks; ?>">Schedule a 1 hour interview meeting</a>.</p>
        <div class="responsive-iframe-container">
            <iframe class="responsive-iframe" src="//cdnapisec.kaltura.com/apps/kea/latest/index.html" width='100%' height='100%' frameborder='0' allow='encrypted-media *;fullscreen *;autoplay *;picture-in-picture *;sync-xhr *' sandbox='allow-same-origin allow-scripts allow-forms' allowfullscreen webkitallowfullscreen mozAllowFullScreen></iframe>
        </div>
    </div>
</body>

</html>