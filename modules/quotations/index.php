<?php
// modules/quotations/index.php
$gid = $_SESSION['garage_id'];

// Handle DELETE
if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare("DELETE FROM quotations WHERE id = ? AND garage_id = ?");
    $stmt->execute([$id, $gid]);
    flash_message('success', 'Quotation deleted successfully.');
    header("Location: ?module=quotations");
    exit;
}

// Handle Convert to Job Card
if ($action === 'convert' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND garage_id = ?");
    $stmt->execute([$id, $gid]);
    $q = $stmt->fetch();
    if ($found) {
        // We just redirect to Job Card add page with pre-filled ID
        header("Location: ?module=jobcards&action=add&quotation_id=$id");
        exit;
    }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int)$_POST['customer_id'];
    $vehicle_id = (int)$_POST['vehicle_id'];
    $status = $_POST['status'];

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO quotations (garage_id, customer_id, vehicle_id, status, total_amount) VALUES (?,?,?,?,0)");
        $stmt->execute([$gid, $customer_id, $vehicle_id, $status]);
        $qid = $pdo->lastInsertId();

        // Save line items
        $total = 0;
        if (isset($_POST['item_desc'])) {
            for ($i = 0; $i < count($_POST['item_desc']); $i++) {
                $desc = trim($_POST['item_desc'][$i]);
                $qty = (int)$_POST['item_qty'][$i];
                $price = (float)$_POST['item_price'][$i];
                if (!empty($desc) && $qty > 0) {
                    $lineTotal = $qty * $price;
                    $total += $lineTotal;
                    $pdo->prepare("INSERT INTO quotation_items (quotation_id, description, quantity, unit_price, total) VALUES (?,?,?,?,?)")
                        ->execute([$qid, $desc, $qty, $price, $lineTotal]);
                }
            }
        }
        $pdo->prepare("UPDATE quotations SET total_amount = ? WHERE id = ?")->execute([$total, $qid]);
        flash_message('success', 'Quotation created successfully.');
        header("Location: ?module=quotations");
        exit;
    }
    if ($action === 'edit' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE quotations SET customer_id=?, vehicle_id=?, status=? WHERE id=? AND garage_id=?");
        $stmt->execute([$customer_id, $vehicle_id, $status, $id, $gid]);

        // Rebuild line items
        $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?")->execute([$id]);
        $total = 0;
        if (isset($_POST['item_desc'])) {
            for ($i = 0; $i < count($_POST['item_desc']); $i++) {
                $desc = trim($_POST['item_desc'][$i]);
                $qty = (int)$_POST['item_qty'][$i];
                $price = (float)$_POST['item_price'][$i];
                if (!empty($desc) && $qty > 0) {
                    $lineTotal = $qty * $price;
                    $total += $lineTotal;
                    $pdo->prepare("INSERT INTO quotation_items (quotation_id, description, quantity, unit_price, total) VALUES (?,?,?,?,?)")
                        ->execute([$id, $desc, $qty, $price, $lineTotal]);
                }
            }
        }
        $pdo->prepare("UPDATE quotations SET total_amount = ? WHERE id = ?")->execute([$total, $id]);
        flash_message('success', 'Quotation updated successfully.');
        header("Location: ?module=quotations");
        exit;
    }
}

$customers = $pdo->query("SELECT id, name FROM users WHERE garage_id = $gid ORDER BY name")->fetchAll();
$allVehicles = $pdo->query("SELECT v.id, v.make, v.model, v.license_plate, v.customer_id FROM vehicles v WHERE v.garage_id = $gid ORDER BY v.make")->fetchAll();
$statusColors = ['Draft' => 'secondary', 'Sent' => 'info', 'Approved' => 'success', 'Rejected' => 'danger'];

