<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';

requireUser();
$user_id = $_SESSION['user_id'];
$user = getCurrentUser();

// Get barang if pre-selected
$selected_barang_id = isset($_GET['barang_id']) ? (int)$_GET['barang_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $barang_ids = $_POST['barang_id'] ?? [];
    $jumlah_items = $_POST['jumlah'] ?? [];
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $jam_pinjam = $_POST['jam_pinjam'];
    $tanggal_kembali = $_POST['tanggal_kembali_rencana'];
    $jam_kembali = $_POST['jam_kembali_rencana'];
    $keperluan = trim($_POST['keperluan']);
    
    // Validate
    if (empty($barang_ids)) {
        $error = 'Pilih minimal 1 barang!';
    } else {
        // Get barang details and calculate
        $items = [];
        $total_biaya_sewa = 0;
        $total_deposit = 0;
        
        $datetime1 = new DateTime($tanggal_pinjam);
        $datetime2 = new DateTime($tanggal_kembali);
        $durasi = max(1, $datetime1->diff($datetime2)->days);
        
        foreach ($barang_ids as $index => $barang_id) {
            $jumlah = (int)$jumlah_items[$index];
            $barang = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM barang WHERE barang_id = $barang_id"));
            
            if ($barang && $jumlah > 0) {
                // Check availability
                if ($barang['jumlah_tersedia'] < $jumlah) {
                    $error = "Stok {$barang['nama_barang']} tidak cukup!";
                    break;
                }
                
                $subtotal_sewa = $barang['harga_sewa_per_hari'] * $durasi * $jumlah;
                $subtotal_deposit = $barang['deposit'] * $jumlah;
                
                $total_biaya_sewa += $subtotal_sewa;
                $total_deposit += $subtotal_deposit;
                
                $items[] = [
                    'barang_id' => $barang_id,
                    'jumlah' => $jumlah,
                    'harga_sewa' => $barang['harga_sewa_per_hari'],
                    'deposit' => $barang['deposit'],
                    'subtotal_sewa' => $subtotal_sewa,
                    'subtotal_deposit' => $subtotal_deposit
                ];
            }
        }
        
        if (!isset($error) && !empty($items)) {
            $total_bayar = $total_biaya_sewa + $total_deposit;
            $status_pembayaran = ($total_bayar == 0) ? 'gratis' : 'belum_bayar';
            $kode = generateKodePeminjaman();
            
            // Insert peminjaman
            $stmt = mysqli_prepare($connection, 
                "INSERT INTO peminjaman (kode_peminjaman, user_id, tanggal_pinjam, jam_pinjam, tanggal_kembali_rencana, jam_kembali_rencana, keperluan, durasi_hari, biaya_sewa, biaya_deposit, total_bayar, status_pembayaran, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            mysqli_stmt_bind_param($stmt, "sisssssdddss", 
                $kode, $user_id, $tanggal_pinjam, $jam_pinjam, $tanggal_kembali, $jam_kembali, 
                $keperluan, $durasi, $total_biaya_sewa, $total_deposit, $total_bayar, $status_pembayaran);
            
            if (mysqli_stmt_execute($stmt)) {
                $peminjaman_id = mysqli_insert_id($connection);
                
                // Insert details
                foreach ($items as $item) {
                    mysqli_query($connection, 
                        "INSERT INTO peminjaman_detail (peminjaman_id, barang_id, jumlah, harga_sewa_per_hari, deposit_per_item, durasi_hari, subtotal_sewa, subtotal_deposit, kondisi_pinjam) 
                         VALUES ($peminjaman_id, {$item['barang_id']}, {$item['jumlah']}, {$item['harga_sewa']}, {$item['deposit']}, $durasi, {$item['subtotal_sewa']}, {$item['subtotal_deposit']}, 'Baik')");
                    
                    // Update stok
                    updateStokBarang($connection, $item['barang_id'], $item['jumlah'], 'kurangi');
                }
                
                logActivity('CREATE_PEMINJAMAN', "Membuat peminjaman: $kode");
                setFlashMessage('success', 'Peminjaman berhasil diajukan! Menunggu approval admin.');
                header('Location: index.php');
                exit;
            }
        }
    }
}

// Get available barang - show all items including those being used and inactive
$barang_list = mysqli_query($connection, 
    "SELECT b.*, k.nama_kategori,
            (SELECT SUM(pd.jumlah) FROM peminjaman_detail pd 
             JOIN peminjaman p ON pd.peminjaman_id = p.peminjaman_id 
             WHERE pd.barang_id = b.barang_id AND p.status IN ('approved', 'ongoing', 'late')) as sedang_dipinjam,
            (SELECT MIN(CONCAT(p.tanggal_kembali_rencana, ' ', p.jam_kembali_rencana)) 
             FROM peminjaman_detail pd 
             JOIN peminjaman p ON pd.peminjaman_id = p.peminjaman_id 
             WHERE pd.barang_id = b.barang_id AND p.status IN ('approved', 'ongoing', 'late')) as jadwal_kembali
     FROM barang b 
     LEFT JOIN kategori k ON b.kategori_id = k.kategori_id 
     WHERE b.kondisi = 'Baik'
     ORDER BY b.status ASC, b.jumlah_tersedia DESC, b.nama_barang");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Barang - User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            background-image:
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.08) 0px, transparent 50%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(180deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-logo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.8rem;
            backdrop-filter: blur(10px);
        }
        
        .sidebar-header h5 {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: white;
        }
        
        .sidebar-header small {
            color: rgba(255,255,255,0.8);
            display: block;
            font-size: 0.85rem;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 13px 20px;
            margin: 3px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.25);
            color: white;
            font-weight: 600;
        }
        
        .sidebar .nav-link i {
            font-size: 1.1rem;
            width: 22px;
        }
        
        .sidebar hr {
            border-color: rgba(255,255,255,0.15);
            margin: 20px 15px;
        }
        
        .user-profile-box {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: rgba(0,0,0,0.15);
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .user-info h6 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
        }
        
        .user-info small {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.7);
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 30px;
        }
        
        /* Page Header */
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .page-header h2 {
            color: #1f2937;
            font-weight: 700;
            margin: 0;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-header p {
            color: #6b7280;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* Card Styles */
        .card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .card:hover {
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
        }
        
        .card-header {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            padding: 18px 25px;
            border: none;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Barang Item */
        .barang-item {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f9fafb;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .barang-item:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateX(3px);
        }
        
        .barang-item.selected {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.15);
        }
        
        .barang-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #3b82f6;
        }
        
        .barang-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
        }
        
        .barang-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .barang-image i {
            font-size: 1.5rem;
            color: #1e40af;
        }
        
        .barang-info {
            flex: 1;
        }
        
        .barang-info h6 {
            color: #1f2937;
            font-weight: 700;
            margin: 0 0 4px 0;
            font-size: 0.95rem;
        }
        
        .barang-info small {
            color: #6b7280;
            font-size: 0.8rem;
        }
        
        .barang-badge {
            display: flex;
            gap: 6px;
            margin-top: 6px;
            flex-wrap: wrap;
        }
        
        .badge-gratis {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .badge-berbayar {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .badge-deposit {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        
        .barang-quantity {
            text-align: right;
        }
        
        .barang-quantity label {
            font-size: 0.75rem;
            color: #6b7280;
            display: block;
            margin-bottom: 4px;
        }
        
        .barang-quantity input {
            width: 70px;
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }
        
        .barang-quantity input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .barang-quantity small {
            display: block;
            color: #9ca3af;
            font-size: 0.7rem;
            margin-top: 4px;
        }
        
        /* Form Elements */
        .form-label {
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .form-control, .form-select {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-control:disabled {
            background: #f3f4f6;
            color: #9ca3af;
        }
        
        /* Calculate Box */
        .calculate-box {
            background: white;
            border-radius: 14px;
            padding: 25px;
            position: sticky;
            top: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        
        .calculate-box h5 {
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #1f2937;
        }
        
        .selected-items {
            max-height: 250px;
            overflow-y: auto;
            padding-right: 8px;
        }
        
        .selected-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .selected-item:last-child {
            border-bottom: none;
        }
        
        .selected-item-name {
            color: #6b7280;
            font-size: 0.85rem;
        }
        
        .selected-item-price {
            font-weight: 600;
            text-align: right;
            color: #1f2937;
        }
        
        .calc-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .calc-row:last-child {
            border-bottom: 2px solid #3b82f6;
            padding-top: 12px;
            margin-top: 8px;
        }
        
        .calc-label {
            color: #6b7280;
            font-size: 0.85rem;
        }
        
        .calc-value {
            font-weight: 700;
            font-size: 1rem;
            color: #1f2937;
        }
        
        .calc-total {
            font-size: 1.6rem;
            font-weight: 800;
            color: #3b82f6;
            margin: 15px 0;
            text-align: center;
        }
        
        .calc-info {
            background: #eff6ff;
            border-radius: 8px;
            padding: 12px;
            margin: 15px 0;
            font-size: 0.85rem;
            color: #1e40af;
            border-left: 3px solid #3b82f6;
        }
        
        .calc-info i {
            margin-right: 6px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            width: 100%;
            border: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }
        
        .btn-submit:disabled {
            background: #d1d5db;
            color: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Alert */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }
        
        .alert i {
            font-size: 1.1rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px 15px;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
            }
            
            .calculate-box {
                position: static;
                margin-top: 20px;
            }
            
            .barang-item {
                flex-direction: column;
                text-align: center;
            }
            
            .barang-quantity {
                margin-top: 12px;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<?php include __DIR__ . '/../../views/default/sidebar_user.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Navbar -->
    <?php 
    $navbar_path = $_SERVER['DOCUMENT_ROOT'] . '/inventaris/views/default/navbar_user.php';
    if (file_exists($navbar_path)) {
        include $navbar_path;
    }
    ?>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2><i class="fas fa-plus-circle"></i> Form Peminjaman Barang</h2>
                <p class="mb-0">Pilih barang yang ingin dipinjam dan lengkapi detail peminjaman</p>
            </div>
            <a href="/inventaris/user/peminjaman/index.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= $error ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="formPeminjaman">
            <div class="row">
                <div class="col-lg-7">
                    <!-- Pilih Barang -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-boxes"></i> Pilih Barang</h5>
                        </div>
                        <div class="card-body" style="max-height:500px;overflow-y:auto;">
                            <?php mysqli_data_seek($barang_list, 0); ?>
                            <?php while($b = mysqli_fetch_assoc($barang_list)): 
                                $is_inactive = ($b['status'] === 'inactive');
                                $is_out_of_stock = ($b['jumlah_tersedia'] <= 0);
                                $is_available = !$is_inactive && !$is_out_of_stock;
                                $sedang_dipinjam = $b['sedang_dipinjam'] ?? 0;
                            ?>
                            <div class="barang-item <?= $b['barang_id']==$selected_barang_id?'selected':'' ?> <?= !$is_available ? 'opacity-50' : '' ?>" 
                                 <?php if ($is_available): ?>onclick="toggleBarang(<?= $b['barang_id'] ?>, this)"<?php endif; ?>
                                 style="<?= !$is_available ? 'cursor: not-allowed; border-color: #dc2626;' : '' ?>">
                                
                                <?php if ($is_available): ?>
                                <input type="checkbox" class="barang-checkbox" 
                                       id="barang_<?= $b['barang_id'] ?>" 
                                       name="barang_id[]" 
                                       value="<?= $b['barang_id'] ?>"
                                       data-harga="<?= $b['harga_sewa_per_hari'] ?>"
                                       data-deposit="<?= $b['deposit'] ?>"
                                       data-nama="<?= htmlspecialchars($b['nama_barang']) ?>"
                                       <?= $b['barang_id']==$selected_barang_id?'checked':'' ?>>
                                <?php else: ?>
                                <i class="fas fa-ban text-danger" style="font-size: 1.2rem;"></i>
                                <?php endif; ?>
                                
                                <div class="barang-image">
                                    <?php if($b['gambar'] && file_exists("../../uploads/barang/".$b['gambar'])): ?>
                                    <img src="/inventaris/uploads/barang/<?= $b['gambar'] ?>" alt="<?= htmlspecialchars($b['nama_barang']) ?>">
                                    <?php else: ?>
                                    <i class="fas fa-box"></i>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="barang-info">
                                    <h6><?= htmlspecialchars($b['nama_barang']) ?></h6>
                                    <small><?= htmlspecialchars($b['nama_kategori']) ?></small>
                                    <div class="barang-badge">
                                        <?php if($b['is_berbayar']): ?>
                                        <span class="badge-berbayar">
                                            <i class="fas fa-dollar-sign me-1"></i>BERBAYAR - <?= formatRupiah($b['harga_sewa_per_hari']) ?>/hari
                                        </span>
                                        <?php else: ?>
                                        <span class="badge-gratis">
                                            <i class="fas fa-gift me-1"></i>GRATIS
                                        </span>
                                        <?php endif; ?>
                                        <?php if($b['deposit']>0): ?>
                                        <span class="badge-deposit">
                                            <i class="fas fa-shield-alt me-1"></i>Deposit: <?= formatRupiah($b['deposit']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Stock & Usage Status -->
                                    <?php if ($is_inactive): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-dark">
                                            <i class="fas fa-lock me-1"></i>TIDAK TERSEDIA
                                        </span>
                                    </div>
                                    <?php elseif ($is_out_of_stock): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>STOK HABIS
                                        </span>
                                        <?php if ($b['jadwal_kembali']): ?>
                                        <small class="d-block text-muted mt-1">
                                            <i class="fas fa-clock me-1"></i>Kembali: <?= date('d/m/Y H:i', strtotime($b['jadwal_kembali'])) ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php elseif ($sedang_dipinjam > 0): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-info" style="font-size: 0.7rem;">
                                            <i class="fas fa-user-clock me-1"></i><?= $sedang_dipinjam ?> sedang dipinjam
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="barang-quantity">
                                    <?php if ($is_available): ?>
                                    <label>Jumlah:</label>
                                    <input type="number" class="form-control form-control-sm jumlah-input" 
                                           name="jumlah[]" 
                                           min="1" max="<?= $b['jumlah_tersedia'] ?>" 
                                           value="1" 
                                           onchange="calculate()">
                                    <small>Maks: <?= $b['jumlah_tersedia'] ?></small>
                                    <?php else: ?>
                                    <small class="text-danger fw-bold">Tidak Tersedia</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    
                    <!-- Tanggal & Keperluan -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-calendar"></i> Detail Peminjaman</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Pinjam <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="tanggal_pinjam" id="tanggal_pinjam" required onchange="calculate()" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jam Pinjam <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" name="jam_pinjam" required value="08:00">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Kembali <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="tanggal_kembali_rencana" id="tanggal_kembali" required onchange="calculate()" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jam Kembali <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" name="jam_kembali_rencana" required value="17:00">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Keperluan <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="keperluan" rows="4" required placeholder="Jelaskan untuk apa barang ini dipinjam..."></textarea>
                                <small class="text-muted">Contoh: Rapat kantor, presentasi client, event perusahaan, dll.</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-5">
                    <!-- Calculate Box -->
                    <div class="calculate-box">
                        <h5><i class="fas fa-calculator"></i> Ringkasan Biaya</h5>
                        
                        <div class="selected-items" id="selected-items">
                            <div class="text-center py-3">
                                <i class="fas fa-shopping-cart fa-2x opacity-50 mb-2"></i>
                                <p class="opacity-75 mb-0">Belum ada barang dipilih</p>
                            </div>
                        </div>
                        
                        <hr class="border-gray-200">
                        
                        <div class="calc-row">
                            <span class="calc-label">Durasi Peminjaman:</span>
                            <span class="calc-value" id="durasi-display">0 hari</span>
                        </div>
                        
                        <div class="calc-row">
                            <span class="calc-label">Biaya Sewa:</span>
                            <span class="calc-value" id="sewa-display">Rp 0</span>
                        </div>
                        
                        <div class="calc-row">
                            <span class="calc-label">Deposit:</span>
                            <span class="calc-value" id="deposit-display">Rp 0</span>
                        </div>
                        
                        <hr class="border-gray-300">
                        
                        <div class="text-center">
                            <div class="mb-2" style="color: #6b7280; font-size: 0.85rem;">TOTAL BAYAR</div>
                            <div class="calc-total" id="total-display">Rp 0</div>
                        </div>
                        
                        <div class="calc-info">
                            <i class="fas fa-info-circle"></i>
                            <span>Deposit akan dikembalikan setelah barang dikembalikan dalam kondisi baik.</span>
                        </div>
                        
                        <button type="submit" class="btn-submit" id="btnSubmit" disabled>
                            <i class="fas fa-paper-plane"></i>
                            Ajukan Peminjaman
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleBarang(id, element) {
    const checkbox = element.querySelector('.barang-checkbox');
    checkbox.checked = !checkbox.checked;
    element.classList.toggle('selected', checkbox.checked);
    calculate();
}

function calculate() {
    const checkboxes = document.querySelectorAll('.barang-checkbox:checked');
    const tanggalPinjam = document.getElementById('tanggal_pinjam').value;
    const tanggalKembali = document.getElementById('tanggal_kembali').value;
    
    let totalSewa = 0;
    let totalDeposit = 0;
    let durasi = 1;
    let itemsHtml = '';
    
    // Calculate durasi
    if (tanggalPinjam && tanggalKembali) {
        const date1 = new Date(tanggalPinjam);
        const date2 = new Date(tanggalKembali);
        const diffTime = Math.abs(date2 - date1);
        durasi = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
    }
    
    // Calculate per barang
    checkboxes.forEach((cb, index) => {
        const jumlahInput = cb.closest('.barang-item').querySelector('.jumlah-input');
        const jumlah = parseInt(jumlahInput.value) || 1;
        const harga = parseFloat(cb.dataset.harga);
        const deposit = parseFloat(cb.dataset.deposit);
        const nama = cb.dataset.nama;
        
        const subtotalSewa = harga * durasi * jumlah;
        const subtotalDeposit = deposit * jumlah;
        
        totalSewa += subtotalSewa;
        totalDeposit += subtotalDeposit;
        
        itemsHtml += `<div class="selected-item">
            <div class="selected-item-name">${nama} <small>(x${jumlah})</small></div>
            <div class="selected-item-price">
                ${subtotalSewa > 0 ? formatRupiah(subtotalSewa) : '<span class="badge-gratis" style="font-size: 0.7rem; padding: 2px 6px;">GRATIS</span>'}
                ${subtotalDeposit > 0 ? '<br><small style="opacity: 0.7;">+Deposit: '+formatRupiah(subtotalDeposit)+'</small>' : ''}
            </div>
        </div>`;
    });
    
    const total = totalSewa + totalDeposit;
    
    // Update display
    document.getElementById('durasi-display').textContent = durasi + ' hari';
    document.getElementById('sewa-display').textContent = formatRupiah(totalSewa);
    document.getElementById('deposit-display').textContent = formatRupiah(totalDeposit);
    document.getElementById('total-display').textContent = formatRupiah(total);
    document.getElementById('selected-items').innerHTML = itemsHtml || '<div class="text-center py-3"><i class="fas fa-shopping-cart fa-2x opacity-50 mb-2"></i><p class="opacity-75 mb-0">Belum ada barang dipilih</p></div>';
    document.getElementById('btnSubmit').disabled = checkboxes.length === 0;
}

function formatRupiah(angka) {
    return 'Rp ' + angka.toLocaleString('id-ID');
}

// Initial calculate
document.addEventListener('DOMContentLoaded', calculate);

// Add listeners to jumlah inputs
document.querySelectorAll('.jumlah-input').forEach(input => {
    input.addEventListener('change', calculate);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>