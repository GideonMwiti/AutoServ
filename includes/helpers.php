<?php
// includes/helpers.php
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function flash_message($type = 'success', $message) {
    $_SESSION['flash_msg'] = ['type' => $type, 'message' => $message];
    $_SESSION['flash_shown'] = false;
}

function display_flash() {
    if (isset($_SESSION['flash_msg']) && !$_SESSION['flash_shown']) {
        echo '<div class="alert alert-' . e($_SESSION['flash_msg']['type']) . ' alert-dismissible fade show" role="alert">
                ' . e($_SESSION['flash_msg']['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        $_SESSION['flash_shown'] = true;
        unset($_SESSION['flash_msg']);
    }
}
?>