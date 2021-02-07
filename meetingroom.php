<?php
require_once('./config.php');
require_once('./utils.php');
$participantKs = filter_var($_GET['ks'], FILTER_SANITIZE_STRING);
if ($participantKs === false) {
    redirect_to_page('index.html');
}
$meetingRoomSrcUrl = $kalturaMeetingRoomLaunchBaseUrl . $participantKs;
?>
<!DOCTYPE HTML>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Kaltura Job Application Video Interview Room</title>
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/theme.css" type="text/css" media="all" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="32x32" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="192x192" />
    <link rel="apple-touch-icon" href="./css/images/fav_icon.png" />
    <meta name="msapplication-TileImage" content="./css/images/fav_icon.png" />
</head>

<body>
    <div class="main">
        <h1>Kaltura Job Application Video Interview</h1>
        <iframe src="<?php echo $meetingRoomSrcUrl; ?>" wmode=transparent allow="microphone *; camera *; speakers *; usermedia *; autoplay *; fullscreen *;" width="1100px" height="700px"></iframe>
    </div>
</body>

</html>