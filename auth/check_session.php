<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    echo json_encode([
        'logged' => true,
        'user_id' => (int)$_SESSION['user_id'],
        'username' => htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES),
        'role' => $_SESSION['role']
    ]);
} else {
    echo json_encode(['logged' => false]);
}
?>