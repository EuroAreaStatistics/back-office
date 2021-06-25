<?php
// routing script only for use from builtin PHP web server
// usage:
//   env REMOTE_USER=admin php -S localhost:8000 devServer.php
if (php_sapi_name() !== 'cli-server') exit;

// show all errors on console only
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 0);

// fake user login
$_SERVER['REMOTE_USER'] ??= $_ENV['REMOTE_USER'];

// redirect /api/ to /root/index.php
// and serve everything else from original location
if (!preg_match('/^\/api\//', $_SERVER['REQUEST_URI'])) return false;
require_once __DIR__.'/root/index.php';
