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

// Ambil data
$stmt = $con->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();

if (!$produk) {
    header("Location: produk.php?msg=" . urlencode("❌ Produk tidak ditemukan"));
    exit;
}

// Proses update
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $harga = floatval($_POST['harga'] ?? 0);
    $stok = intval($_POST['stok'] ?? 0);
    $kategori_id = !empty($_POST['kategori_id']) ? intval($_POST['kategori_id']) : null;

    if (!$nama || $harga <= 0) {
        $msg = "❌ Nama dan harga wajib diisi.";
    } else {
        $stmt = $con->prepare("
            UPDATE produk 
            SET nama = ?, harga = ?, stok = ?, kategori_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sdiii", $nama, $harga, $stok, $kategori_id, $id);
        if ($stmt->execute()) {
            header("Location: produk.php?msg=" . urlencode("✅ Produk berhasil diperbarui!"));
            exit;
        } else {
            $msg = "❌ Gagal: " . $stmt->error;
        }
    }
}

// Kategori
$kat = $con->query("SELECT id, nama FROM kategori ORDER BY nama");
$kategori_list = $kat->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>✏️ Edit Produk</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <h2>✏️ Edit Produk: <?= htmlspecialchars($produk['nama']) ?></h2>

  <?php if ($msg): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label>Nama Produk</label>
      <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($produk['nama']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Harga (Rp)</label>
      <input type="number" name="harga" class="form-control" value="<?= $produk['harga'] ?>" min="0" step="100" required>
    </div>
    <div class="mb-3">
      <label>Stok</label>
      <input type="number" name="stok" class="form-control" value="<?= $produk['stok'] ?>" min="0" required>
    </div>
    <div class="mb-3">
      <label>Kategori</label>
      <select name="kategori_id" class="form-select">
        <option value="">– Pilih –</option>
        <?php foreach ($kategori_list as $k): ?>
          <option value="<?= $k['id'] ?>" <?= $k['id'] == $produk['kategori_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($k['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-warning">✏️ Perbarui</button>
      <a href="produk.php" class="btn btn-secondary">❌ Batal</a>
    </div>
  </form>
</div>
</body>
</html>