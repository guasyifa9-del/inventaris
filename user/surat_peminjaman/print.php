<?php
/**
 * ============================================================================
 * SURAT PEMINJAMAN - PRINT PDF (Bukti Pengambilan Barang)
 * Untuk Kepala Departemen
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
    die('Akses ditolak!');
}

$surat_id = (int)($_GET['id'] ?? 0);
if (!$surat_id) {
    die('ID Surat tidak valid');
}

// Get surat detail - only own surat
$query = "
    SELECT sp.*, 
           u_kepala.nama_lengkap as kepala_nama,
           u_kepala.departemen as dept_kepala,
           u_approve.nama_lengkap as approved_by_nama
    FROM surat_peminjaman sp
    LEFT JOIN users u_kepala ON sp.kepala_departemen_id = u_kepala.user_id
    LEFT JOIN users u_approve ON sp.disetujui_oleh = u_approve.user_id
    WHERE sp.surat_id = $surat_id
    AND sp.kepala_departemen_id = {$user['user_id']}
";
$result = mysqli_query($connection, $query);
$surat = mysqli_fetch_assoc($result);

if (!$surat) {
    die('Surat tidak ditemukan');
}

// Get items
$items_query = "
    SELECT spd.*, b.nama_barang, b.kode_barang
    FROM surat_peminjaman_detail spd
    JOIN barang b ON spd.barang_id = b.barang_id
    WHERE spd.surat_id = $surat_id
";
$items_result = mysqli_query($connection, $items_query);
$items = [];
if ($items_result) {
    while ($row = mysqli_fetch_assoc($items_result)) {
        $items[] = $row;
    }
}

// Status text
$status_text = [
    'draft' => 'Konsep',
    'dikirim' => 'Menunggu Approval',
    'disetujui' => 'DISETUJUI',
    'ditolak' => 'Ditolak',
    'sedang_digunakan' => 'Sedang Digunakan',
    'selesai' => 'Selesai',
    'cancelled' => 'Dibatalkan'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Peminjaman - <?= htmlspecialchars($surat['nomor_surat']) ?></title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Times New Roman', serif; 
            font-size: 12pt; 
            line-height: 1.5;
            background: #f0f0f0;
            padding: 20px;
        }
        
        .document {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20mm;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 18pt;
            margin-bottom: 5px;
        }
        
        .header h2 {
            font-size: 14pt;
            font-weight: normal;
        }
        
        .header p {
            font-size: 10pt;
            color: #666;
        }
        
        .title {
            text-align: center;
            margin: 25px 0;
        }
        
        .title h3 {
            font-size: 14pt;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        
        .title p {
            font-size: 11pt;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            width: 160px;
            font-weight: bold;
        }
        
        .info-value {
            flex: 1;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            border: 1px solid #333;
            padding: 8px 10px;
            text-align: left;
        }
        
        th {
            background: #f5f5f5;
            font-weight: bold;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .total-row {
            background: #e8e8e8;
            font-weight: bold;
        }
        
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-box .line {
            margin-top: 80px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        
        .stamp {
            position: relative;
            display: inline-block;
        }
        
        .stamp.approved::after {
            content: 'DISETUJUI';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            font-size: 24pt;
            font-weight: bold;
            color: rgba(0, 128, 0, 0.3);
            border: 4px solid rgba(0, 128, 0, 0.3);
            padding: 10px 20px;
            border-radius: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-disetujui { background: #d4edda; color: #155724; border: 2px solid #155724; }
        .status-ditolak { background: #f8d7da; color: #721c24; border: 2px solid #721c24; }
        .status-dikirim { background: #d1ecf1; color: #0c5460; border: 2px solid #0c5460; }
        .status-draft { background: #e2e3e5; color: #383d41; border: 2px solid #383d41; }
        
        .print-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .print-buttons a, .print-buttons button {
            padding: 12px 25px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-print { background: #28a745; color: white; }
        .btn-back { background: #6c757d; color: white; }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0,0,0,0.05);
            z-index: -1;
            white-space: nowrap;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }
        
        .terms {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            font-size: 10pt;
        }
        
        .terms h4 {
            margin-bottom: 10px;
        }
        
        .terms ol {
            margin-left: 20px;
        }
    </style>
</head>
<body>
    <div class="print-buttons no-print">
        <a href="detail.php?id=<?= $surat_id ?>" class="btn-back">
            ← Kembali
        </a>
        <button class="btn-print" onclick="window.print()">
            🖨️ Cetak / Print PDF
        </button>
    </div>
    
    <?php if ($surat['status'] !== 'disetujui'): ?>
    <div class="watermark"><?= strtoupper($status_text[$surat['status']] ?? 'DRAFT') ?></div>
    <?php endif; ?>
    
    <div class="document">
        <!-- Header -->
        <div class="header">
            <h1>PT. SEGITIGA BERMUDA</h1>
            <h2>SISTEM INVENTARIS KANTOR</h2>
            <p>Jl. Contoh Alamat No. 123, Kota, Provinsi 12345</p>
            <p>Telp: (021) 123-4567 | Email: info@perusahaan.com</p>
        </div>
        
        <!-- Title -->
        <div class="title">
            <h3>SURAT BUKTI PEMINJAMAN BARANG</h3>
            <p>No: <?= htmlspecialchars($surat['nomor_surat']) ?></p>
        </div>
        
        <!-- Status -->
        <div style="text-align: center; margin-bottom: 20px;">
            <span class="status-badge status-<?= $surat['status'] ?>">
                <?= $status_text[$surat['status']] ?? $surat['status'] ?>
            </span>
        </div>
        
        <!-- Info Section -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Tanggal Pengajuan</div>
                <div class="info-value">: <?= date('d F Y', strtotime($surat['tanggal_surat'] ?? $surat['created_at'])) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Pemohon</div>
                <div class="info-value">: <?= htmlspecialchars($surat['kepala_nama']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Departemen</div>
                <div class="info-value">: <?= htmlspecialchars($surat['departemen']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Keperluan</div>
                <div class="info-value">: <?= htmlspecialchars($surat['keperluan']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Periode Peminjaman</div>
                <div class="info-value">: <?= date('d/m/Y', strtotime($surat['tanggal_mulai_pinjam'])) ?> s/d <?= date('d/m/Y', strtotime($surat['tanggal_selesai_pinjam'])) ?> (<?= $surat['durasi_hari'] ?> hari)</div>
            </div>
            <?php if ($surat['keterangan_peminjaman']): ?>
            <div class="info-row">
                <div class="info-label">Keterangan</div>
                <div class="info-value">: <?= htmlspecialchars($surat['keterangan_peminjaman']) ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Items Table -->
        <p><strong>Daftar Barang yang Dipinjam:</strong></p>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 40px;">No</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th class="text-center" style="width: 80px;">Jumlah</th>
                    <th class="text-right" style="width: 120px;">Harga/Hari</th>
                    <th class="text-right" style="width: 120px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $total = 0;
                foreach ($items as $item): 
                    $subtotal = $item['subtotal_sewa'] ?? 0;
                    $total += $subtotal;
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($item['kode_barang'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                    <td class="text-center"><?= $item['jumlah'] ?? 1 ?> unit</td>
                    <td class="text-right"><?= formatRupiah($item['harga_sewa_per_hari'] ?? 0) ?></td>
                    <td class="text-right"><?= formatRupiah($subtotal) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-right">TOTAL BIAYA:</td>
                    <td class="text-right"><?= formatRupiah($surat['total_biaya'] ?? $total) ?></td>
                </tr>
            </tfoot>
        </table>
        
        <!-- Terms -->
        <div class="terms">
            <h4>Ketentuan Peminjaman:</h4>
            <ol>
                <li>Peminjam bertanggung jawab penuh atas barang yang dipinjam.</li>
                <li>Barang harus dikembalikan dalam kondisi baik sesuai tanggal yang ditentukan.</li>
                <li>Kerusakan atau kehilangan barang menjadi tanggung jawab peminjam.</li>
                <li>Surat ini sebagai bukti sah pengambilan barang dari gudang inventaris.</li>
                <li>Perpanjangan peminjaman harus mengajukan surat baru.</li>
            </ol>
        </div>
        
        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <p>Pemohon,</p>
                <div class="line">
                    <?= htmlspecialchars($surat['kepala_nama']) ?><br>
                    <small>Kepala Departemen <?= htmlspecialchars($surat['departemen']) ?></small>
                </div>
            </div>
            <div class="signature-box">
                <p>Disetujui oleh,</p>
                <div class="line <?= $surat['status'] === 'disetujui' ? 'stamp approved' : '' ?>">
                    <?php if ($surat['status'] === 'disetujui' && $surat['approved_by_nama']): ?>
                        <?= htmlspecialchars($surat['approved_by_nama']) ?><br>
                        <small>Kepala Inventaris</small><br>
                        <small><?= date('d/m/Y', strtotime($surat['tanggal_persetujuan'])) ?></small>
                    <?php else: ?>
                        _______________________<br>
                        <small>Kepala Inventaris</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Receiver Section (only for approved) -->
        <?php if ($surat['status'] === 'disetujui'): ?>
        <div style="margin-top: 40px; padding: 15px; border: 2px dashed #333;">
            <p><strong>BUKTI SERAH TERIMA BARANG</strong></p>
            <p style="margin-top: 15px;">Dengan ini menyatakan bahwa barang-barang tersebut di atas telah diterima dalam kondisi baik.</p>
            
            <div class="signature-section" style="margin-top: 20px;">
                <div class="signature-box">
                    <p>Yang Menyerahkan,</p>
                    <div class="line">
                        _______________________<br>
                        <small>Petugas Gudang</small><br>
                        <small>Tanggal: ___/___/______</small>
                    </div>
                </div>
                <div class="signature-box">
                    <p>Yang Menerima,</p>
                    <div class="line">
                        _______________________<br>
                        <small>Perwakilan Dept. <?= htmlspecialchars($surat['departemen']) ?></small><br>
                        <small>Tanggal: ___/___/______</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="footer">
            <p>Dicetak pada: <?= date('d F Y H:i:s') ?> | Sistem Inventaris Kantor</p>
            <p>Dokumen ini sah tanpa tanda tangan basah jika sudah disetujui dalam sistem.</p>
        </div>
    </div>
    
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autoprint') === '1') {
            window.onload = function() { window.print(); };
        }
    </script>
</body>
</html>
