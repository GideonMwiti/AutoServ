<?php
// includes/auth.php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function loginUser($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT id, name, role, password, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] != 1) {
            return "Your account has been deactivated.";
        }
        
        session_regenerate_id(true); // Prevent session fixation
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time(); // Record activity time
        
        return true;
    }
    
    return "Invalid email or password.";
}

function logoutUser() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Ensure session timeout is respected (e.g. 2 hours)
if (defined('SESSION_LIFETIME') && isLoggedIn() && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    logoutUser();
}
?>