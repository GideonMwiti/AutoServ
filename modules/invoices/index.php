<?php
// modules/invoices/index.php
$gid = $_SESSION['garage_id'];

// Handle DELETE
if ($action === 'delete' && $id > 0) {
    $pdo->prepare("DELETE FROM invoices WHERE id = ? AND garage_id = ?")->execute([$id, $gid]);
    flash_message('success', 'Invoice deleted successfully.');
    header("Location: ?module=invoices");
    exit;
}

// Handle Payment POST
if ($action === 'pay' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $method = trim($_POST['method']);
    $reference = trim($_POST['reference']);

    // Check if invoice belongs to this garage
    $invCheck = $pdo->prepare("SELECT id FROM invoices WHERE id = ? AND garage_id = ?");
    $invCheck->execute([$id, $gid]);
    if ($invCheck->fetch()) {
        $pdo->prepare("INSERT INTO payments (invoice_id, amount, method, reference) VALUES (?,?,?,?)")
            ->execute([$id, $amount, $method, $reference]);

        // Update invoice paid amount and status
        $inv = $pdo->prepare("SELECT amount, paid FROM invoices WHERE id = ?");
        $inv->execute([$id]);
        $invoice = $inv->fetch();
        $newPaid = $invoice['paid'] + $amount;
        $newStatus = $newPaid >= $invoice['amount'] ? 'Paid' : 'Partial';
        $pdo->prepare("UPDATE invoices SET paid = ?, status = ? WHERE id = ?")->execute([$newPaid, $newStatus, $id]);

        flash_message('success', 'Payment of $' . number_format($amount, 2) . ' recorded.');
    } else {
        flash_message('danger', 'Unauthorized payment action.');
    }
    header("Location: ?module=invoices&action=view&id=$id");
    exit;
}

// Handle ADD / EDIT POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $customer_id = (int)$_POST['customer_id'];
    $job_card_id = !empty($_POST['job_card_id']) ? (int)$_POST['job_card_id'] : null;
    $amount = (float)$_POST['amount'];
    $issue_date = $_POST['issue_date'];
    $due_date = $_POST['due_date'];

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO invoices (garage_id, customer_id, job_card_id, amount, issue_date, due_date) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$gid, $customer_id, $job_card_id, $amount, $issue_date, $due_date]);
        flash_message('success', 'Invoice created successfully.');
        header("Location: ?module=invoices");
        exit;
    }
    if ($action === 'edit' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE invoices SET customer_id=?, job_card_id=?, amount=?, issue_date=?, due_date=? WHERE id=? AND garage_id=?");
        $stmt->execute([$customer_id, $job_card_id, $amount, $issue_date, $due_date, $id, $gid]);
        flash_message('success', 'Invoice updated successfully.');
        header("Location: ?module=invoices");
        exit;
    }
}

$customers = $pdo->query("SELECT id, name FROM users WHERE garage_id = $gid ORDER BY name")->fetchAll();
$jobcards = $pdo->query("SELECT jc.id, v.make, v.model, v.license_plate, jc.total_amount FROM job_cards jc LEFT JOIN vehicles v ON jc.vehicle_id = v.id WHERE jc.garage_id = $gid ORDER BY jc.id DESC")->fetchAll();
$statusColors = ['Unpaid' => 'danger', 'Partial' => 'warning text-dark', 'Paid' => 'success'];

// Pre-fill logic for job card
$pre_jc_id = isset($_GET['job_card_id']) ? (int)$_GET['job_card_id'] : 0;
$pre_data = ['customer_id' => '', 'amount' => '0.00'];
if ($pre_jc_id > 0) {
    $stmt = $pdo->prepare("SELECT jc.*, v.customer_id FROM job_cards jc LEFT JOIN vehicles v ON jc.vehicle_id = v.id WHERE jc.id = ? AND jc.garage_id = ?");
    $stmt->execute([$pre_jc_id, $gid]);
    $found = $stmt->fetch();
    if ($found) {
        $pre_data['customer_id'] = $found['customer_id'];
        $pre_data['amount'] = $found['total_amount'];
    }
}

