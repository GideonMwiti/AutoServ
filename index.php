<?php
// entry point
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

// Simple Router
$module = isset($_GET['module']) ? $_GET['module'] : 'dashboard';
$module_path = "modules/{$module}/index.php";

require_once 'includes/header.php';
require_once 'includes/sidebar.php';

echo '<main class="content px-3 py-2">';
echo '<div class="container-fluid">';

if (file_exists($module_path)) {
    require_once $module_path;
} else {
    echo "<h2>404 - Module Not Found</h2>";
}

echo '</div>';
echo '</main>';

require_once 'includes/footer.php';
?>