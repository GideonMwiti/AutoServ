<?php
// modules/super_users/index.php

if (!isset($_SESSION['is_superadmin']) || !$_SESSION['is_superadmin']) {
    die("Access Denied.");
}

// Handle DELETE
if ($action === 'delete' && $id > 0) {
    if ($id == $_SESSION['user_id']) {
        flash_message('danger', 'You cannot delete yourself.');
    } else {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        flash_message('success', 'User deleted successfully.');
    }
    header("Location: ?module=super_users");
    exit;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $garage_id = ($role === 'Superadmin') ? null : (int)$_POST['garage_id'];
    $status = isset($_POST['status']) ? 1 : 0;

    if ($action === 'add') {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, garage_id) VALUES (?,?,?,?,?,?)");
        try {
            $stmt->execute([$name, $email, $password, $role, $status, $garage_id]);
            flash_message('success', 'User created successfully.');
        } catch (PDOException $e) {
            flash_message('danger', 'Error: Email already exists.');
        }
        header("Location: ?module=super_users");
        exit;
    }
    
    if ($action === 'edit' && $id > 0) {
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=?, role=?, status=?, garage_id=? WHERE id=?");
            $stmt->execute([$name, $email, $password, $role, $status, $garage_id, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, status=?, garage_id=? WHERE id=?");
            $stmt->execute([$name, $email, $role, $status, $garage_id, $id]);
        }
        flash_message('success', 'User updated successfully.');
        header("Location: ?module=super_users");
        exit;
    }
}

$garages = $pdo->query("SELECT id, name FROM garages ORDER BY name")->fetchAll();

// FORMS
if ($action === 'add'):
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Register New User</h3>
    <a href="?module=super_users" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="?module=super_users&action=add">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Password</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Role</label>
                <select name="role" id="roleSelect" class="form-select" required onchange="toggleGarage()">
                    <option value="Superadmin">Platform Superadmin</option>
                    <option value="Admin" selected>Garage Admin</option>
                    <option value="Employee">Garage Employee</option>
                    <option value="Accountant">Garage Accountant</option>
                    <option value="Support Staff">Garage Support</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Assign to Garage</label>
                <select name="garage_id" id="garageSelect" class="form-select" required>
                    <option value="">Select garage...</option>
                    <?php foreach($garages as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="status" id="statusCheck" checked>
                    <label class="form-check-label fw-bold" for="statusCheck">Active User</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Save User</button>
    </form>
</div></div>
<script>
function toggleGarage() {
    const role = document.getElementById('roleSelect').value;
    const gSelect = document.getElementById('garageSelect');
    if (role === 'Superadmin') {
        gSelect.disabled = true;
        gSelect.value = '';
        gSelect.required = false;
    } else {
        gSelect.disabled = false;
        gSelect.required = true;
    }
}
toggleGarage();
</script>

<?php elseif ($action === 'edit' && $id > 0):
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$id]); $u = $stmt->fetch();
    if(!$u) die('User not found');
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Edit User</h3>
    <a href="?module=super_users" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="?module=super_users&action=edit&id=<?= $u['id'] ?>">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= e($u['name']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= e($u['email']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">New Password <small class="text-muted">(leave blank to keep)</small></label>
                <input type="password" name="password" class="form-control" minlength="6">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Role</label>
                <select name="role" id="roleSelect" class="form-select" required onchange="toggleGarage()">
                    <?php foreach(['Superadmin','Admin','Employee','Accountant','Support Staff','Customer'] as $r): ?>
                    <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Assign to Garage</label>
                <select name="garage_id" id="garageSelect" class="form-select" required>
                    <option value="">Select garage...</option>
                    <?php foreach($garages as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= $u['garage_id'] == $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="status" id="statusCheck" <?= $u['status']?'checked':'' ?>>
                    <label class="form-check-label fw-bold" for="statusCheck">Active User</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Update User</button>
    </form>
</div></div>
<script>
function toggleGarage() {
    const role = document.getElementById('roleSelect').value;
    const gSelect = document.getElementById('garageSelect');
    if (role === 'Superadmin') {
        gSelect.disabled = true;
        gSelect.value = '';
        gSelect.required = false;
    } else {
        gSelect.disabled = false;
        gSelect.required = true;
    }
}
toggleGarage();
</script>

<?php else:
    // LIST
    $users = $pdo->query("SELECT u.*, g.name as garage_name FROM users u LEFT JOIN garages g ON u.garage_id = g.id ORDER BY u.id DESC")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Manage Users</h3>
    <a href="?module=super_users&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Register New User</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Assigned Garage</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><strong><?= e($u['name']) ?></strong></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge bg-<?= $u['role'] === 'Superadmin' ? 'dark' : 'primary-custom' ?>"><?= $u['role'] ?></span></td>
                    <td><?= $u['role'] === 'Superadmin' ? '<span class="text-muted fst-italic">Global Platform</span>' : e($u['garage_name'] ?? 'Unassigned') ?></td>
                    <td><?= $u['status'] == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Suspended</span>' ?></td>
                    <td class="text-end">
                        <a href="?module=super_users&action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                        <?php if($u['id'] != $_SESSION['user_id']): ?>
                        <a href="?module=super_users&action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?');"><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
