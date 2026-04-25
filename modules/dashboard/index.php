<?php
// modules/dashboard/index.php
$gid = $_SESSION['garage_id'];

// If somehow gid is missing at this stage (should have been caught by index.php)
if (!$gid && !$_SESSION['is_superadmin']) {
    echo "Access Denied: No garage assigned.";
    return;
}

try {
    // Live stats from DB with parameterized queries
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE garage_id = ?");
    $stmt->execute([$gid]);
    $totalVehicles = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_cards WHERE garage_id = ? AND status IN ('Pending','In Progress')");
    $stmt->execute([$gid]);
    $pendingJobs = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN invoices i ON p.invoice_id = i.id WHERE i.garage_id = ? AND p.paid_at >= DATE_FORMAT(NOW(),'%Y-%m-01')");
    $stmt->execute([$gid]);
    $monthRevenue = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE garage_id = ? AND quantity <= 5");
    $stmt->execute([$gid]);
    $lowStockCount = $stmt->fetchColumn();

    // Recent job cards
    $stmt = $pdo->prepare("SELECT jc.*, v.make, v.model, v.license_plate, u.name as customer_name 
                            FROM job_cards jc 
                            LEFT JOIN vehicles v ON jc.vehicle_id = v.id 
                            LEFT JOIN users u ON v.customer_id = u.id 
                            WHERE jc.garage_id = ? 
                            ORDER BY jc.created_at DESC LIMIT 5");
    $stmt->execute([$gid]);
    $recentJobs = $stmt->fetchAll();

    // Chart data: jobs per day this week
    $stmt = $pdo->prepare("SELECT DAYNAME(created_at) as day_name, COUNT(*) as cnt 
                            FROM job_cards 
                            WHERE garage_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                            GROUP BY day_name, DAYOFWEEK(created_at) 
                            ORDER BY DAYOFWEEK(created_at)");
    $stmt->execute([$gid]);
    $weeklyData = $stmt->fetchAll();
    
    $dayLabels = []; $dayValues = [];
    foreach ($weeklyData as $d) { $dayLabels[] = $d['day_name']; $dayValues[] = (int)$d['cnt']; }
    if (empty($dayLabels)) { $dayLabels = ['Mon','Tue','Wed','Thu','Fri','Sat']; $dayValues = [0,0,0,0,0,0]; }

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Database error: ' . e($e->getMessage()) . '</div>';
    // Log the error for internal use if needed
    error_log($e->getMessage());
}

$statusColors = ['Pending' => 'secondary', 'In Progress' => 'warning text-dark', 'Completed' => 'success', 'Delivered' => 'info'];
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
            <div><div class="text-muted small fw-semibold">Total Vehicles</div><div class="fs-4 fw-bold"><?= (int)$totalVehicles ?></div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center">
            <div class="stat-icon bg-light text-warning me-3"><i class="fa-solid fa-clipboard-list"></i></div>
            <div><div class="text-muted small fw-semibold">Pending Jobs</div><div class="fs-4 fw-bold"><?= (int)$pendingJobs ?></div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center">
            <div class="stat-icon bg-light text-success me-3"><i class="fa-solid fa-dollar-sign"></i></div>
            <div><div class="text-muted small fw-semibold">Revenue (Month)</div><div class="fs-4 fw-bold">$ <?= number_format((float)$monthRevenue, 0) ?></div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center">
            <div class="stat-icon bg-light text-danger me-3"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div><div class="text-muted small fw-semibold">Low Stock</div><div class="fs-4 fw-bold"><?= (int)$lowStockCount ?> Items</div></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title fw-semibold text-primary-custom mb-4">Job Activity This Week</h5>
                <canvas id="serviceTrendsChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title fw-semibold text-primary-custom mb-4">Recent Job Cards</h5>
                <div class="small">
                    <?php if (empty($recentJobs)): ?>
                    <p class="text-muted">No job cards yet. <a href="?module=jobcards&action=add">Create one</a>.</p>
                    <?php else: foreach ($recentJobs as $jc): ?>
                    <div class="d-flex align-items-start mb-3 border-bottom pb-2">
                        <div class="bg-<?= $statusColors[$jc['status']] ?? 'secondary' ?> rounded-circle p-2 text-white me-3" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa-solid fa-<?= $jc['status'] === 'Completed' || $jc['status'] === 'Delivered' ? 'check' : 'wrench' ?> fs-6"></i>
                        </div>
                        <div>
                            <a href="?module=jobcards&action=view&id=<?= $jc['id'] ?>" class="text-decoration-none">
                                <strong>#JC-<?= str_pad($jc['id'],5,'0',STR_PAD_LEFT) ?></strong>
                            </a>
                            <?= e($jc['make'].' '.$jc['model']) ?>
                            <span class="badge bg-<?= $statusColors[$jc['status']] ?? 'secondary' ?> ms-1"><?= $jc['status'] ?></span><br>
                            <span class="text-muted"><?= e($jc['customer_name'] ?? 'N/A') ?> · <?= date('M d', strtotime($jc['created_at'])) ?></span>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('serviceTrendsChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($dayLabels) ?>,
                datasets: [{
                    label: 'Jobs Created',
                    data: <?= json_encode($dayValues) ?>,
                    backgroundColor: '#F59E0B',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
});
</script>