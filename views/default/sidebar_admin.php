<?php
/**
 * Sidebar Admin Template - Standarisasi v2.0
 * Width: 260px (konsisten dengan semua halaman)
 */

// Mendapatkan current page untuk active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Get user info
$user_name = isset($user['nama_lengkap']) ? $user['nama_lengkap'] : 'Administrator';

// Determine role label based on jenis_pengguna
$jenis_pengguna = $user['jenis_pengguna'] ?? 'kepala_departemen';
$role_labels = [
    'inventory_manager' => 'Inventory Manager',
    'kepala_departemen' => 'Kepala Departemen'
];
$user_role = $role_labels[$jenis_pengguna] ?? ucfirst($user['role'] ?? 'User');

// Check roles for different features
$is_inventory_manager = ($user['role'] === 'inventory_manager' || $jenis_pengguna === 'inventory_manager');
$is_kepala_departemen = ($jenis_pengguna === 'kepala_departemen');

// Panel title based on role
if ($is_inventory_manager) {
    $panel_title = 'INVENTORY MANAGER';
} else {
    $panel_title = 'KEPALA DEPARTEMEN';
}

// Count pending items for badges
$pending_surat = 0;
$pending_approval = 0;
$stok_menipis = 0;

if (isset($connection)) {
    // Pending surat peminjaman
    $result_surat = mysqli_query($connection, "SELECT COUNT(*) as total FROM surat_peminjaman WHERE status = 'pending'");
    if ($result_surat) {
        $pending_surat = mysqli_fetch_assoc($result_surat)['total'] ?? 0;
    }
    
    // Pending peminjaman approval
    $result_pending = mysqli_query($connection, "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'pending'");
    if ($result_pending) {
        $pending_approval = mysqli_fetch_assoc($result_pending)['total'] ?? 0;
    }
    
    // Stok menipis
    $result_stok = mysqli_query($connection, "SELECT COUNT(*) as total FROM barang WHERE jumlah_tersedia <= 2 AND status = 'active'");
    if ($result_stok) {
        $stok_menipis = mysqli_fetch_assoc($result_stok)['total'] ?? 0;
    }
}
?>

<style>
    /* ============================================
       SIDEBAR ADMIN - STANDARISASI v2.0
       Width: 260px (konsisten semua halaman)
       ============================================ */
    
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 260px;
        height: 100vh;
        background: linear-gradient(180deg, #1e40af 0%, #2563eb 100%);
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

    .menu-section-title {
        color: rgba(255,255,255,0.5);
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 10px 15px 5px;
        margin: 0;
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

    .menu-badge {
        margin-left: auto;
        background: #ef4444;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 700;
        min-width: 20px;
        text-align: center;
        animation: pulse-badge 2s infinite;
    }

    @keyframes pulse-badge {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
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
        <div class="sidebar-logo">📦</div>
        <h3 class="sidebar-title"><?= $panel_title ?></h3>
        <p class="sidebar-subtitle">Sistem Inventaris Kantor</p>
    </div>

    <!-- Sidebar Menu -->
    <div class="sidebar-menu">
        <div class="menu-section">
            <div class="menu-item">
                <a href="/inventaris/admin/dashboard.php" class="menu-link <?= ($current_page == 'dashboard') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        <div class="menu-divider"></div>

        <!-- SURAT PEMINJAMAN -->
        <?php if ($is_inventory_manager || $is_kepala_departemen): ?>
        <div class="menu-section">
            <div class="menu-item">
                <a href="/inventaris/admin/surat_peminjaman/index.php" class="menu-link <?= ($current_dir == 'surat_peminjaman') ? 'active' : '' ?>">
                    <i class="fas fa-file-contract"></i>
                    <span>Surat Peminjaman</span>
                    <?php if ($pending_surat > 0): ?>
                        <span class="menu-badge"><?= $pending_surat ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="menu-section">
            <div class="menu-item">
                <a href="/inventaris/admin/pengembalian/index.php" class="menu-link <?= ($current_dir == 'pengembalian' || $current_dir == 'peminjaman') ? 'active' : '' ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Manajemen Pinjaman</span>
                    <?php if ($pending_approval > 0): ?>
                        <span class="menu-badge"><?= $pending_approval ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="menu-item">
                <a href="/inventaris/admin/pembayaran_denda/index.php" class="menu-link <?= ($current_dir == 'pembayaran_denda') ? 'active' : '' ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Pembayaran Denda</span>
                </a>
            </div>
        </div>

        <div class="menu-divider"></div>

        <!-- KELOLA BARANG - For Inventory Manager -->
        <?php if ($is_inventory_manager): ?>
        <div class="menu-section">
            <div class="menu-item">
                <a href="/inventaris/admin/barang/index.php" class="menu-link <?= ($current_dir == 'barang') ? 'active' : '' ?>">
                    <i class="fas fa-boxes"></i>
                    <span>Kelola Barang</span>
                    <?php if ($stok_menipis > 0): ?>
                        <span class="menu-badge" style="background: #f59e0b;"><?= $stok_menipis ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- USER MANAGEMENT & LAPORAN - Only for Inventory Manager -->
        <?php if ($is_inventory_manager): ?>
        <div class="menu-section">
            <div class="menu-item">
                <a href="/inventaris/admin/users/index.php" class="menu-link <?= ($current_dir == 'users') ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Kelola User</span>
                </a>
            </div>

            <div class="menu-item">
                <a href="/inventaris/admin/laporan/index.php" class="menu-link <?= ($current_dir == 'laporan') ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($user_name, 0, 1)) ?>
            </div>
            <div class="user-details">
                <p class="user-name"><?= htmlspecialchars($user_name) ?></p>
                <p class="user-role"><?= $user_role ?></p>
            </div>
        </div>
        <a href="/inventaris/logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>
