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

// Check if karyawan has active loans
$check = mysqli_query($connection, "
    SELECT COUNT(*) as total FROM peminjaman 
    WHERE karyawan_id = $id AND status IN ('pending', 'approved', 'ongoing', 'late')
");
$has_loans = mysqli_fetch_assoc($check)['total'] > 0;

if ($has_loans) {
    setFlashMessage('warning', 'Karyawan tidak dapat dihapus karena masih memiliki peminjaman aktif!');
    header('Location: index.php');
    exit;
}

// Delete karyawan
$nama = $karyawan['nama_karyawan'];
if (mysqli_query($connection, "DELETE FROM karyawan WHERE karyawan_id = $id")) {
    logActivity('DELETE_KARYAWAN', "Menghapus karyawan: $nama");
    setFlashMessage('success', "Karyawan $nama berhasil dihapus!");
} else {
    setFlashMessage('danger', 'Gagal menghapus karyawan: ' . mysqli_error($connection));
}

header('Location: index.php');
exit;
