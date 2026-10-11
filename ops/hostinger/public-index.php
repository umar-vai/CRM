<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Replace this placeholder with the verified private current-release path
// during deployment. Only this entrypoint and public assets belong in web root.
$privateRelease = '__NOVAFLOW_PRIVATE_CURRENT__';

if (file_exists($maintenance = $privateRelease.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $privateRelease.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $privateRelease.'/bootstrap/app.php';
$app->handleRequest(Request::capture());
