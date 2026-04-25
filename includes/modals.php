<!-- Quick Add Customer Modal -->
<div class="modal fade" id="quickAddCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2"></i>Quick Add Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="quickAddCustomerForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email (Optional)</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number (Optional)</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn custom-btn-primary" id="saveQuickCustomer">Save Customer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveQuickCustomer');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const form = document.getElementById('quickAddCustomerForm');
            const formData = new FormData(form);

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            fetch('ajax/quick_add_customer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update all customer selects on the page
                    const selects = document.querySelectorAll('select[name="customer_id"]');
                    selects.forEach(select => {
                        const option = new Option(data.customer.name, data.customer.id, true, true);
                        select.add(option);
                    });

                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('quickAddCustomerModal'));
                    modal.hide();
                    form.reset();

                    // Optional: show a small toast or alert
                    alert('Customer added and selected!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving.');
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'Save Customer';
            });
        });
    }

    const saveSBtn = document.getElementById('saveQuickSupplier');
    if (saveSBtn) {
        saveSBtn.addEventListener('click', function() {
            const form = document.getElementById('quickAddSupplierForm');
            const formData = new FormData(form);

            saveSBtn.disabled = true;
            saveSBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            fetch('ajax/quick_add_supplier.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const selects = document.querySelectorAll('select[name="supplier_id"]');
                    selects.forEach(select => {
                        const option = new Option(data.supplier.name, data.supplier.id, true, true);
                        select.add(option);
                    });
                    bootstrap.Modal.getInstance(document.getElementById('quickAddSupplierModal')).hide();
                    form.reset();
                    alert('Supplier added and selected!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .finally(() => {
                saveSBtn.disabled = false;
                saveSBtn.innerHTML = 'Save Supplier';
            });
        });
    }
});
</script>

<!-- Quick Add Supplier Modal -->
<div class="modal fade" id="quickAddSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-truck-moving me-2"></i>Quick Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="quickAddSupplierForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Company Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning fw-bold text-dark" id="saveQuickSupplier">Save Supplier</button>
            </div>
        </div>
    </div>
</div>
