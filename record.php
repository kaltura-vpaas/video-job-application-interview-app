<?php
require_once('./config.php');
require_once('./utils.php');
$applicantName = filter_var($_POST["candidate_name"], FILTER_SANITIZE_STRING);
$applicantEmail = filter_var($_POST["candidate_email"], FILTER_SANITIZE_EMAIL);
$applicantLinkedin = filter_var($_POST["candidate_linkedin"], FILTER_SANITIZE_URL);
$applicantPhone = filter_var($_POST["candidate_phone"], FILTER_SANITIZE_STRING);
$jobPositions = $_POST['jobpos'];
$jobPositionsStr = 'Selected job positions: ';
foreach ($jobPositions as $jobPos) {
    $jobPositionsStr .= filter_var($jobPos, FILTER_SANITIZE_STRING) . ', ';
}
if ($applicantName === false || $applicantEmail === false || $applicantLinkedin === false || $applicantPhone === false || $jobPositions === false) {
    redirect_to_page('index.html');
}
$entryName = $applicantName . ' [Job Application]';
$entryDescription = $applicantName . "\n" . $applicantEmail . "\n" . $applicantLinkedin . "\n" . $applicantPhone . "\n" . $jobPositionsStr;
$sessionStartRESTAPIUrl = 'https://cdnapisec.kaltura.com/api_v3/service/session/action/start/format/1/secret/' . $apiAdminSecret . '/partnerId/' . $partnerId . '/type/' . $sessionType . '/expiry/' . $expire . '/userId/' . $applicantEmail . '/privileges/editadmintags:*,appid:' . $appName . '-' . $appDomain . ($privacyContext != null ? ',privacycontext:' . $privacyContext : '');
$ks = file_get_contents($sessionStartRESTAPIUrl);
$ks = trim($ks, '"');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Kaltura Record Job Application</title>
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/theme.css" type="text/css" media="all" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="32x32" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="192x192" />
    <link rel="apple-touch-icon" href="./css/images/fav_icon.png" />
    <meta name="msapplication-TileImage" content="./css/images/fav_icon.png" />
    <script src="https://www.kaltura.com/apps/expressrecorder/latest/express-recorder.js"></script>
</head>

<body>
    <script>
        const ks = "<?php echo $ks; ?>";
        const partnerId = <?php echo $partnerId; ?>;
        const playerId = <?php echo $recorderPlayerId; ?>;
        const applicantName = "<?php echo $applicantName; ?>";
        const applicantEmail = "<?php echo $applicantEmail; ?>";
        const applicantLinkedin = "<?php echo $applicantLinkedin; ?>";
        const applicantPhone = "<?php echo $applicantPhone; ?>";
        const entryName = "<?php echo $entryName; ?>";
        const appName = "<?php echo $appName; ?>";
        const entryDescription = `<?php echo $entryDescription; ?>`; //multiline

        var uploadDoneHandler = function(event) {
            var entryId = event.detail.entryId;
            console.log("upload done!", entryId);

            const updateMediaUrl = 'https://www.kaltura.com/api_v3/service/media/action/update?format=1';
            var updateMediaBody = {
                ks: ks,
                entryId: entryId,
                mediaEntry: {
                    objectType: 'KalturaMediaEntry',
                    tags: 'job-application',
                    description: entryDescription
                }
            };
            fetch(updateMediaUrl, {
                method: 'POST',
                body: JSON.stringify(updateMediaBody),
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            }).then(function(response) {
                // The API call was successful!
                if (response.ok) {
                    return response.json();
                } else {
                    return Promise.reject(response);
                }
            }).then(function(data) {
                // This is the JSON from our response
                console.log(data);
                updateUiPostUpload(data);
            }).catch(function(err) {
                // There was an error
                console.log('Something went wrong.', err);
            });
        };

        var updateUiPostUpload = function(mediaEntry) {
            var entryId = mediaEntry.id;
            var elem = document.querySelector('#recorder-wrap');
            elem.style.display = 'none';
            elem.parentNode.removeChild(elem);

            elem = document.querySelector('#instructions');
            elem.style.display = 'none';
            elem.parentNode.removeChild(elem);

            elem = document.querySelector('#completemessage');
            elem.style.display = 'block';

            elem = document.querySelector('#applicanId');
            elem.textContent = entryId;

            elem = document.querySelector('#link2editor');
            var editor_link = './edit.php?id=' + entryId + '&name=' + applicantName + '&ui=' + playerId + '&pid=' + partnerId + '&uid=' + encodeURI(applicantEmail) + '&ks=' + ks;
            elem.setAttribute('href', editor_link);
        }
    </script>
    <div class="main">
        <h1>Kaltura Video Job Application</h1>
        <div id="instructions">
            <p>Please record a brief video below.</p>
            <p>🤓 We love quirky so make it fun!<br />we'd like to know you, and why you'd make an <a href="https://corp.kaltura.com/company/about/" target="_blank">awesome Kalturian</a>!</p>
        </div>
        <div id="completemessage" style="display:none;">
            <h2>Thank you!</h2>
            <p>Your application ID is: <span style="background-color: #FFFF00;" id="applicanId"></span></p>
            <p><span style="font-weight:bold;background-color:yellow;">Next step</span>: <a href="#" style="font-weight:bold;" id="link2editor">Review &amp; edit your video application</a>.</p>
        </div>
        <div id="recorder-wrap" class="well embed-wrap">
            <div class="recorder-wrap__widget">
                <div class="recorder" id="recorder">
                    <script type="text/javascript">
                        var component = Kaltura.ExpressRecorder.create('recorder', {
                            "ks": ks,
                            "serviceUrl": "https://www.kaltura.com",
                            "app": "<?php echo $appName; ?>",
                            "playerUrl": "https://cdnapisec.kaltura.com",
                            "conversionProfileId": null,
                            "partnerId": partnerId,
                            "entryName": entryName,
                            "uiConfId": playerId,
                            "browserNotSupportedText": "Your browser is not supported. Please use a modern browser",
                            "maxRecordingTime": 300,
                            "showUploadUI": true
                        });
                        //component.instance.addEventListener("mediaUploadProgress", uploadProgressHandler);
                        //component.instance.addEventListener("mediaUploadCancelled", uploadCancelHandler);
                        //component.instance.addEventListener("mediaUploadEnded", uploadDoneHandler);
                        component.instance.addEventListener("mediaUploadEnded", uploadDoneHandler);
                        //component.instance.addEventListener("mediaUploadStarted", uploadStartHandler);
                        //component.instance.addEventListener("recordingEnded", handleRecordingEnded);
                    </script>
                </div>
            </div>
        </div>
    </div>
</body>

</html>