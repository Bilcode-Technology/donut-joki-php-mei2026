<?php
session_start();
// Hubungkan ke file koneksi database agar query INSERT buku tamu bisa berjalan
require_once 'koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil apa pun nama yang diketik oleh user di form
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // SISTEM LOGIN PINTAR: Nama bebas apa saja, yang penting password-nya '12345'
    if ($password === '12345' && !empty($username)) {
        
        // Simpan data asli yang diketik user ke dalam Session
        $_SESSION['user_id'] = 1; // Diubah ke 1 agar sinkron dengan Foreign Key di tabel database
        $_SESSION['username'] = $username;   // Menyimpan nama asli yang diketik
        $_SESSION['role'] = 'customer';
        
        // =========================================================
        // OTOMATIS DAFTARKAN NAMA ASLI USER KE BUKU TAMU
        // =========================================================
        $stmt_tamu = $pdo->prepare("INSERT INTO buku_tamu (user_id, nama_tamu) VALUES (?, ?)");
        $stmt_tamu->execute([$_SESSION['user_id'], $_SESSION['username']]);
        // =========================================================
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Password salah, Kak! Coba gunakan password '12345' 💕";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Toko Donat Kawaii 🍩</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        .floating-deco {
            position: absolute;
            font-size: 2.5rem;
            z-index: 1;
            animation: floatSlow 4s ease-in-out infinite;
            user-select: none;
        }
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(15deg); }
        }
    </style>
</head>
<body class="login-body d-flex align-items-center justify-content-center" style="position: relative; overflow: hidden;">

    <div class="floating-deco" style="top: 10%; left: 10%; animation-delay: 0s;">🍩</div>
    <div class="floating-deco" style="bottom: 15%; left: 8%; animation-delay: 1s;">✨</div>
    <div class="floating-deco" style="top: 15%; right: 10%; animation-delay: 2s;">🧁</div>
    <div class="floating-deco" style="bottom: 10%; right: 8%; animation-delay: 1.5s;">🌈</div>
    <div class="floating-deco" style="top: 50%; left: 5%; animation-delay: 0.5s; font-size: 2rem;">🌸</div>
    <div class="floating-deco" style="top: 45%; right: 5%; animation-delay: 2.5s; font-size: 2rem;">🎀</div>

<div class="container" style="position: relative; z-index: 5;">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="login-card p-5 text-center animate__animated animate__bounceIn">
                <div class="fs-1 kawaii-float">🍩✨🍪</div>
                <h2 class="mt-3 mb-2 fw-bold" style="color: #6C4A4A;">Pecinta Manis Kesini!</h2>
                <p class="text-muted small mb-4">Silakan masuk menggunakan nama panggilan terimutmu ✨</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-pill py-2 small text-center border-0 animate__animated animate__shakeX" style="background-color: #FFCCD5; color: #900C3F;">
                        <?=$error?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label small fw-bold text-muted ms-3 mb-1">Nama Panggilan Kakak ✨</label>
                        <input type="text" name="username" class="form-control login-input text-center" placeholder="Contoh: Nathael, Gerald, Sasa..." required autocomplete="off">
                    </div>
                    <div class="mb-4 text-start">
                        <label class="form-label small fw-bold text-muted ms-3 mb-1">Password Pengunjung 🔑</label>
                        <input type="password" name="password" class="form-control login-input text-center" placeholder="Ketik password '12345'" required>
                    </div>
                    <button type="submit" class="btn btn-kawaii w-100 fw-bold rounded-pill py-2.5">Buka Toko Donat 🚀</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>