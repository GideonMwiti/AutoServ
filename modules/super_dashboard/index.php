<?php
// modules/super_dashboard/index.php
if (!isset($_SESSION['is_superadmin']) || !$_SESSION['is_superadmin']) {
    die("Access Denied.");
}

// 1. Basic Platform Counts
$totalGarages = $pdo->query("SELECT COUNT(*) FROM garages")->fetchColumn();
$activeGarages = $pdo->query("SELECT COUNT(*) FROM garages WHERE status = 1")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'Superadmin'")->fetchColumn();

// 2. Performance Metrics
$totalPlatformRevenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments")->fetchColumn();
$totalJobCards = $pdo->query("SELECT COUNT(*) FROM job_cards")->fetchColumn();

// 3. Top Active Garages (by Job Cards)
$topGarages = $pdo->query("SELECT g.name, COUNT(jc.id) as job_count, g.id 
                           FROM garages g 
                           LEFT JOIN job_cards jc ON g.id = jc.garage_id 
                           GROUP BY g.id, g.name 
                           ORDER BY job_count DESC LIMIT 5")->fetchAll();

// 4. Growth Data (Monthly Registration)
$growthData = $pdo->query("SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as count 
                           FROM garages 
                           GROUP BY month 
                           ORDER BY MIN(created_at) ASC LIMIT 6")->fetchAll();
$growthLabels = []; $growthValues = [];
foreach ($growthData as $row) {
    $growthLabels[] = $row['month'];
    $growthValues[] = (int)$row['count'];
}

// 5. Recent System Logs (Mocked/Basic)
$recentGarages = $pdo->query("SELECT * FROM garages ORDER BY created_at DESC LIMIT 5")->fetchAll();

// 6. DB Size (Approximate for MySQL)
$dbSize = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom">Superadmin Command Center</h3>
    <div class="btn-group">
        <a href="?module=super_garages&action=add" class="btn custom-btn-primary"><i class="fa-solid fa-plus me-1"></i> Register Garage</a>
        <a href="?module=settings&tab=system" class="btn btn-outline-secondary"><i class="fa-solid fa-gears me-1"></i> System Config</a>
    </div>
</div>

<!-- Key Platform Metrics -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center bg-white shadow-sm rounded border-start border-primary border-4">
            <div class="stat-icon bg-light text-primary me-3 p-2 rounded-circle"><i class="fa-solid fa-building fa-lg"></i></div>
            <div>
                <div class="text-muted small fw-bold">Garages</div>
                <div class="fs-4 fw-bold"><?= $totalGarages ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center bg-white shadow-sm rounded border-start border-success border-4">
            <div class="stat-icon bg-light text-success me-3 p-2 rounded-circle"><i class="fa-solid fa-dollar-sign fa-lg"></i></div>
            <div>
                <div class="text-muted small fw-bold">Global Revenue</div>
                <div class="fs-4 fw-bold text-success">$<?= number_format($totalPlatformRevenue, 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center bg-white shadow-sm rounded border-start border-warning border-4">
            <div class="stat-icon bg-light text-warning me-3 p-2 rounded-circle"><i class="fa-solid fa-clipboard-check fa-lg"></i></div>
            <div>
                <div class="text-muted small fw-bold">Total Jobs</div>
                <div class="fs-4 fw-bold"><?= $totalJobCards ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center bg-white shadow-sm rounded border-start border-info border-4">
            <div class="stat-icon bg-light text-info me-3 p-2 rounded-circle"><i class="fa-solid fa-database fa-lg"></i></div>
            <div>
                <div class="text-muted small fw-bold">DB Utilization</div>
                <div class="fs-4 fw-bold"><?= $dbSize ?> MB</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Platform Growth Chart -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title fw-bold text-primary-custom mb-4">Tenant Growth Trend</h5>
                <canvas id="growthChart" height="120"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Performing Garages -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title fw-bold text-primary-custom mb-4">Top Active Tenants</h5>
                <div class="list-group list-group-flush">
                    <?php foreach($topGarages as $tg): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <div class="fw-bold"><?= e($tg['name']) ?></div>
                            <small class="text-muted">Garage ID: #<?= $tg['id'] ?></small>
                        </div>
                        <span class="badge bg-primary-custom rounded-pill"><?= $tg['job_count'] ?> Jobs</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Registry Table -->
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h5 class="card-title fw-bold text-primary-custom mb-0">Recent Registrations</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr><th>Tenant</th><th>Account Email</th><th>Phone</th><th>Status</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentGarages as $g): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= e($g['name']) ?></div>
                                    <div class="text-muted small">Joined <?= date('M d, Y', strtotime($g['created_at'])) ?></div>
                                </td>
                                <td><?= e($g['email'] ?? 'N/A') ?></td>
                                <td><?= e($g['phone'] ?? '-') ?></td>
                                <td><?= $g['status'] == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Suspended</span>' ?></td>
                                <td class="text-end">
                                    <a href="?module=super_garages&action=edit&id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen-to-square"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('growthChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($growthLabels) ?>,
                datasets: [{
                    label: 'New Garages',
                    data: <?= json_encode($growthValues) ?>,
                    borderColor: '#4285f4',
                    backgroundColor: 'rgba(66, 133, 244, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#4285f4',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
