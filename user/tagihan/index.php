<?php
/**
 * ============================================================================
 * USER - TAGIHAN & DENDA
 * ============================================================================
 * Menampilkan semua tagihan denda user (dari surat_peminjaman dan peminjaman)
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';

requireAuth();
$current_user = getCurrentUser();
$user_id = $current_user['user_id'];

$flash = getFlashMessage();

// Get tagihan from surat_peminjaman (untuk kepala departemen)
$tagihan_surat = [];
if ($current_user['jenis_pengguna'] === 'kepala_departemen') {
    $query_surat = "
        SELECT sp.surat_id, sp.nomor_surat, sp.total_denda, sp.denda_keterlambatan, 
               sp.biaya_kerusakan, sp.kondisi_pengembalian, sp.status,
               sp.tanggal_kembali_aktual, sp.created_at,
               'surat_peminjaman' as source
        FROM surat_peminjaman sp
        WHERE sp.kepala_departemen_id = ?
        AND sp.status IN ('pending_payment', 'paid_waiting_confirm')
        ORDER BY 
            CASE WHEN sp.status = 'pending_payment' THEN 0 ELSE 1 END,
            sp.updated_at DESC
    ";
    $stmt = mysqli_prepare($connection, $query_surat);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $tagihan_surat[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Get tagihan from peminjaman biasa (untuk semua user)
$tagihan_peminjaman = [];
$query_pinjam = "
    SELECT p.peminjaman_id, p.kode_peminjaman, t.total_tagihan, 
           t.denda_keterlambatan, t.biaya_kerusakan, t.status as tagihan_status,
           p.status as peminjaman_status, p.created_at,
           'peminjaman' as source
    FROM peminjaman p
    LEFT JOIN tagihan_denda t ON p.peminjaman_id = t.peminjaman_id
    WHERE p.user_id = ?
    AND p.status = 'pending_payment'
    AND t.status = 'unpaid'
    ORDER BY t.created_at DESC
";
$stmt = mysqli_prepare($connection, $query_pinjam);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $tagihan_peminjaman[] = $row;
}
mysqli_stmt_close($stmt);

$total_tagihan = count($tagihan_surat) + count($tagihan_peminjaman);
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: #e8f0fe;
            min-height: 100vh;
        }
        
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 30px;
        }
        
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-header h2 {
            font-weight: 700;
            font-size: 1.6rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .card-header-danger {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            padding: 20px 25px;
        }
        
        .tagihan-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
        }
        
        .tagihan-item:hover {
            border-color: #dc2626;
            box-shadow: 0 5px 20px rgba(220, 38, 38, 0.15);
        }
        
        .tagihan-item.paid {
            border-color: #22c55e;
            background: #f0fdf4;
        }
        
        .amount-display {
            font-size: 1.5rem;
            font-weight: 800;
            color: #dc2626;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 30px;
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #22c55e;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../../views/default/sidebar_user.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h2><i class="fas fa-file-invoice-dollar text-danger"></i> Tagihan & Denda</h2>
        <p class="text-muted mb-0 mt-2">Kelola dan bayar tagihan denda Anda</p>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($total_tagihan > 0): ?>
    
    <!-- Tagihan dari Surat Peminjaman -->
    <?php if (!empty($tagihan_surat)): ?>
    <div class="card">
        <div class="card-header-danger">
            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Tagihan Surat Peminjaman</h5>
        </div>
        <div class="card-body p-4">
            <?php foreach ($tagihan_surat as $t): ?>
            <div class="tagihan-item <?= $t['status'] == 'paid_waiting_confirm' ? 'paid' : '' ?>">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="fw-bold text-primary"><?= htmlspecialchars($t['nomor_surat']) ?></div>
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            Dikembalikan: <?= $t['tanggal_kembali_aktual'] ? date('d/m/Y', strtotime($t['tanggal_kembali_aktual'])) : '-' ?>
                        </small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Kondisi: 
                            <span class="badge bg-<?= $t['kondisi_pengembalian'] == 'Baik' ? 'success' : ($t['kondisi_pengembalian'] == 'Rusak Ringan' ? 'warning' : 'danger') ?>">
                                <?= $t['kondisi_pengembalian'] ?? '-' ?>
                            </span>
                        </small>
                        <small class="text-muted">
                            Telat: <?= formatRupiah($t['denda_keterlambatan'] ?? 0) ?> | 
                            Kerusakan: <?= formatRupiah($t['biaya_kerusakan'] ?? 0) ?>
                        </small>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="amount-display"><?= formatRupiah($t['total_denda']) ?></div>
                        <?php if ($t['status'] == 'paid_waiting_confirm'): ?>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sudah Dibayar</span>
                        <?php else: ?>
                        <span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Belum Dibayar</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2 text-end">
                        <?php if ($t['status'] == 'pending_payment'): ?>
                        <a href="bayar.php?type=surat&id=<?= $t['surat_id'] ?>" class="btn btn-danger">
                            <i class="fas fa-money-bill-wave me-1"></i>Bayar
                        </a>
                        <?php else: ?>
                        <span class="text-success"><i class="fas fa-hourglass-half me-1"></i>Menunggu Konfirmasi</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tagihan dari Peminjaman Biasa -->
    <?php if (!empty($tagihan_peminjaman)): ?>
    <div class="card">
        <div class="card-header-danger">
            <h5 class="mb-0"><i class="fas fa-box me-2"></i>Tagihan Peminjaman</h5>
        </div>
        <div class="card-body p-4">
            <?php foreach ($tagihan_peminjaman as $t): ?>
            <div class="tagihan-item">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="fw-bold text-primary"><?= htmlspecialchars($t['kode_peminjaman']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">
                            Telat: <?= formatRupiah($t['denda_keterlambatan'] ?? 0) ?> | 
                            Kerusakan: <?= formatRupiah($t['biaya_kerusakan'] ?? 0) ?>
                        </small>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="amount-display"><?= formatRupiah($t['total_tagihan']) ?></div>
                    </div>
                    <div class="col-md-2 text-end">
                        <a href="../peminjaman/bayar_tagihan.php?id=<?= $t['peminjaman_id'] ?>" class="btn btn-danger">
                            <i class="fas fa-money-bill-wave me-1"></i>Bayar
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h4 class="text-success">Tidak Ada Tagihan</h4>
                <p class="text-muted">Semua tagihan denda Anda sudah lunas. Terima kasih!</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
