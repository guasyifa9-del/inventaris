<?php
/**
 * QR Code Integration
 * Generate QR codes untuk barang dan peminjaman
 * Scan QR untuk quick pickup dan return
 * 
 * INSTALASI:
 * composer require endroid/qr-code
 * 
 * FEATURES:
 * - Generate QR code untuk setiap barang
 * - Generate QR code untuk setiap peminjaman
 * - Scan QR untuk verify pickup
 * - Scan QR untuk verify return
 */

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class QRCodeManager {
    private $connection;
    private $baseUrl;
    
    public function __construct($connection) {
        $this->connection = $connection;
        $this->baseUrl = getenv('APP_URL') ?: 'http://localhost';
    }
    
    /**
     * Generate QR code for barang
     */
    public function generateBarangQR($barang_id, $save = true) {
        $stmt = mysqli_prepare($this->connection, 
            "SELECT * FROM barang WHERE barang_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $barang_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $barang = mysqli_fetch_assoc($result);
        
        if (!$barang) {
            return false;
        }
        
        // QR data contains verification URL
        $qrData = json_encode([
            'type' => 'barang',
            'id' => $barang_id,
            'kode' => $barang['kode_barang'],
            'url' => $this->baseUrl . '/qr/barang.php?id=' . $barang_id,
            'timestamp' => time()
        ]);
        
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($qrData)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->labelText($barang['nama_barang'])
            ->labelFont(new NotoSans(14))
            ->labelAlignment(new LabelAlignmentCenter())
            ->build();
        
        if ($save) {
            $filename = 'qr_barang_' . $barang_id . '.png';
            $filepath = __DIR__ . '/../uploads/qrcodes/' . $filename;
            
            // Create directory if not exists
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0777, true);
            }
            
            $result->saveToFile($filepath);
            
            // Update database
            $stmt = mysqli_prepare($this->connection,
                "UPDATE barang SET qr_code = ? WHERE barang_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $filename, $barang_id);
            mysqli_stmt_execute($stmt);
            
            return $filename;
        }
        
        return $result->getString();
    }
    
    /**
     * Generate QR code for peminjaman
     */
    public function generatePeminjamanQR($peminjaman_id, $save = true) {
        $stmt = mysqli_prepare($this->connection,
            "SELECT * FROM peminjaman WHERE peminjaman_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $peminjaman_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $peminjaman = mysqli_fetch_assoc($result);
        
        if (!$peminjaman) {
            return false;
        }
        
        // QR data for pickup/return verification
        $qrData = json_encode([
            'type' => 'peminjaman',
            'id' => $peminjaman_id,
            'kode' => $peminjaman['kode_peminjaman'],
            'user_id' => $peminjaman['user_id'],
            'url' => $this->baseUrl . '/qr/peminjaman.php?id=' . $peminjaman_id,
            'timestamp' => time(),
            'signature' => $this->generateSignature($peminjaman_id)
        ]);
        
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->labelText($peminjaman['kode_peminjaman'])
            ->labelFont(new NotoSans(14))
            ->labelAlignment(new LabelAlignmentCenter())
            ->build();
        
        if ($save) {
            $filename = 'qr_peminjaman_' . $peminjaman_id . '.png';
            $filepath = __DIR__ . '/../uploads/qrcodes/' . $filename;
            
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0777, true);
            }
            
            $result->saveToFile($filepath);
            return $filename;
        }
        
        return $result->getString();
    }
    
    /**
     * Generate signature for QR verification
     */
    private function generateSignature($peminjaman_id) {
        $secret = getenv('QR_SECRET') ?: 'your-qr-secret-key';
        return hash_hmac('sha256', $peminjaman_id, $secret);
    }
    
    /**
     * Verify QR signature
     */
    public function verifySignature($peminjaman_id, $signature) {
        $expected = $this->generateSignature($peminjaman_id);
        return hash_equals($expected, $signature);
    }
    
    /**
     * Process QR scan for pickup
     */
    public function processPickup($qrData, $admin_id) {
        $data = json_decode($qrData, true);
        
        if (!$data || $data['type'] !== 'peminjaman') {
            return ['success' => false, 'message' => 'QR Code tidak valid'];
        }
        
        if (!$this->verifySignature($data['id'], $data['signature'])) {
            return ['success' => false, 'message' => 'QR Code tidak terverifikasi'];
        }
        
        $peminjaman_id = $data['id'];
        
        // Check peminjaman status
        $stmt = mysqli_prepare($this->connection,
            "SELECT * FROM peminjaman WHERE peminjaman_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $peminjaman_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $peminjaman = mysqli_fetch_assoc($result);
        
        if (!$peminjaman) {
            return ['success' => false, 'message' => 'Peminjaman tidak ditemukan'];
        }
        
        if ($peminjaman['status'] !== 'approved') {
            return ['success' => false, 'message' => 'Peminjaman belum disetujui'];
        }
        
        // Update status to ongoing
        mysqli_begin_transaction($this->connection);
        
        try {
            $stmt = mysqli_prepare($this->connection,
                "UPDATE peminjaman SET status = 'ongoing', 
                 approved_by = ?, approved_at = NOW() 
                 WHERE peminjaman_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $admin_id, $peminjaman_id);
            mysqli_stmt_execute($stmt);
            
            // Update barang availability
            $stmt = mysqli_prepare($this->connection,
                "UPDATE barang b
                 JOIN peminjaman_detail pd ON b.barang_id = pd.barang_id
                 SET b.jumlah_tersedia = b.jumlah_tersedia - pd.jumlah
                 WHERE pd.peminjaman_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $peminjaman_id);
            mysqli_stmt_execute($stmt);
            
            // Log activity
            $log_stmt = mysqli_prepare($this->connection,
                "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan) 
                 VALUES (?, 'QR_PICKUP', ?)");
            $keterangan = "Pickup peminjaman {$peminjaman['kode_peminjaman']} via QR scan";
            mysqli_stmt_bind_param($log_stmt, "is", $admin_id, $keterangan);
            mysqli_stmt_execute($log_stmt);
            
            mysqli_commit($this->connection);
            
            return [
                'success' => true, 
                'message' => 'Pickup berhasil diverifikasi',
                'data' => $peminjaman
            ];
            
        } catch (Exception $e) {
            mysqli_rollback($this->connection);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process QR scan for return
     */
    public function processReturn($qrData, $admin_id) {
        $data = json_decode($qrData, true);
        
        if (!$data || $data['type'] !== 'peminjaman') {
            return ['success' => false, 'message' => 'QR Code tidak valid'];
        }
        
        if (!$this->verifySignature($data['id'], $data['signature'])) {
            return ['success' => false, 'message' => 'QR Code tidak terverifikasi'];
        }
        
        $peminjaman_id = $data['id'];
        
        // Check peminjaman status
        $stmt = mysqli_prepare($this->connection,
            "SELECT * FROM peminjaman WHERE peminjaman_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $peminjaman_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $peminjaman = mysqli_fetch_assoc($result);
        
        if (!$peminjaman) {
            return ['success' => false, 'message' => 'Peminjaman tidak ditemukan'];
        }
        
        if (!in_array($peminjaman['status'], ['ongoing', 'late'])) {
            return ['success' => false, 'message' => 'Peminjaman tidak dalam status ongoing'];
        }
        
        mysqli_begin_transaction($this->connection);
        
        try {
            // Update peminjaman status
            $stmt = mysqli_prepare($this->connection,
                "UPDATE peminjaman SET 
                 status = 'returned',
                 tanggal_kembali_actual = NOW(),
                 jam_kembali_actual = NOW()
                 WHERE peminjaman_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $peminjaman_id);
            mysqli_stmt_execute($stmt);
            
            // Update barang availability
            $stmt = mysqli_prepare($this->connection,
                "UPDATE barang b
                 JOIN peminjaman_detail pd ON b.barang_id = pd.barang_id
                 SET b.jumlah_tersedia = b.jumlah_tersedia + pd.jumlah
                 WHERE pd.peminjaman_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $peminjaman_id);
            mysqli_stmt_execute($stmt);
            
            // Create pengembalian record
            $stmt = mysqli_prepare($this->connection,
                "INSERT INTO pengembalian 
                 (peminjaman_id, tanggal_pengembalian, diterima_oleh, kondisi_umum)
                 VALUES (?, NOW(), ?, 'Baik')");
            mysqli_stmt_bind_param($stmt, "ii", $peminjaman_id, $admin_id);
            mysqli_stmt_execute($stmt);
            
            // Log activity
            $log_stmt = mysqli_prepare($this->connection,
                "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan) 
                 VALUES (?, 'QR_RETURN', ?)");
            $keterangan = "Return peminjaman {$peminjaman['kode_peminjaman']} via QR scan";
            mysqli_stmt_bind_param($log_stmt, "is", $admin_id, $keterangan);
            mysqli_stmt_execute($log_stmt);
            
            mysqli_commit($this->connection);
            
            return [
                'success' => true,
                'message' => 'Pengembalian berhasil diverifikasi',
                'data' => $peminjaman
            ];
            
        } catch (Exception $e) {
            mysqli_rollback($this->connection);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Batch generate QR codes for all barang
     */
    public function generateAllBarangQR() {
        $result = mysqli_query($this->connection, 
            "SELECT barang_id FROM barang WHERE status = 'active'");
        
        $count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            if ($this->generateBarangQR($row['barang_id'])) {
                $count++;
            }
        }
        
        return $count;
    }
}

/**
 * QR Scanner Page (HTML)
 * File: /qr/scanner.php
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Scan QR Code</h1>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div id="reader" class="mb-4"></div>
            
            <div id="result" class="hidden">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <h3 class="font-semibold text-green-800 mb-2">Scan Berhasil!</h3>
                    <div id="result-data" class="text-sm text-gray-700"></div>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="processPickup()" 
                            class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-box-open mr-2"></i>Proses Pickup
                    </button>
                    <button onclick="processReturn()" 
                            class="flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700">
                        <i class="fas fa-undo mr-2"></i>Proses Return
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let scannedData = null;
        
        function onScanSuccess(decodedText, decodedResult) {
            try {
                scannedData = JSON.parse(decodedText);
                document.getElementById('result').classList.remove('hidden');
                document.getElementById('result-data').innerHTML = `
                    <p><strong>Type:</strong> ${scannedData.type}</p>
                    <p><strong>Kode:</strong> ${scannedData.kode}</p>
                    <p><strong>ID:</strong> ${scannedData.id}</p>
                `;
                
                // Stop scanning
                html5QrCode.stop();
            } catch (e) {
                alert('QR Code tidak valid');
            }
        }
        
        function onScanFailure(error) {
            // Ignore scan failures
        }
        
        // Initialize scanner
        let html5QrCode = new Html5Qrcode("reader");
        html5QrCode.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            onScanSuccess,
            onScanFailure
        );
        
        async function processPickup() {
            if (!scannedData) return;
            
            try {
                const response = await fetch('/api/qr/pickup', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(scannedData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Pickup berhasil diverifikasi!');
                    window.location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }
        
        async function processReturn() {
            if (!scannedData) return;
            
            try {
                const response = await fetch('/api/qr/return', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(scannedData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Return berhasil diverifikasi!');
                    window.location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }
    </script>
</body>
</html>
