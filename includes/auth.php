<?php
// includes/auth.php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function loginUser($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT id, name, role, password, status, garage_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] != 1) {
            return "Your account has been deactivated.";
        }
        
        if ($user['role'] !== 'Superadmin') {
            $gStmt = $pdo->prepare("SELECT status FROM garages WHERE id = ?");
            $gStmt->execute([$user['garage_id']]);
            $garageStatus = $gStmt->fetchColumn();
            if ($garageStatus == 0) {
                return "Your garage account has been suspended.";
            }
        }
        
        session_regenerate_id(true); // Prevent session fixation
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['garage_id'] = $user['garage_id'];
        $_SESSION['is_superadmin'] = ($user['role'] === 'Superadmin');
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