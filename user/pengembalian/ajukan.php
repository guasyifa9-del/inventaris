<?php
/**
 * ============================================================================
 * PENGEMBALIAN BARANG - Ajukan Pengembalian
 * Kepala Departemen mengajukan pengembalian barang
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
    SELECT sp.*
    FROM surat_peminjaman sp
    WHERE sp.surat_id = $surat_id 
    AND sp.kepala_departemen_id = {$user['user_id']}
    AND sp.status = 'sedang_digunakan'
";
$result = mysqli_query($connection, $query);
$surat = mysqli_fetch_assoc($result);

if (!$surat) {
    setFlashMessage('error', 'Surat tidak ditemukan atau tidak bisa dikembalikan!');
    header('Location: index.php');
    exit();
}

// Get items
$items_query = "
    SELECT spd.*, b.nama_barang, b.kode_barang
    FROM surat_peminjaman_detail spd
    JOIN barang b ON spd.barang_id = b.barang_id
    WHERE spd.surat_id = $surat_id
";
$items_result = mysqli_query($connection, $items_query);
$items = [];
if ($items_result) {
    while ($row = mysqli_fetch_assoc($items_result)) {
        $items[] = $row;
    }
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catatan = trim($_POST['catatan_pengembalian'] ?? '');
    $kondisi = $_POST['kondisi_barang'] ?? 'baik';
    
    // Update surat status
    $update = "
        UPDATE surat_peminjaman 
        SET status = 'menunggu_konfirmasi_kembali',
            catatan_pengembalian = '" . mysqli_real_escape_string($connection, $catatan) . "',
            kondisi_pengembalian = '" . mysqli_real_escape_string($connection, $kondisi) . "',
            tanggal_pengajuan_kembali = NOW()
        WHERE surat_id = $surat_id
    ";
    
    if (mysqli_query($connection, $update)) {
        setFlashMessage('success', 'Pengajuan pengembalian berhasil! Menunggu konfirmasi dari Manager Inventaris.');
        header('Location: index.php');
        exit();
    } else {
        $error = 'Gagal mengajukan pengembalian: ' . mysqli_error($connection);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Pengembalian - <?= htmlspecialchars($surat['nomor_surat']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        .page-header { background: white; border-radius: 16px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #10b981, #059669); color: white; border-radius: 12px 12px 0 0 !important; padding: 15px 20px; }
        
        .kondisi-option {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .kondisi-option:hover {
            border-color: #3b82f6;
        }
        
        .kondisi-option.selected {
            border-color: #10b981;
            background: #ecfdf5;
        }
        
        .kondisi-option input {
            display: none;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            padding: 12px 30px;
        }
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
                    <h2><i class="fas fa-undo me-2"></i>Ajukan Pengembalian</h2>
                    <p class="text-muted mb-0">Surat: <?= htmlspecialchars($surat['nomor_surat']) ?></p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Info Surat -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle me-2"></i>Informasi Peminjaman
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <small class="text-muted">Nomor Surat</small>
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($surat['nomor_surat']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Departemen</small>
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($surat['departemen']) ?></p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <small class="text-muted">Periode Peminjaman</small>
                                    <p class="mb-0"><?= date('d/m/Y', strtotime($surat['tanggal_mulai_pinjam'])) ?> - <?= date('d/m/Y', strtotime($surat['tanggal_selesai_pinjam'])) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Keperluan</small>
                                    <p class="mb-0"><?= htmlspecialchars($surat['keperluan']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Daftar Barang -->
                    <div class="card">
                        <div class="card-header" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            <i class="fas fa-boxes me-2"></i>Barang yang Dikembalikan
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Barang</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['kode_barang'] ?? '-') ?></td>
                                        <td><strong><?= htmlspecialchars($item['nama_barang']) ?></strong></td>
                                        <td class="text-center"><?= $item['jumlah'] ?? 1 ?> unit</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailBarangModal<?= $item['barang_id'] ?>" title="Lihat Detail">
                                                <i class="fas fa-info-circle"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal Detail Barang -->
                                    <div class="modal fade" id="detailBarangModal<?= $item['barang_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title"><?= htmlspecialchars($item['nama_barang']) ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php 
                                                    // Get full barang details
                                                    $barang_detail_query = "SELECT * FROM barang WHERE barang_id = {$item['barang_id']}";
                                                    $barang_detail_res = mysqli_query($connection, $barang_detail_query);
                                                    $barang_detail = mysqli_fetch_assoc($barang_detail_res);
                                                    ?>
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Kode Barang</small>
                                                        <strong><?= htmlspecialchars($barang_detail['kode_barang'] ?? '-') ?></strong>
                                                    </div>
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Nama</small>
                                                        <strong><?= htmlspecialchars($barang_detail['nama_barang']) ?></strong>
                                                    </div>
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Kategori</small>
                                                        <strong><?= htmlspecialchars($barang_detail['kategori'] ?? '-') ?></strong>
                                                    </div>
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Deskripsi</small>
                                                        <p class="mb-0"><?= htmlspecialchars($barang_detail['deskripsi'] ?? '-') ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Jumlah Dikembalikan</small>
                                                        <strong><?= $item['jumlah'] ?? 1 ?> unit</strong>
                                                    </div>
                                                    <div class="alert alert-info mb-0">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        <small>Pastikan barang dikembalikan dalam kondisi yang tepat sesuai dengan pernyataan di form pengembalian.</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Form Pengembalian -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-clipboard-check me-2"></i>Form Pengembalian
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Kondisi Barang <span class="text-danger">*</span></label>
                                
                                <label class="kondisi-option d-block mb-2 selected" id="opt-baik">
                                    <input type="radio" name="kondisi_barang" value="baik" checked>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2 fs-4"></i>
                                        <div>
                                            <strong>Baik</strong>
                                            <br><small class="text-muted">Semua barang dalam kondisi baik</small>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="kondisi-option d-block mb-2" id="opt-rusak-ringan">
                                    <input type="radio" name="kondisi_barang" value="rusak_ringan">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-circle text-warning me-2 fs-4"></i>
                                        <div>
                                            <strong>Rusak Ringan</strong>
                                            <br><small class="text-muted">Ada kerusakan kecil</small>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="kondisi-option d-block" id="opt-rusak-berat">
                                    <input type="radio" name="kondisi_barang" value="rusak_berat">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-times-circle text-danger me-2 fs-4"></i>
                                        <div>
                                            <strong>Rusak Berat</strong>
                                            <br><small class="text-muted">Kerusakan signifikan</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Catatan Pengembalian</label>
                                <textarea class="form-control" name="catatan_pengembalian" rows="4" 
                                    placeholder="Jelaskan kondisi barang saat dikembalikan..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-submit btn-success w-100" onclick="return confirm('Ajukan pengembalian barang ini?')">
                                <i class="fas fa-paper-plane me-2"></i>Ajukan Pengembalian
                            </button>
                            
                            <p class="text-muted text-center mt-3 mb-0" style="font-size: 0.85rem;">
                                <i class="fas fa-info-circle"></i> Pengembalian akan dikonfirmasi oleh Manager Inventaris
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        document.querySelectorAll('.kondisi-option').forEach(opt => {
            opt.addEventListener('click', function() {
                document.querySelectorAll('.kondisi-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
