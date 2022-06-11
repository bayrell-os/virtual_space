<?php

define( "BASE_PATH", dirname(__DIR__) );
require_once BASE_PATH . "/vendor/autoload.php";

global $app;

/* Run app */
$app = create_app_instance();
$app->init();
$app->runWebApp();
