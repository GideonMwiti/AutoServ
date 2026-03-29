<?php
// modules/invoices/index.php
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Invoices Management</h3>
    <a href="?module=invoices&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Add New</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name / Reference</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Active records will appear here. Backend ready to connect to PDO fetching.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
