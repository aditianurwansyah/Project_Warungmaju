<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

require_once 'config/config.php'; // ← Pastikan ini MySQLi

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $con->prepare("DELETE FROM produk WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $msg = "✅ Produk berhasil dihapus!";
    } else {
        $msg = "❌ Gagal menghapus produk: " . $stmt->error;
    }
    header("Location: produk.php?msg=" . urlencode($msg));
    exit;
}

// Ambil data
$sql = "
    SELECT p.id, p.nama, p.harga, p.stok, 
           COALESCE(k.nama, '-') as kategori,
           DATE_FORMAT(p.created_at, '%d %b %Y') as created_at
    FROM produk p 
    LEFT JOIN kategori k ON p.kategori_id = k.id 
    ORDER BY p.id DESC
";
$result = $con->query($sql);
$produk_list = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>📦 Daftar Produk</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .action-btn { margin: 0 2px; }
    .toast { position: fixed; top: 20px; right: 20px; z-index: 9999; }
    .loading { text-align: center; padding: 20px; color: #7f8c8d; }
    .modal-title i { margin-right: 8px; }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>📦 Daftar Produk</h2>
    <a href="tambah_produk.php" class="btn btn-success">➕ Tambah Produk</a>
  </div> 

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-info alert-dismissible fade show">
      <?= htmlspecialchars($_GET['msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle">
      <thead class="table-dark">
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>Harga</th>
          <th>Stok</th>
          <th>Kategori</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($produk_list)): ?>
          <tr><td colspan="6" class="text-center">Tidak ada produk</td></tr>
        <?php else: ?>
          <?php foreach ($produk_list as $index => $p): ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td><?= htmlspecialchars($p['nama']) ?></td>
              <td>Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
              <td><?= $p['stok'] ?></td>
              <td><?= htmlspecialchars($p['kategori']) ?></td>
              <td class="text-nowrap">
                <!-- ✅ DIPERBAIKI: ke halaman detail/edit -->
                <a href="detail_produk.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-info">👁️ Detail</a>
                <a href="edit_produk.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                <a href="?action=delete&id=<?= $p['id'] ?>"  
                   onclick="return confirm('Yakin hapus <?= addslashes(htmlspecialchars($p['nama'])) ?>?')"
                   class="btn btn-sm btn-danger">🗑️ Hapus</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="text-center mt-4">
    <a href="dashboard.html" class="btn btn-outline-secondary">🏠 Kembali ke Dashboard</a>
  </div> 
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>