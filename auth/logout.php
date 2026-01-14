<?php
session_start();
session_destroy(); // Hapus semua session
header("Location: ../login.html");
exit;
?> 