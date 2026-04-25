<?php
// modules/users/index.php
$gid = $_SESSION['garage_id'];

// Handle DELETE
if ($action === 'delete' && $id > 0) {
    if ($id == $_SESSION['user_id']) {
        flash_message('danger', 'You cannot delete your own account.');
    } else {
        // Ensure user belongs to this garage
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND garage_id = ?");
        $stmt->execute([$id, $gid]);
        if ($stmt->rowCount() > 0) {
            flash_message('success', 'User deleted successfully.');
        } else {
            flash_message('danger', 'Unauthorized or user not found.');
        }
    }
    header("Location: ?module=users");
    exit;
}

// Handle ADD / EDIT POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role  = $_POST['role'];
    $status = isset($_POST['status']) ? 1 : 0;

    if ($action === 'add') {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, garage_id) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$name, $email, $password, $role, $status, $gid]);
            flash_message('success', 'User created successfully.');
        } catch (PDOException $e) {
            flash_message('danger', 'Error: Email may already exist.');
        }
        header("Location: ?module=users");
        exit;
    }

    if ($action === 'edit' && $id > 0) {
        // Check local ownership
        $check = $pdo->prepare("SELECT id FROM users WHERE id=? AND garage_id=?");
        $check->execute([$id, $gid]);
        if ($check->fetch()) {
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=?, role=?, status=? WHERE id=? AND garage_id=?");
                $stmt->execute([$name, $email, $password, $role, $status, $id, $gid]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, status=? WHERE id=? AND garage_id=?");
                $stmt->execute([$name, $email, $role, $status, $id, $gid]);
            }
            flash_message('success', 'User updated successfully.');
        } else {
            flash_message('danger', 'Unauthorized edit.');
        }
        header("Location: ?module=users");
        exit;
    }
}

// SHOW ADD FORM
if ($action === 'add'):
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Add New Staff Member</h3>
    <a href="?module=users" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="?module=users&action=add">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter staff name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="staff@example.com" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Login Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="Admin">Admin</option>
                        <option value="Employee" selected>Employee (Mechanic)</option>
                        <option value="Accountant">Accountant</option>
                        <option value="Support Staff">Support Staff</option>
                        <option value="Customer">Customer</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <div class="form-check form-switch pb-2">
                        <input class="form-check-input" type="checkbox" name="status" id="statusCheck" checked>
                        <label class="form-check-label fw-bold" for="statusCheck">Active Status</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Save User</button>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit' && $id > 0):
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND garage_id = ?");
    $stmt->execute([$id, $gid]);
    $user = $stmt->fetch();
    if (!$user) { echo '<div class="alert alert-danger">User not found or access denied.</div>'; return; }
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Edit Staff Details</h3>
    <a href="?module=users" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="?module=users&action=edit&id=<?= $user['id'] ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">New Password <small class="text-muted">(leave blank to keep)</small></label>
                    <input type="password" name="password" class="form-control" minlength="6">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Role</label>
                    <select name="role" class="form-select" required>
                        <?php foreach (['Admin','Employee','Accountant','Support Staff','Customer'] as $r): ?>
                        <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <div class="form-check form-switch pb-2">
                        <input class="form-check-input" type="checkbox" name="status" id="statusCheck" <?= $user['status'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="statusCheck">Active Status</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Update User</button>
        </form>
    </div>
</div>

<?php else:
    // LIST VIEW
    $page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
    $perPage = 15;
    $offset = ($page - 1) * $perPage;
    $total = $pdo->prepare("SELECT COUNT(*) FROM users WHERE garage_id = ?");
    $total->execute([$gid]);
    $total = $total->fetchColumn(); $totalPages = ceil($total / $perPage);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE garage_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $gid, PDO::PARAM_INT);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Garage Staff Management</h3>
    <a href="?module=users&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Add New Staff</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No users found for your garage.</td></tr>
                    <?php else: foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><strong><?= e($u['name']) ?></strong></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="badge bg-primary-custom"><?= e($u['role']) ?></span></td>
                        <td><?= $u['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' ?></td>
                        <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td class="text-end">
                            <a href="?module=users&action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <a href="?module=users&action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')"><i class="fa-solid fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <nav class="mt-3"><ul class="pagination justify-content-end mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?module=users&page=<?= $page-1 ?>">Previous</a></li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link <?= $i == $page ? 'bg-primary-custom border-0' : 'text-primary-custom' ?>" href="?module=users&page=<?= $i ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link text-primary-custom" href="?module=users&page=<?= $page+1 ?>">Next</a></li>
        </ul></nav>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
