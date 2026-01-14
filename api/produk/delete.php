<?php
require_once '../../config/config.php';

$id = $_POST['id'] ?? '';
$stmt = $con->prepare("DELETE FROM produk WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Produk berhasil dihapus!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus produk: ' . $stmt->error]);
}
$stmt->close();
exit;
?>