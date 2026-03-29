<?php
// logout.php
session_start();
require_once 'config/constants.php';
require_once 'includes/auth.php';
logoutUser();
?>