// ADD FORM
if ($action === 'add'):
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Create Invoice</h3>
    <a href="?module=invoices" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="?module=invoices&action=add">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Customer</label>
                <select name="customer_id" class="form-select" required>
                    <option value="">Select...</option>
                    <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= $pre_data['customer_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Linked Job Card (optional)</label>
                <select name="job_card_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($jobcards as $jc): ?>
                    <option value="<?= $jc['id'] ?>" <?= $pre_jc_id == $jc['id'] ? 'selected' : '' ?>>#JC-<?= str_pad($jc['id'],5,'0',STR_PAD_LEFT) ?> — <?= e($jc['make'].' '.$jc['model']) ?> ($<?= number_format($jc['total_amount'],2) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Amount ($)</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="0" value="<?= $pre_data['amount'] ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Issue Date</label>
                <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Due Date</label>
                <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
            </div>
        </div>
        <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Create Invoice</button>
    </form>
</div></div>

<?php elseif ($action === 'edit' && $id > 0):
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND garage_id = ?"); $stmt->execute([$id, $gid]); $inv = $stmt->fetch();
    if (!$inv) { echo '<div class="alert alert-danger">Invoice not found.</div>'; return; }
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Edit Invoice #INV-<?= str_pad($inv['id'],5,'0',STR_PAD_LEFT) ?></h3>
    <a href="?module=invoices" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="?module=invoices&action=edit&id=<?= $inv['id'] ?>">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Customer</label>
                <select name="customer_id" class="form-select" required>
                    <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= $inv['customer_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Linked Job Card</label>
                <select name="job_card_id" class="form-select">
                    <option value="">None</option>
                    <?php foreach ($jobcards as $jc): ?><option value="<?= $jc['id'] ?>" <?= $inv['job_card_id'] == $jc['id'] ? 'selected' : '' ?>>#JC-<?= str_pad($jc['id'],5,'0',STR_PAD_LEFT) ?> — <?= e($jc['make'].' '.$jc['model']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Amount ($)</label>
                <input type="number" name="amount" class="form-control" step="0.01" value="<?= $inv['amount'] ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Issue Date</label>
                <input type="date" name="issue_date" class="form-control" value="<?= $inv['issue_date'] ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Due Date</label>
                <input type="date" name="due_date" class="form-control" value="<?= $inv['due_date'] ?>" required>
            </div>
        </div>
        <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Update Invoice</button>
    </form>
</div></div>

<?php elseif ($action === 'view' && $id > 0):
    $stmt = $pdo->prepare("SELECT i.*, u.name as customer_name, u.email as customer_email FROM invoices i LEFT JOIN users u ON i.customer_id = u.id WHERE i.id = ? AND i.garage_id = ?");
    $stmt->execute([$id, $gid]); $inv = $stmt->fetch();
    if (!$inv) { echo '<div class="alert alert-danger">Invoice not found.</div>'; return; }
    $payments = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY paid_at DESC");
    $payments->execute([$id]); $payments = $payments->fetchAll();
    $balance = $inv['amount'] - $inv['paid'];
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Invoice #INV-<?= str_pad($inv['id'],5,'0',STR_PAD_LEFT) ?></h3>
    <div>
        <button onclick="window.print()" class="btn btn-outline-dark"><i class="fa-solid fa-print me-1"></i> Print</button>
        <a href="?module=invoices&action=edit&id=<?= $inv['id'] ?>" class="btn btn-outline-primary"><i class="fa-solid fa-pen me-1"></i> Edit</a>
        <a href="?module=invoices" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
            <div class="row mb-3">
                <div class="col-md-6"><strong>Customer:</strong> <?= e($inv['customer_name'] ?? 'N/A') ?></div>
                <div class="col-md-6"><strong>Email:</strong> <?= e($inv['customer_email'] ?? 'N/A') ?></div>
                <div class="col-md-6 mt-2"><strong>Issue Date:</strong> <?= date('M d, Y', strtotime($inv['issue_date'])) ?></div>
                <div class="col-md-6 mt-2"><strong>Due Date:</strong> <?= date('M d, Y', strtotime($inv['due_date'])) ?></div>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-md-4"><div class="fs-4 fw-bold">$ <?= number_format($inv['amount'], 2) ?></div><small class="text-muted">Total Amount</small></div>
                <div class="col-md-4"><div class="fs-4 fw-bold text-success">$ <?= number_format($inv['paid'], 2) ?></div><small class="text-muted">Paid</small></div>
                <div class="col-md-4"><div class="fs-4 fw-bold text-danger">$ <?= number_format($balance, 2) ?></div><small class="text-muted">Balance</small></div>
            </div>
            <div class="text-center mt-3"><span class="badge bg-<?= $statusColors[$inv['status']] ?? 'secondary' ?> fs-6 px-3 py-2"><?= $inv['status'] ?></span></div>
        </div></div>

        <!-- Payment History -->
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h5 class="mb-3">Payment History</h5>
            <table class="table table-sm">
                <thead class="table-light"><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                    <tr><td colspan="4" class="text-muted text-center">No payments recorded.</td></tr>
                    <?php else: foreach ($payments as $pay): ?>
                    <tr>
                        <td><?= date('M d, Y H:i', strtotime($pay['paid_at'])) ?></td>
                        <td class="text-success fw-bold">$ <?= number_format($pay['amount'], 2) ?></td>
                        <td><?= e($pay['method']) ?></td>
                        <td><?= e($pay['reference'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="col-md-4">
        <?php if ($balance > 0): ?>
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <h5 class="mb-3">Record Payment</h5>
            <form method="POST" action="?module=invoices&action=pay&id=<?= $inv['id'] ?>">
                <div class="mb-3">
                    <label class="form-label fw-bold">Amount ($)</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="<?= $balance ?>" value="<?= $balance ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Method</label>
                    <select name="method" class="form-select">
                        <option value="Cash">Cash</option>
                        <option value="M-Pesa">M-Pesa</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Card">Card</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Reference</label>
                    <input type="text" name="reference" class="form-control" placeholder="Transaction ref...">
                </div>
                <button type="submit" class="btn custom-btn-primary w-100"><i class="fa-solid fa-money-bill me-1"></i> Record Payment</button>
            </form>
        </div></div>
        <?php else: ?>
        <div class="card border-0 shadow-sm bg-success text-white"><div class="card-body p-4 text-center">
            <i class="fa-solid fa-circle-check fa-3x mb-3"></i>
            <h5>Fully Paid</h5>
        </div></div>
        <?php endif; ?>
    </div>
</div>

<?php else:
    // LIST
    $invoices = $pdo->query("SELECT i.*, u.name as customer_name FROM invoices i LEFT JOIN users u ON i.customer_id = u.id WHERE i.garage_id = $gid ORDER BY i.created_at DESC")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Invoices Management</h3>
    <a href="?module=invoices&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Create Invoice</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Invoice #</th><th>Customer</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due Date</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No invoices found.</td></tr>
                    <?php else: foreach ($invoices as $inv): $bal = $inv['amount'] - $inv['paid']; ?>
                    <tr>
                        <td class="fw-bold">#INV-<?= str_pad($inv['id'],5,'0',STR_PAD_LEFT) ?></td>
                        <td><?= e($inv['customer_name'] ?? 'N/A') ?></td>
                        <td>$ <?= number_format($inv['amount'], 2) ?></td>
                        <td class="text-success">$ <?= number_format($inv['paid'], 2) ?></td>
                        <td class="<?= $bal > 0 ? 'text-danger fw-bold' : '' ?>">$ <?= number_format($bal, 2) ?></td>
                        <td><span class="badge bg-<?= $statusColors[$inv['status']] ?? 'secondary' ?>"><?= $inv['status'] ?></span></td>
                        <td><?= date('M d, Y', strtotime($inv['due_date'])) ?></td>
                        <td class="text-end">
                            <a href="?module=invoices&action=view&id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="fa-solid fa-eye"></i></a>
                            <a href="?module=invoices&action=edit&id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <a href="?module=invoices&action=delete&id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this invoice?')"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
