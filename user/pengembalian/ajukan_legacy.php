<?php
/**
 * ============================================================================
 * PENGEMBALIAN BARANG - Ajukan Pengembalian (Legacy/Direct Borrowing)
 * Kepala Departemen mengajukan pengembalian barang dari peminjaman langsung
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

$peminjaman_id = intval($_GET['id'] ?? 0);
if (!$peminjaman_id) {
    header('Location: index.php');
    exit();
}

// Get peminjaman data
$query = "
    SELECT p.*, u.nama_lengkap, u.departemen
    FROM peminjaman p
    LEFT JOIN users u ON p.user_id = u.user_id
    WHERE p.peminjaman_id = $peminjaman_id 
    AND p.user_id = {$user['user_id']}
    AND p.status IN ('approved', 'ongoing', 'late')
";
$result = mysqli_query($connection, $query);
$peminjaman = mysqli_fetch_assoc($result);

if (!$peminjaman) {
    setFlashMessage('error', 'Peminjaman tidak ditemukan atau tidak bisa dikembalikan!');
    header('Location: index.php');
    exit();
}

// Get items
$items_query = "
    SELECT pd.*, b.nama_barang, b.kode_barang
    FROM peminjaman_detail pd
    JOIN barang b ON pd.barang_id = b.barang_id
    WHERE pd.peminjaman_id = $peminjaman_id
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
    
    mysqli_begin_transaction($connection);
    
    try {
        // Update peminjaman status to return_pending
        $update = "
            UPDATE peminjaman 
            SET status = 'return_pending',
                tanggal_kembali_aktual = NOW(),
                catatan_admin = CONCAT(IFNULL(catatan_admin, ''), '\nPengembalian diajukan: " . mysqli_real_escape_string($connection, $catatan) . " - Kondisi: $kondisi')
            WHERE peminjaman_id = $peminjaman_id
        ";
        
        if (!mysqli_query($connection, $update)) {
            throw new Exception('Gagal update status peminjaman');
        }
        
        // Update kondisi_kembali for each item
        $kondisi_item = ($kondisi === 'baik') ? 'Baik' : (($kondisi === 'rusak_ringan') ? 'Rusak Ringan' : 'Rusak Berat');
        $update_items = "
            UPDATE peminjaman_detail 
            SET kondisi_kembali = '$kondisi_item'
            WHERE peminjaman_id = $peminjaman_id
        ";
        
        if (!mysqli_query($connection, $update_items)) {
            throw new Exception('Gagal update kondisi barang');
        }
        
        mysqli_commit($connection);
        
        setFlashMessage('success', 'Pengajuan pengembalian berhasil! Menunggu konfirmasi dari Admin.');
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        mysqli_rollback($connection);
        $error = 'Gagal mengajukan pengembalian: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Pengembalian - <?= htmlspecialchars($peminjaman['kode_peminjaman']) ?></title>
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
                    <p class="text-muted mb-0">
                        Peminjaman: <?= htmlspecialchars($peminjaman['kode_peminjaman']) ?>
                        <span class="badge bg-secondary">Peminjaman Langsung</span>
                    </p>
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
                    <!-- Info Peminjaman -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle me-2"></i>Informasi Peminjaman
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <small class="text-muted">Kode Peminjaman</small>
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($peminjaman['kode_peminjaman']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Departemen</small>
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($peminjaman['departemen'] ?? '-') ?></p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <small class="text-muted">Tanggal Pinjam</small>
                                    <p class="mb-0"><?= date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Batas Pengembalian</small>
                                    <p class="mb-0"><?= date('d/m/Y', strtotime($peminjaman['tanggal_kembali_rencana'])) ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <small class="text-muted">Keperluan</small>
                                    <p class="mb-0"><?= htmlspecialchars($peminjaman['keperluan']) ?></p>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['kode_barang'] ?? '-') ?></td>
                                        <td><strong><?= htmlspecialchars($item['nama_barang']) ?></strong></td>
                                        <td class="text-center"><?= $item['jumlah'] ?? 1 ?> unit</td>
                                    </tr>
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
                                <i class="fas fa-info-circle"></i> Pengembalian akan dikonfirmasi oleh Admin
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
