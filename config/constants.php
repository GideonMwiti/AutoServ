<?php
// Site Constants
define('APP_NAME', 'AutoServ Garage');
define('BASE_URL', 'http://localhost/AutoServ/'); // Update as needed
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Security Defaults
define('SESSION_LIFETIME', 3600 * 2); // 2 hours
define('MAX_LOGIN_ATTEMPTS', 5);

// Error Handling (Set to 1 for debugging)
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');
error_reporting(E_ALL);
?>