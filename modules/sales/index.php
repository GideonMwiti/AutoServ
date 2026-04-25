<?php
// modules/sales/index.php
$gid = $_SESSION['garage_id'];

// Handle DELETE
if ($action === 'delete' && $id > 0) {
    $sale = $pdo->prepare("SELECT * FROM sales WHERE id = ? AND garage_id = ?");
    $sale->execute([$id, $gid]);
    if ($sale->fetch()) {
        // Restore stock before deleting
        $items = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
        $items->execute([$id]);
        foreach ($items->fetchAll() as $item) {
            $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")->execute([$item['quantity'], $item['product_id']]);
        }
        $pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$id]);
        flash_message('success', 'Sale deleted and stock restored.');
    }
    header("Location: ?module=sales");
    exit;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $notes = trim($_POST['notes']);

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO sales (garage_id, customer_id, total_amount, notes, created_by) VALUES (?,?,0,?,?)");
        $stmt->execute([$gid, $customer_id, $notes, $_SESSION['user_id']]);
        $saleId = $pdo->lastInsertId();

        $total = 0;
        if (isset($_POST['product_id'])) {
            for ($i = 0; $i < count($_POST['product_id']); $i++) {
                $pid = (int)$_POST['product_id'][$i];
                $qty = (int)$_POST['qty'][$i];
                if ($pid > 0 && $qty > 0) {
                    // Get product price
                    $prod = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                    $prod->execute([$pid]);
                    $price = $prod->fetchColumn();
                    $lineTotal = $qty * $price;
                    $total += $lineTotal;

                    $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total) VALUES (?,?,?,?,?)")
                        ->execute([$saleId, $pid, $qty, $price, $lineTotal]);

                    // Deduct stock
                    $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?")->execute([$qty, $pid]);
                }
            }
        }
        $pdo->prepare("UPDATE sales SET total_amount = ? WHERE id = ?")->execute([$total, $saleId]);
        flash_message('success', 'Sale recorded successfully. Stock updated.');
        header("Location: ?module=sales");
        exit;
    }
}

$customers = $pdo->query("SELECT id, name FROM users WHERE garage_id = $gid ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT id, product_number, name, price, quantity FROM products WHERE garage_id = $gid AND quantity > 0 ORDER BY name")->fetchAll();

// ADD FORM
if ($action === 'add'):
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">New Part Sale</h3>
    <a href="?module=sales" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <form method="POST" action="?module=sales&action=add">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Customer (optional)</label>
                <div class="input-group">
                    <select name="customer_id" class="form-select">
                        <option value="">Walk-in Customer</option>
                        <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickAddCustomerModal">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Notes</label>
                <input type="text" name="notes" class="form-control" placeholder="Sale notes...">
            </div>
        </div>

        <h5 class="mt-3 mb-3">Products</h5>
        <div id="saleItems">
            <div class="row mb-2 sale-item">
                <div class="col-md-6">
                    <select name="product_id[]" class="form-select" required>
                        <option value="">Select product...</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= e($p['product_number'].' - '.$p['name']) ?> (Stock: <?= $p['quantity'] ?>, $<?= number_format($p['price'],2) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><input type="number" name="qty[]" class="form-control" placeholder="Quantity" min="1" value="1" required></div>
                <div class="col-md-3"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.sale-item').remove()"><i class="fa-solid fa-trash"></i></button></div>
            </div>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addSaleItem()"><i class="fa-solid fa-plus me-1"></i> Add Product</button>
        <br>
        <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Record Sale</button>
    </form>
</div></div>
<script>
function addSaleItem() {
    const options = document.querySelector('.sale-item select').innerHTML;
    const html = `<div class="row mb-2 sale-item">
        <div class="col-md-6"><select name="product_id[]" class="form-select" required>${options}</select></div>
        <div class="col-md-3"><input type="number" name="qty[]" class="form-control" placeholder="Quantity" min="1" value="1" required></div>
        <div class="col-md-3"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.sale-item').remove()"><i class="fa-solid fa-trash"></i></button></div>
    </div>`;
    document.getElementById('saleItems').insertAdjacentHTML('beforeend', html);
}
</script>

<?php elseif ($action === 'view' && $id > 0):
    $stmt = $pdo->prepare("SELECT s.*, u.name as customer_name, cb.name as created_by_name FROM sales s LEFT JOIN users u ON s.customer_id = u.id LEFT JOIN users cb ON s.created_by = cb.id WHERE s.id = ? AND s.garage_id = ?");
    $stmt->execute([$id, $gid]); $sale = $stmt->fetch();
    if (!$sale) { echo '<div class="alert alert-danger">Sale not found.</div>'; return; }
    $saleItems = $pdo->prepare("SELECT si.*, p.name as product_name, p.product_number FROM sale_items si LEFT JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
    $saleItems->execute([$id]); $saleItems = $saleItems->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Sale #S-<?= str_pad($sale['id'], 5, '0', STR_PAD_LEFT) ?></h3>
    <div>
        <button onclick="window.print()" class="btn btn-outline-dark me-1"><i class="fa-solid fa-print"></i> Print</button>
        <a href="?module=sales" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
    </div>
</div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <div class="row mb-3">
        <div class="col-md-4"><strong>Customer:</strong> <?= e($sale['customer_name'] ?? 'Walk-in') ?></div>
        <div class="col-md-4"><strong>Sold By:</strong> <?= e($sale['created_by_name']) ?></div>
        <div class="col-md-4"><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($sale['created_at'])) ?></div>
    </div>
    <table class="table table-bordered">
        <thead class="table-light"><tr><th>Product</th><th>Part #</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($saleItems as $si): ?>
            <tr>
                <td><?= e($si['product_name'] ?? '') ?></td>
                <td><span class="badge bg-light text-dark border"><?= e($si['product_number'] ?? '') ?></span></td>
                <td><?= $si['quantity'] ?></td>
                <td>$ <?= number_format($si['unit_price'], 2) ?></td>
                <td>$ <?= number_format($si['total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="table-dark"><td colspan="4" class="text-end fw-bold">Grand Total</td><td class="fw-bold">$ <?= number_format($sale['total_amount'], 2) ?></td></tr>
        </tbody>
    </table>
</div></div>

<?php else:
    // LIST
    $sales = $pdo->query("SELECT s.*, u.name as customer_name FROM sales s LEFT JOIN users u ON s.customer_id = u.id WHERE s.garage_id = $gid ORDER BY s.created_at DESC")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Part Sales</h3>
    <a href="?module=sales&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> New Sale</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Sale #</th><th>Customer</th><th>Total</th><th>Notes</th><th>Date</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No sales recorded yet.</td></tr>
                    <?php else: foreach ($sales as $s): ?>
                    <tr>
                        <td class="fw-bold">#S-<?= str_pad($s['id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td><?= e($s['customer_name'] ?? 'Walk-in') ?></td>
                        <td class="fw-bold text-success">$ <?= number_format($s['total_amount'], 2) ?></td>
                        <td><small class="text-muted"><?= e($s['notes'] ?? '-') ?></small></td>
                        <td><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                        <td class="text-end">
                            <a href="?module=sales&action=view&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a>
                            <a href="?module=sales&action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this sale and restore stock?')"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
