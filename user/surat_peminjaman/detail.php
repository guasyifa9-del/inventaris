<?php
/**
 * ============================================================================
 * SURAT PEMINJAMAN - Detail untuk Kepala Departemen
 * ============================================================================
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';

requireAuth();
$user = getCurrentUser();

// Only allow kepala_departemen
if ($user['jenis_pengguna'] !== 'kepala_departemen') {
    $_SESSION['error_message'] = 'Halaman ini hanya untuk Kepala Departemen!';
    header('Location: /inventaris/user/dashboard.php');
    exit();
}

$surat_id = intval($_GET['id'] ?? 0);
if (!$surat_id) {
    header('Location: index.php');
    exit();
}

// Get surat peminjaman
$query = "
    SELECT 
        sp.*,
        u_kepala.nama_lengkap as kepala_nama,
        u_approve.nama_lengkap as approved_by_nama,
        k.nama_karyawan,
        k.jabatan as karyawan_jabatan
    FROM surat_peminjaman sp
    LEFT JOIN users u_kepala ON sp.kepala_departemen_id = u_kepala.user_id
    LEFT JOIN users u_approve ON sp.disetujui_oleh = u_approve.user_id
    LEFT JOIN karyawan k ON sp.karyawan_id = k.karyawan_id
    WHERE sp.surat_id = $surat_id AND sp.kepala_departemen_id = {$user['user_id']}
";

$result = mysqli_query($connection, $query);
$surat = mysqli_fetch_assoc($result);

if (!$surat) {
    setFlashMessage('error', 'Surat tidak ditemukan!');
    header('Location: index.php');
    exit();
}

// Get detail barang
$detail_query = "
    SELECT spd.*, b.nama_barang, b.kode_barang
    FROM surat_peminjaman_detail spd
    JOIN barang b ON spd.barang_id = b.barang_id
    WHERE spd.surat_id = $surat_id
";
$detail_result = mysqli_query($connection, $detail_query);
$details = [];
if ($detail_result) {
    while ($row = mysqli_fetch_assoc($detail_result)) {
        $details[] = $row;
    }
}

// Handle send action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'kirim') {
    if ($surat['status'] === 'draft') {
        $update = "UPDATE surat_peminjaman SET status = 'dikirim', tanggal_pengiriman = NOW(), dikirim_oleh = {$user['user_id']} WHERE surat_id = $surat_id";
        if (mysqli_query($connection, $update)) {
            setFlashMessage('success', 'Surat berhasil dikirim ke Kepala Inventaris!');
            header("Location: detail.php?id=$surat_id");
            exit();
        }
    }
}

// Status badges
$status_badges = [
    'draft' => ['bg-secondary', 'Konsep', 'text-dark'],
    'dikirim' => ['bg-info', 'Dikirim (Menunggu Approval)', 'text-white'],
    'disetujui' => ['bg-success', 'Disetujui', 'text-white'],
    'ditolak' => ['bg-danger', 'Ditolak', 'text-white'],
    'sedang_digunakan' => ['bg-warning', 'Sedang Digunakan', 'text-dark'],
    'selesai' => ['bg-primary', 'Selesai', 'text-white'],
    'cancelled' => ['bg-dark', 'Dibatalkan', 'text-white']
];

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Surat - <?= htmlspecialchars($surat['nomor_surat']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        .page-header { background: white; border-radius: 16px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .page-header h2 { font-size: 1.5rem; font-weight: 700; margin: 0; }
        
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px 12px 0 0 !important; padding: 15px 20px; }
        
        .timeline { position: relative; padding: 20px 0; }
        .timeline-item { display: flex; align-items: flex-start; margin-bottom: 20px; }
        .timeline-dot { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; margin-right: 15px; flex-shrink: 0; }
        .timeline-content { flex: 1; }
        .timeline-content h6 { margin-bottom: 5px; font-weight: 600; }
        .timeline-content small { color: #6b7280; }
        
        .table { margin-bottom: 0; }
        .table th { background: #f8fafc; font-weight: 600; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../views/default/sidebar_user.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-file-contract me-2"></i><?= htmlspecialchars($surat['nomor_surat']) ?></h2>
                    <p class="text-muted mb-0">Detail surat peminjaman</p>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($surat['status'] === 'draft'): ?>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="action" value="kirim" class="btn btn-success" onclick="return confirm('Kirim surat ini ke Kepala Inventaris?')">
                                <i class="fas fa-paper-plane me-2"></i>Kirim
                            </button>
                        </form>
                        <a href="create.php?id=<?= $surat_id ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Edit
                        </a>
                    <?php endif; ?>
                    <?php if (in_array($surat['status'], ['disetujui', 'sedang_digunakan', 'selesai'])): ?>
                    <a href="print.php?id=<?= $surat_id ?>" class="btn btn-primary" target="_blank">
                        <i class="fas fa-print me-2"></i>Cetak Bukti
                    </a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Left: Info -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-info-circle me-2"></i>Informasi Surat</span>
                        <?php $badge = $status_badges[$surat['status']] ?? ['bg-secondary', 'Unknown', '']; ?>
                        <span class="badge <?= $badge[0] ?> fs-6"><?= $badge[1] ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted">Keperluan</small>
                                <p class="mb-0 fw-bold"><?= htmlspecialchars($surat['keperluan']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Departemen</small>
                                <p class="mb-0 fw-bold"><?= htmlspecialchars($surat['departemen']) ?></p>
                            </div>
                        </div>
                        <?php if ($surat['nama_karyawan']): ?>
                        <div class="row mb-3">
                            <div class="col-12">
                                <small class="text-muted">Untuk Karyawan</small>
                                <p class="mb-0">
                                    <span class="badge bg-info fs-6">
                                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($surat['nama_karyawan']) ?>
                                        <?php if ($surat['karyawan_jabatan']): ?>
                                        - <?= htmlspecialchars($surat['karyawan_jabatan']) ?>
                                        <?php endif; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <small class="text-muted">Tanggal Mulai</small>
                                <p class="mb-0 fw-bold"><?= date('d M Y', strtotime($surat['tanggal_mulai_pinjam'])) ?></p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Tanggal Selesai</small>
                                <p class="mb-0 fw-bold"><?= date('d M Y', strtotime($surat['tanggal_selesai_pinjam'])) ?></p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Durasi</small>
                                <p class="mb-0 fw-bold"><?= $surat['durasi_hari'] ?> hari</p>
                            </div>
                        </div>
                        <?php if ($surat['keterangan_peminjaman']): ?>
                            <div class="mb-3">
                                <small class="text-muted">Keterangan</small>
                                <p class="mb-0"><?= htmlspecialchars($surat['keterangan_peminjaman']) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($surat['status'] === 'ditolak' && $surat['alasan_penolakan']): ?>
                            <div class="alert alert-danger">
                                <strong><i class="fas fa-times-circle me-2"></i>Alasan Penolakan:</strong><br>
                                <?= htmlspecialchars($surat['alasan_penolakan']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Barang List -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-boxes me-2"></i>Daftar Barang
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Barang</th>
                                    <th>Jumlah</th>
                                    <th>Harga/Hari</th>
                                    <th>Durasi</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['kode_barang'] ?? '-') ?></td>
                                        <td><strong><?= htmlspecialchars($d['nama_barang']) ?></strong></td>
                                        <td><?= $d['jumlah'] ?? 1 ?></td>
                                        <td><?= formatRupiah($d['harga_sewa_per_hari'] ?? 0) ?></td>
                                        <td><?= $d['durasi_hari'] ?? 1 ?> hari</td>
                                        <td><strong><?= formatRupiah($d['subtotal_sewa'] ?? 0) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th colspan="5" class="text-end">TOTAL BIAYA:</th>
                                    <th><?= formatRupiah($surat['total_biaya']) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Right: Timeline -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history me-2"></i>Timeline
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-dot bg-secondary"><i class="fas fa-file"></i></div>
                                <div class="timeline-content">
                                    <h6>Surat Dibuat</h6>
                                    <small><?= date('d M Y H:i', strtotime($surat['created_at'])) ?></small>
                                </div>
                            </div>
                            
                            <?php if ($surat['tanggal_pengiriman']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot bg-info"><i class="fas fa-paper-plane"></i></div>
                                    <div class="timeline-content">
                                        <h6>Dikirim ke Kepala Inventaris</h6>
                                        <small><?= date('d M Y H:i', strtotime($surat['tanggal_pengiriman'])) ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($surat['status'] === 'disetujui' && $surat['tanggal_persetujuan']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot bg-success"><i class="fas fa-check"></i></div>
                                    <div class="timeline-content">
                                        <h6>Disetujui oleh <?= htmlspecialchars($surat['approved_by_nama']) ?></h6>
                                        <small><?= date('d M Y H:i', strtotime($surat['tanggal_persetujuan'])) ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($surat['status'] === 'ditolak' && $surat['tanggal_penolakan']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot bg-danger"><i class="fas fa-times"></i></div>
                                    <div class="timeline-content">
                                        <h6>Ditolak</h6>
                                        <small><?= date('d M Y H:i', strtotime($surat['tanggal_penolakan'])) ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
