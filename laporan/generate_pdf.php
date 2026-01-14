<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("❌ Akses ditolak. Silakan login.");
}

require_once '../config/config.php';

// 1. Ambil & Validasi Parameter
$start = $_GET['start'] ?? date('Y-m-d', strtotime('-7 days'));
$end = $_GET['end'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = date('Y-m-d', strtotime('-7 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) $end = date('Y-m-d');

// 2. Query Data
$stmt = $con->prepare("
    SELECT 
        t.tanggal, 
        u.username AS kasir,
        p.nama AS produk,
        dt.qty,
        dt.harga_satuan,
        (dt.qty * dt.harga_satuan) AS subtotal
    FROM transaksi t
    INNER JOIN users u ON t.user_id = u.id
    INNER JOIN detail_transaksi dt ON t.id = dt.transaksi_id
    INNER JOIN produk p ON dt.produk_id = p.id
    WHERE DATE(t.tanggal) BETWEEN ? AND ?
    ORDER BY t.tanggal DESC
");
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    $total += $row['subtotal'];
}

// 3. Bangun Konten PDF
$content = "BT\n/F1 16 Tf\n50 800 Td\n(LAPORAN PENJUALAN WARUNG MAJU) Tj\nET\n";
$content .= "BT\n/F1 10 Tf\n50 780 Td\n(Periode: " . $start . " s/d " . $end . ") Tj\nET\n";

// Header Tabel
$content .= "BT /F1 10 Tf\n";
$content .= "50 740 Td (Tanggal) Tj\n80 0 Td (Kasir) Tj\n90 0 Td (Produk) Tj\n140 0 Td (Qty) Tj\n40 0 Td (Harga) Tj\n80 0 Td (Subtotal) Tj\nET\n";
$content .= "50 735 m 550 735 l s\n"; // Garis horizontal

// Isi Data
$y = 720;
foreach ($data as $row) {
    if ($y < 50) break; // Batas bawah halaman
    
    $tgl   = date('d/m/y', strtotime($row['tanggal']));
    $ksr   = substr($row['kasir'], 0, 10);
    $prd   = substr($row['produk'], 0, 20);
    $qty   = $row['qty'];
    $hrg   = number_format($row['harga_satuan'], 0, ',', '.');
    $sub   = number_format($row['subtotal'], 0, ',', '.');

    $content .= "BT /F1 9 Tf\n";
    $content .= "50 $y Td ($tgl) Tj\n";
    $content .= "80 0 Td ($ksr) Tj\n";
    $content .= "90 0 Td ($prd) Tj\n";
    $content .= "140 0 Td ($qty) Tj\n";
    $content .= "40 0 Td (Rp $hrg) Tj\n";
    $content .= "80 0 Td (Rp $sub) Tj\n";
    $content .= "ET\n";
    $y -= 15;
}

$content .= "50 " . ($y+5) . " m 550 " . ($y+5) . " l s\n";
$content .= "BT /F1 11 Tf\n400 " . ($y-10) . " Td (TOTAL: Rp " . number_format($total, 0, ',', '.') . ") Tj ET\n";

// 4. Struktur Objek PDF
$objs = [];
$objs[] = "%PDF-1.4\n";
$objs[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
$objs[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
$objs[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 595 842] /Contents 5 0 R >>\nendobj\n";
$objs[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
$objs[] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream\nendobj\n";

// 5. Output dengan kalkulasi XREF yang tepat
$output = "";
$offsets = [];
foreach ($objs as $i => $obj) {
    if ($i > 0) $offsets[$i] = strlen($output);
    $output .= $obj;
}

$xref_pos = strlen($output);
$output .= "xref\n0 6\n0000000000 65535 f \n";
for ($i = 1; $i <= 5; $i++) {
    $output .= sprintf("%010d 00000 n \n", $offsets[$i]);
}
$output .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref_pos\n%%EOF";

header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=Laporan_" . date('Ymd') . ".pdf");
echo $output;
exit;