<?php
// modules/vehicles/index.php
$gid = $_SESSION['garage_id'];

// Handle DELETE
if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ? AND garage_id = ?");
    $stmt->execute([$id, $gid]);
    flash_message('success', 'Vehicle deleted successfully.');
    header("Location: ?module=vehicles");
    exit;
}

// Handle ADD / EDIT POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id   = (int)$_POST['customer_id'];
    $make          = trim($_POST['make']);
    $model         = trim($_POST['model']);
    $year          = (int)$_POST['year'];
    $license_plate = trim($_POST['license_plate']);
    $vin           = trim($_POST['vin']);
    $engine_number = trim($_POST['engine_number']);

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO vehicles (garage_id, customer_id, make, model, year, license_plate, vin, engine_number) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$gid, $customer_id, $make, $model, $year, $license_plate, $vin, $engine_number]);
        flash_message('success', 'Vehicle added successfully.');
        header("Location: ?module=vehicles");
        exit;
    }
    if ($action === 'edit' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE vehicles SET customer_id=?, make=?, model=?, year=?, license_plate=?, vin=?, engine_number=? WHERE id=? AND garage_id=?");
        $stmt->execute([$customer_id, $make, $model, $year, $license_plate, $vin, $engine_number, $id, $gid]);
        flash_message('success', 'Vehicle updated successfully.');
        header("Location: ?module=vehicles");
        exit;
    }
}

// Fetch customers for dropdown
$customers = $pdo->query("SELECT id, name FROM users WHERE garage_id = $gid ORDER BY name")->fetchAll();

// ADD FORM
if ($action === 'add'):
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Add Vehicle</h3>
    <a href="?module=vehicles" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="?module=vehicles&action=add">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Customer (Owner)</label>
                    <div class="input-group">
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickAddCustomerModal">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Make</label>
                    <input type="text" name="make" class="form-control" placeholder="e.g. Toyota" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Model</label>
                    <input type="text" name="model" class="form-control" placeholder="e.g. Camry" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Year</label>
                    <input type="number" name="year" class="form-control" min="1990" max="2030" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">License Plate</label>
                    <input type="text" name="license_plate" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">VIN</label>
                    <input type="text" name="vin" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Engine Number</label>
                    <input type="text" name="engine_number" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Save Vehicle</button>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit' && $id > 0):
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND garage_id = ?");
    $stmt->execute([$id, $gid]);
    $v = $stmt->fetch();
    if (!$v) { echo '<div class="alert alert-danger">Vehicle not found.</div>'; return; }
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Edit Vehicle</h3>
    <a href="?module=vehicles" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="?module=vehicles&action=edit&id=<?= $v['id'] ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Customer / Owner</label>
                    <select name="customer_id" class="form-select" required>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $v['customer_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Make</label>
                    <input type="text" name="make" class="form-control" value="<?= e($v['make']) ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Model</label>
                    <input type="text" name="model" class="form-control" value="<?= e($v['model']) ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Year</label>
                    <input type="number" name="year" class="form-control" value="<?= $v['year'] ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">License Plate</label>
                    <input type="text" name="license_plate" class="form-control" value="<?= e($v['license_plate']) ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">VIN</label>
                    <input type="text" name="vin" class="form-control" value="<?= e($v['vin']) ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Engine Number</label>
                    <input type="text" name="engine_number" class="form-control" value="<?= e($v['engine_number']) ?>">
                </div>
            </div>
            <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Update Vehicle</button>
        </form>
    </div>
</div>

<?php else:
    // LIST VIEW
    $page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
    $perPage = 15;
    $offset = ($page - 1) * $perPage;
    $total = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE garage_id = $gid")->fetchColumn();
    $totalPages = ceil($total / $perPage);
    
    $stmt = $pdo->prepare("SELECT v.*, u.name as customer_name FROM vehicles v LEFT JOIN users u ON v.customer_id = u.id WHERE v.garage_id = ? ORDER BY v.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $gid, PDO::PARAM_INT);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $vehicles = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Vehicle Management</h3>
    <a href="?module=vehicles&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Vehicle</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Make & Model</th>
                        <th>License Plate</th>
                        <th>Customer</th>
                        <th>Year</th>
                        <th>VIN</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehicles)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No vehicles registered yet.</td></tr>
                    <?php else: foreach ($vehicles as $v): ?>
                    <tr>
                        <td><strong><?= e($v['make']) ?> <?= e($v['model']) ?></strong></td>
                        <td><span class="badge bg-light text-dark border"><?= e($v['license_plate']) ?></span></td>
                        <td><?= e($v['customer_name'] ?? 'N/A') ?></td>
                        <td><?= $v['year'] ?></td>
                        <td><small class="text-muted"><?= e($v['vin']) ?></small></td>
                        <td class="text-end">
                            <a href="?module=jobcards&action=add&vehicle_id=<?= $v['id'] ?>" class="btn btn-sm btn-success" title="New Job Card"><i class="fa-solid fa-file-circle-plus"></i></a>
                            <a href="?module=vehicles&action=edit&id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <a href="?module=vehicles&action=delete&id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this vehicle?')"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <nav class="mt-3"><ul class="pagination justify-content-end mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?module=vehicles&page=<?= $page-1 ?>">Previous</a></li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link <?= $i == $page ? 'bg-primary-custom border-0' : 'text-primary-custom' ?>" href="?module=vehicles&page=<?= $i ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link text-primary-custom" href="?module=vehicles&page=<?= $page+1 ?>">Next</a></li>
        </ul></nav>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
