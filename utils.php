<?php
function redirect_to_page($page)
{
    $baseUrl = get_base_url();
    header("Location: $baseUrl/$page");
    exit(1);
}

function get_base_url()
{
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) &&
        ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $protocol = 'https';
    }
    $host  = $_SERVER['HTTP_HOST'];
    $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    return "$protocol://$host$uri";
}
