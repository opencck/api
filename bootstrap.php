<?php
/**
 * JSON RPC API Backend bootstrap
 *
 * @package openCCK
 * @author Krupkin Sergey <rekryt@yandex.ru>
 */
defined('PATH_ROOT') or define('PATH_ROOT', __DIR__);
define('PATH_INCLUDES', __DIR__);

// load dependencies
include 'vendor/autoload.php';
// load functions
include 'lib/functions.php';
// load definitions
include 'lib/definitions.php';
