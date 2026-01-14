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

$total = 0;

// 3. Header HTTP untuk Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_WarungMaju_" . date('Ymd') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// 4. Output dalam format Tabel HTML agar rapi di Excel
?>
<meta charset="UTF-8">
<table border="1">
    <thead>
        <tr style="background-color: #4CAF50; color: white; font-weight: bold;">
            <th width="150">Tanggal</th>
            <th width="100">Kasir</th>
            <th width="200">Produk</th>
            <th width="50">Qty</th>
            <th width="120">Harga Satuan</th>
            <th width="120">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): 
            $total += $row['subtotal'];
        ?>
            <tr>
                <td style="vertical-align: top;"><?php echo date('Y-m-d H:i', strtotime($row['tanggal'])); ?></td>
                <td><?php echo htmlspecialchars($row['kasir']); ?></td>
                <td><?php echo htmlspecialchars($row['produk']); ?></td>
                <td align="center"><?php echo $row['qty']; ?></td>
                <td align="right"><?php echo number_format($row['harga_satuan'], 0, ',', '.'); ?></td>
                <td align="right"><?php echo number_format($row['subtotal'], 0, ',', '.'); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
    <tfoot>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <td colspan="5" align="right">TOTAL PENJUALAN:</td>
            <td align="right">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
        </tr>
    </tfoot>
</table>
<?php
exit;
?>