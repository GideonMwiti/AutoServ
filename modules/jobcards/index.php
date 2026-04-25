<?php
// modules/jobcards/index.php
$gid = $_SESSION['garage_id'];

// Handle DELETE
if ($action === 'delete' && $id > 0) {
    // Delete service line items first, though FK should cascade
    $pdo->prepare("DELETE FROM job_cards WHERE id = ? AND garage_id = ?")->execute([$id, $gid]);
    flash_message('success', 'Job Card deleted successfully.');
    header("Location: ?module=jobcards");
    exit;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $mechanic_id = !empty($_POST['mechanic_id']) ? (int)$_POST['mechanic_id'] : null;
    $status = $_POST['status'];
    $diagnostics = trim($_POST['diagnostics']);
    $total_amount = (float)$_POST['total_amount'];

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO job_cards (garage_id, vehicle_id, mechanic_id, status, diagnostics, total_amount) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$gid, $vehicle_id, $mechanic_id, $status, $diagnostics, $total_amount]);
        flash_message('success', 'Job Card created successfully.');
        header("Location: ?module=jobcards");
        exit;
    }
    if ($action === 'edit' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE job_cards SET vehicle_id=?, mechanic_id=?, status=?, diagnostics=?, total_amount=? WHERE id=? AND garage_id=?");
        $stmt->execute([$vehicle_id, $mechanic_id, $status, $diagnostics, $total_amount, $id, $gid]);
        flash_message('success', 'Job Card updated successfully.');
        header("Location: ?module=jobcards");
        exit;
    }
}

// Fetch dropdown data
$vehicles = $pdo->query("SELECT v.id, v.make, v.model, v.license_plate, u.name as customer_name FROM vehicles v LEFT JOIN users u ON v.customer_id = u.id WHERE v.garage_id = $gid ORDER BY v.make")->fetchAll();
$mechanics = $pdo->query("SELECT id, name FROM users WHERE role IN ('Employee','Admin') AND garage_id = $gid ORDER BY name")->fetchAll();

$statusColors = ['Pending' => 'secondary', 'In Progress' => 'warning text-dark', 'Completed' => 'success', 'Delivered' => 'info'];

// Pre-fill from quotation OR vehicle
$pre_q_id = isset($_GET['quotation_id']) ? (int)$_GET['quotation_id'] : 0;
$pre_vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$pre_data = ['vehicle_id' => $pre_vehicle_id, 'total_amount' => '0.00', 'diagnostics' => ''];

if ($pre_q_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND garage_id = ?");
    $stmt->execute([$pre_q_id, $gid]);
    $found_q = $stmt->fetch();
    if ($found_q) {
        $pre_data['vehicle_id'] = $found_q['vehicle_id'];
        $pre_data['total_amount'] = $found_q['total_amount'];
        // Fetch items for diagnostics
        $stmt = $pdo->prepare("SELECT description FROM quotation_items WHERE quotation_id = ?");
        $stmt->execute([$pre_q_id]);
        $items = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $pre_data['diagnostics'] = "Converted from Quotation #Q-" . str_pad($pre_q_id, 5, '0', STR_PAD_LEFT) . ".\nTasks:\n- " . implode("\n- ", $items);
    }
}

