<?php
session_start();

// Cek apakah ada ID produk yang dikirim lewat tombol
if (isset($_GET['id'])) {
    $id_produk = intval($_GET['id']);

    // Jika produk sudah ada di keranjang, tambah jumlahnya (Quantity)
    if (isset($_SESSION['cart'][$id_produk])) {
        $_SESSION['cart'][$id_produk] += 1;
    } else {
        // Jika belum ada, masukkan produk baru dengan jumlah 1
        $_SESSION['cart'][$id_produk] = 1;
    }
}

// Setelah berhasil mencatat ke keranjang, otomatis balikkan halaman ke index.php
header("Location: index.php");
exit();
?>