<?php
// modules/super_garages/index.php

if (!isset($_SESSION['is_superadmin']) || !$_SESSION['is_superadmin']) {
    die("Access Denied.");
}

// Handle GET Actions
if ($action === 'delete' && $id > 0) {
    if ($id == 1) {
        flash_message('danger', 'Cannot delete the Default Garage.');
    } else {
        $pdo->prepare("DELETE FROM garages WHERE id = ?")->execute([$id]);
        flash_message('success', 'Garage deleted successfully.');
    }
    header("Location: ?module=super_garages");
    exit;
}

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $status = isset($_POST['status']) ? 1 : 0;

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO garages (name, email, phone, status) VALUES (?,?,?,?)");
        $stmt->execute([$name, $email, $phone, $status]);
        $newGarageId = $pdo->lastInsertId();
        
        // Seed default settings for the new garage
        $settings = [
            ['business_name', $name],
            ['currency', 'USD'],
            ['timezone', 'America/New_York']
        ];
        $setStmt = $pdo->prepare("INSERT INTO settings (garage_id, setting_key, setting_value) VALUES (?,?,?)");
        foreach($settings as $s) {
            $setStmt->execute([$newGarageId, $s[0], $s[1]]);
        }

        flash_message('success', 'Garage created successfully.');
        header("Location: ?module=super_garages");
        exit;
    }
    
    if ($action === 'edit' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE garages SET name=?, email=?, phone=?, status=? WHERE id=?");
        $stmt->execute([$name, $email, $phone, $status, $id]);
        flash_message('success', 'Garage updated successfully.');
        header("Location: ?module=super_garages");
        exit;
    }
}

// FORMS
if ($action === 'add'):
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Register New Garage</h3>
    <a href="?module=super_garages" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="?module=super_garages&action=add">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Garage Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Email Address</label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Phone Number</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="col-md-6 mb-3 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="status" id="statusCheck" checked>
                    <label class="form-check-label fw-bold" for="statusCheck">Active</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Save Garage</button>
    </form>
</div></div>

<?php elseif ($action === 'edit' && $id > 0): 
    $stmt = $pdo->prepare("SELECT * FROM garages WHERE id=?"); $stmt->execute([$id]); $g = $stmt->fetch();
    if(!$g) die('Garage not found');
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Edit Garage</h3>
    <a href="?module=super_garages" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="?module=super_garages&action=edit&id=<?= $g['id'] ?>">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Garage Name</label>
                <input type="text" name="name" class="form-control" value="<?= e($g['name']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= e($g['email']) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= e($g['phone']) ?>">
            </div>
            <div class="col-md-6 mb-3 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="status" id="statusCheck" <?= $g['status']?'checked':'' ?>>
                    <label class="form-check-label fw-bold" for="statusCheck">Active</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Update Garage</button>
    </form>
</div></div>

<?php else: 
    // LIST VIEW
    $garages = $pdo->query("SELECT * FROM garages ORDER BY created_at DESC")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Manage Garages</h3>
    <a href="?module=super_garages&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Register New Garage</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr><th>ID</th><th>Garage Name</th><th>Contact</th><th>Status</th><th>Registered</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach($garages as $g): ?>
                <tr>
                    <td class="fw-bold">#<?= $g['id'] ?></td>
                    <td><strong><?= e($g['name']) ?></strong></td>
                    <td><?= e($g['email']?$g['email']:'-') ?><br><small class="text-muted"><?= e($g['phone']?$g['phone']:'') ?></small></td>
                    <td><?= $g['status'] == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Suspended</span>' ?></td>
                    <td><?= date('M d, Y', strtotime($g['created_at'])) ?></td>
                    <td class="text-end">
                        <a href="?module=super_garages&action=edit&id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                        <?php if($g['id'] != 1): ?>
                        <a href="?module=super_garages&action=delete&id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('WARNING: This will permanently delete the garage and ALL its data (users, jobs, invoices, etc). Are you sure?');"><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
