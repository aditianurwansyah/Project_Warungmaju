<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

require_once 'config/config.php';

$msg = '';
$nama = '';
$harga = '';
$stok = 5; // default stok 5
$kategori_id = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $harga = floatval($_POST['harga'] ?? 0);
    $stok = intval($_POST['stok'] ?? 5);
    $kategori_id = !empty($_POST['kategori_id']) ? intval($_POST['kategori_id']) : null;

    // Validasi
    if (!$nama) {
        $msg = "❌ Nama produk wajib diisi.";
    } elseif ($harga <= 0) {
        $msg = "❌ Harga harus lebih dari 0.";
    } elseif ($stok < 0) {
        $msg = "❌ Stok tidak boleh negatif.";
    } else {
        // Simpan ke database
        $stmt = $con->prepare("
            INSERT INTO produk (nama, harga, stok, kategori_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("sdii", $nama, $harga, $stok, $kategori_id);

        if ($stmt->execute()) {
            header("Location: produk.php?msg=" . urlencode("✅ Produk '$nama' berhasil ditambahkan!"));
            exit;
        } else {
            $msg = "❌ Gagal menyimpan: " . $con->error;
        }
    }
}

// Ambil kategori untuk dropdown
$kat_result = $con->query("SELECT id, nama FROM kategori ORDER BY nama");
$kategori_list = $kat_result ? $kat_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>➕ Tambah Produk</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .card { box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); }
  </style>
</head>
<body class="bg-light">
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>➕ Tambah Produk Baru</h2>
      <a href="produk.php" class="btn btn-outline-secondary">⬅️ Kembali ke Daftar</a>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-<?= strpos($msg, '❌') !== false ? 'danger' : 'success' ?> alert-dismissible fade show">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <form method="POST">
          <div class="mb-3">
            <label for="nama" class="form-label">📦 Nama Produk</label>
            <input 
              type="text" 
              id="nama" 
              name="nama" 
              class="form-control" 
              value="<?= htmlspecialchars($nama) ?>" 
              required 
              maxlength="100"
              placeholder="Contoh: Beras 1kg, Teh Botol"
            >
          </div>

          <div class="mb-3">
            <label for="harga" class="form-label">💰 Harga (Rp)</label>
            <input 
              type="number" 
              id="harga" 
              name="harga" 
              class="form-control" 
              value="<?= $harga ?>" 
              min="0" 
              step="100"
              required
              placeholder="Contoh: 12000"
            >
            <div class="form-text">Harga per unit</div>
          </div>

          <div class="mb-3">
            <label for="stok" class="form-label">📦 Stok Awal</label>
            <input 
              type="number" 
              id="stok" 
              name="stok" 
              class="form-control" 
              value="<?= $stok ?>" 
              min="0"
              required
              placeholder="Contoh: 50"
            >
            <div class="form-text">Jumlah stok awal yang tersedia</div>
          </div>

          <div class="mb-3">
            <label for="kategori_id" class="form-label">🏷️ Kategori</label>
            <select id="kategori_id" name="kategori_id" class="form-select" required>
              <option value="">– Pilih Kategori –</option>
              <?php foreach ($kategori_list as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $k['id'] == $kategori_id ? 'selected' : '' ?>>
                  <?= htmlspecialchars($k['nama']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">💾 Simpan Produk</button>
            <button type="reset" class="btn btn-secondary">🧹 Reset</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>