// ADD FORM
if ($action === 'add'):
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Create Job Card</h3>
    <a href="?module=jobcards" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="?module=jobcards&action=add">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Vehicle</label>
                <select name="vehicle_id" class="form-select" required>
                    <option value="">Select vehicle...</option>
                    <?php foreach ($vehicles as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= $pre_data['vehicle_id'] == $v['id'] ? 'selected' : '' ?>><?= e($v['make'].' '.$v['model'].' ('.$v['license_plate'].')') ?> — <?= e($v['customer_name'] ?? 'N/A') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Assign Mechanic</label>
                <select name="mechanic_id" class="form-select">
                    <option value="">Unassigned</option>
                    <?php foreach ($mechanics as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="Pending" selected>Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                    <option value="Delivered">Delivered</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Total Amount ($)</label>
                <input type="number" name="total_amount" class="form-control" step="0.01" min="0" value="<?= $pre_data['total_amount'] ?>">
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Diagnostics / Notes</label>
                <textarea name="diagnostics" class="form-control" rows="4" placeholder="Describe the issue and diagnostics..."><?= e($pre_data['diagnostics']) ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Create Job Card</button>
    </form>
</div></div>

<?php elseif ($action === 'edit' && $id > 0):
    $stmt = $pdo->prepare("SELECT * FROM job_cards WHERE id = ? AND garage_id = ?"); $stmt->execute([$id, $gid]); $jc = $stmt->fetch();
    if (!$jc) { echo '<div class="alert alert-danger">Job Card not found.</div>'; return; }
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Edit Job Card #JC-<?= str_pad($jc['id'], 5, '0', STR_PAD_LEFT) ?></h3>
    <a href="?module=jobcards" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="?module=jobcards&action=edit&id=<?= $jc['id'] ?>">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Vehicle</label>
                <select name="vehicle_id" class="form-select" required>
                    <?php foreach ($vehicles as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= $jc['vehicle_id'] == $v['id'] ? 'selected' : '' ?>><?= e($v['make'].' '.$v['model'].' ('.$v['license_plate'].')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Assign Mechanic</label>
                <select name="mechanic_id" class="form-select">
                    <option value="">Unassigned</option>
                    <?php foreach ($mechanics as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $jc['mechanic_id'] == $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['Pending','In Progress','Completed','Delivered'] as $st): ?>
                    <option value="<?= $st ?>" <?= $jc['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Total Amount ($)</label>
                <input type="number" name="total_amount" class="form-control" step="0.01" value="<?= $jc['total_amount'] ?>">
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Diagnostics / Notes</label>
                <textarea name="diagnostics" class="form-control" rows="4"><?= e($jc['diagnostics']) ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Update Job Card</button>
    </form>
</div></div>

<?php elseif ($action === 'view' && $id > 0):
    $stmt = $pdo->prepare("SELECT jc.*, v.make, v.model, v.license_plate, v.vin, u.name as customer_name, m.name as mechanic_name FROM job_cards jc LEFT JOIN vehicles v ON jc.vehicle_id = v.id LEFT JOIN users u ON v.customer_id = u.id LEFT JOIN users m ON jc.mechanic_id = m.id WHERE jc.id = ? AND jc.garage_id = ?");
    $stmt->execute([$id, $gid]); $jc = $stmt->fetch();
    if (!$jc) { echo '<div class="alert alert-danger">Job Card not found.</div>'; return; }
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Job Card #JC-<?= str_pad($jc['id'], 5, '0', STR_PAD_LEFT) ?></h3>
    <div>
        <a href="?module=invoices&action=add&job_card_id=<?= $jc['id'] ?>" class="btn btn-success"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Create Invoice</a>
        <button onclick="window.print()" class="btn btn-outline-dark"><i class="fa-solid fa-print me-1"></i> Print</button>
        <a href="?module=jobcards&action=edit&id=<?= $jc['id'] ?>" class="btn btn-outline-primary"><i class="fa-solid fa-pen me-1"></i> Edit</a>
        <a href="?module=jobcards" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
            <h5 class="mb-3">Vehicle Details</h5>
            <div class="row">
                <div class="col-md-6 mb-2"><strong>Vehicle:</strong> <?= e($jc['make'].' '.$jc['model']) ?></div>
                <div class="col-md-6 mb-2"><strong>Plate:</strong> <span class="badge bg-light text-dark border"><?= e($jc['license_plate']) ?></span></div>
                <div class="col-md-6 mb-2"><strong>Customer:</strong> <?= e($jc['customer_name'] ?? 'N/A') ?></div>
                <div class="col-md-6 mb-2"><strong>VIN:</strong> <?= e($jc['vin']) ?></div>
            </div>
            <hr>
            <h5 class="mb-3">Diagnostics</h5>
            <p class="text-muted"><?= nl2br(e($jc['diagnostics'] ?? 'No diagnostics recorded.')) ?></p>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
            <h5 class="mb-3">Job Info</h5>
            <p><strong>Status:</strong> <span class="badge bg-<?= $statusColors[$jc['status']] ?? 'secondary' ?>"><?= e($jc['status']) ?></span></p>
            <p><strong>Mechanic:</strong> <?= e($jc['mechanic_name'] ?? 'Unassigned') ?></p>
            <p><strong>Amount:</strong> <span class="fs-5 fw-bold text-success">$ <?= number_format($jc['total_amount'], 2) ?></span></p>
            <p><strong>Created:</strong> <?= date('M d, Y H:i', strtotime($jc['created_at'])) ?></p>
        </div></div>
    </div>
</div>

<?php else:
    // LIST
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
    $where = "WHERE jc.garage_id = ?";
    $params = [$gid];
    if ($statusFilter && in_array($statusFilter, ['Pending','In Progress','Completed','Delivered'])) {
        $where .= " AND jc.status = ?";
        $params[] = $statusFilter;
    }
    $page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
    $perPage = 15;
    $offset = ($page - 1) * $perPage;

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM job_cards jc $where");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $perPage);

    $sql = "SELECT jc.*, v.make, v.model, v.license_plate, u.name as customer_name, m.name as mechanic_name
            FROM job_cards jc
            LEFT JOIN vehicles v ON jc.vehicle_id = v.id
            LEFT JOIN users u ON v.customer_id = u.id
            LEFT JOIN users m ON jc.mechanic_id = m.id
            $where ORDER BY jc.created_at DESC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobcards = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Job Cards Management</h3>
    <a href="?module=jobcards&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Create Job Card</a>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <select class="form-select" onchange="window.location='?module=jobcards&status='+this.value">
            <option value="" <?= !$statusFilter ? 'selected' : '' ?>>All Statuses</option>
            <?php foreach (['Pending','In Progress','Completed','Delivered'] as $st): ?>
            <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Job ID</th><th>Vehicle & Customer</th><th>Mechanic</th><th>Status</th><th>Amount</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($jobcards)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No job cards found.</td></tr>
                    <?php else: foreach ($jobcards as $jc): ?>
                    <tr>
                        <td class="fw-bold text-primary">#JC-<?= str_pad($jc['id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <strong><?= e($jc['make'].' '.$jc['model']) ?> (<?= e($jc['license_plate']) ?>)</strong><br>
                            <small class="text-muted"><?= e($jc['customer_name'] ?? 'N/A') ?></small>
                        </td>
                        <td><?= e($jc['mechanic_name'] ?? 'Unassigned') ?></td>
                        <td><span class="badge bg-<?= $statusColors[$jc['status']] ?? 'secondary' ?>"><?= e($jc['status']) ?></span></td>
                        <td>$ <?= number_format($jc['total_amount'], 2) ?></td>
                        <td class="text-end">
                            <a href="?module=jobcards&action=view&id=<?= $jc['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="fa-solid fa-eye"></i></a>
                            <a href="?module=jobcards&action=edit&id=<?= $jc['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <a href="?module=jobcards&action=delete&id=<?= $jc['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this job card?')"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <nav class="mt-3"><ul class="pagination justify-content-end mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?module=jobcards&status=<?= $statusFilter ?>&page=<?= $page-1 ?>">Previous</a></li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link <?= $i == $page ? 'bg-primary-custom border-0' : 'text-primary-custom' ?>" href="?module=jobcards&status=<?= $statusFilter ?>&page=<?= $i ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link text-primary-custom" href="?module=jobcards&status=<?= $statusFilter ?>&page=<?= $page+1 ?>">Next</a></li>
        </ul></nav>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
