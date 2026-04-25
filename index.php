<?php
// entry point
session_start();
require_once 'config/constants.php';

try {
    require_once 'config/database.php';
    require_once 'includes/auth.php';
    require_once 'includes/helpers.php';

    // Check if user is logged in
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }

    // Multi-tenant session integrity check
    if (!isset($_SESSION['is_superadmin']) || (!$_SESSION['is_superadmin'] && !isset($_SESSION['garage_id']))) {
        // If session is old/corrupt, force logout to refresh roles & garage_id
        session_unset();
        session_destroy();
        header("Location: login.php?error=session_refresh");
        exit;
    }

    $is_superadmin = isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'];
    $default_module = $is_superadmin ? 'super_dashboard' : 'dashboard';

    $module = isset($_GET['module']) ? preg_replace('/[^a-z0-9_]/', '', $_GET['module']) : $default_module;
    $action = isset($_GET['action']) ? preg_replace('/[^a-z0-9_]/', '', $_GET['action']) : 'list';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Access control
    if ($is_superadmin && strpos($module, 'super_') !== 0 && $module !== 'settings') {
        $module = 'super_dashboard';
    } elseif (!$is_superadmin && strpos($module, 'super_') === 0) {
        $module = 'dashboard';
    }

    $module_path = "modules/{$module}/index.php";

    require_once 'includes/header.php';
    require_once 'includes/sidebar.php';

    echo '<main class="content px-3 py-2 flex-grow-1">';
    echo '<div class="container-fluid">';

    // Display flash messages
    display_flash();

    if (file_exists($module_path)) {
        require_once $module_path;
    } else {
        echo "<h2>404 - Module Not Found</h2>";
    }

    echo '</div>';
    echo '</main>';

    require_once 'includes/footer.php';

} catch (Exception $e) {
    // If headers haven't been sent, we can still show a nice error page
    if (!headers_sent()) {
        @require_once 'includes/header.php';
        echo '<div class="container mt-5">';
    }
    
    echo '<div class="alert alert-danger shadow-lg border-0 p-4">';
    echo '<h4 class="fw-bold text-danger"><i class="fa-solid fa-circle-exclamation me-2"></i> System Error Detected</h4>';
    echo '<hr>';
    echo '<p class="mb-2">The system encountered an unexpected error. This often happens if the database connection details are incorrect or if a table is missing.</p>';
    echo '<div class="bg-dark text-white p-3 rounded mb-3"><strong>Error Message:</strong><br>' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<p class="small text-muted"><strong>Stack Trace:</strong></p>';
    echo '<pre class="small text-muted bg-light p-2 border" style="max-height: 200px; overflow-y: auto;">' . $e->getTraceAsString() . '</pre>';
    echo '<a href="index.php" class="btn btn-outline-danger mt-3">Try Refreshing</a>';
    echo '</div>';

    if (!headers_sent()) {
        echo '</div>';
        @require_once 'includes/footer.php';
    }
}
?>