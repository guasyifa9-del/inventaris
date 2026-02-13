<?php
/**
 * ============================================================================
 * USER AJUKAN PENGEMBALIAN
 * ============================================================================
 * 
 * File: user/peminjaman/ajukan_pengembalian.php
 * 
 * Features:
 * - User request untuk mengembalikan barang
 * - Cek kondisi barang self-report
 * - Jadwal pengembalian
 * 
 * ============================================================================
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';

requireUser();
$current_user = getCurrentUser();
$user = $current_user; // For navbar/sidebar compatibility

$id = (int)$_GET['id'];

// Get peminjaman data
$query = "
    SELECT p.* 
    FROM peminjaman p
    WHERE p.peminjaman_id = ? 
    AND p.user_id = ? 
    AND p.status IN ('approved', 'ongoing', 'late')
";

$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "ii", $id, $current_user['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$peminjaman = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$peminjaman) {
    setFlashMessage('error', 'Peminjaman tidak ditemukan atau tidak dapat dikembalikan!');
    header('Location: index.php');
    exit;
}

// Get barang details
$details = mysqli_query($connection,
    "SELECT pd.*, b.nama_barang, b.kode_barang
     FROM peminjaman_detail pd
     LEFT JOIN barang b ON pd.barang_id = b.barang_id
     WHERE pd.peminjaman_id = $id");

// Check if already have pending return request
$check_request = mysqli_query($connection,
    "SELECT * FROM permintaan_pengembalian 
     WHERE peminjaman_id = $id AND status = 'pending'");

if (mysqli_num_rows($check_request) > 0) {
    $existing_request = mysqli_fetch_assoc($check_request);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi input
    $errors = [];
    
    $tanggal_pengembalian = $_POST['tanggal_pengembalian'] ?? '';
    $jam_pengembalian = $_POST['jam_pengembalian'] ?? '';
    $kondisi_barang = $_POST['kondisi_barang'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    
    // Validasi required fields
    if (empty($tanggal_pengembalian)) {
        $errors[] = 'Tanggal pengembalian harus diisi!';
    }
    if (empty($jam_pengembalian)) {
        $errors[] = 'Jam pengembalian harus diisi!';
    }
    if (empty($kondisi_barang)) {
        $errors[] = 'Kondisi barang harus dipilih!';
    }
    
    // Validate tanggal - harus di masa depan
    if (!empty($tanggal_pengembalian) && !empty($jam_pengembalian)) {
        $tanggal_request = new DateTime($tanggal_pengembalian . ' ' . $jam_pengembalian);
        $now = new DateTime();
        
        if ($tanggal_request < $now) {
            $errors[] = 'Tanggal dan jam pengembalian harus di masa depan!';
        }
    }
    
    // Jika tidak ada error, proses insert
    if (empty($errors)) {
        // Insert permintaan pengembalian
        $insert_query = "
            INSERT INTO permintaan_pengembalian 
            (peminjaman_id, user_id, tanggal_pengembalian_request, jam_pengembalian_request, 
             kondisi_barang_laporan, catatan_user, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ";
        
        $stmt = mysqli_prepare($connection, $insert_query);
        mysqli_stmt_bind_param($stmt, "iissss", 
            $id, 
            $current_user['user_id'],
            $tanggal_pengembalian,
            $jam_pengembalian,
            $kondisi_barang,
            $catatan
        );
        
        if (mysqli_stmt_execute($stmt)) {
            // Update peminjaman catatan
            $update_query = "UPDATE peminjaman 
                SET catatan_admin = CONCAT(IFNULL(catatan_admin, ''), '\n[', NOW(), '] User mengajukan pengembalian')
                WHERE peminjaman_id = ?";
            $stmt2 = mysqli_prepare($connection, $update_query);
            mysqli_stmt_bind_param($stmt2, "i", $id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
            
            // Log activity
            logActivity('AJUKAN_PENGEMBALIAN', "Mengajukan pengembalian: {$peminjaman['kode_peminjaman']}");
            
            // Set success message
            setFlashMessage('success', 'Permintaan pengembalian berhasil diajukan! Menunggu konfirmasi admin.');
            
            // Redirect ke detail
            header('Location: detail.php?id=' . $id);
            exit;
        } else {
            $errors[] = 'Gagal menyimpan permintaan pengembalian: ' . mysqli_error($connection);
        }
        mysqli_stmt_close($stmt);
    }
    
    // Jika ada error, set flash message
    if (!empty($errors)) {
        setFlashMessage('error', implode('<br>', $errors));
    }
}

$flash = getFlashMessage();
$denda_info = getInfoDenda($peminjaman);
$sisa_waktu = getSisaWaktuPeminjaman($peminjaman['tanggal_kembali_rencana'], $peminjaman['jam_kembali_rencana']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Pengembalian - Inventaris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/inventaris/assets/css/style.css">
    <style>
        .info-box {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .warning-box {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 20px;
            border-radius: 10px;
        }
        .kondisi-option {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .kondisi-option:hover {
            border-color: #06b6d4;
            background: #f0f9ff;
        }
        .kondisi-option input[type="radio"]:checked + label {
            color: #0891b2;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<?php include __DIR__ . '/../../views/default/sidebar_user.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Navbar -->
    <?php include __DIR__ . '/../../views/default/navbar_user.php'; ?>
    
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-undo text-success me-2"></i>Ajukan Pengembalian Barang</h2>
            <a href="detail.php?id=<?= $id ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>

        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($existing_request)): ?>
        <!-- Existing Request Alert -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Anda sudah mengajukan permintaan pengembalian!</strong>
            <br>Jadwal: <?= date('d/m/Y H:i', strtotime($existing_request['tanggal_pengembalian_request'] . ' ' . $existing_request['jam_pengembalian_request'])) ?>
            <br>Status: <span class="badge bg-warning">Menunggu Konfirmasi Admin</span>
        </div>
        <?php else: ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Info Peminjaman -->
                <div class="info-box">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-3">Informasi Peminjaman</h4>
                            <p class="mb-2">
                                <i class="fas fa-barcode me-2"></i>
                                <strong>Kode:</strong> <?= $peminjaman['kode_peminjaman'] ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-calendar me-2"></i>
                                <strong>Target Kembali:</strong> 
                                <?= date('d/m/Y H:i', strtotime($peminjaman['tanggal_kembali_rencana'] . ' ' . $peminjaman['jam_kembali_rencana'])) ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                <strong><?= $sisa_waktu['formatted'] ?></strong>
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-box-open fa-4x mb-2"></i>
                            <h5>Ready to Return?</h5>
                        </div>
                    </div>
                </div>

                <?php if ($peminjaman['status'] == 'late'): ?>
                <div class="warning-box mb-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h5>Peminjaman Terlambat!</h5>
                    <p class="mb-2">
                        Terlambat: <strong><?= $denda_info['hari_terlambat'] ?> hari</strong> | 
                        Denda: <strong><?= formatRupiah($denda_info['total_denda']) ?></strong>
                    </p>
                    <p class="mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Segera kembalikan untuk menghindari denda tambahan!
                    </p>
                </div>
                <?php endif; ?>

                <!-- Form Ajukan Pengembalian -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>Formulir Permintaan Pengembalian
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        Tanggal Pengembalian <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           name="tanggal_pengembalian" 
                                           min="<?= date('Y-m-d') ?>"
                                           value="<?= date('Y-m-d') ?>"
                                           required>
                                    <small class="text-muted">Pilih tanggal rencana pengembalian</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        Jam Pengembalian <span class="text-danger">*</span>
                                    </label>
                                    <input type="time" 
                                           class="form-control" 
                                           name="jam_pengembalian"
                                           value="<?= date('H:i') ?>"
                                           required>
                                    <small class="text-muted">Jam operasional: 08:00 - 17:00</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold mb-3">
                                    Kondisi Barang (Self-Report) <span class="text-danger">*</span>
                                </label>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="kondisi-option">
                                            <input type="radio" 
                                                   name="kondisi_barang" 
                                                   value="Baik" 
                                                   id="kondisi_baik" 
                                                   required>
                                            <label for="kondisi_baik" class="d-block text-center mb-0">
                                                <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                                                <h6>Semua Baik</h6>
                                                <small class="text-muted">Tidak ada kerusakan</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="kondisi-option">
                                            <input type="radio" 
                                                   name="kondisi_barang" 
                                                   value="Rusak Ringan" 
                                                   id="kondisi_rusak">
                                            <label for="kondisi_rusak" class="d-block text-center mb-0">
                                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-2"></i>
                                                <h6>Ada Kerusakan</h6>
                                                <small class="text-muted">Rusak ringan/berat</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="kondisi-option">
                                            <input type="radio" 
                                                   name="kondisi_barang" 
                                                   value="Hilang" 
                                                   id="kondisi_hilang">
                                            <label for="kondisi_hilang" class="d-block text-center mb-0">
                                                <i class="fas fa-times-circle fa-3x text-danger mb-2"></i>
                                                <h6>Ada yang Hilang</h6>
                                                <small class="text-muted">Barang tidak lengkap</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Catatan Tambahan</label>
                                <textarea class="form-control" 
                                          name="catatan" 
                                          rows="4"
                                          placeholder="Jelaskan kondisi barang atau catatan khusus jika ada kerusakan/kehilangan..."></textarea>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Informasi:</strong>
                                <ul class="mb-0 mt-2 small">
                                    <li>Permintaan ini akan dikirim ke admin untuk dijadwalkan</li>
                                    <li>Admin akan mengonfirmasi jadwal pengembalian Anda</li>
                                    <li>Pastikan datang sesuai jadwal yang disetujui</li>
                                    <li>Kondisi barang akan dicek ulang oleh admin saat pengembalian</li>
                                    <?php if ($denda_info['has_denda']): ?>
                                    <li class="text-danger"><strong>Denda akan dipotong dari deposit saat pengembalian</strong></li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Ajukan Permintaan Pengembalian
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Daftar Barang -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-boxes me-2"></i>Barang yang Akan Dikembalikan
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $count = 0;
                        while ($item = mysqli_fetch_assoc($details)): 
                            $count++;
                        ?>
                        <div class="d-flex align-items-start mb-3 pb-3 <?= $count < mysqli_num_rows($details) ? 'border-bottom' : '' ?>">
                            <div class="bg-primary text-white rounded-circle me-3" 
                                 style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <strong><?= $count ?></strong>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= htmlspecialchars($item['nama_barang']) ?></h6>
                                <p class="small text-muted mb-0">
                                    <?= $item['kode_barang'] ?> | Qty: <strong><?= $item['jumlah'] ?></strong>
                                </p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Panduan -->
                <div class="card shadow-sm">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i>Panduan Pengembalian
                        </h5>
                    </div>
                    <div class="card-body">
                        <ol class="small mb-0">
                            <li class="mb-2">Isi form permintaan pengembalian dengan lengkap</li>
                            <li class="mb-2">Tunggu konfirmasi jadwal dari admin</li>
                            <li class="mb-2">Pastikan semua barang dalam kondisi baik</li>
                            <li class="mb-2">Datang sesuai jadwal yang telah dikonfirmasi</li>
                            <li class="mb-2">Serahkan barang ke admin untuk dicek</li>
                            <li class="mb-0">Terima deposit kembali (dikurangi denda jika ada)</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const kondisi = document.querySelector('input[name="kondisi_barang"]:checked');
    const tanggal = document.querySelector('input[name="tanggal_pengembalian"]').value;
    const jam = document.querySelector('input[name="jam_pengembalian"]').value;
    
    if (!kondisi) {
        e.preventDefault();
        alert('❌ Harap pilih kondisi barang!');
        return false;
    }
    
    if (!tanggal || !jam) {
        e.preventDefault();
        alert('❌ Harap isi tanggal dan jam pengembalian!');
        return false;
    }
    
    // Check if datetime is in the future
    const selectedDateTime = new Date(tanggal + ' ' + jam);
    const now = new Date();
    
    if (selectedDateTime <= now) {
        e.preventDefault();
        alert('❌ Tanggal dan jam pengembalian harus di masa depan!');
        return false;
    }
    
    // Show loading
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengirim...';
    
    return true;
});

// Highlight selected kondisi
document.querySelectorAll('input[name="kondisi_barang"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.kondisi-option').forEach(option => {
            option.style.borderColor = '#e5e7eb';
            option.style.background = 'white';
        });
        
        if (this.checked) {
            const parent = this.closest('.kondisi-option');
            parent.style.borderColor = '#06b6d4';
            parent.style.background = '#f0f9ff';
        }
    });
});
</script>

</body>
</html>