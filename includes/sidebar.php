<?php
// includes/sidebar.php
?>
<nav id="sidebar" class="bg-primary-custom text-white vh-100 position-sticky top-0 d-flex flex-column" style="min-width: 250px;">
    <div class="p-4 pt-4 d-flex flex-column flex-grow-1 h-100 overflow-y-auto">
        <a href="<?= BASE_URL ?>" class="text-decoration-none">
            <h4 class="mb-4 text-white fw-bold d-flex align-items-center">
                <i class="fa-solid fa-wrench accent-color me-2"></i> AutoServ
            </h4>
        </a>
        <ul class="list-unstyled components mb-auto">
            <?php if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin']): ?>
            <!-- SUPERADMIN NAVIGATION -->
            <li class="<?= $module == 'super_dashboard' ? 'active' : '' ?>">
                <a href="?module=super_dashboard" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-gauge-high me-2 w-20px"></i> Dashboard
                </a>
            </li>
            <li class="<?= $module == 'super_garages' ? 'active' : '' ?>">
                <a href="?module=super_garages" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-building me-2 w-20px"></i> Garages
                </a>
            </li>
            <li class="<?= $module == 'super_users' ? 'active' : '' ?>">
                <a href="?module=super_users" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-users me-2 w-20px"></i> Users
                </a>
            </li>
            <li class="<?= $module == 'settings' ? 'active' : '' ?>">
                <a href="?module=settings" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-gear me-2 w-20px"></i> Settings
                </a>
            </li>
            <?php else: ?>
            <!-- GARAGE NAVIGATION -->
            <li class="<?= $module == 'dashboard' ? 'active' : '' ?>">
                <a href="?module=dashboard" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-gauge-high me-2 w-20px"></i> Dashboard
                </a>
            </li>
            <li class="<?= $module == 'jobcards' ? 'active' : '' ?>">
                <a href="?module=jobcards" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-clipboard-list me-2 w-20px"></i> Job Cards
                </a>
            </li>
            <li class="<?= $module == 'vehicles' ? 'active' : '' ?>">
                <a href="?module=vehicles" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-car me-2 w-20px"></i> Vehicles
                </a>
            </li>
            <li class="<?= $module == 'services' ? 'active' : '' ?>">
                <a href="?module=services" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-screwdriver-wrench me-2 w-20px"></i> Services
                </a>
            </li>
            <li class="<?= $module == 'inventory' ? 'active' : '' ?>">
                <a href="?module=inventory" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-boxes-stacked me-2 w-20px"></i> Inventory
                </a>
            </li>
            <li class="<?= $module == 'quotations' ? 'active' : '' ?>">
                <a href="?module=quotations" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-file-contract me-2 w-20px"></i> Quotations
                </a>
            </li>
            <li class="<?= $module == 'sales' ? 'active' : '' ?>">
                <a href="?module=sales" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-tags me-2 w-20px"></i> Sales
                </a>
            </li>
            <?php if ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'Accountant'): ?>
            <li class="<?= $module == 'invoices' ? 'active' : '' ?>">
                <a href="?module=invoices" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-file-invoice-dollar me-2 w-20px"></i> Invoices
                </a>
            </li>
            <li class="<?= $module == 'reports' ? 'active' : '' ?>">
                <a href="?module=reports" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-chart-pie me-2 w-20px"></i> Reports
                </a>
            </li>
            <?php endif; ?>
            <?php if ($_SESSION['user_role'] === 'Admin'): ?>
            <li class="<?= $module == 'users' ? 'active' : '' ?>">
                <a href="?module=users" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-users-gear me-2 w-20px"></i> Users
                </a>
            </li>
            <li class="<?= $module == 'settings' ? 'active' : '' ?>">
                <a href="?module=settings" class="text-white text-decoration-none d-block py-2 px-3 rounded mb-1">
                    <i class="fa-solid fa-gear me-2 w-20px"></i> Settings
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>
        </ul>
        <div class="mt-4 pt-3">
            <div class="small fw-light text-white-50">Welcome, <?= e($_SESSION['user_name']) ?></div>
            <?php if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin']): ?>
                <div class="small fw-bold text-warning mt-1">SUPERADMIN</div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Page Content  -->
<div id="content" class="w-100 bg-light d-flex flex-column min-vh-100">
    
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
      <div class="container-fluid">
        <button type="button" id="sidebarCollapse" class="btn btn-primary-custom d-lg-none">
          <i class="fa fa-bars"></i>
          <span class="sr-only">Toggle Menu</span>
        </button>
        <div class="d-flex ms-auto align-items-center">
            <span class="badge bg-success me-3 px-3 py-2 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> Online</span>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none text-dark" id="userDropdown" data-bs-toggle="dropdown">
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white me-2" style="width: 32px; height: 32px;">
                        <?= substr(e($_SESSION['user_name']), 0, 1) ?>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item" href="?module=settings&tab=profile"><i class="fa-solid fa-user me-2 text-muted"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
      </div>
    </nav>
