<?php
// modules/dashboard/index.php
// Placeholder Dashboard elements as Phase 1 initialization
?>
<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Dashboard</h3>
    <div class="btn-group">
        <a href="?module=jobcards&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> New Job Card</a>
        <a href="?module=vehicles&action=add" class="btn btn-outline-secondary"><i class="fa-solid fa-car me-1"></i> Add Vehicle</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center">
            <div class="stat-icon bg-light text-primary-custom me-3"><i class="fa-solid fa-car-side"></i></div>
            <div>
                <div class="text-muted small fw-semibold">Total Vehicles</div>
                <div class="fs-4 fw-bold">142</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center">
            <div class="stat-icon bg-light text-warning me-3"><i class="fa-solid fa-clipboard-list"></i></div>
            <div>
                <div class="text-muted small fw-semibold">Pending Jobs</div>
                <div class="fs-4 fw-bold">18</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center">
            <div class="stat-icon bg-light text-success me-3"><i class="fa-solid fa-dollar-sign"></i></div>
            <div>
                <div class="text-muted small fw-semibold">Revenue (Month)</div>
                <div class="fs-4 fw-bold">$12,450</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center">
            <div class="stat-icon bg-light text-danger me-3"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <div class="text-muted small fw-semibold">Low Stock Alerts</div>
                <div class="fs-4 fw-bold">5 Items</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title fw-semibold text-primary-custom mb-4">Service Trends</h5>
                <canvas id="serviceTrendsChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title fw-semibold text-primary-custom mb-4">Recent Activity</h5>
                <div class="small">
                    <div class="d-flex align-items-start mb-3 border-bottom pb-2">
                        <div class="bg-success rounded-circle p-2 text-white me-3" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-check fs-6"></i></div>
                        <div>
                            <strong>Job Card #1024</strong> Completed<br>
                            <span class="text-muted">By Mike - 2 hours ago</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Will attach to footer via custom.js, included inline for fast testing preview
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('serviceTrendsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                datasets: [{
                    label: 'Jobs Completed',
                    data: [12, 19, 3, 5, 2, 3],
                    backgroundColor: '#1F2937'
                }, {
                    label: 'New Jobs',
                    data: [5, 10, 8, 12, 6, 7],
                    backgroundColor: '#F59E0B'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>