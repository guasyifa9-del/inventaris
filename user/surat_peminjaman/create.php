<?php
/**
 * ============================================================================
 * SURAT PEMINJAMAN - Create untuk Kepala Departemen dan Staff
 * MODIFIED: Staff now can also create surat peminjaman
 * ============================================================================
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';

requireAuth();
$user = getCurrentUser();

// Allow both kepala_departemen and staff (MODIFIED)
$allowed_roles = ['kepala_departemen', 'staff'];
if (!in_array($user['jenis_pengguna'], $allowed_roles)) {
    $_SESSION['error_message'] = 'Anda tidak memiliki akses ke halaman ini!';
    header('Location: /inventaris/user/dashboard.php');
    exit();
}

$error = '';
$success = '';

// Get available barang with category name
$barang_query = "SELECT b.*, k.nama_kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id = k.kategori_id WHERE b.status = 'active' AND b.jumlah_tersedia > 0 ORDER BY b.nama_barang ASC";
$barang_result = mysqli_query($connection, $barang_query);

// Get categories for filter
$kategori_query = "SELECT kategori_id, nama_kategori FROM kategori ORDER BY nama_kategori ASC";
$kategori_result = mysqli_query($connection, $kategori_query);

// Get karyawan for this kepala departemen
$karyawan_query = "SELECT karyawan_id, nama_karyawan, jabatan FROM karyawan WHERE kepala_departemen_id = {$user['user_id']} AND status = 'active' ORDER BY nama_karyawan ASC";
$karyawan_result = mysqli_query($connection, $karyawan_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keperluan = trim($_POST['keperluan'] ?? '');
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $karyawan_id = !empty($_POST['karyawan_id']) ? (int)$_POST['karyawan_id'] : null;
    $barang_ids = $_POST['barang_id'] ?? [];
    $jumlah_items = $_POST['jumlah'] ?? [];
    $action = $_POST['action'] ?? 'draft';
    
    // Combine date and time
    $datetime_mulai = $tanggal_mulai . ' ' . $jam_mulai . ':00';
    $datetime_selesai = $tanggal_selesai . ' ' . $jam_selesai . ':00';
    
    if (empty($keperluan) || empty($tanggal_mulai) || empty($tanggal_selesai)) {
        $error = 'Keperluan, tanggal mulai, dan tanggal selesai harus diisi!';
    } elseif (empty($barang_ids)) {
        $error = 'Pilih minimal 1 barang yang akan dipinjam!';
    } else {
        // Generate nomor surat
        $tahun = date('Y');
        $bulan = date('m');
        $counter_query = "SELECT COUNT(*) as cnt FROM surat_peminjaman WHERE YEAR(created_at) = $tahun";
        $counter_result = mysqli_query($connection, $counter_query);
        $counter = mysqli_fetch_assoc($counter_result)['cnt'] + 1;
        
        $nomor_surat = sprintf("%03d/SP/%s/%d", $counter, $user['departemen'], $tahun);
        
        // Determine status
        $status = ($action === 'kirim') ? 'DIKIRIM' : 'DRAFT';
        
        // Handle file upload
        $file_upload = null;
        if (isset($_FILES['dokumen_pendukung']) && $_FILES['dokumen_pendukung']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../uploads/surat_peminjaman/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['dokumen_pendukung']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['dokumen_pendukung']['tmp_name'], $file_path)) {
                $file_upload = $file_name;
            }
        }
        
        mysqli_begin_transaction($connection);
        
        try {
            // Calculate durasi
            $durasi = ceil((strtotime($tanggal_selesai) - strtotime($tanggal_mulai)) / 86400) + 1;
            if ($durasi < 1) $durasi = 1;
            
            // Insert surat peminjaman
            $insert_surat = "INSERT INTO surat_peminjaman 
                (nomor_surat, kepala_departemen_id, karyawan_id, departemen, tanggal_surat, keperluan, keterangan_peminjaman,
                 tanggal_mulai_pinjam, jam_mulai_pinjam, tanggal_selesai_pinjam, jam_selesai_pinjam, durasi_hari,
                 file_surat, status)
                VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($connection, $insert_surat);
            mysqli_stmt_bind_param($stmt, 'siisssssssiss', 
                $nomor_surat, 
                $user['user_id'],
                $karyawan_id,
                $user['departemen'],
                $keperluan,
                $keterangan,
                $tanggal_mulai,
                $jam_mulai,
                $tanggal_selesai,
                $jam_selesai,
                $durasi,
                $file_upload,
                $status
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Gagal menyimpan surat peminjaman');
            }
            
            $surat_id = mysqli_insert_id($connection);
            
            // Insert detail barang
            foreach ($barang_ids as $index => $barang_id) {
                $jumlah = (int)$jumlah_items[$index];
                
                if ($jumlah > 0) {
                    // Get barang info
                    $barang_query = "SELECT * FROM barang WHERE barang_id = ?";
                    $stmt_barang = mysqli_prepare($connection, $barang_query);
                    mysqli_stmt_bind_param($stmt_barang, 'i', $barang_id);
                    mysqli_stmt_execute($stmt_barang);
                    $barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_barang));
                    
                    if (!$barang) {
                        throw new Exception('Barang tidak ditemukan');
                    }
                    
                    // Check availability
                    if ($barang['jumlah_tersedia'] < $jumlah) {
                        throw new Exception("Stok {$barang['nama_barang']} tidak mencukupi");
                    }
                    
                    $harga_sewa = $barang['harga_sewa_per_hari'] ?? 0;
                    $subtotal = $harga_sewa * $jumlah * $durasi;
                    
                    $insert_detail = "INSERT INTO surat_peminjaman_detail 
                        (surat_id, barang_id, jumlah, harga_sewa_per_hari, durasi_hari, subtotal_sewa)
                        VALUES (?, ?, ?, ?, ?, ?)";
                    
                    $stmt_detail = mysqli_prepare($connection, $insert_detail);
                    mysqli_stmt_bind_param($stmt_detail, 'iiidid', 
                        $surat_id, $barang_id, $jumlah, $harga_sewa, $durasi, $subtotal
                    );
                    
                    if (!mysqli_stmt_execute($stmt_detail)) {
                        throw new Exception('Gagal menyimpan detail barang');
                    }
                }
            }
            
            mysqli_commit($connection);
            
            if ($action === 'kirim') {
                $_SESSION['success_message'] = 'Surat peminjaman berhasil dibuat dan dikirim!';
            } else {
                $_SESSION['success_message'] = 'Surat peminjaman berhasil disimpan sebagai draft!';
            }
            
            header('Location: detail.php?id=' . $surat_id);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($connection);
            $error = $e->getMessage();
        }
    }
}

// Display role info (MODIFIED to show both roles)
$role_display = ($user['jenis_pengguna'] === 'kepala_departemen') ? 'Kepala Departemen' : 'Staff';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Surat Peminjaman - Sistem Inventaris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        .page-header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3561;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .barang-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
        }
        .barang-item.selected {
            background: #e7f1ff;
            border-color: #4169E1;
        }
        .btn-primary {
            background: #4169E1;
            border: none;
        }
        .btn-primary:hover {
            background: #1E40AF;
        }
        .alert-info {
            background: #e7f1ff;
            border-color: #4169E1;
            color: #1E40AF;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../views/default/sidebar_user.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1"><i class="fas fa-file-contract text-primary"></i> Buat Surat Peminjaman Baru</h4>
                    <p class="text-muted mb-0">Form pembuatan surat peminjaman barang inventaris</p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>Informasi:</strong> Anda login sebagai <strong><?= $role_display ?></strong> dari departemen <strong><?= htmlspecialchars($user['departemen']) ?></strong>. 
            Surat yang dibuat akan menggunakan identitas ini.
        </div>

        <form method="POST" enctype="multipart/form-data" id="formSurat">
            <!-- Section 1: Informasi Dasar -->
            <div class="form-section">
                <h5 class="section-title">1. Informasi Dasar</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pembuat Surat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" readonly>
                        <small class="text-muted">Auto-filled dari profil Anda</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Departemen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['departemen']) ?>" readonly>
                        <small class="text-muted">Auto-filled dari profil Anda</small>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Untuk Karyawan <small class="text-muted">(opsional)</small></label>
                        <select name="karyawan_id" class="form-select">
                            <option value="">-- Pilih jika untuk karyawan tertentu --</option>
                            <?php 
                            if (mysqli_num_rows($karyawan_result) > 0) {
                                mysqli_data_seek($karyawan_result, 0);
                                while ($k = mysqli_fetch_assoc($karyawan_result)): 
                            ?>
                                <option value="<?= $k['karyawan_id'] ?>">
                                    <?= htmlspecialchars($k['nama_karyawan']) ?> - <?= htmlspecialchars($k['jabatan'] ?: 'No Position') ?>
                                </option>
                            <?php 
                                endwhile;
                            } 
                            ?>
                        </select>
                        <small class="text-muted">Nama dan jabatan karyawan akan tercantum di surat. <a href="../karyawan/add.php">Tambah karyawan baru</a></small>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Keperluan Peminjaman <span class="text-danger">*</span></label>
                        <textarea name="keperluan" class="form-control" rows="3" required placeholder="Contoh: Rapat koordinasi bulanan departemen"></textarea>
                        <small class="text-muted">Jelaskan tujuan peminjaman barang</small>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Keterangan Tambahan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Informasi tambahan (opsional)"></textarea>
                    </div>
                </div>
            </div>

            <!-- Section 2: Periode Peminjaman -->
            <div class="form-section">
                <h5 class="section-title">2. Periode Peminjaman</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                        <input type="time" name="jam_mulai" class="form-control" required value="09:00">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_selesai" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                        <input type="time" name="jam_selesai" class="form-control" required value="17:00">
                    </div>
                </div>
            </div>

            <!-- Section 3: Pilih Barang -->
            <div class="form-section">
                <h5 class="section-title">3. Pilih Barang/Ruang yang Dipinjam</h5>
                
                <div class="mb-3">
                    <label class="form-label">Filter Kategori</label>
                    <select class="form-select" id="filterKategori">
                        <option value="">Semua Kategori</option>
                        <?php while ($kat = mysqli_fetch_assoc($kategori_result)): ?>
                            <option value="<?= $kat['kategori_id'] ?>">
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div id="daftarBarang">
                    <?php 
                    mysqli_data_seek($barang_result, 0);
                    while ($brg = mysqli_fetch_assoc($barang_result)): 
                    ?>
                        <div class="barang-item" data-kategori="<?= $brg['kategori_id'] ?>">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" 
                                       class="form-check-input me-3 barang-checkbox" 
                                       name="barang_id[]" 
                                       value="<?= $brg['barang_id'] ?>"
                                       data-harga="<?= $brg['harga_sewa_per_hari'] ?? 0 ?>"
                                       data-nama="<?= htmlspecialchars($brg['nama_barang']) ?>">
                                
                                <?php 
                                // Display barang image
                                $gambar = $brg['gambar'];
                                $gambar_path = '/inventaris/uploads/barang/' . $gambar;
                                $gambar_file = __DIR__ . '/../../uploads/barang/' . $gambar;
                                $has_image = !empty($gambar) && file_exists($gambar_file);
                                ?>
                                <div class="me-3" style="width: 60px; height: 60px; flex-shrink: 0;">
                                    <?php if ($has_image): ?>
                                        <img src="<?= $gambar_path ?>" 
                                             alt="<?= htmlspecialchars($brg['nama_barang']) ?>" 
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-box text-muted" style="font-size: 24px;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex-grow-1">
                                    <strong><?= htmlspecialchars($brg['nama_barang']) ?></strong>
                                    <div class="text-muted small">
                                        Kategori: <?= htmlspecialchars($brg['nama_kategori'] ?? '-') ?> | 
                                        Tersedia: <?= $brg['jumlah_tersedia'] ?> unit | 
                                        Biaya: Rp <?= number_format($brg['harga_sewa_per_hari'] ?? 0, 0, ',', '.') ?>/hari
                                    </div>
                                </div>
                                <div>
                                    <label class="small text-muted">Jumlah:</label>
                                    <input type="number" 
                                           name="jumlah[]" 
                                           class="form-control form-control-sm jumlah-input" 
                                           style="width: 80px;"
                                           min="1" 
                                           max="<?= $brg['jumlah_tersedia'] ?>" 
                                           value="1" 
                                           disabled>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="mt-3 p-3 bg-light rounded">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Total Barang Dipilih:</strong> <span id="totalBarang">0</span>
                        </div>
                        <div class="col-md-6 text-end">
                            <strong>Total Biaya:</strong> <span id="totalBiaya" class="text-primary">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Upload Dokumen -->
            <div class="form-section">
                <h5 class="section-title">4. Upload Dokumen Pendukung (Opsional)</h5>
                <input type="file" name="dokumen_pendukung" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, DOC, DOCX, JPG, PNG (Max: 5MB)</small>
            </div>

            <!-- Action Buttons -->
            <div class="form-section">
                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
                        <i class="fas fa-save"></i> Simpan Sebagai Draft
                    </button>
                    <button type="submit" name="action" value="kirim" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Kirim ke Kepala Inventaris
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter kategori
        document.getElementById('filterKategori').addEventListener('change', function() {
            const kategori = this.value;
            const items = document.querySelectorAll('.barang-item');
            
            items.forEach(item => {
                if (kategori === '' || item.dataset.kategori === kategori) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Handle checkbox selection
        document.querySelectorAll('.barang-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const parent = this.closest('.barang-item');
                const jumlahInput = parent.querySelector('.jumlah-input');
                
                if (this.checked) {
                    parent.classList.add('selected');
                    jumlahInput.disabled = false;
                } else {
                    parent.classList.remove('selected');
                    jumlahInput.disabled = true;
                }
                
                updateTotal();
            });
        });

        // Handle jumlah input
        document.querySelectorAll('.jumlah-input').forEach(input => {
            input.addEventListener('input', updateTotal);
        });

        function updateTotal() {
            let totalBarang = 0;
            let totalBiaya = 0;
            
            document.querySelectorAll('.barang-checkbox:checked').forEach(checkbox => {
                const parent = checkbox.closest('.barang-item');
                const jumlah = parseInt(parent.querySelector('.jumlah-input').value) || 0;
                const harga = parseFloat(checkbox.dataset.harga) || 0;
                
                totalBarang += jumlah;
                totalBiaya += (jumlah * harga);
            });
            
            document.getElementById('totalBarang').textContent = totalBarang;
            document.getElementById('totalBiaya').textContent = 'Rp ' + totalBiaya.toLocaleString('id-ID');
        }

        // Form validation
        document.getElementById('formSurat').addEventListener('submit', function(e) {
            const checked = document.querySelectorAll('.barang-checkbox:checked').length;
            
            if (checked === 0) {
                e.preventDefault();
                alert('Pilih minimal 1 barang yang akan dipinjam!');
                return false;
            }
        });
    </script>
</body>
</html>
