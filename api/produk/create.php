<?php
header("Content-Type: application/json; charset=utf-8");
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false, 'msg' => 'Unauthorized']));

require_once '../../config/config.php';

$data = json_decode(file_get_contents('php://input'), true);
$nama = trim($data['nama'] ?? '');
$harga = floatval($data['harga'] ?? 0);
$stok = intval($data['stok'] ?? 0);
$kategori_id = !empty($data['kategori_id']) ? intval($data['kategori_id']) : null;

if (!$nama || $harga <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'msg' => 'Nama dan harga wajib diisi dengan nilai valid']);
    exit;
}

$stmt = $con->prepare("
    INSERT INTO produk (nama, harga, stok, kategori_id) 
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("sdii", $nama, $harga, $stok, $kategori_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'id' => $con->insert_id,
        'data' => compact('nama', 'harga', 'stok', 'kategori_id')
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Gagal menyimpan: ' . $con->error]);
}
?>