<?php
session_start();
$conn = new mysqli("localhost", "root", "", "donut_shop");

// Jika keranjang kosong, kembalikan ke index
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

// Logika ketika tombol "Selesaikan Pembayaran" ditekan
if (isset($_POST['place_order'])) {
    // Di sini Kakak bisa menambahkan logika penyimpanan ke database (tabel pesanan/orders)
    // Untuk simulasi, kita kosongkan keranjang dan lempar ke halaman sukses
    unset($_SESSION['cart']);
    header("Location: index.php?status=checkout_success");
    exit();
}

$total_harga_produk = 0;
$item_keranjang = [];

// Ambil data produk di keranjang dari database
foreach ($_SESSION['cart'] as $id_produk => $qty) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($product = $result->fetch_assoc()) {
        $product['qty'] = $qty;
        $product['subtotal'] = $product['harga'] * $qty;
        $total_harga_produk += $product['subtotal'];
        $item_keranjang[] = $product;
    }
}

// Biaya Tambahan (Simulasi)
$ongkos_kirim = 10000;
$total_pembayaran = $total_harga_produk + $ongkos_kirim;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Pemesanan - DonutShop 🍩</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #FFFDF9;
            font-family: 'Quicksand', sans-serif;
            color: #6C4A4A;
        }
        .card-checkout {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .btn-checkout {
            background-color: #FF8FAB;
            color: white;
            font-weight: 700;
            border-radius: 30px;
            transition: all 0.3s;
        }
        .btn-checkout:hover {
            background-color: #FF6B8B;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <div class="container my-4 text-center">
        <h2 class="fw-bold">🛍️ Halaman Checkout Pesanan</h2>
        <p class="text-muted">Periksa kembali donat impianmu sebelum dikirim ya!</p>
    </div>

    <div class="container mb-5">
        <form method="POST" action="">
            <div class="row g-4">
                
                <div class="col-lg-7">
                    <div class="card card-checkout p-4 mb-4">
                        <h5 class="fw-bold mb-3">📍 Alamat Pengiriman</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Nama Penerima</label>
                                <input type="text" name="nama_penerima" class="form-control rounded-pill" required placeholder="Masukkan nama lengkap...">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Nomor Telepon/WhatsApp</label>
                                <input type="tel" name="telepon" class="form-control rounded-pill" required placeholder="Contoh: 081234567xxx">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Alamat Lengkap Rumah</label>
                                <textarea name="alamat" class="form-control" rows="3" required placeholder="Nama jalan, nomor rumah, RT/RW, kecamatan..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Catatan untuk Penjual (Opsional)</label>
                                <input type="text" name="catatan" class="form-control rounded-pill" placeholder="Contoh: Donat jangan terlalu rapat agar tidak hancur">
                            </div>
                        </div>
                    </div>

                    <div class="card card-checkout p-4">
                        <h5 class="fw-bold mb-3">💳 Metode Pembayaran</h5>
                        <div class="form-check mb-3 p-3 border rounded-3">
                            <input class="form-check-input ms-1" type="radio" name="payment_method" id="pay_cod" value="COD" checked required>
                            <label class="form-check-label ms-3 fw-bold" for="pay_cod">
                                Bayar di Tempat (COD) 💵
                            </label>
                            <div class="small text-muted ms-3">Bayar langsung secara tunai saat abang kurir mengantarkan donat ke depan rumahmu.</div>
                        </div>
                        <div class="form-check p-3 border rounded-3">
                            <input class="form-check-input ms-1" type="radio" name="payment_method" id="pay_tf" value="Transfer Bank">
                            <label class="form-check-label ms-3 fw-bold" for="pay_tf">
                                E-Wallet / Transfer QRIS 📱
                            </label>
                            <div class="small text-muted ms-3">Gunakan dompet digital pilihanmu untuk proses instan yang higienis.</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card card-checkout p-4 sticky-top" style="top: 100px; z-index: 1;">
                        <h5 class="fw-bold mb-3">🛒 Ringkasan Belanja</h5>
                        
                        <div class="mb-4">
                            <?php foreach ($item_keranjang as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div>
                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($item['nama']) ?></h6>
                                    <small class="text-muted"><?= $item['qty'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></small>
                                </div>
                                <span class="fw-bold text-dark">Rp <?= number_format($product['subtotal'], 0, ',', '.') ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <h5 class="fw-bold mb-3">🧾 Rincian Biaya</h5>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span>Total Harga Donat</span>
                            <span>Rp <?= number_format($total_harga_produk, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 small">
                            <span>Ongkos Kirim Jasa Kurir</span>
                            <span>Rp <?= number_format($ongkos_kirim, 0, ',', '.') ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold fs-5">Total Pembayaran</span>
                            <span class="fw-bold fs-5 text-danger">Rp <?= number_format($total_pembayaran, 0, ',', '.') ?></span>
                        </div>

                        <button type="submit" name="place_order" class="btn btn-checkout w-100 py-3 shadow" onclick="return confirm('Apakah semua rincian alamat dan pesanan sudah benar?')">
                            Selesaikan Pembayaran 🎉
                        </button>
                        
                        <a href="index.php#katalog" class="btn btn-link w-100 text-center mt-3 text-muted small text-decoration-none">
                            ← Kembali Tambah Donat Lagi
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>