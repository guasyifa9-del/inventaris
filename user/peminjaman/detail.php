<?php
/**
 * ============================================================================
 * USER PEMINJAMAN DETAIL - WITH DENDA INFO
 * ============================================================================
 * 
 * File: user/peminjaman/detail.php
 * 
 * Features:
 * - Display peminjaman details
 * - Show denda information if late
 * - Countdown timer
 * - Payment information
 * - Ukuran lebih normal dan proporsional
 * 
 * ============================================================================
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';

requireUser();
$current_user = getCurrentUser();

$id = (int)$_GET['id'];

// Get peminjaman data
$query = "
    SELECT p.*, 
           u.nama_lengkap,
           u.departemen,
           u.email
    FROM peminjaman p
    LEFT JOIN users u ON p.user_id = u.user_id
    WHERE p.peminjaman_id = ? AND p.user_id = ?
";

$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "ii", $id, $current_user['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$peminjaman = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$peminjaman) {
    setFlashMessage('error', 'Peminjaman tidak ditemukan!');
    header('Location: index.php');
    exit;
}

// Get barang details
$details = mysqli_query($connection,
    "SELECT pd.*, b.nama_barang, b.kode_barang, b.gambar
     FROM peminjaman_detail pd
     LEFT JOIN barang b ON pd.barang_id = b.barang_id
     WHERE pd.peminjaman_id = $id");

// Calculate info
$status_badge = getStatusBadge($peminjaman['status']);
$sisa_waktu = getSisaWaktuPeminjaman($peminjaman['tanggal_kembali_rencana'], $peminjaman['jam_kembali_rencana']);
$denda_info = getInfoDenda($peminjaman);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peminjaman - Inventaris</title>
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
        
        /* Denda Alert - Ukuran lebih normal */
        .denda-alert {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        
        .denda-alert .row {
            align-items: center;
        }
        
        .denda-alert .col-md-2 {
            text-align: center;
        }
        
        .denda-alert i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .denda-alert h4 {
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        
        .denda-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .denda-info-row:last-child {
            border-bottom: none;
        }
        
        .denda-info-row p {
            margin: 0;
            font-size: 0.85rem;
        }
        
        .denda-info-row h5 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
        }
        
        .denda-info-row h4 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        /* Countdown Box - Ukuran lebih normal */
        .countdown-box {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .countdown-box.late {
            background: linear-gradient(135deg, #dc3545, #c82333);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        
        .countdown-box i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .countdown-box h5 {
            font-size: 0.95rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .countdown-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 5px;
        }
        
        .countdown-box p {
            margin: 0;
            font-size: 0.85rem;
            opacity: 0.9;
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
            padding: 16px 20px;
            border: none;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .card-header.bg-primary {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
        }
        
        .card-header.bg-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .card-header.bg-info {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Barang Card */
        .barang-card {
            transition: transform 0.2s ease;
            height: 100%;
            border: 1px solid #f3f4f6;
        }
        
        .barang-card:hover {
            transform: translateY(-3px);
            border-color: #3b82f6;
        }
        
        /* Info Table */
        .info-table {
            margin-bottom: 0;
        }
        
        .info-table td {
            padding: 8px 0;
            font-size: 0.9rem;
        }
        
        .info-table td i {
            width: 20px;
            text-align: center;
        }
        
        /* Action Buttons */
        .action-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }
        
        .action-card i {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .action-card h6 {
            font-size: 1rem;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .action-card p {
            font-size: 0.85rem;
            margin-bottom: 15px;
            color: #6b7280;
        }
        
        .btn-lg {
            padding: 10px 20px;
            font-size: 0.95rem;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(220, 53, 69, 0.3);
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
            
            .countdown-number {
                font-size: 2rem;
            }
            
            .denda-alert i {
                font-size: 2rem;
            }
            
            .card-body {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<?php include __DIR__ . '/../../views/default/sidebar_user.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2><i class="fas fa-file-alt"></i> Detail Peminjaman</h2>
                <p class="mb-0">Informasi lengkap peminjaman Anda</p>
            </div>
            <a href="index.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- Alert Denda jika Late -->
        <?php if ($denda_info['is_late'] && $denda_info['has_denda']): ?>
        <div class="denda-alert">
            <div class="row">
                <div class="col-md-2 text-center">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="col-md-10">
                    <h4>⚠️ PEMINJAMAN TERLAMBAT!</h4>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="denda-info-row">
                                <p><strong>Hari Terlambat:</strong></p>
                                <h5><?= $denda_info['hari_terlambat'] ?> Hari</h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="denda-info-row">
                                <p><strong>Denda/Hari:</strong></p>
                                <h4><?= formatRupiah($denda_info['denda_per_hari']) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="denda-info-row">
                                <p><strong>Total Denda:</strong></p>
                                <h5><?= $denda_info['formatted_denda'] ?></h5>
                            </div>
                        </div>
                    </div>
                    <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
                    <p class="mb-0 small">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Segera kembalikan barang!</strong> Denda akan terus bertambah setiap hari.
                        Denda akan dipotong dari deposit saat pengembalian.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Info Peminjaman -->
                <div class="card">
                    <div class="card-header bg-primary">
                        <i class="fas fa-info-circle me-2"></i>Informasi Peminjaman
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="fas fa-barcode me-2 text-muted"></i>
                                    <strong>Kode:</strong> 
                                    <span class="badge bg-dark" style="font-size: 0.85rem; padding: 6px 12px;"><?= $peminjaman['kode_peminjaman'] ?></span>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-calendar me-2 text-muted"></i>
                                    <strong>Tanggal Pinjam:</strong><br>
                                    <?= date('d F Y, H:i', strtotime($peminjaman['tanggal_pinjam'] . ' ' . $peminjaman['jam_pinjam'])) ?>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-calendar-check me-2 text-muted"></i>
                                    <strong>Target Kembali:</strong><br>
                                    <?= date('d F Y, H:i', strtotime($peminjaman['tanggal_kembali_rencana'] . ' ' . $peminjaman['jam_kembali_rencana'])) ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="fas fa-flag me-2 text-muted"></i>
                                    <strong>Status:</strong><br>
                                    <span class="badge bg-<?= $status_badge['class'] ?>" style="font-size: 0.85rem; padding: 6px 12px;">
                                        <i class="fas <?= $status_badge['icon'] ?>"></i>
                                        <?= $status_badge['text'] ?>
                                    </span>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-clipboard-list me-2 text-muted"></i>
                                    <strong>Keperluan:</strong><br>
                                    <?= htmlspecialchars($peminjaman['keperluan']) ?>
                                </p>
                                <?php if ($peminjaman['catatan_admin']): ?>
                                <p class="mb-2">
                                    <i class="fas fa-sticky-note me-2 text-muted"></i>
                                    <strong>Catatan Admin:</strong><br>
                                    <span class="text-info"><?= htmlspecialchars($peminjaman['catatan_admin']) ?></span>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daftar Barang -->
                <div class="card">
                    <div class="card-header bg-success">
                        <i class="fas fa-boxes me-2"></i>Daftar Barang Dipinjam
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php mysqli_data_seek($details, 0); ?>
                            <?php while ($item = mysqli_fetch_assoc($details)): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card barang-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start">
                                            <?php if ($item['gambar']): ?>
                                            <img src="/inventaris/uploads/barang/<?= $item['gambar'] ?>" 
                                                 class="me-3 rounded" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                            <div class="me-3 bg-secondary rounded d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-box text-white"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1" style="font-size: 0.95rem;"><?= htmlspecialchars($item['nama_barang']) ?></h6>
                                                <p class="small text-muted mb-1">
                                                    Kode: <strong><?= $item['kode_barang'] ?></strong>
                                                </p>
                                                <p class="small mb-1">
                                                    <span class="badge bg-info" style="font-size: 0.75rem; padding: 4px 8px;">Qty: <?= $item['jumlah'] ?></span>
                                                    <?php if ($item['harga_sewa_per_hari'] > 0): ?>
                                                    <span class="badge bg-warning text-dark" style="font-size: 0.75rem; padding: 4px 8px;">
                                                        <?= formatRupiah($item['harga_sewa_per_hari']) ?>/hari
                                                    </span>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if ($item['kondisi_kembali']): ?>
                                                <p class="small mb-0">
                                                    <strong>Kondisi Kembali:</strong> 
                                                    <span class="badge bg-<?= $item['kondisi_kembali'] == 'Baik' ? 'success' : 'danger' ?>" style="font-size: 0.75rem; padding: 4px 8px;">
                                                        <?= $item['kondisi_kembali'] ?>
                                                    </span>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Countdown / Status Box -->
                <?php if (in_array($peminjaman['status'], ['approved', 'ongoing', 'late'])): ?>
                <div class="countdown-box <?= $denda_info['is_late'] ? 'late' : '' ?>">
                    <?php if ($denda_info['is_late']): ?>
                        <i class="fas fa-exclamation-triangle"></i>
                        <h5>TERLAMBAT</h5>
                        <div class="countdown-number"><?= $denda_info['hari_terlambat'] ?></div>
                        <p class="mb-0">Hari</p>
                    <?php else: ?>
                        <i class="fas fa-clock"></i>
                        <h5>SISA WAKTU</h5>
                        <div class="countdown-number"><?= $sisa_waktu['days'] ?></div>
                        <p class="mb-0">Hari <?= $sisa_waktu['hours'] ?> Jam</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Payment Summary -->
                <div class="card">
                    <div class="card-header bg-info">
                        <i class="fas fa-money-bill-wave me-2"></i>Rincian Biaya
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0 info-table">
                            <tr>
                                <td>Biaya Sewa:</td>
                                <td class="text-end"><strong><?= formatRupiah($peminjaman['biaya_sewa']) ?></strong></td>
                            </tr>
                            <tr>
                                <td>Deposit:</td>
                                <td class="text-end"><strong><?= formatRupiah($peminjaman['biaya_deposit']) ?></strong></td>
                            </tr>
                            <?php if ($denda_info['has_denda']): ?>
                            <tr>
                                <td><strong>Denda Terlambat:</strong></td>
                                <td class="text-end"><strong class="text-danger"><?= $denda_info['formatted_denda'] ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            <tr style="border-top: 2px solid #e5e7eb;">
                                <td><strong>TOTAL:</strong></td>
                                <td class="text-end">
                                    <h5 class="mb-0 <?= $denda_info['has_denda'] ? 'text-danger' : 'text-success' ?>" style="font-size: 1.2rem;">
                                        <?= formatRupiah($peminjaman['total_bayar']) ?>
                                    </h5>
                                </td>
                            </tr>
                        </table>

                        <?php if ($denda_info['has_denda']): ?>
                        <div class="alert alert-danger small mb-0 mt-3 p-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Perhatian!</strong> Denda akan dipotong dari deposit Anda saat pengembalian.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <?php if ($peminjaman['status'] == 'pending'): ?>
                <div class="action-card text-center">
                    <i class="fas fa-clock text-warning"></i>
                    <h6>Menunggu Persetujuan Admin</h6>
                    <p class="small text-muted mb-3">
                        Peminjaman Anda sedang dalam proses review
                    </p>
                    <a href="cancel.php?id=<?= $peminjaman['peminjaman_id'] ?>" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Batalkan peminjaman ini?')">
                        <i class="fas fa-times me-1"></i>Batalkan Peminjaman
                    </a>
                </div>
                
                <?php elseif (in_array($peminjaman['status'], ['ongoing', 'late'])): ?>
                <!-- Action Buttons untuk Ongoing/Late -->
                <div class="card">
                    <div class="card-header bg-success">
                        <i class="fas fa-tools me-2"></i>Aksi Cepat
                    </div>
                    <div class="card-body">
                        <!-- Tombol Ajukan Pengembalian -->
                        <div class="d-grid gap-2 mb-2">
                            <a href="ajukan_pengembalian.php?id=<?= $peminjaman['peminjaman_id'] ?>" 
                               class="btn btn-success">
                                <i class="fas fa-undo me-2"></i>
                                Ajukan Pengembalian
                            </a>
                        </div>
                        
                        <!-- Tombol Bayar Denda (hanya jika ada tagihan dan status pending_payment) -->
                        <?php if ($peminjaman['status'] == 'pending_payment'): ?>
                        <div class="d-grid gap-2">
                            <a href="bayar_tagihan.php?id=<?= $peminjaman['peminjaman_id'] ?>" 
                               class="btn btn-danger">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                Bayar Tagihan
                            </a>
                        </div>
                        <?php elseif ($denda_info['is_late'] && $denda_info['has_denda']): ?>
                        <!-- Info denda yang akan dikenakan saat pengembalian -->
                        <div class="alert alert-warning small mb-2 p-2">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Estimasi Denda:</strong> <?= formatRupiah($denda_info['total_denda']) ?>
                            <br><small class="text-muted">Denda akan dihitung final oleh admin saat pengembalian diproses.</small>
                        </div>
                        <?php endif; ?>
                        
                        <hr class="my-3">
                        
                        <div class="text-center">
                            <i class="fas fa-box-open text-success" style="font-size: 2rem;"></i>
                            <h6 class="mt-3">Barang Sedang Dipinjam</h6>
                            <p class="small text-muted mb-2">
                                Target kembali:
                                <br><strong><?= date('d/m/Y H:i', strtotime($peminjaman['tanggal_kembali_rencana'] . ' ' . $peminjaman['jam_kembali_rencana'])) ?></strong>
                            </p>
                            
                            <?php if ($denda_info['is_late']): ?>
                            <div class="alert alert-danger small mb-0 p-2">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <strong>Terlambat <?= $denda_info['hari_terlambat'] ?> hari!</strong> Ajukan pengembalian sekarang untuk menghindari denda tambahan.
                            </div>
                            <?php else: ?>
                            <div class="alert alert-success small mb-0 p-2">
                                <i class="fas fa-check-circle me-1"></i>
                                Masih on-time! Ajukan pengembalian kapan saja.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php elseif ($peminjaman['status'] == 'returned'): ?>
                <div class="action-card text-center">
                    <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>
                    <h6 class="mt-3">Sudah Dikembalikan</h6>
                    <p class="small text-muted mb-0">
                        Terima kasih telah mengembalikan barang
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>