<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';

requireUser();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = mysqli_real_escape_string($connection, trim($_POST['nama_karyawan']));
    $nik = mysqli_real_escape_string($connection, trim($_POST['nik']));
    $jabatan = mysqli_real_escape_string($connection, trim($_POST['jabatan']));
    $email = mysqli_real_escape_string($connection, trim($_POST['email']));
    $no_telepon = mysqli_real_escape_string($connection, trim($_POST['no_telepon']));
    $user_id = $user['user_id'];
    
    if (empty($nama)) {
        setFlashMessage('danger', 'Nama karyawan wajib diisi!');
    } else {
        $sql = "INSERT INTO karyawan (kepala_departemen_id, nama_karyawan, nik, jabatan, email, no_telepon) 
                VALUES ($user_id, '$nama', '$nik', '$jabatan', '$email', '$no_telepon')";
        
        if (mysqli_query($connection, $sql)) {
            logActivity('ADD_KARYAWAN', "Menambahkan karyawan: $nama ($jabatan)");
            setFlashMessage('success', "Karyawan $nama berhasil ditambahkan!");
            header('Location: index.php');
            exit;
        } else {
            setFlashMessage('danger', 'Gagal menambahkan karyawan: ' . mysqli_error($connection));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karyawan - Inventaris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-content { max-width: 700px; margin: 50px auto; padding: 20px; }
        .card-form { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .card-header-custom { 
            background: linear-gradient(135deg, #3b82f6, #1e40af); 
            color: white; border-radius: 16px 16px 0 0 !important; padding: 25px; 
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="card-form">
        <div class="card-header-custom">
            <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Tambah Karyawan Baru</h4>
            <p class="mb-0 opacity-75">Departemen: <?= htmlspecialchars($user['departemen']) ?></p>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Karyawan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-lg" name="nama_karyawan" 
                           placeholder="Nama lengkap karyawan" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">NIK <small class="text-muted">(opsional)</small></label>
                        <input type="text" class="form-control" name="nik" placeholder="Nomor Induk Karyawan">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Jabatan/Posisi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="jabatan" placeholder="cth: Staff IT, Sekretaris" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Email <small class="text-muted">(opsional)</small></label>
                        <input type="email" class="form-control" name="email" placeholder="email@kantor.com">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">No. Telepon <small class="text-muted">(opsional)</small></label>
                        <input type="text" class="form-control" name="no_telepon" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Simpan Karyawan
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
