<?php
session_start();
$conn = new mysqli("localhost", "root", "", "donut_shop");

$error = "";
if (isset($_POST['login_admin'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Username & Password bawaan
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Ih salah! Username atau Password Adminnya keliru tuh... 🤫🍩";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerbang Rahasia Pemilik Toko 🍩</title>
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
    <div class="floating-deco" style="top: 50%; left: 5%; animation-delay: 0.5s; font-size: 2rem;">👑</div>
    <div class="floating-deco" style="top: 45%; right: 5%; animation-delay: 2.5s; font-size: 2rem;">🔑</div>

    <div class="login-container animate__animated animate__bounceIn" style="position: relative; z-index: 5; max-width: 420px; width: 100%;">
        <div class="login-card p-5 text-center">
            <div class="fs-1 mb-2">👑👩‍🍳</div>
            <h2 class="fw-bold mb-1" style="color: #6C4A4A; font-family: 'Fredoka', sans-serif;">Gerbang Admin</h2>
            <p class="text-muted small mb-4">Silakan masuk untuk meracik menu donat ajaib baru!</p>
            
            <?php if($error): ?>
                <div class="alert alert-danger py-2 small rounded-pill border-0 animate__animated animate__shakeX" style="background-color: #FFCCD5; color: #900C3F;">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3 text-start">
                    <label class="small fw-bold text-muted ms-3 mb-1">Nama Panggilan Admin</label>
                    <input type="text" name="username" class="form-control login-input text-center" required placeholder="Ketik username admin..." autocomplete="off">
                </div>
                <div class="mb-4 text-start">
                    <label class="small fw-bold text-muted ms-3 mb-1">Kunci Password Rahasia</label>
                    <input type="password" name="password" class="form-control login-input text-center" required placeholder="Ketik password rahasia...">
                </div>
                <button type="submit" name="login_admin" class="btn btn-kawaii w-100 fw-bold rounded-pill py-2.5">Buka Dashboard Toko ✨</button>
            </form>
            
            <a href="index.php" class="d-block mt-4 small fw-bold text-decoration-none text-muted hover-link">
                🎈 Kembali ke Etalase Depan
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>