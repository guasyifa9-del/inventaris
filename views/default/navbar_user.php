<?php
/**
 * Navbar for user pages - Modern Version
 * Fixed: Proper user variable handling
 */

// Get current user if not already set
if (!isset($user) || empty($user)) {
    $user = getCurrentUser();
}

// Fallback if still not set
if (!isset($user['nama_lengkap'])) {
    $user['nama_lengkap'] = 'User';
}
?>
<nav class="navbar navbar-expand-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3); border-radius: 15px; margin-bottom: 30px; padding: 0 20px;">
    <div class="container-fluid">
        <!-- Logo Section -->
        <div class="navbar-brand d-flex align-items-center gap-3">
            <div style="width: 45px; height: 45px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
                <i class="fas fa-box fa-lg" style="color: white;"></i>
            </div>
            <div>
                <div style="font-weight: 800; font-size: 1.3rem; color: white; letter-spacing: -0.5px;">Inventaris</div>
                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.7); font-weight: 500; letter-spacing: 1px; text-transform: uppercase;">KANTOR</div>
            </div>
        </div>
        
        <!-- Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUser" 
                style="border: none; padding: 8px; border-radius: 8px; background: rgba(255,255,255,0.15); transition: all 0.3s;">
            <span class="navbar-toggler-icon" style="filter: brightness(0) invert(1);"></span>
        </button>
        
        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarUser">
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- Date & Time -->
                <li class="nav-item me-4 d-none d-lg-block">
                    <div class="d-flex align-items-center gap-3">
                        <div class="nav-item-text" style="color: rgba(255,255,255,0.9); font-weight: 600;">
                            <i class="far fa-calendar me-2"></i>
                            <span id="current-date"><?= date('l, d F Y') ?></span>
                        </div>
                        <div class="nav-item-text" style="color: rgba(255,255,255,0.9); font-weight: 600;">
                            <i class="far fa-clock me-2"></i>
                            <span id="current-time"><?= date('H:i:s') ?></span>
                        </div>
                    </div>
                </li>
                
                <!-- Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle p-0" href="#" id="navbarDropdown" data-bs-toggle="dropdown" 
                       style="display: flex; align-items: center; gap: 10px; padding: 10px 15px; border-radius: 10px; transition: all 0.3s; background: rgba(255,255,255,0.15);">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; color: white; backdrop-filter: blur(10px);">
                            <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                        </div>
                        <div class="d-none d-md-block" style="color: white; font-weight: 600; font-size: 0.95rem;">
                            <?= htmlspecialchars($user['nama_lengkap']) ?>
                        </div>
                        <i class="fas fa-chevron-down" style="color: rgba(255,255,255,0.8); font-size: 0.8rem;"></i>
                    </a>
                    
                    <ul class="dropdown-menu dropdown-menu-end" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.15); margin-top: 8px; min-width: 220px; padding: 10px 0;">
                        <!-- Profile Info Header -->
                        <div class="px-4 py-3" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 15px 15px 0 0; margin: -10px -10px 10px -10px;">
                            <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 4px;"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                            <div style="font-size: 0.8rem; opacity: 0.9;"><?= htmlspecialchars($user['email']) ?></div>
                            <div style="font-size: 0.75rem; opacity: 0.8; margin-top: 6px; background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 20px; display: inline-block;">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($user['departemen']) ?>
                            </div>
                        </div>
                        
                        <!-- Menu Items -->
                        <li>
                            <a class="dropdown-item" href="/inventaris/user/profile/index.php" 
                               style="color: #1a1a2e; font-weight: 600; padding: 12px 20px; transition: all 0.2s; display: flex; align-items: center; gap: 10px;">
                                <div style="width: 35px; height: 35px; border-radius: 10px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span>Profil Saya</span>
                            </a>
                        </li>
                        
                        <li>
                            <a class="dropdown-item" href="/inventaris/user/peminjaman/index.php" 
                               style="color: #1a1a2e; font-weight: 600; padding: 12px 20px; transition: all 0.2s; display: flex; align-items: center; gap: 10px;">
                                <div style="width: 35px; height: 35px; border-radius: 10px; background: linear-gradient(135deg, #10b981, #14b8a6); display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-history"></i>
                                </div>
                                <span>Riwayat Peminjaman</span>
                            </a>
                        </li>
                        
                        <li>
                            <a class="dropdown-item" href="/inventaris/user/barang/index.php" 
                               style="color: #1a1a2e; font-weight: 600; padding: 12px 20px; transition: all 0.2s; display: flex; align-items: center; gap: 10px;">
                                <div style="width: 35px; height: 35px; border-radius: 10px; background: linear-gradient(135deg, #f59e0b, #f97316); display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-box"></i>
                                </div>
                                <span>Katalog Barang</span>
                            </a>
                        </li>
                        
                        <li><hr class="dropdown-divider" style="margin: 8px 0; opacity: 0.1;"></li>
                        
                        <li>
                            <a class="dropdown-item text-danger" href="/inventaris/logout.php" 
                               style="font-weight: 700; padding: 12px 20px; transition: all 0.2s; display: flex; align-items: center; gap: 10px;">
                                <div style="width: 35px; height: 35px; border-radius: 10px; background: linear-gradient(135deg, #ef4444, #dc2626); display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
// Update time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    document.getElementById('current-time').textContent = timeString;
}
setInterval(updateTime, 1000);
</script>