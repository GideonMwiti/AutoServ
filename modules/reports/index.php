<?php
// modules/reports/index.php
$gid = $_SESSION['garage_id'];
$is_superadmin = isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'];

// If superadmin, we show global platform data. Otherwise, scope to the specific garage.
$where = $is_superadmin ? "1=1" : "garage_id = " . (int)$gid;

// Fetch report data
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(paid),0) FROM invoices WHERE $where")->fetchColumn();
$totalOutstanding = $pdo->query("SELECT COALESCE(SUM(amount - paid),0) FROM invoices WHERE status != 'Paid' AND $where")->fetchColumn();
$totalJobCards = $pdo->query("SELECT COUNT(*) FROM job_cards WHERE $where")->fetchColumn();
$pendingJobs = $pdo->query("SELECT COUNT(*) FROM job_cards WHERE status IN ('Pending','In Progress') AND $where")->fetchColumn();
$completedJobs = $pdo->query("SELECT COUNT(*) FROM job_cards WHERE status IN ('Completed','Delivered') AND $where")->fetchColumn();
$totalVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE $where")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE $where")->fetchColumn();
$inventoryValue = $pdo->query("SELECT COALESCE(SUM(price * quantity),0) FROM products WHERE $where")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= 5 AND $where")->fetchColumn();

