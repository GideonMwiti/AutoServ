<?php
// login.php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $result = loginUser($pdo, $email, $password);
    if ($result === true) {
        header("Location: " . BASE_URL . "index.php");
        exit;
    }
}

// Check for session refresh message
if (isset($_GET['error']) && $_GET['error'] == 'session_refresh') {
    $error = "System updated. Please log in again to continue.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <!-- Use CDN if the local folder structure is used primarily for scaffolding purposes -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-800 d-flex justify-content-center align-items-center vh-100">

<div class="card login-card p-4 shadow-lg border-0" style="max-width: 400px; width: 100%; border-radius: 8px;">
    <div class="text-center mb-4">
        <h2 class="text-primary-custom" style="font-weight: 700;">
            <i class="fa-solid fa-wrench accent-color me-2"></i> AutoServ
        </h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
        <div class="mb-3">
            <label class="form-label text-secondary">Email address</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="fa-solid fa-envelope text-muted"></i></span>
                <input type="email" name="email" class="form-control focus-ring focus-ring-warning" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label text-secondary">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="fa-solid fa-lock text-muted"></i></span>
                <input type="password" name="password" class="form-control focus-ring focus-ring-warning" required>
            </div>
            <div class="text-end mt-1">
                <a href="#" class="text-decoration-none small text-muted">Forgot password?</a>
            </div>
        </div>
        <button type="submit" class="btn btn-warning w-100 fw-bold py-2 custom-btn-primary">Sign In</button>
    </form>
</div>

</body>
</html>