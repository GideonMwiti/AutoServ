<?php
// modules/settings/index.php
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
$gid = $_SESSION['garage_id'];
$is_superadmin = isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'];

// Handle profile / settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'profile') {
    if (isset($_POST['business_name']) && !$is_superadmin) {
        // Update garage table (Garage Admin only)
        $pdo->prepare("UPDATE garages SET name = ?, email = ?, phone = ? WHERE id = ?")
            ->execute([$_POST['business_name'], $_POST['garage_email'], $_POST['garage_phone'], $gid]);
        flash_message('success', 'Garage settings updated successfully.');
    } else {
        // Password update
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($current, $user['password'])) {
            flash_message('danger', 'Current password is incorrect.');
        } elseif (strlen($new) < 4) {
            flash_message('danger', 'New password must be at least 4 characters.');
        } elseif ($new !== $confirm) {
            flash_message('danger', 'New password and confirmation do not match.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
            flash_message('success', 'Password updated successfully.');
        }
    }
    header("Location: ?module=settings&tab=profile");
    exit;
}

// System test logic
$testResult = null;
if ($tab === 'system' && isset($_GET['test'])) {
    $test = $_GET['test'];
    if ($test === 'db') {
        try {
            $pdo->query("SELECT 1");
            $version = $pdo->query("SELECT VERSION()")->fetchColumn();
            $tablesData = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $testResult = ['type' => 'success', 'msg' => "Database connection successful! MySQL v$version. Total Tables: " . count($tablesData)];
        } catch (PDOException $e) {
            $testResult = ['type' => 'danger', 'msg' => 'Database connection failed: ' . $e->getMessage()];
        }
    } elseif ($test === 'storage') {
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
        $testFile = $uploadDir . 'test_' . time() . '.tmp';
        if (@file_put_contents($testFile, 'test')) {
            @unlink($testFile);
            $testResult = ['type' => 'success', 'msg' => 'Storage permissions OK. System can write to uploads.'];
        } else {
            $testResult = ['type' => 'danger', 'msg' => 'Storage write permission DENIED.'];
        }
    }
}

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

// Fetch garage info if applicable
$garage = null;
if ($gid) {
    $gStmt = $pdo->prepare("SELECT * FROM garages WHERE id = ?");
    $gStmt->execute([$gid]);
    $garage = $gStmt->fetch();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Account & System Settings</h3>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?= $tab == 'profile' ? 'active text-primary-custom fw-bold' : 'text-muted' ?>" href="?module=settings&tab=profile">
                    <i class="fa-solid fa-user me-2"></i> My Profile
                </a>
            </li>
            <?php if ($is_superadmin): ?>
            <li class="nav-item">
                <a class="nav-link <?= $tab == 'system' ? 'active text-primary-custom fw-bold' : 'text-muted' ?>" href="?module=settings&tab=system">
                    <i class="fa-solid fa-vial me-2"></i> Platform Testing
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="card-body p-4">
        <?php if ($tab == 'profile'): ?>
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <div class="rounded-circle bg-primary-custom text-white d-inline-flex align-items-center justify-content-center mb-2" style="width: 100px; height: 100px; font-size: 40px;">
                        <?= substr(e($currentUser['name']), 0, 1) ?>
                    </div>
                    <div class="fw-bold fs-5"><?= e($currentUser['name']) ?></div>
                    <div class="badge bg-secondary"><?= e($currentUser['role']) ?></div>
                </div>
                <div class="col-md-9">
                    <h5 class="mb-3 border-bottom pb-2">Login Details</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Email Address</label>
                            <div class="fw-bold"><?= e($currentUser['email']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Associated Organization</label>
                            <div class="fw-bold"><?= $is_superadmin ? 'PLATFORM OWNER' : e($garage['name'] ?? 'Unassigned') ?></div>
                        </div>
                    </div>

                    <h5 class="mb-3 border-bottom pb-2">Change Password</h5>
                    <form method="POST">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">New Password</label>
                                <input type="password" name="new_password" class="form-control" minlength="4" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" minlength="4" required>
                            </div>
                        </div>
                        <button type="submit" class="btn custom-btn-primary px-4 fw-bold">Update Password</button>
                    </form>

                    <?php if (!$is_superadmin && $_SESSION['user_role'] === 'Admin' && $garage): ?>
                    <h5 class="mb-3 border-bottom pb-2 mt-5">Garage Business Profile</h5>
                    <form method="POST">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small">Business Name</label>
                                <input type="text" name="business_name" class="form-control" value="<?= e($garage['name']) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Email</label>
                                <input type="email" name="garage_email" class="form-control" value="<?= e($garage['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Phone</label>
                                <input type="text" name="garage_phone" class="form-control" value="<?= e($garage['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark px-4 fw-bold">Save Business Settings</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($tab == 'system' && $is_superadmin): ?>
            <h5 class="mb-4">Platform Diagnostics</h5>
            <?php if ($testResult): ?>
                <div class="alert alert-<?= $testResult['type'] ?> mb-4 shadow-sm">
                    <strong>Result:</strong> <?= e($testResult['msg']) ?>
                </div>
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card bg-light border-0"><div class="card-body">
                        <h6>Database</h6>
                        <p class="small text-muted">Test connection and schema health.</p>
                        <a href="?module=settings&tab=system&test=db" class="btn btn-sm btn-outline-success w-100">Run Test</a>
                    </div></div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light border-0"><div class="card-body">
                        <h6>File System</h6>
                        <p class="small text-muted">Check directory write permissions.</p>
                        <a href="?module=settings&tab=system&test=storage" class="btn btn-sm btn-outline-primary w-100">Run Test</a>
                    </div></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
