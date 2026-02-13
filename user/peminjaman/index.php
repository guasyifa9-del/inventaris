<?php
/**
 * ============================================================================
 * TAGIHAN & DENDA - Halaman Pembayaran Tagihan
 * ============================================================================
 * Halaman ini fokus pada:
 * - Melihat tagihan yang belum lunas
 * - Proses pembayaran tagihan
 * - Riwayat pembayaran
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';

requireUser();
$user = getCurrentUser();

// Get tagihan belum lunas dari tabel peminjaman dengan status pending_payment atau late
$tagihan_query = "
    SELECT p.*, 
           (SELECT COUNT(*) FROM peminjaman_detail WHERE peminjaman_id = p.peminjaman_id) as jumlah_item,
           t.tagihan_id,
           t.total_tagihan,
           t.created_at as tagihan_created,
           pd.status as payment_status,
           pd.created_at as payment_date
    FROM peminjaman p 
    LEFT JOIN tagihan_denda t ON p.peminjaman_id = t.peminjaman_id AND t.status IN ('unpaid', 'pending_verification')
    LEFT JOIN pembayaran_denda pd ON p.peminjaman_id = pd.peminjaman_id AND pd.status = 'pending'
    WHERE p.user_id = ?
    AND p.status IN ('pending_payment', 'late')
    ORDER BY 
        CASE WHEN p.status = 'pending_payment' THEN 1 ELSE 2 END,
        p.tanggal_kembali_rencana ASC
";

$stmt = mysqli_prepare($connection, $tagihan_query);
mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
mysqli_stmt_execute($stmt);
$tagihan_result = mysqli_stmt_get_result($stmt);
$tagihan_list = [];
$total_tagihan_belum_bayar = 0;

while ($row = mysqli_fetch_assoc($tagihan_result)) {
    $tagihan_list[] = $row;
    if ($row['payment_status'] !== 'pending') {
        $total_tagihan_belum_bayar += ($row['total_tagihan'] ?? $row['denda_keterlambatan'] ?? 0);
    }
}
mysqli_stmt_close($stmt);

// Get riwayat pembayaran
$riwayat_query = "
    SELECT pd.*, p.kode_peminjaman, p.keperluan, p.tanggal_pinjam
    FROM pembayaran_denda pd
    JOIN peminjaman p ON pd.peminjaman_id = p.peminjaman_id
    WHERE pd.user_id = ?
    ORDER BY pd.created_at DESC
    LIMIT 10
";

$stmt2 = mysqli_prepare($connection, $riwayat_query);
mysqli_stmt_bind_param($stmt2, "i", $user['user_id']);
mysqli_stmt_execute($stmt2);
$riwayat_result = mysqli_stmt_get_result($stmt2);
$riwayat_list = [];
while ($row = mysqli_fetch_assoc($riwayat_result)) {
    $riwayat_list[] = $row;
}
mysqli_stmt_close($stmt2);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan & Denda - Inventaris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        /* Page Header */
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .page-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* Summary Card */
        .summary-card {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .summary-card.no-debt {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .summary-icon {
            width: 60px; height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        
        .summary-info h5 {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .summary-info h2 {
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
        }
        
        /* Card */
        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 18px 25px;
            font-weight: 700;
            font-size: 1rem;
            border: none;
        }
        
        .card-header.bg-danger {
            background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
            color: white;
        }
        
        .card-header.bg-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563) !important;
            color: white;
        }
        
        /* Tagihan Item */
        .tagihan-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #dc2626;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .tagihan-item.menunggu {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        
        .tagihan-kode {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1f2937;
        }
        
        .tagihan-amount {
            font-size: 1.3rem;
            font-weight: 800;
            color: #dc2626;
        }
        
        .btn-bayar {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-bayar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
            color: white;
        }
        
        /* Badge */
        .badge-verifikasi {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        /* Table */
        .table thead th {
            background: #f9fafb;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 14px 12px;
            border: none;
        }
        
        .table tbody td {
            padding: 15px 12px;
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #10b981;
            margin-bottom: 15px;
        }
        
        .empty-state h5 {
            font-weight: 700;
            color: #1f2937;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .summary-card { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<?php include __DIR__ . '/../../views/default/sidebar_user.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-credit-card"></i> Tagihan & Denda</h2>
        <p class="text-muted mb-0 mt-2">Kelola tagihan dan pembayaran denda Anda</p>
    </div>
    
    <!-- Flash Message -->
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'info-circle' ?> me-2"></i>
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Summary Card -->
    <?php if ($total_tagihan_belum_bayar > 0): ?>
    <div class="summary-card">
        <div class="d-flex align-items-center gap-3">
            <div class="summary-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="summary-info">
                <h5>Total Tagihan Belum Dibayar</h5>
                <h2><?= formatRupiah($total_tagihan_belum_bayar) ?></h2>
            </div>
        </div>
        <div>
            <span class="badge bg-light text-dark fs-6">
                <i class="fas fa-file-invoice me-1"></i>
                <?= count(array_filter($tagihan_list, fn($t) => $t['payment_status'] !== 'pending')) ?> tagihan
            </span>
        </div>
    </div>
    <?php else: ?>
    <div class="summary-card no-debt">
        <div class="d-flex align-items-center gap-3">
            <div class="summary-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="summary-info">
                <h5>Status Tagihan</h5>
                <h2>Tidak Ada Tagihan</h2>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tagihan Belum Lunas -->
    <div class="card">
        <div class="card-header bg-danger">
            <i class="fas fa-file-invoice-dollar me-2"></i>Tagihan Belum Lunas (<?= count($tagihan_list) ?>)
        </div>
        <div class="card-body">
            <?php if (empty($tagihan_list)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h5>Tidak Ada Tagihan</h5>
                <p class="text-muted">Semua tagihan Anda sudah lunas. Bagus!</p>
            </div>
            <?php else: ?>
                <?php foreach ($tagihan_list as $t): 
                    $denda_info = getInfoDenda($t);
                    $amount = $t['total_tagihan'] ?? $denda_info['total_denda'] ?? 0;
                ?>
                <div class="tagihan-item <?= $t['payment_status'] === 'pending' ? 'menunggu' : '' ?>">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="tagihan-kode"><?= htmlspecialchars($t['kode_peminjaman']) ?></div>
                            <small class="text-muted">
                                <?= htmlspecialchars($t['keperluan']) ?>
                            </small>
                            <div class="mt-2">
                                <?php if ($t['status'] === 'late'): ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-clock me-1"></i>
                                    Terlambat <?= $denda_info['hari_terlambat'] ?? 0 ?> hari
                                </span>
                                <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending Payment</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <small class="text-muted d-block">Tanggal Pinjam</small>
                            <strong><?= date('d M Y', strtotime($t['tanggal_pinjam'])) ?></strong>
                            <br>
                            <small class="text-muted"><?= $t['jumlah_item'] ?> item</small>
                        </div>
                        <div class="col-md-2 text-center">
                            <small class="text-muted d-block">Jumlah Tagihan</small>
                            <div class="tagihan-amount"><?= formatRupiah($amount) ?></div>
                        </div>
                        <div class="col-md-3 text-end">
                            <?php if ($t['payment_status'] === 'pending'): ?>
                            <span class="badge-verifikasi">
                                <i class="fas fa-hourglass-half me-1"></i>
                                Menunggu Verifikasi
                            </span>
                            <br>
                            <small class="text-muted mt-2 d-block">
                                Upload: <?= date('d/m/Y H:i', strtotime($t['payment_date'])) ?>
                            </small>
                            <?php else: ?>
                            <a href="bayar_tagihan.php?id=<?= $t['peminjaman_id'] ?>" class="btn btn-bayar">
                                <i class="fas fa-credit-card me-2"></i>Bayar Sekarang
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Riwayat Pembayaran -->
    <div class="card">
        <div class="card-header bg-secondary">
            <i class="fas fa-history me-2"></i>Riwayat Pembayaran
        </div>
        <div class="card-body">
            <?php if (empty($riwayat_list)): ?>
            <p class="text-muted text-center py-4">Belum ada riwayat pembayaran.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode Peminjaman</th>
                            <th>Jumlah</th>
                            <th>Metode</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat_list as $r): 
                            $status_class = [
                                'pending' => 'bg-warning text-dark',
                                'verified' => 'bg-success',
                                'rejected' => 'bg-danger'
                            ][$r['status']] ?? 'bg-secondary';
                            $status_text = [
                                'pending' => 'Menunggu Verifikasi',
                                'verified' => 'Terverifikasi',
                                'rejected' => 'Ditolak'
                            ][$r['status']] ?? $r['status'];
                        ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['kode_peminjaman']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($r['keperluan'] ?? '') ?></small>
                            </td>
                            <td><strong><?= formatRupiah($r['jumlah_denda'] ?? 0) ?></strong></td>
                            <td>
                                <span class="badge bg-info"><?= ucfirst($r['metode_bayar']) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>