<?php
/**
 * Navbar for admin pages
 */

// Get current user if not set
if (!isset($user) || empty($user)) {
    if (function_exists('getCurrentUser')) {
        $user = getCurrentUser();
    }
}

// Fallback
if (!isset($user['nama_lengkap'])) {
    $user['nama_lengkap'] = 'Admin';
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white mb-3 shadow-sm" style="border-radius: 10px;">
    <div class="container-fluid">
        <a class="navbar-brand" href="/inventaris/admin/dashboard.php">
            <i class="fas fa-chart-line text-primary me-2"></i>
            <strong>Inventaris Admin</strong>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fa-lg text-primary"></i>
                        <span class="ms-2"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="/inventaris/admin/settings/index.php">
                                <i class="fas fa-cog me-2"></i>Pengaturan
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/inventaris/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>