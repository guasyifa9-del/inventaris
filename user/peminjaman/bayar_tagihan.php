<?php
/**
 * ============================================================================
 * USER - BAYAR TAGIHAN DENDA
 * ============================================================================
 * 
 * File: user/peminjaman/bayar_tagihan.php
 * 
 * Features:
 * - Tampilkan rincian tagihan (keterlambatan + kerusakan + kehilangan)
 * - Form upload bukti pembayaran
 * - Info rekening
 * 
 * ============================================================================
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';

requireUser();
$current_user = getCurrentUser();

$peminjaman_id = (int)($_GET['id'] ?? 0);

// Get peminjaman + tagihan data
$query = "
    SELECT p.*, 
           t.*,
           t.tagihan_id,
           t.total_tagihan,
           t.denda_keterlambatan,
           t.biaya_kerusakan,
           t.biaya_kehilangan,
           t.kondisi_barang,
           t.catatan_admin,
           u.nama_lengkap,
           u.email,
           u.departemen
    FROM peminjaman p
    LEFT JOIN tagihan_denda t ON p.peminjaman_id = t.peminjaman_id
    LEFT JOIN users u ON p.user_id = u.user_id
    WHERE p.peminjaman_id = ? 
    AND p.user_id = ? 
    AND p.status = 'pending_payment'
    AND t.status = 'unpaid'
";

$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "ii", $peminjaman_id, $current_user['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    setFlashMessage('error', 'Tagihan tidak ditemukan atau sudah dibayar!');
    header('Location: index.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $metode_bayar = $_POST['metode_bayar'];
    $catatan_user = trim($_POST['catatan'] ?? '');
    
    // Upload bukti pembayaran
    if (isset($_FILES['bukti_bayar']) && $_FILES['bukti_bayar']['error'] == 0) {
        $upload_dir = __DIR__ . '/../../uploads/bukti_bayar/';
        
        // Create directory if not exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $upload_result = uploadFile($_FILES['bukti_bayar'], $upload_dir, ['jpg', 'jpeg', 'png', 'pdf']);
        
        if ($upload_result['success']) {
            // Insert pembayaran_denda record
            $insert_query = "
                INSERT INTO pembayaran_denda 
                (peminjaman_id, tagihan_id, user_id, jumlah_denda, metode_bayar, 
                 bukti_bayar, catatan, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ";
            
            $stmt = mysqli_prepare($connection, $insert_query);
            mysqli_stmt_bind_param($stmt, "iiidsss", 
                $peminjaman_id,
                $data['tagihan_id'],
                $current_user['user_id'], 
                $data['total_tagihan'],
                $metode_bayar,
                $upload_result['filename'],
                $catatan_user
            );
            
            if (mysqli_stmt_execute($stmt)) {
                // Update tagihan status to 'pending_verification'
                $update_tagihan = "UPDATE tagihan_denda SET status = 'pending_verification' WHERE tagihan_id = ?";
                $stmt2 = mysqli_prepare($connection, $update_tagihan);
                mysqli_stmt_bind_param($stmt2, "i", $data['tagihan_id']);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
                
                logActivity('BAYAR_TAGIHAN', "Upload bukti bayar tagihan: {$data['kode_peminjaman']} - " . formatRupiah($data['total_tagihan']));
                
                setFlashMessage('success', '✅ Bukti pembayaran berhasil diupload! Menunggu verifikasi admin (maks 1x24 jam). Anda akan diberitahu jika pembayaran sudah diverifikasi.');
                header('Location: index.php');
                exit;
            } else {
                setFlashMessage('error', 'Gagal menyimpan data pembayaran!');
            }
            mysqli_stmt_close($stmt);
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
    <title>Bayar Tagihan Denda - Inventaris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .tagihan-header {
            background: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%);
            color: white;
            padding: 40px;
            border-radius: 20px 20px 0 0;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .tagihan-amount {
            font-size: 3.5rem;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            margin: 20px 0;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        
        .rincian-box {
            background: white;
            padding: 30px;
        }
        
        .rincian-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .rincian-row:last-child {
            border-bottom: none;
            border-top: 3px solid #ef4444;
            padding-top: 20px;
            margin-top: 15px;
        }
        
        .rincian-row.total {
            font-size: 1.3rem;
            font-weight: 800;
            color: #dc2626;
        }
        
        .upload-area {
            border: 3px dashed #3b82f6;
            border-radius: 15px;
            padding: 50px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f9fafb;
        }
        
        .upload-area:hover {
            background: #eff6ff;
            border-color: #2563eb;
            transform: scale(1.02);
        }
        
        .upload-area.dragover {
            background: #dbeafe;
            border-color: #1d4ed8;
        }
        
        .rekening-box {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 15px;
        }
        
        .panduan-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            border-radius: 10px;
        }
        
        .btn-copy {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 6px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .btn-copy:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        
        .info-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 8px 15px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            margin: 5px;
        }
    </style>
</head>
<body>

<div class="container-custom">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="detail.php?id=<?= $peminjaman_id ?>" class="btn btn-light">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Detail
        </a>
    </div>

    <!-- Header Tagihan -->
    <div class="tagihan-header">
        <i class="fas fa-exclamation-triangle fa-4x mb-3"></i>
        <h2>TAGIHAN DENDA & BIAYA</h2>
        <p class="mb-2">Kode Peminjaman: <strong><?= $data['kode_peminjaman'] ?></strong></p>
        <div class="tagihan-amount"><?= formatRupiah($data['total_tagihan']) ?></div>
        <div class="info-badge">
            <i class="fas fa-calendar-times me-2"></i>Harus segera dibayar
        </div>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show mt-4">
        <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row mt-4">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Rincian Tagihan -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Rincian Tagihan</h5>
                </div>
                <div class="rincian-box">
                    <?php if ($data['denda_keterlambatan'] > 0): ?>
                    <div class="rincian-row">
                        <div>
                            <strong>Denda Keterlambatan</strong>
                            <br><small class="text-muted"><?= $data['hari_terlambat'] ?> hari × Rp <?= number_format($data['denda_keterlambatan'] / max(1, $data['hari_terlambat']), 0, ',', '.') ?>/hari</small>
                        </div>
                        <div class="text-end">
                            <strong class="text-danger"><?= formatRupiah($data['denda_keterlambatan']) ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($data['biaya_kerusakan'] > 0): ?>
                    <div class="rincian-row">
                        <div>
                            <strong>Biaya Perbaikan Kerusakan</strong>
                            <br><small class="text-muted">Kondisi: <?= $data['kondisi_barang'] ?></small>
                        </div>
                        <div class="text-end">
                            <strong class="text-danger"><?= formatRupiah($data['biaya_kerusakan']) ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($data['biaya_kehilangan'] > 0): ?>
                    <div class="rincian-row">
                        <div>
                            <strong>Biaya Penggantian Barang Hilang</strong>
                            <br><small class="text-muted">Kondisi: <?= $data['kondisi_barang'] ?></small>
                        </div>
                        <div class="text-end">
                            <strong class="text-danger"><?= formatRupiah($data['biaya_kehilangan']) ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="rincian-row total">
                        <div><strong>TOTAL YANG HARUS DIBAYAR</strong></div>
                        <div><strong><?= formatRupiah($data['total_tagihan']) ?></strong></div>
                    </div>

                    <?php if ($data['catatan_admin']): ?>
                    <div class="alert alert-info mt-3">
                        <strong><i class="fas fa-info-circle me-2"></i>Catatan Admin:</strong><br>
                        <?= nl2br(htmlspecialchars($data['catatan_admin'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form Upload Bukti -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Upload Bukti Pembayaran</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select class="form-select" name="metode_bayar" required>
                                <option value="">Pilih Metode...</option>
                                <option value="QRIS">QRIS (Scan QR Code)</option>
                                <option value="Transfer Bank BCA">Transfer Bank BCA</option>
                                <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
                                <option value="GoPay">GoPay</option>
                                <option value="OVO">OVO</option>
                                <option value="DANA">DANA</option>
                                <option value="Tunai">Tunai</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Bukti Pembayaran <span class="text-danger">*</span></label>
                            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                                <h5>Klik atau Drag & Drop</h5>
                                <p class="text-muted mb-0">Upload screenshot/foto bukti transfer (JPG, PNG, PDF)</p>
                                <input type="file" id="fileInput" name="bukti_bayar" accept=".jpg,.jpeg,.png,.pdf" 
                                       style="display: none;" onchange="handleFileSelect(event)" required>
                            </div>
                            <div id="filePreview" class="mt-3" style="display: none;">
                                <div class="alert alert-success">
                                    <i class="fas fa-file-alt me-2"></i>
                                    <strong>File dipilih:</strong> <span id="fileName"></span>
                                </div>
                                <img id="imagePreview" src="" alt="Preview" class="img-fluid rounded shadow" style="max-height: 300px; display: none;">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Catatan (Opsional)</label>
                            <textarea class="form-control" name="catatan" rows="3" 
                                      placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Bukti Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- QRIS Payment -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>Bayar via QRIS</h5>
                </div>
                <div class="card-body text-center p-4">
                    <img src="/inventaris/assets/images/qris_payment.png" 
                         alt="QRIS Payment" 
                         class="img-fluid mb-3" 
                         style="max-width: 250px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    <p class="mb-2"><strong>Scan dengan aplikasi e-wallet</strong></p>
                    <p class="small text-muted mb-0">
                        GoPay, OVO, DANA, LinkAja, ShopeePay, dll.
                    </p>
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Transfer sesuai nominal: <strong><?= formatRupiah($data['total_tagihan']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Info Rekening -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-university me-2"></i>Informasi Rekening</h5>
                </div>
                <div class="card-body">
                    <div class="rekening-box">
                        <h6 class="fw-bold mb-2">Bank BCA</h6>
                        <p class="mb-1">No. Rek: <strong>1234567890</strong></p>
                        <p class="mb-2">A/n: <strong>PT Inventaris</strong></p>
                        <button class="btn btn-copy btn-sm" onclick="copyToClipboard('1234567890')">
                            <i class="fas fa-copy me-1"></i>Copy
                        </button>
                    </div>

                    <div class="rekening-box">
                        <h6 class="fw-bold mb-2">Bank Mandiri</h6>
                        <p class="mb-1">No. Rek: <strong>0987654321</strong></p>
                        <p class="mb-2">A/n: <strong>PT Inventaris</strong></p>
                        <button class="btn btn-copy btn-sm" onclick="copyToClipboard('0987654321')">
                            <i class="fas fa-copy me-1"></i>Copy
                        </button>
                    </div>

                    <div class="rekening-box">
                        <h6 class="fw-bold mb-2">E-Wallet</h6>
                        <p class="mb-1">GoPay: <strong>081234567890</strong></p>
                        <p class="mb-1">OVO: <strong>081234567890</strong></p>
                        <p class="mb-0">DANA: <strong>081234567890</strong></p>
                    </div>
                </div>
            </div>

            <!-- Panduan -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Panduan</h5>
                </div>
                <div class="card-body">
                    <div class="panduan-box">
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Transfer <strong>TEPAT</strong> sesuai nominal: <?= formatRupiah($data['total_tagihan']) ?></li>
                            <li class="mb-2">Screenshot bukti transfer</li>
                            <li class="mb-2">Upload bukti di form sebelah</li>
                            <li class="mb-2">Tunggu verifikasi admin (max 1x24 jam)</li>
                            <li class="mb-0">Setelah terverifikasi, proses pengembalian selesai</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('filePreview').style.display = 'block';
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }
}

// Drag and drop
const uploadArea = document.querySelector('.upload-area');

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    
    const file = e.dataTransfer.files[0];
    if (file) {
        document.getElementById('fileInput').files = e.dataTransfer.files;
        handleFileSelect({target: {files: [file]}});
    }
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Nomor rekening berhasil dicopy: ' + text);
    });
}
</script>

</body>
</html>