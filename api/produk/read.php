<?php
// api/produk/read.php — Diperbaiki
header("Content-Type: application/json; charset=utf-8");

session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode([]));

require_once '../../config/config.php';

$type = $_GET['type'] ?? 'all';

try {
    if ($type === 'summary') {
        $stmt = $con->prepare("SELECT COUNT(*) as count FROM produk");
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
    } elseif ($type === 'stok_total') {
        $stmt = $con->prepare("SELECT COALESCE(SUM(stok), 0) as total FROM produk");
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
    } else {
        $stmt = $con->prepare("
            SELECT p.*, k.nama as kategori
            FROM produk p
            LEFT JOIN kategori k ON p.kategori_id = k.id
            ORDER BY p.id DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode($data);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>