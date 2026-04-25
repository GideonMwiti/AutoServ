<?php
// modules/services/index.php
$gid = $_SESSION['garage_id'];

// Handle DELETE
if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND garage_id = ?");
    $stmt->execute([$id, $gid]);
    flash_message('success', 'Service deleted successfully.');
    header("Location: ?module=services");
    exit;
}

// Handle ADD / EDIT POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $desc  = trim($_POST['description']);
    $price = (float)$_POST['price'];

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO services (garage_id, name, description, price) VALUES (?,?,?,?)");
        $stmt->execute([$gid, $name, $desc, $price]);
        flash_message('success', 'Service added successfully.');
        header("Location: ?module=services");
        exit;
    }
    if ($action === 'edit' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE services SET name=?, description=?, price=? WHERE id=? AND garage_id=?");
        $stmt->execute([$name, $desc, $price, $id, $gid]);
        flash_message('success', 'Service updated successfully.');
        header("Location: ?module=services");
        exit;
    }
}

// ADD FORM
if ($action === 'add'):
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Add Service</h3>
    <a href="?module=services" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="?module=services&action=add">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Service Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Oil Change" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Price ($)</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Service description..."></textarea>
                </div>
            </div>
            <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Save Service</button>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit' && $id > 0):
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND garage_id = ?");
    $stmt->execute([$id, $gid]);
    $svc = $stmt->fetch();
    if (!$svc) { echo '<div class="alert alert-danger">Service not found.</div>'; return; }
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Edit Service</h3>
    <a href="?module=services" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="?module=services&action=edit&id=<?= $svc['id'] ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Service Name</label>
                    <input type="text" name="name" class="form-control" value="<?= e($svc['name']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Price ($)</label>
                    <input type="number" name="price" class="form-control" step="0.01" value="<?= $svc['price'] ?>" required>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= e($svc['description']) ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Update Service</button>
        </form>
    </div>
</div>

<?php else:
    // LIST
    $services = $pdo->query("SELECT * FROM services WHERE garage_id = $gid ORDER BY name")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Services Management</h3>
    <a href="?module=services&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Service</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Service Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No services added yet.</td></tr>
                    <?php else: foreach ($services as $s): ?>
                    <tr>
                        <td><?= $s['id'] ?></td>
                        <td><strong><?= e($s['name']) ?></strong></td>
                        <td><small class="text-muted"><?= e($s['description'] ?? '-') ?></small></td>
                        <td>$ <?= number_format($s['price'], 2) ?></td>
                        <td class="text-end">
                            <a href="?module=services&action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                            <a href="?module=services&action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this service?')"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