// VIEW
if ($action === 'view' && $id > 0):
    $stmt = $pdo->prepare("SELECT q.*, u.name as customer_name, u.email as customer_email, v.make, v.model, v.license_plate, v.vin FROM quotations q LEFT JOIN users u ON q.customer_id = u.id LEFT JOIN vehicles v ON q.vehicle_id = v.id WHERE q.id = ? AND q.garage_id = ?");
    $stmt->execute([$id, $gid]); $q = $stmt->fetch();
    if (!$q) { echo '<div class="alert alert-danger">Quotation not found.</div>'; return; }
    $items = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?"); $items->execute([$id]); $items = $items->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Quotation #Q-<?= str_pad($q['id'], 5, '0', STR_PAD_LEFT) ?></h3>
    <div class="no-print">
        <?php if($q['status'] === 'Approved'): ?>
        <a href="?module=jobcards&action=add&quotation_id=<?= $q['id'] ?>" class="btn btn-success"><i class="fa-solid fa-gear me-1"></i> Convert to Job Card</a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-outline-dark"><i class="fa-solid fa-print me-1"></i> Print</button>
        <a href="?module=quotations&action=edit&id=<?= $q['id'] ?>" class="btn btn-outline-primary"><i class="fa-solid fa-pen"></i> Edit</a>
        <a href="?module=quotations" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm"><div class="card-body p-5">
    <div class="row mb-5">
        <div class="col-6">
            <h4 class="fw-bold text-primary-custom">QUOTATION</h4>
            <div class="text-muted small">No: #Q-<?= str_pad($q['id'], 5, '0', STR_PAD_LEFT) ?></div>
            <div class="text-muted small">Date: <?= date('M d, Y', strtotime($q['created_at'])) ?></div>
        </div>
        <div class="col-6 text-end">
            <div class="badge bg-<?= $statusColors[$q['status']] ?? 'secondary' ?> fs-6"><?= $q['status'] ?></div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <h6 class="text-uppercase text-muted small fw-bold">Customer</h6>
            <div class="fw-bold"><?= e($q['customer_name']) ?></div>
            <div class="small"><?= e($q['customer_email']) ?></div>
        </div>
        <div class="col-md-6">
            <h6 class="text-uppercase text-muted small fw-bold">Vehicle</h6>
            <div class="fw-bold"><?= e($q['make'].' '.$q['model']) ?></div>
            <div class="small">Plate: <?= e($q['license_plate']) ?></div>
            <div class="small text-muted">VIN: <?= e($q['vin']) ?></div>
        </div>
    </div>

    <table class="table table-bordered mt-4">
        <thead class="table-light"><tr><th>Description</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Total</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['description']) ?></td>
                <td class="text-center"><?= $item['quantity'] ?></td>
                <td class="text-end">$ <?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-end">$ <?= number_format($item['total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="table-dark">
                <td colspan="3" class="text-end fw-bold">Grand Total</td>
                <td class="text-end fw-bold">$ <?= number_format($q['total_amount'], 2) ?></td>
            </tr>
        </tbody>
    </table>
    <div class="mt-5 pt-3">
        <p class="small text-muted fst-italic">This quotation is valid for 14 days from the date of issue.</p>
    </div>
</div></div>

