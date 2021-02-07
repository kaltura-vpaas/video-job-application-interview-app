<?php
require_once('./config.php');
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
    <link rel="stylesheet" href="./css/theme-recruiter.css" type="text/css" media="all" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="32x32" />
    <link rel="icon" href="./css/images/fav_icon.png" sizes="192x192" />
    <link rel="apple-touch-icon" href="./css/images/fav_icon.png" />
    <meta name="msapplication-TileImage" content="./css/images/fav_icon.png" />

    <script src="./js/pristine.js"></script>
</head>

<body>
    <div class="container">
        <h1>Recruiter Login</h1>
        <form id="loginForm" action="./recruiter.php" method="POST">
            <div class="form-group">
                <input id="uid" name="uid" required type="email" placeholder="Kaltura login email" class="form-control field" />
            </div>
            <div class="form-group">
                <input id="pass" name="pass" required type="password" placeholder="password" class="form-control field" />
            </div>
            <input type="submit" value="login" class="btn" />
        </form>
        <div class="pass-link">
            <a href="https://kmc.kaltura.com/index.php/kmcng/login">Lost your password?</a>
        </div>
    </div>
    <script>
        var pristine;
        var form;
        window.addEventListener('DOMContentLoaded', (event) => {
            form = document.getElementById("loginForm");
            pristine = new Pristine(form);

            form.addEventListener('submit', function(event) {
                const pid = <?php echo $partnerId; ?>;
                const expiry = <?php echo $expire; ?>;
                event.preventDefault();
                var is_valid = pristine.validate();
                if (is_valid == true) {
                    const kalturaUserLoginUrl = "https://www.kaltura.com/api_v3/service/user/action/loginByLoginId/format/1";
                    var creds = {
                        loginId: form.elements['uid'].value,
                        password: form.elements['pass'].value,
                        expiry: expiry
                    };
                    fetch(kalturaUserLoginUrl, {
                            method: "POST",
                            mode: 'cors',
                            cache: 'no-cache',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            redirect: 'follow',
                            body: JSON.stringify(creds)
                        })
                        .then(function(response) {
                            // The API call was successful!
                            if (response.ok) {
                                return response.json();
                            } else {
                                return Promise.reject(response);
                            }
                        })
			.then(function(ks) {
			    if (typeof ks != 'string' || ks.code == "USER_WRONG_PASSWORD") {
				alert('wrong password or email');
			    } else {
	                            form = document.getElementById("loginForm");
	                            var data = {
	                                ks: ks,
       	                        	uid: form.elements['uid'].value
	                            };
			    	redirectPost('./recruiter.php', data);
			    }
                        })
                        .catch(function(err) {
                            // There was an error
                            console.log("Something went wrong.", err);
                            alert('Oh snap! something went wrong!');
                        });
                }
            });
        });

        function redirectPost(url, data) {
            var form = document.createElement('form');
            form.style.display = 'none';
            document.body.appendChild(form);
            form.method = 'post';
            form.action = url;
            for (var name in data) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = data[name];
                form.appendChild(input);
            }
            form.submit();
        }
    </script>
</body>

</html>
