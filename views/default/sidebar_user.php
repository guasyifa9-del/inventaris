<?php
/**
 * Sidebar User Template - Standarisasi v2.0
 * Width: 260px (konsisten dengan semua halaman)
 */

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Get current user if not set
if (!isset($user) || empty($user)) {
    if (function_exists('getCurrentUser')) {
        $user = getCurrentUser();
    }
}

// Fallback
if (!isset($user['nama_lengkap'])) {
    $user['nama_lengkap'] = 'User';
}
if (!isset($user['departemen'])) {
    $user['departemen'] = 'Staff';
}
if (!isset($user['jenis_pengguna'])) {
    $user['jenis_pengguna'] = 'kepala_departemen';
}

// Determine role label
$role_labels = [
    'kepala_departemen' => 'Kepala Departemen'
];
$user_role = $role_labels[$user['jenis_pengguna']] ?? 'Kepala Departemen';
$is_kepala_departemen = true; // Only kepala_departemen can access user panel now

// Panel title
$panel_title = 'KEPALA DEPARTEMEN';
?>

<style>
    /* ============================================
       SIDEBAR USER - STANDARISASI v2.0
       Width: 260px (konsisten semua halaman)
       ============================================ */
    
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 260px;
        height: 100vh;
        background: linear-gradient(180deg, #1e40af 0%, #3b82f6 100%);
        padding: 0;
        box-shadow: 4px 0 20px rgba(0,0,0,0.15);
        z-index: 1000;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    .sidebar::-webkit-scrollbar {
        width: 5px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.05);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.2);
        border-radius: 3px;
    }

    /* Sidebar Header */
    .sidebar-header {
        padding: 25px 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        background: rgba(0,0,0,0.1);
    }

    .sidebar-logo {
        width: 60px;
        height: 60px;
        background: rgba(255,255,255,0.15);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        backdrop-filter: blur(10px);
        font-size: 28px;
        color: white;
    }

    .sidebar-title {
        color: white;
        font-size: 1rem;
        font-weight: 700;
        margin: 0 0 3px 0;
        letter-spacing: 0.5px;
    }

    .sidebar-subtitle {
        color: rgba(255,255,255,0.7);
        font-size: 0.8rem;
        margin: 0;
        font-weight: 400;
    }

    /* Sidebar Menu */
    .sidebar-menu {
        padding: 15px 0;
        flex: 1;
    }

    .menu-section {
        padding: 0 12px;
        margin-bottom: 5px;
    }

    .menu-item {
        margin: 3px 0;
    }

    .menu-link {
        display: flex;
        align-items: center;
        padding: 12px 18px;
        color: rgba(255,255,255,0.85);
        text-decoration: none;
        transition: all 0.25s ease;
        position: relative;
        font-weight: 500;
        font-size: 0.9rem;
        border-radius: 10px;
        margin: 0 8px;
    }

    .menu-link:hover {
        background: rgba(255,255,255,0.12);
        color: white;
        transform: translateX(3px);
    }

    .menu-link.active {
        background: rgba(255,255,255,0.2);
        color: white;
        font-weight: 600;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .menu-link i {
        width: 22px;
        margin-right: 12px;
        font-size: 1rem;
        text-align: center;
    }

    .menu-divider {
        height: 1px;
        background: rgba(255,255,255,0.1);
        margin: 15px 20px;
    }

    /* Sidebar Footer */
    .sidebar-footer {
        margin-top: auto;
        padding: 20px 15px;
        border-top: 1px solid rgba(255,255,255,0.1);
        background: rgba(0,0,0,0.1);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
        padding: 0 5px;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .user-details {
        flex: 1;
        min-width: 0;
    }

    .user-name {
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        margin: 0 0 2px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-role {
        color: rgba(255,255,255,0.7);
        font-size: 0.75rem;
        margin: 0;
    }

    .user-dept {
        color: rgba(255,255,255,0.5);
        font-size: 0.7rem;
        margin: 2px 0 0 0;
    }

    .btn-logout {
        width: 100%;
        padding: 10px 15px;
        background: rgba(239,68,68,0.9);
        border: none;
        color: white;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        text-decoration: none;
    }

    .btn-logout:hover {
        background: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(239,68,68,0.4);
        color: white;
    }

    /* Content area adjustment */
    .content, .main-content {
        margin-left: 260px;
        min-height: 100vh;
        padding: 30px;
        position: relative;
        z-index: 1;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .content, .main-content {
            margin-left: 0;
            padding: 20px;
        }
    }
</style>

<div class="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-boxes"></i>
        </div>
        <h3 class="sidebar-title"><?= $panel_title ?></h3>
        <p class="sidebar-subtitle">Sistem Inventaris Kantor</p>
    </div>

    <!-- Sidebar Menu -->
    <div class="sidebar-menu">
        <div class="menu-section">
            <div class="menu-item">
                <a href="/inventaris/user/dashboard.php" class="menu-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        <div class="menu-divider"></div>

        <div class="menu-section">
            <div class="menu-item">
                <a href="/inventaris/user/barang/index.php" class="menu-link <?= ($current_dir == 'barang') ? 'active' : '' ?>">
                    <i class="fas fa-boxes"></i>
                    <span>Lihat Barang</span>
                </a>
            </div>

            <!-- SURAT PEMINJAMAN - Available for all users -->
            <div class="menu-item">
                <a href="/inventaris/user/surat_peminjaman/index.php" class="menu-link <?= ($current_dir == 'surat_peminjaman') ? 'active' : '' ?>">
                    <i class="fas fa-file-contract"></i>
                    <span>Surat Peminjaman</span>
                </a>
            </div>

            <!-- TAGIHAN & DENDA -->
            <div class="menu-item">
                <a href="/inventaris/user/tagihan/index.php" class="menu-link <?= ($current_dir == 'tagihan') ? 'active' : '' ?>">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Tagihan & Denda</span>
                </a>
            </div>

            <!-- KELOLA KARYAWAN - For department heads -->
            <div class="menu-item">
                <a href="/inventaris/user/karyawan/index.php" class="menu-link <?= ($current_dir == 'karyawan') ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Kelola Karyawan</span>
                </a>
            </div>

            <div class="menu-item">
                <a href="/inventaris/user/pengembalian/index.php" class="menu-link <?= ($current_dir == 'pengembalian') ? 'active' : '' ?>">
                    <i class="fas fa-undo"></i>
                    <span>Pengembalian</span>
                </a>
            </div>
        </div>

        <div class="menu-divider"></div>

        <div class="menu-section">
            <div class="menu-item">
                <a href="/inventaris/user/profile/index.php" class="menu-link <?= ($current_dir == 'profile') ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>Profil</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <p class="user-name"><?= htmlspecialchars($user['nama_lengkap']) ?></p>
                <p class="user-role"><?= $user_role ?></p>
                <p class="user-dept"><?= htmlspecialchars($user['departemen']) ?></p>
            </div>
        </div>
        <a href="/inventaris/logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>
