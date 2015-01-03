<?php
/**
 * Error reporting
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

/**
 * Database configuration
 */
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', 'localhost');
define('DB_NAME', 'task_manager');
define('DBS_TYPE', 'mysql');
 
define('USER_CREATED_SUCCESSFULLY', 0);
define('USER_CREATE_FAILED', 1);
define('USER_ALREADY_EXISTED', 2);
