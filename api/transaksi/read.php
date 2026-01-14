<?php
// api/transaksi/read.php — Diperbaiki
header("Content-Type: application/json; charset=utf-8");

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

require_once '../../config/config.php';

$type = $_GET['type'] ?? 'default';
$start = $_GET['start'] ?? date('Y-m-d', strtotime('-7 days'));
$end = $_GET['end'] ?? date('Y-m-d');

try {
    // —————— MODE 1: Transaksi Hari Ini ——————
    if ($type === 'hari_ini') {
        $stmt = $con->prepare("
            SELECT COUNT(*) as count 
            FROM transaksi 
            WHERE DATE(tanggal) = CURDATE()
        ");
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        echo json_encode($row);
        exit;
    }

    // —————— MODE 2: Ringkasan Minggu ——————
    if ($type === 'summary') {
        $stmt1 = $con->prepare("
            SELECT COUNT(*) as transaksi_minggu 
            FROM transaksi 
            WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $stmt1->execute();
        $transaksi_minggu = $stmt1->get_result()->fetch_assoc()['transaksi_minggu'];

        $stmt2 = $con->prepare("
            SELECT COALESCE(SUM(total), 0) as total_penjualan 
            FROM transaksi 
            WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $stmt2->execute();
        $total_penjualan = $stmt2->get_result()->fetch_assoc()['total_penjualan'];

        echo json_encode([
            'transaksi_minggu' => (int)$transaksi_minggu,
            'total_penjualan' => (float)$total_penjualan
        ]);
        exit;
    }

    // —————— MODE 3: Data Detail ——————
    $stmt = $con->prepare("
        SELECT 
            t.id as transaksi_id,
            DATE_FORMAT(t.tanggal, '%d %b %Y %H:%i') as tanggal,
            u.username as kasir,
            p.nama as produk,
            dt.qty,
            dt.harga_satuan,
            (dt.qty * dt.harga_satuan) as subtotal
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
    $data = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'msg' => '❌ ' . $e->getMessage()
    ]);
}
?>