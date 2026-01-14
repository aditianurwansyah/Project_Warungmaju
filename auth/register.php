<?php
// auth/register.php — DIPERBAIKI (tanpa error bind_param)
session_start();
header("Content-Type: text/html; charset=utf-8");

// 1. Koneksi database (pastikan path benar)
require_once '../config/config.php';

// 2. Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("<script>alert('❌ Metode tidak diizinkan'); window.location='register.html';</script>");
}

// 3. Ambil data
$username   = trim($_POST['username'] ?? '');
$password   = $_POST['password'] ?? '';
$konfirmasi = $_POST['konfirmasiPassword'] ?? '';

// 4. Validasi
if (!$username || !$password || !$konfirmasi) {
    die("<script>alert('❌ Semua field wajib diisi!'); history.back();</script>");
}

if (strlen($username) < 4) {
    die("<script>alert('❌ Username minimal 4 karakter'); history.back();</script>");
}

if (strlen($password) < 6) {
    die("<script>alert('❌ Password minimal 6 karakter'); history.back();</script>");
}

if ($password !== $konfirmasi) {
    die("<script>alert('❌ Password dan konfirmasi tidak sama!'); history.back();</script>");
}

// 5. Cek duplikat username
$check = $con->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username); // ✅ "s" = 1 string
$check->execute();
if ($check->get_result()->fetch_assoc()) {
    die("<script>alert('❌ Username sudah digunakan'); history.back();</script>");
}

// 6. 🔑 SIMPAN KE DATABASE — INI YANG DIPERBAIKI!
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Query: 3 kolom → username, password, role
$stmt = $con->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $hashed, $role); // ✅ "sss" = 3 string

$role = 'kasir'; // Default role untuk registrasi publik

if ($stmt->execute()) {
    echo "<script>
        alert('✅ Registrasi berhasil! Silakan login.');
        window.location = '../login.html';
    </script>";
} else {
    // Log error untuk debugging
    error_log("MySQL Error (register): " . $stmt->error);
    echo "<script>
        alert('❌ Gagal menyimpan: " . addslashes($con->error) . "');
        history.back();
    </script>";
}

$stmt->close();
$con->close();
?>