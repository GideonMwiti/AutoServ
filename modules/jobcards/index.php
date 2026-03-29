<?php
// modules/jobcards/index.php
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Job Cards Management</h3>
    <a href="?module=jobcards&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Create Job Card</a>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <select class="form-select">
            <option>All Statuses</option>
            <option>Pending</option>
            <option>In Progress</option>
            <option>Completed</option>
            <option>Delivered</option>
        </select>
    </div>
    <div class="col-md-4">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Search vehicle or ID...">
            <button class="btn btn-outline-secondary"><i class="fa-solid fa-search"></i></button>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Job ID</th>
                        <th>Vehicle & Customer</th>
                        <th>Mechanic</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Mock Data -->
                    <tr>
                        <td class="fw-bold text-primary">#JC-10024</td>
                        <td>
                            <strong>Toyota Camry (XYZ-1234)</strong><br>
                            <small class="text-muted">John Doe</small>
                        </td>
                        <td>Mike Johnson</td>
                        <td><span class="badge bg-warning text-dark">In Progress</span></td>
                        <td>$ 450.00</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></button>
                            <button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-outline-success"><i class="fa-solid fa-print"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-primary">#JC-10023</td>
                        <td>
                            <strong>Honda Civic (ABC-9876)</strong><br>
                            <small class="text-muted">Jane Smith</small>
                        </td>
                        <td>Unassigned</td>
                        <td><span class="badge bg-secondary">Pending</span></td>
                        <td>$ 0.00</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></button>
                            <button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-outline-success"><i class="fa-solid fa-print"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <nav aria-label="Page navigation" class="mt-3 text-end">
          <ul class="pagination justify-content-end mb-0">
            <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
            <li class="page-item active"><a class="page-link bg-primary-custom border-0" href="#">1</a></li>
            <li class="page-item"><a class="page-link text-primary-custom" href="#">Next</a></li>
          </ul>
        </nav>
    </div>
</div>
