<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';

requireUser();
$user = getCurrentUser();

$id = (int)($_GET['id'] ?? 0);
$karyawan = mysqli_fetch_assoc(mysqli_query($connection, 
    "SELECT * FROM karyawan WHERE karyawan_id = $id AND kepala_departemen_id = {$user['user_id']}"
));

if (!$karyawan) {
    setFlashMessage('danger', 'Karyawan tidak ditemukan!');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = mysqli_real_escape_string($connection, trim($_POST['nama_karyawan']));
    $nik = mysqli_real_escape_string($connection, trim($_POST['nik']));
    $jabatan = mysqli_real_escape_string($connection, trim($_POST['jabatan']));
    $email = mysqli_real_escape_string($connection, trim($_POST['email']));
    $no_telepon = mysqli_real_escape_string($connection, trim($_POST['no_telepon']));
    $status = $_POST['status'];
    
    if (empty($nama)) {
        setFlashMessage('danger', 'Nama karyawan wajib diisi!');
    } else {
        $sql = "UPDATE karyawan SET 
                nama_karyawan = '$nama', 
                nik = '$nik', 
                jabatan = '$jabatan', 
                email = '$email', 
                no_telepon = '$no_telepon',
                status = '$status'
                WHERE karyawan_id = $id";
        
        if (mysqli_query($connection, $sql)) {
            logActivity('EDIT_KARYAWAN', "Mengubah data karyawan: $nama");
            setFlashMessage('success', "Data karyawan $nama berhasil diupdate!");
            header('Location: index.php');
            exit;
        } else {
            setFlashMessage('danger', 'Gagal mengupdate karyawan: ' . mysqli_error($connection));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Karyawan - Inventaris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { max-width: 700px; margin: 50px auto; padding: 20px; }
        .card-form { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .card-header-custom { 
            background: linear-gradient(135deg, #f59e0b, #d97706); 
            color: white; border-radius: 16px 16px 0 0 !important; padding: 25px; 
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="card-form">
        <div class="card-header-custom">
            <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Karyawan</h4>
            <p class="mb-0 opacity-75"><?= htmlspecialchars($karyawan['nama_karyawan']) ?></p>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Karyawan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-lg" name="nama_karyawan" 
                           value="<?= htmlspecialchars($karyawan['nama_karyawan']) ?>" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">NIK</label>
                        <input type="text" class="form-control" name="nik" 
                               value="<?= htmlspecialchars($karyawan['nik']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Jabatan/Posisi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="jabatan" 
                               value="<?= htmlspecialchars($karyawan['jabatan']) ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?= htmlspecialchars($karyawan['email']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">No. Telepon</label>
                        <input type="text" class="form-control" name="no_telepon" 
                               value="<?= htmlspecialchars($karyawan['no_telepon']) ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select class="form-select" name="status">
                        <option value="active" <?= $karyawan['status'] == 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $karyawan['status'] == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning btn-lg">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
