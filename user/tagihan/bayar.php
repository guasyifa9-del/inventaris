<?php
/**
 * ============================================================================
 * USER - BAYAR TAGIHAN
 * ============================================================================
 * Halaman untuk membayar tagihan denda
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';

requireAuth();
$current_user = getCurrentUser();

$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($type) || $id <= 0) {
    setFlashMessage('error', 'Parameter tidak valid!');
    header('Location: index.php');
    exit;
}

$tagihan = null;

// Get tagihan data based on type
if ($type === 'surat') {
    $query = "
        SELECT sp.*, k.nama_karyawan
        FROM surat_peminjaman sp
        LEFT JOIN karyawan k ON sp.karyawan_id = k.karyawan_id
        WHERE sp.surat_id = ? 
        AND sp.kepala_departemen_id = ? 
        AND sp.status = 'pending_payment'
    ";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $current_user['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tagihan = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$tagihan) {
        setFlashMessage('error', 'Tagihan tidak ditemukan atau sudah dibayar!');
        header('Location: index.php');
        exit;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $metode_bayar = $_POST['metode_bayar'];
    $catatan_user = trim($_POST['catatan'] ?? '');
    
    if (isset($_FILES['bukti_bayar']) && $_FILES['bukti_bayar']['error'] == 0) {
        $upload_dir = __DIR__ . '/../../uploads/bukti_bayar/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $upload_result = uploadFile($_FILES['bukti_bayar'], $upload_dir, ['jpg', 'jpeg', 'png', 'pdf']);
        
        if ($upload_result['success']) {
            if ($type === 'surat') {
                $tgl_bayar = date('Y-m-d H:i:s');
                $tgl_bayar_display = date('d/m/Y H:i');
                $bukti_file = mysqli_real_escape_string($connection, $upload_result['filename']);
                $metode_bayar_escaped = mysqli_real_escape_string($connection, $metode_bayar);
                $catatan_escaped = mysqli_real_escape_string($connection, $catatan_user);
                $catatan_lengkap = " | User sudah bayar ($tgl_bayar_display) - $catatan_escaped";
                
                $update_query = "
                    UPDATE surat_peminjaman 
                    SET catatan_pengembalian = CONCAT(IFNULL(catatan_pengembalian,''), '$catatan_lengkap'),
                        bukti_bayar = '$bukti_file',
                        metode_bayar = '$metode_bayar_escaped',
                        tanggal_bayar = '$tgl_bayar',
                        status = 'paid_waiting_confirm'
                    WHERE surat_id = $id
                ";
                
                if (mysqli_query($connection, $update_query)) {
                    logActivity('BAYAR_DENDA_SURAT', "Pembayaran denda surat {$tagihan['nomor_surat']} - " . formatRupiah($tagihan['total_denda']));
                    setFlashMessage('success', 'Bukti pembayaran berhasil diupload! Menunggu konfirmasi admin.');
                    header('Location: index.php');
                    exit;
                } else {
                    setFlashMessage('error', 'Gagal menyimpan data pembayaran!');
                }
            }
        } else {
            setFlashMessage('error', 'Gagal upload file: ' . $upload_result['message']);
        }
    } else {
        setFlashMessage('error', 'Harap upload bukti pembayaran!');
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayar Tagihan - Inventaris</title>
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
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header-danger {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            padding: 30px;
        }
        
        .rincian-box {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            padding: 25px;
        }
        
        .rincian-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .rincian-row:last-child {
            border-bottom: none;
            border-top: 3px solid #ef4444;
            padding-top: 20px;
            margin-top: 10px;
        }
        
        .total-amount {
            font-size: 2rem;
            font-weight: 800;
            color: #dc2626;
        }
        
        .upload-area {
            border: 3px dashed #d1d5db;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            background: #f9fafb;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .metode-item {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .metode-item:hover, .metode-item.active {
            border-color: #3b82f6;
            background: #eff6ff;
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
    <div class="mb-4">
        <a href="index.php" class="btn btn-light">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Tagihan
        </a>
    </div>

    <div class="card">
        <div class="header-danger">
            <h3 class="mb-2"><i class="fas fa-file-invoice-dollar me-2"></i>Bayar Tagihan Denda</h3>
            <?php if ($type === 'surat'): ?>
            <p class="mb-0 opacity-75">Surat Peminjaman: <?= htmlspecialchars($tagihan['nomor_surat']) ?></p>
            <?php endif; ?>
        </div>
        
        <div class="card-body p-4">
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Rincian Tagihan + QR Code -->
                <div class="col-lg-5 mb-4">
                    <h5 class="mb-3"><i class="fas fa-receipt me-2"></i>Rincian Tagihan</h5>
                    
                    <div class="rincian-box">
                        <div class="rincian-row">
                            <span>Denda Keterlambatan</span>
                            <strong><?= formatRupiah($tagihan['denda_keterlambatan'] ?? 0) ?></strong>
                        </div>
                        <div class="rincian-row">
                            <span>Biaya Kerusakan/Kehilangan</span>
                            <strong><?= formatRupiah($tagihan['biaya_kerusakan'] ?? 0) ?></strong>
                        </div>
                        <div class="rincian-row">
                            <span class="fs-5 fw-bold">TOTAL TAGIHAN</span>
                            <span class="total-amount"><?= formatRupiah($tagihan['total_denda'] ?? 0) ?></span>
                        </div>
                    </div>
                    
                    <!-- QR Code Pembayaran -->
                    <div class="mt-4 p-4 bg-white rounded-3 border text-center">
                        <h6 class="mb-3"><i class="fas fa-qrcode me-2"></i>Scan untuk Bayar</h6>
                        <div class="qr-container p-3 bg-light rounded d-inline-block">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=PAYMENT-<?= $id ?>-<?= $tagihan['total_denda'] ?>" 
                                 alt="QR Code Pembayaran" class="img-fluid" style="max-width: 180px;">
                        </div>
                        <p class="text-muted small mt-3 mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Scan QR code ini dengan aplikasi e-wallet atau mobile banking
                        </p>
                    </div>
                    
                    <div class="mt-3 p-3 bg-warning bg-opacity-10 rounded">
                        <strong><i class="fas fa-info-circle me-2"></i>Kondisi Barang:</strong>
                        <p class="mb-0 mt-1"><?= htmlspecialchars($tagihan['kondisi_pengembalian'] ?? '-') ?></p>
                    </div>
                </div>
                
                <!-- Form Pembayaran -->
                <div class="col-lg-7">
                    <h5 class="mb-3"><i class="fas fa-credit-card me-2"></i>Form Pembayaran</h5>
                    
                    <form method="POST" enctype="multipart/form-data" id="paymentForm">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Metode Pembayaran <span class="text-danger">*</span></label>
                            
                            <label class="metode-item d-flex align-items-center" id="metode_transfer">
                                <input type="radio" name="metode_bayar" value="Transfer Bank" class="form-check-input me-3" required>
                                <i class="fas fa-university fa-lg me-3 text-primary"></i>
                                <div>
                                    <strong>Transfer Bank</strong>
                                    <small class="d-block text-muted">BCA, BNI, BRI, Mandiri, dll</small>
                                </div>
                            </label>
                            
                            <label class="metode-item d-flex align-items-center" id="metode_ewallet">
                                <input type="radio" name="metode_bayar" value="E-Wallet" class="form-check-input me-3">
                                <i class="fas fa-wallet fa-lg me-3 text-success"></i>
                                <div>
                                    <strong>E-Wallet</strong>
                                    <small class="d-block text-muted">GoPay, OVO, DANA, ShopeePay</small>
                                </div>
                            </label>
                            
                            <label class="metode-item d-flex align-items-center" id="metode_qris">
                                <input type="radio" name="metode_bayar" value="QRIS" class="form-check-input me-3">
                                <i class="fas fa-qrcode fa-lg me-3 text-info"></i>
                                <div>
                                    <strong>QRIS</strong>
                                    <small class="d-block text-muted">Scan QR code di atas</small>
                                </div>
                            </label>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Upload Bukti Pembayaran <span class="text-danger">*</span></label>
                            <div class="upload-area" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h6>Klik atau drag file untuk upload bukti pembayaran</h6>
                                <p class="text-muted small mb-0">Format: JPG, PNG, PDF (Maks. 5MB)</p>
                                <p class="text-primary fw-bold mt-2" id="fileName"></p>
                            </div>
                            <input type="file" id="bukti_bayar" name="bukti_bayar" accept=".jpg,.jpeg,.png,.pdf" class="d-none" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" name="catatan" rows="2" placeholder="Catatan tambahan..."></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Bukti Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Handle upload area click
document.getElementById('uploadArea').addEventListener('click', function() {
    document.getElementById('bukti_bayar').click();
});

// Show selected file name
document.getElementById('bukti_bayar').addEventListener('change', function() {
    const fileName = this.files[0] ? this.files[0].name : '';
    document.getElementById('fileName').textContent = fileName;
    if (fileName) {
        document.getElementById('uploadArea').style.borderColor = '#22c55e';
        document.getElementById('uploadArea').style.background = '#f0fdf4';
    }
});

// Highlight selected payment method
document.querySelectorAll('.metode-item input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.metode-item').forEach(item => {
            item.classList.remove('active');
        });
        this.closest('.metode-item').classList.add('active');
    });
});
</script>

</body>
</html>
