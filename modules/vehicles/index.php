<?php
// modules/vehicles/index.php
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
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Toyota Camry</strong></td>
                        <td><span class="badge bg-light text-dark border">XYZ-1234</span></td>
                        <td>John Doe</td>
                        <td>2018</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></button>
                            <button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Honda Civic</strong></td>
                        <td><span class="badge bg-light text-dark border">ABC-9876</span></td>
                        <td>Jane Smith</td>
                        <td>2021</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></button>
                            <button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
