<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

require_once 'config/config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: produk.php?msg=" . urlencode("❌ ID tidak valid"));
    exit;
}

$stmt = $con->prepare("
    SELECT p.*, k.nama as kategori 
    FROM produk p 
    LEFT JOIN kategori k ON p.kategori_id = k.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();

if (!$produk) {
    header("Location: produk.php?msg=" . urlencode("❌ Produk tidak ditemukan"));
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>👁️ Detail Produk</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <div class="card">
    <div class="card-header bg-info text-white">
      <h3>📦 <?= htmlspecialchars($produk['nama']) ?></h3>
    </div>
    <div class="card-body">
      <table class="table table-borderless">
        <tr><th style="width:150px">ID</th><td><?= $produk['id'] ?></td></tr>
        <tr><th>Harga</th><td>Rp <?= number_format($produk['harga'], 0, ',', '.') ?></td></tr>
        <tr><th>Stok</th><td><?= $produk['stok'] ?> unit</td></tr>
        <tr><th>Kategori</th><td><?= htmlspecialchars($produk['kategori'] ?? '–') ?></td></tr>
        <tr><th>Ditambahkan</th><td><?= date('d M Y H:i', strtotime($produk['created_at'])) ?></td></tr>
      </table>
      
      <div class="mt-4">
        <a href="edit_produk.php?id=<?= $produk['id'] ?>" class="btn btn-warning">✏️ Edit</a>
        <a href="produk.php" class="btn btn-secondary">⬅️ Kembali</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>