<?php 
// FORMS
elseif ($action === 'add' || $action === 'edit'): 
    $isEdit = ($action === 'edit');
    if ($isEdit) {
        $stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND garage_id = ?"); $stmt->execute([$id, $gid]); $q = $stmt->fetch();
        if (!$q) die('Quotation not found');
        $items = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?"); $items->execute([$id]); $items = $items->fetchAll();
    } else {
        $q = ['customer_id'=>'', 'vehicle_id'=>'', 'status'=>'Draft'];
        $items = [];
    }
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom"><?= $isEdit ? 'Edit Quotation #Q-'.str_pad($q['id'],5,'0',STR_PAD_LEFT) : 'New Quotation' ?></h3>
    <a href="?module=quotations" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Customer</label>
                <div class="input-group">
                    <select name="customer_id" class="form-select" required>
                        <option value="">Select...</option>
                        <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= ($q['customer_id'] == $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickAddCustomerModal">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Vehicle</label>
                <select name="vehicle_id" class="form-select" required>
                    <option value="">Select...</option>
                    <?php foreach ($allVehicles as $v): ?><option value="<?= $v['id'] ?>" <?= ($q['vehicle_id'] == $v['id']) ? 'selected' : '' ?>><?= e($v['make'].' '.$v['model'].' ('.$v['license_plate'].')') ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['Draft','Sent','Approved','Rejected'] as $st): ?>
                    <option value="<?= $st ?>" <?= ($q['status'] === $st) ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <h5 class="mt-3 mb-3">Line Items</h5>
        <div id="lineItems">
            <?php if(empty($items)): ?>
            <div class="row mb-2 line-item">
                <div class="col-md-5"><input type="text" name="item_desc[]" class="form-control" placeholder="Description" required></div>
                <div class="col-md-2"><input type="number" name="item_qty[]" class="form-control" placeholder="Qty" value="1" min="1" required></div>
                <div class="col-md-3"><input type="number" name="item_price[]" class="form-control" placeholder="Unit Price" step="0.01" min="0" required></div>
                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.line-item').remove()"><i class="fa-solid fa-trash"></i></button></div>
            </div>
            <?php else: foreach ($items as $it): ?>
            <div class="row mb-2 line-item">
                <div class="col-md-5"><input type="text" name="item_desc[]" class="form-control" value="<?= e($it['description']) ?>" required></div>
                <div class="col-md-2"><input type="number" name="item_qty[]" class="form-control" value="<?= $it['quantity'] ?>" min="1" required></div>
                <div class="col-md-3"><input type="number" name="item_price[]" class="form-control" value="<?= $it['unit_price'] ?>" step="0.01" required></div>
                <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.line-item').remove()"><i class="fa-solid fa-trash"></i></button></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addLineItem()"><i class="fa-solid fa-plus me-1"></i> Add Line</button>
        <br>
        <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Save Quotation</button>
    </form>
</div></div>
<script>
function addLineItem() {
    const html = `<div class="row mb-2 line-item">
        <div class="col-md-5"><input type="text" name="item_desc[]" class="form-control" placeholder="Description" required></div>
        <div class="col-md-2"><input type="number" name="item_qty[]" class="form-control" placeholder="Qty" value="1" min="1" required></div>
        <div class="col-md-3"><input type="number" name="item_price[]" class="form-control" placeholder="Unit Price" step="0.01" min="0" required></div>
        <div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.line-item').remove()"><i class="fa-solid fa-trash"></i></button></div>
    </div>`;
    document.getElementById('lineItems').insertAdjacentHTML('beforeend', html);
}
</script>

<?php else:
    // LIST
    $quotations = $pdo->query("SELECT q.*, u.name as customer_name, v.make, v.model, v.license_plate FROM quotations q LEFT JOIN users u ON q.customer_id = u.id LEFT JOIN vehicles v ON q.vehicle_id = v.id WHERE q.garage_id = $gid ORDER BY q.created_at DESC")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Quotations Management</h3>
    <a href="?module=quotations&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Create Quotation</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Quotation #</th><th>Customer</th><th>Vehicle</th><th>Amount</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($quotations)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No quotations found.</td></tr>
                    <?php else: foreach ($quotations as $q): ?>
                    <tr>
                        <td class="fw-bold">#Q-<?= str_pad($q['id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td><?= e($q['customer_name'] ?? 'N/A') ?></td>
                        <td><?= e($q['make'].' '.$q['model']) ?></td>
                        <td>$ <?= number_format($q['total_amount'], 2) ?></td>
                        <td><span class="badge bg-<?= $statusColors[$q['status']] ?? 'secondary' ?>"><?= e($q['status']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($q['created_at'])) ?></td>
                        <td class="text-end">
                            <a href="?module=quotations&action=view&id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="fa-solid fa-eye"></i></a>
                            <a href="?module=quotations&action=edit&id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <a href="?module=quotations&action=delete&id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this quotation?')"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
