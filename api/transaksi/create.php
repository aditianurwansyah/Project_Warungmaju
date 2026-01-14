<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

require_once '../../config/config.php';

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'] ?? [];

if (empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'msg' => 'Tidak ada item']);
    exit;
}

try {
    // Mulai transaksi
    $con->begin_transaction();

    // Buat header transaksi
    $stmtTransaksi = $con->prepare("INSERT INTO transaksi (user_id, total) VALUES (?, ?)");
    $total = array_sum(array_map(fn($item) => $item['harga'] * $item['qty'], $items));
    $stmtTransaksi->bind_param("id", $_SESSION['user_id'], $total);
    if (!$stmtTransaksi->execute()) {
        throw new Exception("Gagal membuat transaksi: " . $con->error);
    }
    $transaksi_id = $con->insert_id;

    // Simpan detail & kurangi stok
    $stmtDetail = $con->prepare("
        INSERT INTO detail_transaksi (transaksi_id, produk_id, qty, harga_satuan) 
        VALUES (?, ?, ?, ?)
    ");
    $stmtUpdateStok = $con->prepare("UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?");

    foreach ($items as $item) {
        $produk_id = intval($item['id']);
        $qty = intval($item['qty']);
        $harga_satuan = floatval($item['harga']);

        // Cek stok cukup
        $cekStok = $con->prepare("SELECT stok FROM produk WHERE id = ?");
        $cekStok->bind_param("i", $produk_id);
        $cekStok->execute();
        $stokRow = $cekStok->get_result()->fetch_assoc();
        $stokSaatIni = $stokRow['stok'] ?? 0;

        if ($stokSaatIni < $qty) {
            throw new Exception("Stok {$item['nama']} tidak mencukupi (tersedia: $stokSaatIni)");
        }

        // Insert detail
        $stmtDetail->bind_param("iiid", $transaksi_id, $produk_id, $qty, $harga_satuan);
        if (!$stmtDetail->execute()) {
            throw new Exception("Gagal simpan detail: " . $con->error);
        }

        // Kurangi stok
        $stmtUpdateStok->bind_param("iii", $qty, $produk_id, $qty);
        if (!$stmtUpdateStok->execute()) {
            throw new Exception("Gagal kurangi stok: " . $con->error);
        }
    }

    // Commit
    $con->commit();
    echo json_encode([
        'success' => true,
        'transaksi_id' => $transaksi_id,
        'msg' => '✅ Transaksi berhasil'
    ]);

} catch (Exception $e) {
    // Rollback jika error
    $con->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'msg' => '❌ Gagal: ' . $e->getMessage()
    ]);
}
?>