<?php
/**
 * ============================================================================
 * AMBIL BARANG - Kepala Departemen
 * Mengubah status dari 'disetujui' ke 'sedang_digunakan'
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
    $_SESSION['error_message'] = 'ID Surat tidak valid!';
    header('Location: index.php');
    exit();
}

// Cek surat peminjaman
$query = "SELECT * FROM surat_peminjaman WHERE surat_id = ? AND kepala_departemen_id = ? AND status = 'disetujui'";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, 'ii', $surat_id, $user['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$surat = mysqli_fetch_assoc($result);

if (!$surat) {
    $_SESSION['error_message'] = 'Surat peminjaman tidak ditemukan atau bukan milik Anda!';
    header('Location: index.php');
    exit();
}

// Update status ke sedang_digunakan dan kurangi stok barang
mysqli_begin_transaction($connection);

try {
    // Update status
    $update = "UPDATE surat_peminjaman SET status = 'sedang_digunakan' WHERE surat_id = ?";
    $stmt_update = mysqli_prepare($connection, $update);
    mysqli_stmt_bind_param($stmt_update, 'i', $surat_id);
    
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception('Gagal mengupdate status surat');
    }
    
    // Kurangi stok barang
    $items_query = "SELECT * FROM surat_peminjaman_detail WHERE surat_id = ?";
    $stmt_items = mysqli_prepare($connection, $items_query);
    mysqli_stmt_bind_param($stmt_items, 'i', $surat_id);
    mysqli_stmt_execute($stmt_items);
    $items_result = mysqli_stmt_get_result($stmt_items);
    
    while ($item = mysqli_fetch_assoc($items_result)) {
        $reduce_stock = "UPDATE barang SET jumlah_tersedia = jumlah_tersedia - ? WHERE barang_id = ?";
        $stmt_reduce = mysqli_prepare($connection, $reduce_stock);
        mysqli_stmt_bind_param($stmt_reduce, 'ii', $item['jumlah'], $item['barang_id']);
        mysqli_stmt_execute($stmt_reduce);
    }
    
    mysqli_commit($connection);
    
    $_SESSION['success_message'] = 'Barang berhasil diambil! Silakan ajukan pengembalian setelah selesai menggunakan.';
    header('Location: index.php');
    exit();
    
} catch (Exception $e) {
    mysqli_rollback($connection);
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    header('Location: index.php');
    exit();
}
