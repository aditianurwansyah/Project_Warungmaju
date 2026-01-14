<?php
header("Content-Type: application/json; charset=utf-8");
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'msg' => 'Metode tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$remember = $input['remember'] ?? false; // Input baru: checkbox remember me

if (!$username || !$password) {
    echo json_encode(['success' => false, 'msg' => 'Username dan password wajib diisi']);
    exit;
}

require_once '../config/config.php';

$stmt = $con->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    // 1. Kelola Session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    session_regenerate_id(true);

    // 2. Logika Cookie (Remember Me)
    if ($remember) {
        // Set cookie selama 30 hari (30 * 24 * 60 * 60)
        $expiry = time() + (30 * 24 * 60 * 60);
        
        // Simpan username di cookie (Hanya contoh, idealnya gunakan Token yang di-hash)
        setcookie('user_login', $user['username'], $expiry, "/", "", false, true); 
        // Parameter: Nama, Nilai, Expired, Path, Domain, Secure, HttpOnly
    }

    echo json_encode([
        'success' => true,
        'msg' => 'Login berhasil',
        'role' => $user['role']
    ]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'msg' => 'Username atau password salah']);
}
?>