<?php
define('PATH_ROOT', dirname(__DIR__));

// Initialize Framework
include '../bootstrap.php';

use API\App;

// Load and start application
$app = App::getInstance();
$app->init()->execute();
