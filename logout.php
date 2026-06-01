<?php
session_start();
// Hapus data session user
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['role']);

// Arahkan kembali ke halaman beranda
header("Location: index.php");
exit();
?>
