<?php
error_reporting(E_ALL | E_STRICT);

// Define path to tests
define('TESTS_PATH', __DIR__);

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
	realpath(TESTS_PATH . '/../library'),
    get_include_path(),
)));
