<?php
// modules/inventory/index.php
$gid = $_SESSION['garage_id'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';

// Delete Logic
if ($action === 'delete' && $id > 0) {
    if ($tab === 'products') {
        $pdo->prepare("DELETE FROM products WHERE id=? AND garage_id=?")->execute([$id, $gid]);
        flash_message('success', 'Product deleted.');
    } else {
        $pdo->prepare("DELETE FROM suppliers WHERE id=? AND garage_id=?")->execute([$id, $gid]);
        flash_message('success', 'Supplier deleted.');
    }
    header("Location: ?module=inventory&tab=$tab");
    exit;
}

// Post Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($tab === 'products') {
        $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
        $name = trim($_POST['name']);
        $product_number = trim($_POST['product_number']);
        $unit = trim($_POST['unit']);
        $qty = (int)$_POST['quantity'];
        $price = (float)$_POST['price'];

        if ($action === 'add') {
            $stmt=$pdo->prepare("INSERT INTO products (garage_id, supplier_id, name, product_number, unit, quantity, price) VALUES (?,?,?,?,?,?,?)");
            try {
                $stmt->execute([$gid, $supplier_id, $name, $product_number, $unit, $qty, $price]);
                flash_message('success', 'Product added.');
            } catch (PDOException $e) {
                flash_message('danger', 'Error: Product number may already exist.');
            }
        } elseif ($action === 'edit' && $id > 0) {
            $stmt=$pdo->prepare("UPDATE products SET supplier_id=?, name=?, product_number=?, unit=?, quantity=?, price=? WHERE id=? AND garage_id=?");
            try {
                $stmt->execute([$supplier_id, $name, $product_number, $unit, $qty, $price, $id, $gid]);
                flash_message('success', 'Product updated.');
            } catch (PDOException $e) {
                flash_message('danger', 'Error: Product number may already exist.');
            }
        }
    } else {
        // Suppliers handling
        $name = trim($_POST['name']);
        $contact = trim($_POST['contact_name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);

        // Fix: DB columns are `contact_person`, not `contact_name` according to the old inventory file! Wait let me check install.sql.
        // Actually earlier inventory used `contact_person`. I will use `contact_person`.
        if ($action === 'add') {
            $stmt=$pdo->prepare("INSERT INTO suppliers (garage_id, name, contact_person, phone, email, address) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$gid, $name, $contact, $phone, $email, $address]);
            flash_message('success', 'Supplier added.');
        } elseif ($action === 'edit' && $id > 0) {
            $stmt=$pdo->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=? WHERE id=? AND garage_id=?");
            $stmt->execute([$name, $contact, $phone, $email, $address, $id, $gid]);
            flash_message('success', 'Supplier updated.');
        }
    }
    header("Location: ?module=inventory&tab=$tab");
    exit;
}

// Data fetching for lists
$suppliers = $pdo->query("SELECT * FROM suppliers WHERE garage_id = $gid ORDER BY name")->fetchAll();

$products = [];
if ($tab === 'products' && $action === 'list') {
    $products = $pdo->query("SELECT p.*, s.name as supplier_name FROM products p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.garage_id = $gid ORDER BY p.name")->fetchAll();
}

// Render Forms/Lists...
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Inventory Management</h3>
    <?php if($action === 'list'): ?>
    <div>
        <?php if($tab === 'products'): ?>
        <a href="?module=inventory&tab=products&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Product</a>
        <?php else: ?>
        <a href="?module=inventory&tab=suppliers&action=add" class="btn btn-warning fw-bold"><i class="fa-solid fa-plus me-1"></i> Add Supplier</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if($action === 'list'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?= $tab==='products'?'active fw-bold text-primary-custom':'text-muted' ?>" href="?module=inventory&tab=products">Products</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab==='suppliers'?'active fw-bold text-primary-custom':'text-muted' ?>" href="?module=inventory&tab=suppliers">Suppliers</a>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <?php if($tab === 'products'): ?>
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Item Name</th><th>Part #</th><th>Supplier</th><th>Stock Level</th><th>Price</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if(empty($products)): ?><tr><td colspan="6" class="text-center py-4 text-muted">No products found.</td></tr>
                    <?php else: foreach($products as $p): 
                        $stockClass = $p['quantity'] <= 5 ? 'text-danger fw-bold' : 'text-success';
                    ?>
                    <tr>
                        <td><strong><?= e($p['name']) ?></strong></td>
                        <td><span class="badge bg-light text-dark border"><?= e($p['product_number']) ?></span></td>
                        <td><?= e($p['supplier_name'] ?? 'N/A') ?></td>
                        <td class="<?= $stockClass ?>"><?= $p['quantity'] ?> <?= e($p['unit']) ?>
                            <?php if($p['quantity'] <= 5): ?><i class="fa-solid fa-circle-exclamation ms-1" title="Low Stock"></i><?php endif; ?>
                        </td>
                        <td>$<?= number_format($p['price'], 2) ?></td>
                        <td class="text-end">
                            <a href="?module=inventory&tab=products&action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                            <a href="?module=inventory&tab=products&action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?');"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Supplier Name</th><th>Contact Person</th><th>Phone / Email</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if(empty($suppliers)): ?><tr><td colspan="4" class="text-center py-4 text-muted">No suppliers found.</td></tr>
                    <?php else: foreach($suppliers as $s): ?>
                    <tr>
                        <td><strong><?= e($s['name']) ?></strong></td>
                        <td><?= e($s['contact_person'] ?? '-') ?></td>
                        <td><?= e($s['phone'] ?? '-') ?><br><small class="text-muted"><?= e($s['email'] ?? '-') ?></small></td>
                        <td class="text-end">
                            <a href="?module=inventory&tab=suppliers&action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                            <a href="?module=inventory&tab=suppliers&action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this supplier?');"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// FORMS
elseif($action === 'add' || $action === 'edit'): 
    $isEdit = ($action === 'edit');
    if($tab === 'products') {
        $p = [];
        if($isEdit) {
            $stmt=$pdo->prepare("SELECT * FROM products WHERE id=? AND garage_id=?"); $stmt->execute([$id, $gid]); $p=$stmt->fetch();
            if(!$p) die('Product not found');
        }
?>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h5 class="mb-4"><?= $isEdit ? 'Edit Product' : 'Add New Product' ?></h5>
    <form method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Product Name</label>
                <input type="text" name="name" class="form-control" value="<?= e($p['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Part / Product #</label>
                <input type="text" name="product_number" class="form-control" value="<?= e($p['product_number'] ?? '') ?>" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Supplier</label>
                <div class="input-group">
                    <select name="supplier_id" class="form-select">
                        <option value="">No Supplier</option>
                        <?php foreach($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($p['supplier_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#quickAddSupplierModal">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Quantity in Stock</label>
                <input type="number" name="quantity" class="form-control" value="<?= $p['quantity'] ?? 0 ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Unit</label>
                <input type="text" name="unit" class="form-control" value="<?= e($p['unit'] ?? 'pcs') ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Selling Price ($)</label>
                <input type="number" name="price" class="form-control" step="0.01" value="<?= $p['price'] ?? 0.00 ?>" required>
            </div>
        </div>
        <button type="submit" class="btn custom-btn-primary px-4"><i class="fa-solid fa-save me-1"></i> Save Product</button>
        <a href="?module=inventory&tab=products" class="btn btn-light ms-2">Cancel</a>
    </form>
</div></div>

<?php } else {
        $s = [];
        if($isEdit) {
            $stmt=$pdo->prepare("SELECT * FROM suppliers WHERE id=? AND garage_id=?"); $stmt->execute([$id, $gid]); $s=$stmt->fetch();
            if(!$s) die('Supplier not found');
        }
?>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h5 class="mb-4"><?= $isEdit ? 'Edit Supplier' : 'Add New Supplier' ?></h5>
    <form method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Company Name</label>
                <input type="text" name="name" class="form-control" value="<?= e($s['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Contact Person</label>
                <input type="text" name="contact_name" class="form-control" value="<?= e($s['contact_person'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= e($s['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= e($s['email'] ?? '') ?>">
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Physical Address</label>
                <textarea name="address" class="form-control" rows="2"><?= e($s['address'] ?? '') ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-warning fw-bold px-4"><i class="fa-solid fa-save me-1"></i> Save Supplier</button>
        <a href="?module=inventory&tab=suppliers" class="btn btn-light ms-2">Cancel</a>
    </form>
</div></div>
<?php } endif; ?>
