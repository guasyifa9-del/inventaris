<?php
/**
 * Email Notification Library
 * Menggunakan PHPMailer untuk mengirim email notifikasi
 * 
 * INSTALASI:
 * composer require phpmailer/phpmailer
 * 
 * KONFIGURASI:
 * Tambahkan di .env:
 * SMTP_HOST=smtp.gmail.com
 * SMTP_PORT=587
 * SMTP_USER=your-email@gmail.com
 * SMTP_PASS=your-app-password
 * SMTP_FROM_EMAIL=noreply@kantor.com
 * SMTP_FROM_NAME=Sistem Inventaris Kantor
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailNotification {
    private $mailer;
    private $connection;
    
    public function __construct($connection) {
        $this->connection = $connection;
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }
    
    private function configureMailer() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = getenv('SMTP_USER');
            $this->mailer->Password   = getenv('SMTP_PASS');
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = getenv('SMTP_PORT') ?: 587;
            
            // Default sender
            $this->mailer->setFrom(
                getenv('SMTP_FROM_EMAIL') ?: 'noreply@kantor.com',
                getenv('SMTP_FROM_NAME') ?: 'Sistem Inventaris Kantor'
            );
            
            // Encoding
            $this->mailer->CharSet = 'UTF-8';
        } catch (Exception $e) {
            error_log("Mailer configuration error: {$e->getMessage()}");
        }
    }
    
    /**
     * Send notification when peminjaman is approved
     */
    public function notifyPeminjamanApproved($peminjaman_id) {
        try {
            $query = "SELECT p.*, u.nama_lengkap, u.email, u.no_telepon,
                     admin.nama_lengkap as approved_by_name
                     FROM peminjaman p
                     JOIN users u ON p.user_id = u.user_id
                     LEFT JOIN users admin ON p.approved_by = admin.user_id
                     WHERE p.peminjaman_id = ?";
            
            $stmt = mysqli_prepare($this->connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $peminjaman_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            
            if (!$data || !$data['email']) {
                return false;
            }
            
            // Get barang details
            $query_detail = "SELECT pd.*, b.nama_barang 
                            FROM peminjaman_detail pd
                            JOIN barang b ON pd.barang_id = b.barang_id
                            WHERE pd.peminjaman_id = ?";
            $stmt_detail = mysqli_prepare($this->connection, $query_detail);
            mysqli_stmt_bind_param($stmt_detail, "i", $peminjaman_id);
            mysqli_stmt_execute($stmt_detail);
            $result_detail = mysqli_stmt_get_result($stmt_detail);
            $items = mysqli_fetch_all($result_detail, MYSQLI_ASSOC);
            
            // Compose email
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($data['email'], $data['nama_lengkap']);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = '✅ Peminjaman Anda Disetujui - ' . $data['kode_peminjaman'];
            
            $barang_list = '';
            foreach ($items as $item) {
                $barang_list .= "<li>{$item['nama_barang']} (Jumlah: {$item['jumlah']})</li>";
            }
            
            $this->mailer->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                    .content { background: #f8fafc; padding: 20px; }
                    .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #2563eb; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                    .button { display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Peminjaman Disetujui ✅</h2>
                    </div>
                    <div class='content'>
                        <p>Halo <strong>{$data['nama_lengkap']}</strong>,</p>
                        <p>Pengajuan peminjaman Anda telah <strong>DISETUJUI</strong>!</p>
                        
                        <div class='info-box'>
                            <strong>Kode Peminjaman:</strong> {$data['kode_peminjaman']}<br>
                            <strong>Tanggal Pinjam:</strong> {$data['tanggal_pinjam']}<br>
                            <strong>Tanggal Kembali:</strong> {$data['tanggal_kembali_rencana']}<br>
                            <strong>Disetujui oleh:</strong> {$data['approved_by_name']}
                        </div>
                        
                        <p><strong>Barang yang dipinjam:</strong></p>
                        <ul>
                            {$barang_list}
                        </ul>
                        
                        <p><strong>Catatan Admin:</strong></p>
                        <p>{$data['catatan_admin']}</p>
                        
                        <p>Silakan ambil barang sesuai jadwal yang telah ditentukan.</p>
                        
                        <a href='" . getenv('APP_URL') . "/user/peminjaman/detail.php?id={$peminjaman_id}' class='button'>
                            Lihat Detail Peminjaman
                        </a>
                    </div>
                    <div class='footer'>
                        <p>Email ini dikirim otomatis oleh Sistem Inventaris Kantor</p>
                        <p>Jangan balas email ini</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $this->mailer->AltBody = "Peminjaman Anda dengan kode {$data['kode_peminjaman']} telah disetujui. Silakan ambil barang pada {$data['tanggal_pinjam']}.";
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email notification error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Send notification when peminjaman is rejected
     */
    public function notifyPeminjamanRejected($peminjaman_id) {
        try {
            $query = "SELECT p.*, u.nama_lengkap, u.email,
                     admin.nama_lengkap as rejected_by_name
                     FROM peminjaman p
                     JOIN users u ON p.user_id = u.user_id
                     LEFT JOIN users admin ON p.approved_by = admin.user_id
                     WHERE p.peminjaman_id = ?";
            
            $stmt = mysqli_prepare($this->connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $peminjaman_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            
            if (!$data || !$data['email']) {
                return false;
            }
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($data['email'], $data['nama_lengkap']);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = '❌ Peminjaman Ditolak - ' . $data['kode_peminjaman'];
            
            $this->mailer->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
                    .content { background: #f8fafc; padding: 20px; }
                    .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #dc2626; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Peminjaman Ditolak ❌</h2>
                    </div>
                    <div class='content'>
                        <p>Halo <strong>{$data['nama_lengkap']}</strong>,</p>
                        <p>Mohon maaf, pengajuan peminjaman Anda telah <strong>DITOLAK</strong>.</p>
                        
                        <div class='info-box'>
                            <strong>Kode Peminjaman:</strong> {$data['kode_peminjaman']}<br>
                            <strong>Ditolak oleh:</strong> {$data['rejected_by_name']}
                        </div>
                        
                        <p><strong>Alasan Penolakan:</strong></p>
                        <p>{$data['catatan_admin']}</p>
                        
                        <p>Silakan hubungi admin untuk informasi lebih lanjut atau ajukan peminjaman baru dengan penyesuaian.</p>
                    </div>
                    <div class='footer'>
                        <p>Email ini dikirim otomatis oleh Sistem Inventaris Kantor</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email notification error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Send reminder for upcoming return deadline
     */
    public function notifyReturnReminder($peminjaman_id) {
        try {
            $query = "SELECT p.*, u.nama_lengkap, u.email,
                     DATEDIFF(p.tanggal_kembali_rencana, CURDATE()) as days_left
                     FROM peminjaman p
                     JOIN users u ON p.user_id = u.user_id
                     WHERE p.peminjaman_id = ? AND p.status = 'ongoing'";
            
            $stmt = mysqli_prepare($this->connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $peminjaman_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            
            if (!$data || !$data['email']) {
                return false;
            }
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($data['email'], $data['nama_lengkap']);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = '⏰ Reminder: Pengembalian Barang - ' . $data['kode_peminjaman'];
            
            $days_text = $data['days_left'] == 1 ? 'besok' : "dalam {$data['days_left']} hari";
            
            $this->mailer->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #d97706; color: white; padding: 20px; text-align: center; }
                    .content { background: #f8fafc; padding: 20px; }
                    .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #d97706; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                    .button { display: inline-block; padding: 10px 20px; background: #d97706; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Reminder Pengembalian ⏰</h2>
                    </div>
                    <div class='content'>
                        <p>Halo <strong>{$data['nama_lengkap']}</strong>,</p>
                        <p>Ini adalah pengingat bahwa barang peminjaman Anda harus dikembalikan <strong>{$days_text}</strong>.</p>
                        
                        <div class='info-box'>
                            <strong>Kode Peminjaman:</strong> {$data['kode_peminjaman']}<br>
                            <strong>Deadline Pengembalian:</strong> {$data['tanggal_kembali_rencana']}<br>
                            <strong>Sisa Waktu:</strong> {$data['days_left']} hari
                        </div>
                        
                        <p>Harap kembalikan barang tepat waktu untuk menghindari denda keterlambatan.</p>
                        
                        <a href='" . getenv('APP_URL') . "/user/pengembalian/ajukan.php?peminjaman_id={$peminjaman_id}' class='button'>
                            Ajukan Pengembalian
                        </a>
                    </div>
                    <div class='footer'>
                        <p>Email ini dikirim otomatis oleh Sistem Inventaris Kantor</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email notification error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Send notification when item is late
     */
    public function notifyLateReturn($peminjaman_id) {
        try {
            $query = "SELECT p.*, u.nama_lengkap, u.email,
                     p.hari_terlambat, p.denda_keterlambatan
                     FROM peminjaman p
                     JOIN users u ON p.user_id = u.user_id
                     WHERE p.peminjaman_id = ?";
            
            $stmt = mysqli_prepare($this->connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $peminjaman_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            
            if (!$data || !$data['email']) {
                return false;
            }
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($data['email'], $data['nama_lengkap']);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = '🚨 URGENT: Peminjaman Terlambat - ' . $data['kode_peminjaman'];
            
            $denda_formatted = number_format($data['denda_keterlambatan'], 0, ',', '.');
            
            $this->mailer->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
                    .content { background: #f8fafc; padding: 20px; }
                    .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #dc2626; }
                    .warning { background: #fee2e2; padding: 15px; margin: 10px 0; border-radius: 5px; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                    .button { display: inline-block; padding: 10px 20px; background: #dc2626; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>⚠️ PEMINJAMAN TERLAMBAT</h2>
                    </div>
                    <div class='content'>
                        <p>Halo <strong>{$data['nama_lengkap']}</strong>,</p>
                        <p><strong>Peminjaman Anda sudah melewati batas waktu pengembalian!</strong></p>
                        
                        <div class='info-box'>
                            <strong>Kode Peminjaman:</strong> {$data['kode_peminjaman']}<br>
                            <strong>Deadline:</strong> {$data['tanggal_kembali_rencana']}<br>
                            <strong>Hari Terlambat:</strong> {$data['hari_terlambat']} hari
                        </div>
                        
                        <div class='warning'>
                            <strong>⚠️ Denda Keterlambatan:</strong><br>
                            <h3 style='margin: 10px 0; color: #dc2626;'>Rp {$denda_formatted}</h3>
                            <p style='margin: 5px 0; font-size: 14px;'>Denda akan terus bertambah setiap hari keterlambatan</p>
                        </div>
                        
                        <p><strong>Segera kembalikan barang untuk menghindari denda yang lebih besar!</strong></p>
                        
                        <a href='" . getenv('APP_URL') . "/user/pengembalian/ajukan.php?peminjaman_id={$peminjaman_id}' class='button'>
                            Kembalikan Sekarang
                        </a>
                    </div>
                    <div class='footer'>
                        <p>Email ini dikirim otomatis oleh Sistem Inventaris Kantor</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email notification error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Send notification when payment is verified
     */
    public function notifyPaymentVerified($peminjaman_id) {
        try {
            $query = "SELECT p.*, u.nama_lengkap, u.email
                     FROM peminjaman p
                     JOIN users u ON p.user_id = u.user_id
                     WHERE p.peminjaman_id = ?";
            
            $stmt = mysqli_prepare($this->connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $peminjaman_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            
            if (!$data || !$data['email']) {
                return false;
            }
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($data['email'], $data['nama_lengkap']);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = '✅ Pembayaran Terverifikasi - ' . $data['kode_peminjaman'];
            
            $total_formatted = number_format($data['total_bayar'], 0, ',', '.');
            
            $this->mailer->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #059669; color: white; padding: 20px; text-align: center; }
                    .content { background: #f8fafc; padding: 20px; }
                    .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #059669; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Pembayaran Terverifikasi ✅</h2>
                    </div>
                    <div class='content'>
                        <p>Halo <strong>{$data['nama_lengkap']}</strong>,</p>
                        <p>Pembayaran Anda telah <strong>TERVERIFIKASI</strong> oleh admin.</p>
                        
                        <div class='info-box'>
                            <strong>Kode Peminjaman:</strong> {$data['kode_peminjaman']}<br>
                            <strong>Total Dibayar:</strong> Rp {$total_formatted}<br>
                            <strong>Status:</strong> Lunas
                        </div>
                        
                        <p>Anda sekarang dapat mengambil barang sesuai jadwal yang telah ditentukan.</p>
                    </div>
                    <div class='footer'>
                        <p>Email ini dikirim otomatis oleh Sistem Inventaris Kantor</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email notification error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
}

// Helper function to send notifications easily
function sendNotification($type, $peminjaman_id) {
    global $connection;
    $emailNotif = new EmailNotification($connection);
    
    switch ($type) {
        case 'approved':
            return $emailNotif->notifyPeminjamanApproved($peminjaman_id);
        case 'rejected':
            return $emailNotif->notifyPeminjamanRejected($peminjaman_id);
        case 'reminder':
            return $emailNotif->notifyReturnReminder($peminjaman_id);
        case 'late':
            return $emailNotif->notifyLateReturn($peminjaman_id);
        case 'payment_verified':
            return $emailNotif->notifyPaymentVerified($peminjaman_id);
        default:
            return false;
    }
}
