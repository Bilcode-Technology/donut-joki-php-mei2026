<?php
session_start();
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1; 
    if ($user_id == 0) { $user_id = 1; }
    
    // 1. TANGKAP INPUTAN NAMA YANG DIKETIC SAAT CO
    $nama_penerima = $_POST['nama'] ?? $_POST['wa_nama'] ?? $_POST['nama_pembeli'] ?? $_POST['username'] ?? 'Pelanggan Toko';
    
    $total_raw = $_POST['total'] ?? '0';
    $total_harga = intval(preg_replace('/[^0-9]/', '', $total_raw)); 
    if ($total_harga == 0) { $total_harga = 15000; } 
    
    $metode = $_POST['metode'] ?? 'COD';
    $alamat = $_POST['alamat'] ?? '-';
    $telepon = $_POST['telepon'] ?? '-';
    $detail_donat = $_POST['detail_donat'] ?? '';
    
    $catatan = "Metode: " . $metode . " | No HP: " . $telepon . " | Alamat: " . $alamat . " | Detail: " . trim($detail_donat);

    try {
        // 2. MASUKKAN NAMA_PENERIMA KE DALAM DATABASE
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, nama_penerima, total_harga, status, catatan) VALUES (?, ?, ?, 'Pending', ?)");
        $stmt->execute([$user_id, $nama_penerima, $total_harga, $catatan]);
        echo "Sukses Terinput";
    } catch (PDOException $err) {
        echo "Gagal Input: " . $err->getMessage();
    }
}
?>