// Monthly revenue data (last 6 months)
$monthlyData = $pdo->query("SELECT DATE_FORMAT(p.paid_at, '%Y-%m') as month, SUM(p.amount) as total 
                            FROM payments p 
                            JOIN invoices i ON p.invoice_id = i.id 
                            WHERE " . ($is_superadmin ? "1=1" : "i.garage_id = " . (int)$gid) . " 
                            AND p.paid_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
                            GROUP BY month 
                            ORDER BY MIN(p.paid_at)")->fetchAll();
$monthLabels = []; $monthValues = [];
foreach ($monthlyData as $m) {
    if (!$m['month']) continue;
    $monthLabels[] = date('M Y', strtotime($m['month'].'-01'));
    $monthValues[] = (float)$m['total'];
}

// Job status distribution
$jobStatusData = $pdo->query("SELECT status, COUNT(*) as cnt FROM job_cards WHERE $where GROUP BY status")->fetchAll();
$jsLabels = []; $jsValues = [];
foreach ($jobStatusData as $js) { $jsLabels[] = $js['status']; $jsValues[] = (int)$js['cnt']; }

// Performance list: If Superadmin, show Top Garages. If Admin, show Top Mechanics.
if ($is_superadmin) {
    $performanceTitle = "Top Garages by Revenue";
    $performanceData = $pdo->query("SELECT g.name as label, COUNT(jc.id) as count, COALESCE(SUM(p.amount),0) as metric 
                                    FROM garages g 
                                    LEFT JOIN job_cards jc ON g.id = jc.garage_id 
                                    LEFT JOIN invoices i ON jc.id = i.job_card_id 
                                    LEFT JOIN payments p ON i.id = p.invoice_id
                                    GROUP BY g.id, g.name ORDER BY metric DESC LIMIT 5")->fetchAll();
} else {
    $performanceTitle = "Top Mechanics Performance";
    $performanceData = $pdo->query("SELECT u.name as label, COUNT(jc.id) as count, COALESCE(SUM(jc.total_amount),0) as metric 
                                    FROM job_cards jc 
                                    LEFT JOIN users u ON jc.mechanic_id = u.id 
                                    WHERE jc.garage_id = $gid AND jc.mechanic_id IS NOT NULL 
                                    GROUP BY u.id, u.name ORDER BY count DESC LIMIT 5")->fetchAll();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-3">
    <h3 class="mb-0 fw-bold text-primary-custom"><?= $is_superadmin ? 'Global Platform Analytics' : 'Garage Performance Reports' ?></h3>
    <?php if($is_superadmin): ?>
        <span class="badge bg-warning text-dark px-3 py-2">LIVE PLATFORM DATA</span>
    <?php endif; ?>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center bg-white shadow-sm rounded">
            <div class="stat-icon bg-light text-success me-3"><i class="fa-solid fa-sack-dollar fa-xl"></i></div>
            <div><div class="text-muted small fw-semibold"><?= $is_superadmin ? 'Total Platform Rev' : 'Total Revenue' ?></div><div class="fs-4 fw-bold text-success">$ <?= number_format($totalRevenue, 2) ?></div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center bg-white shadow-sm rounded">
            <div class="stat-icon bg-light text-danger me-3"><i class="fa-solid fa-file-invoice fa-xl"></i></div>
            <div><div class="text-muted small fw-semibold">Total Outstanding</div><div class="fs-4 fw-bold text-danger">$ <?= number_format($totalOutstanding, 2) ?></div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center bg-white shadow-sm rounded">
            <div class="stat-icon bg-light text-primary me-3"><i class="fa-solid fa-screwdriver-wrench fa-xl"></i></div>
            <div><div class="text-muted small fw-semibold">Processed Jobs</div><div class="fs-4 fw-bold"><?= $totalJobCards ?></div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3 d-flex align-items-center bg-white shadow-sm rounded">
            <div class="stat-icon bg-light text-warning me-3"><i class="fa-solid fa-boxes-packing fa-xl"></i></div>
            <div><div class="text-muted small fw-semibold">Inventory Value</div><div class="fs-4 fw-bold">$ <?= number_format($inventoryValue, 0) ?></div></div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h5 class="card-title fw-bold text-primary-custom mb-4"><?= $is_superadmin ? 'Global Revenue Growth' : 'Garage Revenue Trend' ?></h5>
            <canvas id="revenueChart" height="120"></canvas>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h5 class="card-title fw-bold text-primary-custom mb-4">Service Status Dist.</h5>
            <canvas id="jobStatusChart" height="200"></canvas>
        </div></div>
    </div>
</div>

<!-- Top Metrics -->
<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <h5 class="card-title fw-bold text-primary-custom mb-4"><?= $performanceTitle ?></h5>
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th><?= $is_superadmin ? 'Garage' : 'Mechanic' ?></th><th class="text-center">Jobs</th><th class="text-end"><?= $is_superadmin ? 'Revenue' : 'Earned' ?></th></tr></thead>
                <tbody>
                    <?php if (empty($performanceData)): ?>
                    <tr><td colspan="3" class="text-center text-muted">No activity data available yet.</td></tr>
                    <?php else: foreach ($performanceData as $pd): ?>
                    <tr>
                        <td><strong><?= e($pd['label']) ?></strong></td>
                        <td class="text-center"><span class="badge bg-light text-dark border"><?= $pd['count'] ?></span></td>
                        <td class="text-end fw-bold text-success">$ <?= number_format($pd['metric'], 2) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <h5 class="card-title fw-bold text-primary-custom mb-3">Operational Health</h5>
            <div class="row text-center mt-4">
                <div class="col-4 border-end"><div class="fs-4 fw-bold text-primary"><?= $totalVehicles ?></div><small class="text-muted fw-bold">Vehicles</small></div>
                <div class="col-4 border-end"><div class="fs-4 fw-bold text-warning"><?= $pendingJobs ?></div><small class="text-muted fw-bold">Pending Jobs</small></div>
                <div class="col-4"><div class="fs-4 fw-bold text-danger"><?= $lowStockCount ?></div><small class="text-muted fw-bold">Low Stock</small></div>
            </div>
            <hr class="my-4">
            <div class="p-3 bg-light rounded border-start border-primary border-4">
                <div class="small text-muted fw-bold mb-1">DATA INSIGHT</div>
                <p class="small mb-0">
                    <?php if($is_superadmin): ?>
                        The platform is currently serving <strong><?= $totalGarages ?></strong> active garages with a combined inventory value of <strong>$<?= number_format($inventoryValue, 2) ?></strong>.
                    <?php else: ?>
                        Your garage has processed <strong><?= $totalJobCards ?></strong> jobs to date. <strong><?= $completedJobs ?></strong> have been successfully delivered.
                    <?php endif; ?>
                </p>
            </div>
        </div></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revCtx = document.getElementById('revenueChart');
    if (revCtx) {
        new Chart(revCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($monthLabels) ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?= json_encode($monthValues) ?>,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 3
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }

    // Job Status Chart
    const jsCtx = document.getElementById('jobStatusChart');
    if (jsCtx) {
        new Chart(jsCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($jsLabels) ?>,
                datasets: [{
                    data: <?= json_encode($jsValues) ?>,
                    backgroundColor: ['#6B7280','#F59E0B','#10B981','#3B82F6'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
        });
    }
});
</script>
