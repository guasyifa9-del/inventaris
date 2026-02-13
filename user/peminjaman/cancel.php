<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';
requireUser();

$id = (int)$_GET['id'];
$peminjaman = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM peminjaman WHERE peminjaman_id=$id AND user_id={$_SESSION['user_id']} AND status='pending'"));
if($peminjaman){
    mysqli_query($connection, "UPDATE peminjaman SET status='cancelled' WHERE peminjaman_id=$id");
    $details = mysqli_query($connection, "SELECT * FROM peminjaman_detail WHERE peminjaman_id=$id");
    while($d=mysqli_fetch_assoc($details)){
        updateStokBarang($connection, $d['barang_id'], $d['jumlah'], 'tambah');
    }
    logActivity('CANCEL_PEMINJAMAN', "Membatalkan peminjaman: {$peminjaman['kode_peminjaman']}");
    setFlashMessage('success', 'Peminjaman berhasil dibatalkan.');
}
header('Location: index.php');
exit;
