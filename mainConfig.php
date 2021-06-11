<?php
// absolute URL of server
$baseURL = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off')?'https':'http') . "://$_SERVER[HTTP_HOST]";
// URL for previews
$previewURL = 'http:/127.0.0.1/';
// theme
$themeURL = 'default';

$liveURL = $baseURL;
