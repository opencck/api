<?php

/**
 * Debug function
 * @param mixed $mixed
 * @param bool $exit
 * @return void
 */
function dbg($mixed, $exit = true) {
	if (php_sapi_name() == "cli") {
		echo json_encode($mixed, JSON_PRETTY_PRINT);
	} else {
		echo '<pre>' . print_r($mixed, true) . '</pre>';
	}
	if ($exit) {
		exit();
	}
}

/**
 * Checking that the string starts with
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function startsWith($haystack, $needle) {
	return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
}

/**
 * Checking that the string ends with
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function endsWith($haystack, $needle) {
	return substr_compare($haystack, $needle, -1*strlen($needle)) === 0;
}
