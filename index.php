<?php
session_start();
require_once 'koneksi.php';

// Proteksi Login Sederhana demi keamanan Session Keranjang
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* --- LOGIKA BACK-END (Proses Keranjang VIA AJAX & MANUAL) --- */
if (isset($_GET['action'])) {
    // 1. Tambah ke keranjang
    if ($_GET['action'] == 'add_to_cart' && isset($_GET['p_id'])) {
        $p_id = $_GET['p_id'];
        
        // Cek apakah item sudah ada
        $check = $pdo->prepare("SELECT id, kuantitas FROM cart WHERE user_id = ? AND product_id = ?");
        $check->execute([$user_id, $p_id]);
        $cart_item = $check->fetch();

        if ($cart_item) {
            $update = $pdo->prepare("UPDATE cart SET kuantitas = kuantitas + 1 WHERE id = ?");
            $update->execute([$cart_item['id']]);
        } else {
            $insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, kuantitas) VALUES (?, ?, 1)");
            $insert->execute([$user_id, $p_id]);
        }
        header("Location: index.php#katalog"); exit;
    }
    
    // 2. Aksi Update Kuantitas di Sidebar (+ / - / hapus)
    if ($_GET['action'] == 'update_cart' && isset($_GET['c_id']) && isset($_GET['op'])) {
        $c_id = $_GET['c_id'];
        if ($_GET['op'] == 'plus') {
            $pdo->prepare("UPDATE cart SET kuantitas = kuantitas + 1 WHERE id = ? AND user_id = ?")->execute([$c_id, $user_id]);
        } elseif ($_GET['op'] == 'minus') {
            $pdo->prepare("UPDATE cart SET kuantitas = IF(kuantitas > 1, kuantitas - 1, 1) WHERE id = ? AND user_id = ?")->execute([$c_id, $user_id]);
        } elseif ($_GET['op'] == 'delete') {
            $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")->execute([$c_id, $user_id]);
        }
        header("Location: index.php"); exit;
    }
}

// FITUR BARU: PROSES KIRIM ULASAN MANIS (SUDAH DISSELARASKAN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['submit_review']) || isset($_POST['kirim_ulasan']))) {
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : trim($_POST['nama_pelanggan']);
    $ulasan = isset($_POST['ulasan']) ? trim($_POST['ulasan']) : trim($_POST['komentar']);
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5; 
    
    $ins_rev = $pdo->prepare("INSERT INTO reviews (user_id, nama_pelanggan, rating, komentar, status) VALUES (?, ?, ?, ?, 'pending')");
    $ins_rev->execute([$user_id, $nama, $rating, $ulasan]);
    header("Location: index.php#ulasan"); 
    exit;
}

/* --- AMBIL DATA DARI DATABASE --- */
$promos = $pdo->query("SELECT judul_promo FROM daily_promos WHERE status_aktif = 1 LIMIT 1")->fetch();
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$reviews = $pdo->query("SELECT * FROM reviews WHERE status = 'approved' ORDER BY id DESC")->fetchAll();

// Ambil isi keranjang user aktif beserta hitungan total badge
$cart_stmt = $pdo->prepare("SELECT c.id as cart_id, c.kuantitas, p.* FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll();

// =========================================================
// BAGIAN INI YANG DIPERBAIKI BIAR TIDAK EROR LAGI:
// =========================================================
$total_badge = 0;
$total_belanja = 0; // Nilai awal wajib 0 biar aman saat keranjang kosong

if (!empty($cart_items)) {
    foreach ($cart_items as $item) {
        $total_badge += $item['kuantitas'];
        $total_belanja += ($item['harga'] * $item['kuantitas']);
    }
}
// =========================================================
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donut Shop Kawaii 🍩 Subur Makmur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Efek Timbul pada Kartu */
        .product-card-item .card {
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease, border-color 0.3s ease !important;
        }

        .product-card-item .card:hover {
            transform: translateY(-10px) scale(1.02) !important; /* Kartu naik ke atas */
            box-shadow: 0 20px 35px rgba(255, 143, 171, 0.3) !important; /* Bayangan pink tebal manis */
            border-color: #FF8FAB !important; /* Garis berubah jadi pink ceria */
        }

        /* Efek Donatnya Ikut Membesar dan Miring Imut */
        .product-card-item .card:hover .donut-img-container svg {
            transform: scale(1.1) rotate(5deg) !important;
            transition: transform 0.3s ease !important;
        }

        .product-card-item .donut-img-container svg {
            transition: transform 0.3s ease !important;
        }
    </style>
</head>
<body>

<?php if(isset($_GET['status']) && $_GET['status'] == 'checkout_success'): ?>
    <div class="alert alert-success alert-dismissible fade show text-center py-3 mb-0 rounded-0" role="alert" style="background-color: #E8F5E9; border: none; color: #2E7D32; font-weight: bold; z-index: 9999; position: relative;">
        ✨ Adonan Berhasil Dipesan! Kurir kami akan segera meluncur mengantarkan pesanan manismu. Terima kasih! 🎉🍩
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-kawaii sticky-top">
    <div class="container">
        <a class="navbar-brand fs-3" href="#">🍩 DonutShop</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
           <ul class="navbar-nav mx-auto text-center">
    <li class="nav-item"><a class="nav-link" href="#beranda">Beranda</a></li>
    <li class="nav-item"><a class="nav-link" href="#tentang">Tentang Kami</a></li>
    <li class="nav-item"><a class="nav-link" href="#katalog">Katalog</a></li>
    <li class="nav-item">
    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#modalKontakGemas">Kontak</a>
</li>
</ul>
            <div class="d-flex justify-content-center gap-2 align-items-center ms-auto">
                <button class="btn btn-light position-relative border-0 p-1" data-bs-toggle="offcanvas" data-bs-target="#sidebarCart" style="background: none; font-size: 1.4rem;">
                    🛒 <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white fs-6 id-cart-badge"><?=$total_badge?></span>
                </button>
                <a href="admin_dashboard.php" class="btn btn-link text-decoration-none fw-bold small px-2" style="color: #6C4A4A;">Admin 👑</a>
                <a href="login.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">🚪 Keluar</a>
            </div>
        </div>
    </div>
</nav>

<section id="beranda" class="container my-5 py-5 text-center position-relative">
    <div class="row align-items-center position-relative" style="z-index: 5;">
        
        <div class="col-md-6 order-md-1 text-md-start">

    <div class="text-md-start text-center mb-3" style="user-select: none; pointer-events: none;">
        <span class="badge rounded-pill px-3 py-1.5 align-middle" style="background-color: #FFF0F5; color: #FF8FAB; border: 2px dashed #FFB6C1; font-weight: bold; font-size: 0.85rem;">
            <span style="animation: blink-sparkle 1s infinite;">✨</span> 
            Halo Sahabat Cemilan! 
            <span class="d-inline-block" style="animation: wave-hand 2s infinite; transform-origin: 70% 70%;">👋</span>
        </span>

        <div class="d-flex justify-content-center justify-content-md-start gap-2 mt-2" style="font-size: 1.2rem; opacity: 0.85;">
            <span style="animation: float-slow 3s ease-in-out infinite;">🍩</span>
            <span style="animation: float-slow 3s ease-in-out infinite 0.5s;">🌟</span>
            <span style="animation: float-slow 3s ease-in-out infinite 1s;">🧁</span>
            <span style="animation: float-slow 3s ease-in-out infinite 1.5s;">🌟</span>
            <span style="animation: float-slow 3s ease-in-out infinite 2s;">🍩</span>
        </div>
    </div>

    <style>
        @keyframes wave-hand {
            0%, 100% { transform: rotate( 0deg); }
            10% { transform: rotate(14deg); }
            20% { transform: rotate(-8deg); }
            30% { transform: rotate(14deg); }
            40% { transform: rotate(-4deg); }
            50% { transform: rotate(10deg); }
        }
        @keyframes blink-sparkle {
            0%, 100% { opacity: 0.4; transform: scale(0.9); }
            50% { opacity: 1; transform: scale(1.1); }
        }
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-6px); }
        }
    </style>
    <h1 class="display-4 fw-bold mb-3">Donat Ter-Imut <br><span style="color:var(--accent-pink)">Penuh Kebahagiaan!</span></h1>
    
    <p class="fs-5 text-muted mb-4">Dibuat dengan cinta murni, menghasilkan tekstur selembut awan di langit dongeng...</p>
    <a href="#katalog" class="btn btn-kawaii fs-5 px-5">Lihat Menu Donat 👑</a>
</div>

        <div class="col-md-6 order-md-2 mb-4 position-relative d-flex justify-content-center align-items-center" style="min-height: 400px;">
            
            <div class="fs-1 kawaii-float text-center" style="font-size: 10rem !important; z-index: 2; position: relative;">🍩</div>
            
            <div class="position-absolute" style="top: 15%; left: 15%; font-size: 2rem; z-index: 3; animation: float 4s ease-in-out infinite;">✨</div>
            
            <div class="position-absolute" style="top: 10%; right: 15%; font-size: 2rem; z-index: 3; animation: float 5s ease-in-out infinite 0.5s;">🧁</div>
            
            <div class="position-absolute" style="bottom: 15%; left: 12%; font-size: 2.2rem; z-index: 3; animation: float 3.5s ease-in-out infinite 1s;">🌈</div>
            
            <div class="position-absolute" style="bottom: 10%; right: 15%; font-size: 2rem; z-index: 3; animation: float 6s ease-in-out infinite 1.5s;">🍓</div>
            
            <div class="position-absolute" style="top: -5%; left: 48%; transform: translateX(-50%); font-size: 1.8rem; z-index: 3; animation: float 4.5s ease-in-out infinite 2s;">🎀</div>

        </div>

    </div>
</section>

<section id="tentang" class="py-5 my-5 container position-relative overflow-hidden" style="background: linear-gradient(135deg, #FFF0F5 0%, #FFE4E1 100%); border-radius: 40px; border: 4px dashed #FFB6C1; box-shadow: 0 15px 35px rgba(255,182,193,0.3);">
    
    <div class="position-absolute" style="top: 8%; left: 4%; font-size: 1.8rem; opacity: 0.7; animation: float 3s ease-in-out infinite;">✨</div>
    <div class="position-absolute" style="top: 15%; right: 5%; font-size: 2rem; opacity: 0.6; animation: float 4s ease-in-out infinite 0.5s;">🌈</div>
    <div class="position-absolute" style="bottom: 12%; left: 6%; font-size: 1.8rem; opacity: 0.7; animation: float 3.5s ease-in-out infinite 1s;">🎀</div>
    <div class="position-absolute" style="bottom: 8%; right: 8%; font-size: 2.2rem; opacity: 0.5; animation: float 4.5s ease-in-out infinite;">🌸</div>

    <div class="py-4 px-3 position-relative" style="z-index: 2;">
        <div class="text-center mb-5">
            <span class="fs-1 d-block mb-1">🧁✨👩‍🍳</span>
            <h2 class="fw-bold" style="color: #6C4A4A; font-family: 'Comic Sans MS', cursive, sans-serif; font-size: 2.3rem;">
                Kisah Di Balik Adonan Magic Kami
            </h2>
            <div class="mx-auto my-3" style="width: 120px; height: 5px; background: linear-gradient(to right, #FF8FAB, #FFC1CC); border-radius: 10px;"></div>
            <p class="text-muted fst-italic">“Karena sepotong donat sanggup mengukir senyuman terbesar di wajahmu!”</p>
        </div>

        <div class="row g-4 align-items-center">
            <div class="col-lg-6 px-4">
                <div class="p-4 bg-white rounded-4 shadow-sm border border-2" style="border-color: #FFE5EC; border-radius: 25px !important;">
                    <h4 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: #FF8FAB;">
                        <span>📜</span> Sejarah Lahirnya DonutShop
                    </h4>
                    <p class="text-muted style-text-kawaii mb-3" style="line-height: 1.7; font-size: 0.95rem; text-align: justify;">
                        Berawal dari tahun yang penuh keajaiban, sang Chef Donat kami bermimpi ingin menciptakan camilan yang tidak hanya bikin kenyang, tapi juga bisa menularkan energi kebahagiaan! 
                    </p>
                    <p class="text-muted style-text-kawaii mb-0" style="line-height: 1.7; font-size: 0.95rem; text-align: justify;">
                        Lewat eksperimen ratusan kali di dapur rahasia, terciptalah formula donat ajaib yang selembut awan di langit dongeng. Nama Subur Makmur disematkan sebagai doa tulus, agar siapapun yang menikmati kelezatan pastel ini, harinya akan ikut makmur penuh sukacita dan senyuman manis! 💖🎈
                    </p>
                </div>
            </div>

            <div class="col-lg-6 px-4">
                <div class="row g-3">
                    
                    <div class="col-sm-6">
                        <div class="p-3 text-center h-100 border border-1 bg-white hover-kawaii-card" style="border-radius: 20px; border-color: #FFF59D !important; transition: all 0.3s ease;">
                            <div class="fs-2 mb-2">☁️</div>
                            <h6 class="fw-bold text-dark mb-1">Selembut Awan</h6>
                            <p class="text-muted mb-0 small">Tekstur super empuk, anti seret, dan langsung lumer di lidah!</p>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="p-3 text-center h-100 border border-1 bg-white hover-kawaii-card" style="border-radius: 20px; border-color: #F8BBD0 !important; transition: all 0.3s ease;">
                            <div class="fs-2 mb-2">🍓</div>
                            <h6 class="fw-bold text-dark mb-1">Bahan Premium</h6>
                            <p class="text-muted mb-0 small">100% menggunakan cokelat, selai, dan topping asli kualitas terbaik.</p>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="p-3 text-center h-100 border border-1 bg-white hover-kawaii-card" style="border-radius: 20px; border-color: #B2DFDB !important; transition: all 0.3s ease;">
                            <div class="fs-2 mb-2">🎨</div>
                            <h6 class="fw-bold text-dark mb-1">Visual Ter-Imut</h6>
                            <p class="text-muted mb-0 small">Warna pastel estetik yang sangat *Instagrammable* dan gemas.</p>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="p-3 text-center h-100 border border-1 bg-white hover-kawaii-card" style="border-radius: 20px; border-color: #E1BEE7 !important; transition: all 0.3s ease;">
                            <div class="fs-2 mb-2">💖</div>
                            <h6 class="fw-bold text-dark mb-1">Dibuat Tiap Hari</h6>
                            <p class="text-muted mb-0 small">Selalu baru dari oven, tanpa pengawet berbahaya, aman dikonsumsi.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</section> 
<section id="faq" class="py-5" style="background-color: var(--cream-pastel);">
    <div class="container py-4">
        
        <div class="text-center mb-5">
            <h2 class="fw-bold text-center mb-2 kawaii-float" style="font-size: 2.5rem; color: var(--accent-pink);">
                🍩 Tanya-Tanya Gemas 🍩
            </h2>
            <p class="text-muted small">Klik pada pertanyaan untuk melihat jawaban rahasia para donat!</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="accordion accordion-flush" id="faqDonutAccordion">

                    <div class="accordion-item faq-card mb-3">
                        <h3 class="accordion-header" id="faq-head-1">
                            <button class="accordion-button collapsed faq-btn" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-1" aria-expanded="false" aria-controls="faq-collapse-1">
                                ✨ Apakah donatnya dibuat fresh setiap hari?
                            </button>
                        </h3>
                        <div id="faq-collapse-1" class="accordion-collapse collapse" aria-labelledby="faq-head-1" data-bs-parent="#faqDonutAccordion">
                            <div class="accordion-body faq-answer">
                                Pastinya dong! Semua donat imut kami dipanggang dan dihias dengan penuh cinta setiap pagi subuh, jadi pas sampai di tangan Kakak teksturnya masih super empuk menul-menul seperti awan! ☁️🌸
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item faq-card mb-3">
                        <h3 class="accordion-header" id="faq-head-2">
                            <button class="accordion-button collapsed faq-btn" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-2" aria-expanded="false" aria-controls="faq-collapse-2">
                                🛵 Bisa kirim ke luar kota atau pesan antar lewat kurir?
                            </button>
                        </h3>
                        <div id="faq-collapse-2" class="accordion-collapse collapse" aria-labelledby="faq-head-2" data-bs-parent="#faqDonutAccordion">
                            <div class="accordion-body faq-answer">
                                Bisa banget, Kak! Untuk area sekitar toko, kurir kami siap mengantar sampai depan pintu rumah Kakak. Sedangkan kirim ke luar kota saat ini baru bisa untuk donat kering/bomboloni tertentu ya biar topping-nya gak leleh di jalan! 📦✨
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item faq-card mb-3">
                        <h3 class="accordion-header" id="faq-head-3">
                            <button class="accordion-button collapsed faq-btn" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-3" aria-expanded="false" aria-controls="faq-collapse-3">
                                🎨 Apakah bisa request tulisan atau custom topping ulang tahun?
                            </button>
                        </h3>
                        <div id="faq-collapse-3" class="accordion-collapse collapse" aria-labelledby="faq-head-3" data-bs-parent="#faqDonutAccordion">
                            <div class="accordion-body faq-answer">
                                Wah, boleh banget gess! Kakak bisa pesan donat kustom huruf atau warna-warna pastel lucu untuk kado ulang tahun atau syukuran. Tinggal klik checkout nanti langsung bicarakan detail desainnya sama Admin lewat WA ya! 🎂💝
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item faq-card mb-3">
                        <h3 class="accordion-header" id="faq-head-4">
                            <button class="accordion-button collapsed faq-btn" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-4" aria-expanded="false" aria-controls="faq-collapse-4">
                                🍫 Topping apa nih yang paling best-seller dan disukai?
                            </button>
                        </h3>
                        <div id="faq-collapse-4" class="accordion-collapse collapse" aria-labelledby="faq-head-4" data-bs-parent="#faqDonutAccordion">
                            <div class="accordion-body faq-answer">
                                Juaranya adalah Milo Crunchy Dino dan Strawberry Matcha Pink! Perpaduan rasa manis cokelat premium dan sensasi kriuk-kriuk-nya dijamin bikin Kakak merem-melek ketagihan dari gigitan pertama! 🦖🍓
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</section>

<section id="katalog" class="container my-5 pt-4">
    <div class="text-center mb-4">
        <h2 class="fw-bold" style="color: #6C4A4A;">✨ Menu Donat Ajaib ✨</h2>
        <p class="text-muted mb-3">Pilih dan saring rasa favoritmu tanpa ribet!</p>
        <?php if ($promos): ?>
        <div class="d-flex justify-content-center mb-4">
            <div class="katalog-marquee-box shadow-sm">
                <marquee scrollamount="5" behavior="scroll" direction="left" class="fw-bold">✨🌈 PROMO SPESIAL HARI INI: <?=$promos['judul_promo']?> 🌈✨</marquee>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row justify-content-center mb-5">
        <div class="col-md-6 mb-3">
            <input type="text" id="searchInput" class="form-control rounded-pill text-center border-2 border-info shadow-sm" placeholder="Cari nama donat impianmu... 🔍" style="padding: 12px;">
        </div>
        <div class="col-10 text-center gap-2 d-flex flex-wrap justify-content-center">
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn active" data-filter="all">Semua Rasa 💫</button>
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn" data-filter="Matcha">Matcha 🍵</button>
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn" data-filter="Strawberry">Strawberry 🍓</button>
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn" data-filter="Choco">Choco 🍫</button>
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn" data-filter="Red Velvet">Red Velvet 🍰</button>
        </div>
    </div>

    <div class="row" id="productGrid">
<?php foreach ($products as $p): ?>
    <div class="col-lg-4 col-md-6 col-sm-12 mb-4 product-card-item" data-kategori="<?=$p['kategori']?>" data-nama="<?=strtolower($p['nama'])?>">
        
        <?php 
            $glow_class = "strawberry-glow";
            if (stripos($p['kategori'], 'Matcha') !== false) {
                $glow_class = "matcha-glow";
            } elseif (stripos($p['kategori'], 'Choco') !== false) {
                $glow_class = "choco-glow";
            } elseif (stripos($p['kategori'], 'Red Velvet') !== false) {
                $glow_class = "redvelvet-glow";
            }
        ?>
        <div class="card donut-card text-center p-3 h-100 d-flex flex-column justify-content-between <?= $glow_class ?>">
            
            <span class="position-absolute" style="top: 12px; left: 15px; font-size: 1.1rem; opacity: 0.7;">✨</span>
            <span class="position-absolute" style="top: 12px; right: 15px; font-size: 1.1rem; opacity: 0.7;">💝</span>

            <div>
                <?php 
                    $warna_frosting = "#FFB6C1"; 
                    $warna_meses_1  = "#FFFFF0";
                    $warna_meses_2  = "#FF69B4";

                    if (stripos($p['kategori'], 'Matcha') !== false) {
                        $warna_frosting = "#A3C9A8"; 
                        $warna_meses_1  = "#FFFFFF";
                        $warna_meses_2  = "#4A7C59";
                    } elseif (stripos($p['kategori'], 'Choco') !== false) {
                        $warna_frosting = "#8B5A2B"; 
                        $warna_meses_1  = "#FFD700";
                        $warna_meses_2  = "#FFFFFF";
                    } elseif (stripos($p['kategori'], 'Red Velvet') !== false) {
                        $warna_frosting = "#B22222"; 
                        $warna_meses_1  = "#FFFFFF";
                        $warna_meses_2  = "#FFC0CB";
                    } elseif (stripos($p['kategori'], 'Strawberry') !== false) {
                        $warna_frosting = "#FF69B4"; 
                        $warna_meses_1  = "#FFFFFF";
                        $warna_meses_2  = "#FFFF00";
                    }
                ?>
                
                <div class="donut-img-container text-center py-2" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#productModal<?=$p['id']?>">
                    <?php if (!empty($p['gambar'])): ?>
                        <img src="assets/uploads/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= htmlspecialchars($p['nama']) ?>"
                             style="width:110px; height:110px; object-fit:cover; border-radius:50%; border:3px solid <?= $warna_frosting ?>; box-shadow: 0 5px 12px rgba(0,0,0,0.12);">
                    <?php else: ?>
                    <svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0px 5px 6px rgba(0,0,0,0.12));">
                        <circle cx="50" cy="50" r="40" fill="#F4A460" />
                        <path d="M 50 10 C 75 10, 90 25, 90 50 C 90 65, 80 80, 70 85 C 60 82, 55 88, 45 82 C 35 85, 25 75, 15 65 C 10 50, 25 10, 50 10 Z" fill="<?= $warna_frosting ?>" />
                        <circle cx="50" cy="50" r="14" fill="#FFFDF9" />
                        <rect x="35" y="25" width="6" height="2" rx="1" fill="<?= $warna_meses_1 ?>" transform="rotate(15 35 25)" />
                        <rect x="60" y="25" width="6" height="2" rx="1" fill="<?= $warna_meses_2 ?>" transform="rotate(-30 60 25)" />
                        <rect x="25" y="45" width="6" height="2" rx="1" fill="<?= $warna_meses_2 ?>" transform="rotate(45 25 45)" />
                        <rect x="70" y="45" width="6" height="2" rx="1" fill="<?= $warna_meses_1 ?>" transform="rotate(-15 70 45)" />
                        <rect x="35" y="70" width="6" height="2" rx="1" fill="<?= $warna_meses_2 ?>" transform="rotate(30 35 70)" />
                        <rect x="55" y="72" width="6" height="2" rx="1" fill="<?= $warna_meses_1 ?>" transform="rotate(-45 55 72)" />
                    </svg>
                    <?php endif; ?>
                </div>

                <h4 class="fw-bold fs-5 mt-2" style="color: #6C4A4A; font-family: 'Comic Sans MS', cursive, sans-serif;"><?=$p['nama']?></h4>
                
                <div class="mb-2">
                    <span class="badge border rounded-pill px-3 py-1" style="background-color: #FFF0F5; color: #FF8FAB; border-color: #FFB6C1 !important; font-size: 0.75rem;">
                        🧸 <?=$p['kategori']?>
                    </span>
                </div>
                
                <div class="d-inline-block px-3 py-1 rounded-pill" style="background-color: #FFFDE7; border: 1px dashed #FFF59D;">
                    <h5 class="fw-bold text-danger m-0" style="font-size: 1.1rem;">Rp <?=number_format($p['harga'], 0, ',', '.')?></h5>
                </div>
            </div>
            
            <div class="mt-3 d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill fw-bold flex-fill" data-bs-toggle="modal" data-bs-target="#productModal<?=$p['id']?>" style="border-color: #FFB6C1; color: #FF8FAB; background: #fff; font-size: 0.8rem; padding: 6px 0;">
                    🔍 Detail
                </button>
                <a href="index.php?action=add_to_cart&p_id=<?=$p['id']?>" class="btn btn-kawaii btn-sm rounded-pill fw-bold flex-fill d-flex align-items-center justify-content-center gap-1" style="font-size: 0.8rem; padding: 6px 0;">
                    Beli 🛒
                </a>
            </div>
        </div>
    </div>

    <div class="modal fade" id="productModal<?=$p['id']?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4" style="border-radius: 30px; border: 5px solid #FFB6C1; background-color: #FFFDF9; box-shadow: 0 15px 30px rgba(255, 143, 171, 0.35);">
                <div class="modal-header border-0 justify-content-end p-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-2 mb-3">
                        <?php if (!empty($p['gambar'])): ?>
                            <img src="assets/uploads/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= htmlspecialchars($p['nama']) ?>"
                                 style="width:130px; height:130px; object-fit:cover; border-radius:50%; border:4px solid <?= $warna_frosting ?>; box-shadow: 0 8px 20px rgba(0,0,0,0.15);">
                        <?php else: ?>
                        <svg width="120" height="120" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="50" cy="50" r="40" fill="#F4A460" />
                            <path d="M 50 10 C 75 10, 90 25, 90 50 C 90 65, 80 80, 70 85 C 60 82, 55 88, 45 82 C 35 85, 25 75, 15 65 C 10 50, 25 10, 50 10 Z" fill="<?= $warna_frosting ?>" />
                            <circle cx="50" cy="50" r="14" fill="#FFFDF9" />
                        </svg>
                        <?php endif; ?>
                    </div>
                    <h3 class="fw-bold" style="color: #6C4A4A; font-family: 'Comic Sans MS', cursive, sans-serif;"><?=$p['nama']?></h3>
                    <p class="text-muted small px-2" style="background: #FFF; border-radius: 15px; padding: 10px; border: 1px dashed #FFC1CC; text-align: justify; line-height: 1.5;"><?=$p['deskripsi']?></p>
                    <h4 class="text-danger fw-bold mb-3">Rp <?=number_format($p['harga'], 0, ',', '.')?></h4>
                    <a href="index.php?action=add_to_cart&p_id=<?=$p['id']?>" class="btn btn-kawaii px-4 py-2 rounded-pill fw-bold">Masukkan Keranjang 🛒</a>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
    </div><!-- /productGrid -->
</section><!-- /katalog -->

<section id="ulasan" class="container my-5 py-5" style="background: radial-gradient(#FFE5EC 12%, transparent 12%); background-size: 30px 30px;">
    
    <div class="text-center mb-5">
        <h2 class="fw-bold" style="color: #6C4A4A; font-family: 'Comic Sans MS', cursive, sans-serif; text-shadow: 2px 2px 0 #FFF;">
            💖 Cerita Manis Sahabat DonutShop 🍩
        </h2>
        <p class="badge rounded-pill px-4 py-2" style="background-color: #FFF0F5; color: #FF8FAB; border: 2px dashed #FFB6C1; font-size: 0.85rem;">
            🧁 Lebih dari 1.000+ Senyuman Setiap Hari!
        </p>
    </div>

   <div class="row g-4 justify-content-center mb-5" id="ulasan">
        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $rev): ?>
                <?php 
                    // Logika penentuan desain otomatis berdasarkan nama agar persis seperti versi awal Kakak
                    $nama_cek = strtolower($rev['nama_pelanggan'] ?? '');
                    
                    // Default setting untuk orang baru (Pink Lembut Clarissa)
                    $bg_color = '#FFE5EC'; 
                    $shadow_color = 'rgba(255,143,171,0.15)';
                    $border_style = 'border-0';
                    $emoji = '🍩';
                    $emoji_color = '#FF8FAB';

                    if (str_contains($nama_cek, 'clarissa')) {
                        $bg_color = '#FFE5EC';
                        $shadow_color = 'rgba(255,143,171,0.15)';
                        $border_style = 'border-0';
                        $emoji = '🎀';
                        $emoji_color = '#FF8FAB';
                    } elseif (str_contains($nama_cek, 'daffa')) {
                        $bg_color = '#FFFDE7';
                        $shadow_color = 'rgba(255,213,79,0.12)';
                        $border_style = 'border-2 dashed #FFF59D !important';
                        $emoji = '🦖';
                        $emoji_color = '#FFB300';
                    } elseif (str_contains($nama_cek, 'nabila')) {
                        $bg_color = '#E8F5E9';
                        $shadow_color = 'rgba(163,201,168,0.15)';
                        $border_style = 'border-0';
                        $emoji = '🐱';
                        $emoji_color = '#4A7C59';
                    }
                ?>

                <div class="col-lg-4 col-md-6" style="animation: munculSmooth 0.5s ease-in-out;">
                    <div class="card p-4 h-100 <?= $border_style ?>" style="border-radius: 25px; background-color: <?= $bg_color ?>; box-shadow: 0 10px 18px <?= $shadow_color ?>; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                        
                        <div class="text-warning mb-2">
                            <?php 
                            $rating_star = isset($rev['rating']) ? (int)$rev['rating'] : 5;
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating_star ? '★' : '☆';
                            }
                            ?>
                        </div>
                        
                        <p class="small m-0" style="color: #6C4A4A; font-weight: 500;">
                           "<?= htmlspecialchars($rev['komentar'] ?? $rev['ulasan'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        </p>
                        
                        <div class="d-flex align-items-center mt-3 pt-2" style="border-top: 1px dashed rgba(108,74,74,0.1);">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 35px; height: 35px; background-color: #FFF; color: <?= $emoji_color ?>;">
                                <?= $emoji ?>
                            </div>
                            <div class="ms-2">
                                <h6 class="m-0 fw-bold small"><?= htmlspecialchars($rev['nama_pelanggan'] ?? 'Anonim') ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted py-3">
                <p>Belum ada ulasan manis nih. Yuk tulis ulasan pertamamu! 🍩✨</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card p-4" style="border: 3px solid #FFE5EC; border-radius: 30px; background-color: #FFFDF9; box-shadow: 0 12px 28px rgba(255, 143, 171, 0.12);">
                <div class="text-center mb-4">
                    <h4 class="fw-bold m-0" style="color: #6C4A4A; font-family: 'Comic Sans MS', sans-serif;">🧁 Tulis Cerita Manismu Di Sini</h4>
                </div>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold" style="color: #6C4A4A;">Nama Kakak ✨</label>
                            <input type="text" name="nama" class="form-control" placeholder="Masukkan nama Kakak..." style="border: 2px solid #FFE5EC; border-radius: 20px; background-color: #FFFDF4; padding: 10px; font-size: 0.9rem;" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold" style="color: #6C4A4A;">Seberapa Lezat Donatnya? 🌟</label>
                            <select name="rating" class="form-select rounded-pill px-3 text-center fw-bold" style="border: 2px solid #FFE5EC; background-color: #FFFDF4; color: #D4AF37; padding: 10px; font-size: 0.9rem;" required>
                                <option value="5" selected>⭐⭐⭐⭐⭐ (Sempurna! Nagih Banget)</option>
                                <option value="4">⭐⭐⭐⭐ (Enak & Lembut Banget)</option>
                                <option value="3">⭐⭐⭐ (Lumayan Manis & Pas)</option>
                                <option value="2">⭐⭐ (Biasa Aja / Standar)</option>
                                <option value="1">⭐ (Kurang Cocok di Lidah)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Gimana Rasa Donat Favoritmu? 🍩</label>
                        <textarea name="ulasan" class="form-control" rows="3" placeholder="Ceritain topping favoritmu..." style="border: 2px solid #FFE5EC; border-radius: 20px; background-color: #FFFDF4;" required></textarea>
                    </div>

                    <div class="text-center">
                        <button type="submit" name="kirim_ulasan" class="btn fw-bold px-5 py-2.5 rounded-pill text-white" style="background: #FF8FAB; border:none;">Kirim Ulasan Manis 💌</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</section>

<section id="info-toko" class="py-5" style="background-color: var(--cream-pastel); border-top: 4px dashed var(--pink-pastel);">
    <div class="container">
        
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-2 kawaii-float" style="font-size: 2.5rem; color: var(--accent-pink); font-family: 'Fredoka', sans-serif;">
                📍 Temukan Toko Kami 📍
            </h2>
            <p class="text-muted small">Mampir yuk! Intip lokasi offline dan jam terbang donat-donat imut kami ✨</p>
        </div>

        <div class="row g-4 align-items-stretch">
            
            <div class="col-lg-6">
                <div class="p-3 rounded-5 h-100 bg-white" style="border: 3px solid white; box-shadow: 0 8px 20px rgba(0,0,0,0.05); min-height: 420px;">
                    <iframe 
                        width="100%" 
                        height="100%" 
                        style="border:0; min-height: 400px; height: 100%; border-radius: 20px;" 
                        src="https://maps.google.com/maps?q=Jl.+Braga+Kec+Sumur+Bandung+Kota+Bandung+Jawa+Barat&t=&z=15&ie=UTF8&iwloc=&output=embed" 
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                </div>
            </div>

            <div class="col-lg-6 d-flex flex-column justify-content-between gap-3">
                
                <div class="p-4 rounded-5 flex-grow-1 d-flex align-items-center" style="background-color: #CAFFBF; border: 3px solid white; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
                    <div class="d-flex align-items-center gap-3">
                        <span style="font-size: 2.2rem;">⏰</span>
                        <div>
                            <h4 class="fw-bold mb-1" style="color: #6C4A4A; font-family: 'Fredoka', sans-serif;">Jam Operasional</h4>
                            <p class="mb-0 small" style="color: #6C4A4A;">Setiap Hari: <span class="fw-bold text-success">07:00 - 21:00 WIB</span></p>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-5 flex-grow-1 d-flex align-items-center" style="background-color: #FFF5E1; border: 3px solid white; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
                    <div class="d-flex align-items-center gap-3">
                        <span style="font-size: 2.2rem;">📍</span>
                        <div>
                            <h4 class="fw-bold mb-1" style="color: #6C4A4A; font-family: 'Fredoka', sans-serif;">Alamat Fisik</h4>
                            <p class="mb-0 small" style="color: #6C4A4A;">Jl. Braga, Kec. Sumur Bandung, Kota Bandung, Jawa Barat</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-5 flex-grow-1 d-flex align-items-center" style="background-color: #9BF6FF; border: 3px solid white; box-shadow: 0 8px 20px rgba(0,0,0,0.05);">
                    <div class="d-flex align-items-center gap-3 w-100">
                        <span style="font-size: 2.2rem;">📱</span>
                        <div class="w-100">
                            <h4 class="fw-bold mb-2" style="color: #6C4A4A; font-family: 'Fredoka', sans-serif;">Media Sosial</h4>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="https://instagram.com/donutdy.id" target="_blank" class="text-decoration-none text-dark small">
                                    <i class="fab fa-instagram text-danger fa-lg me-1"></i><b>@donutdy.id</b>
                                </a>
                                <a href="https://facebook.com/dodonutdy" target="_blank" class="text-decoration-none text-dark small">
                                    <i class="fab fa-facebook text-primary fa-lg me-1"></i><b>dodonutdy</b>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /col-lg-6 kanan -->
        </div><!-- /row -->
    </div><!-- /container -->
</section><!-- /info-toko -->

<style>
    @keyframes munculSmooth {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const stars = document.querySelectorAll('.star-btn');
        function colorStars(value) {
            stars.forEach(star => {
                star.style.color = parseInt(star.getAttribute('data-value')) <= parseInt(value) ? '#FFC107' : '#DDD';
            });
        }
        colorStars(5);
        stars.forEach(star => {
            star.addEventListener('click', function() {
                document.getElementById('ratingValue').value = this.getAttribute('data-value');
                colorStars(this.getAttribute('data-value'));
            });
            star.style.cursor = 'pointer';
        });
    });
</script>


<div class="offcanvas offcanvas-end" tabindex="-1" id="sidebarCart" aria-labelledby="sidebarCartLabel" style="width: 450px !important;">
    <div class="offcanvas-header border-bottom" style="background-color: #FFF0F5;">
        <h5 class="offcanvas-title fw-bold" id="sidebarCartLabel" style="color: #6C4A4A;">🛍️ Formulir & Checkout Pesanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body d-flex flex-column justify-content-between p-0" style="height: 100vh;">
        <div class="p-3" style="overflow-y: auto; flex: 1;">
            
            <div class="mb-4">
                <span class="fw-bold d-block mb-2 text-muted small">🛒 RINCIAN DONATMU:</span>
                <div class="p-3 rounded-4" style="background-color: #FFFDF9; border: 2px dashed #FFB6C1;">
                    <div id="wa_list_produk">
                        <?php if(!empty($cart_items)): ?>
                            <?php foreach($cart_items as $item): 
                                $subtotal = $item['harga'] * $item['kuantitas'];
                            ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom small product-wa-row" 
                                     data-nama="<?= htmlspecialchars($item['nama']) ?>" 
                                     data-qty="<?= $item['kuantitas'] ?>">
                                    <div class="flex-grow-1 me-2">
                                        <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($item['nama']) ?></h6>
                                        <small class="text-muted"><?= $item['kuantitas'] ?> pcs x Rp <?= number_format($item['harga'], 0, ',', '.') ?></small>
                                        
                                        <div class="mt-1 d-flex align-items-center gap-1">
                                            <a href="index.php?action=update_cart&c_id=<?= $item['cart_id'] ?>&op=minus" class="btn btn-outline-secondary p-0 text-center rounded-circle" style="width: 22px; height: 22px; line-height: 20px; font-size: 0.8rem; text-decoration:none;">-</a>
                                            <span class="mx-1 small fw-bold"><?= $item['kuantitas'] ?></span>
                                            <a href="index.php?action=update_cart&c_id=<?= $item['cart_id'] ?>&op=plus" class="btn btn-outline-secondary p-0 text-center rounded-circle" style="width: 22px; height: 22px; line-height: 20px; font-size: 0.8rem; text-decoration:none;">+</a>
                                            <a href="index.php?action=update_cart&c_id=<?= $item['cart_id'] ?>&op=delete" class="btn btn-link text-danger ms-2 p-0 small text-decoration-none" onclick="return confirm('Hapus donat ini dari keranjang? 🥺')">
                                                <i class="fas fa-trash-alt"></i> Hapus
                                            </a>
                                        </div>
                                    </div>
                                    <span class="fw-bold text-danger align-self-start">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3 text-muted small">Keranjangmu kosong nih 🧸</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <span class="fw-bold d-block mb-2 text-muted small">📍 ALAMAT PENGIRIMAN:</span>
                <div class="mb-2">
                    <label class="form-label mb-1 small fw-bold">Nama Lengkap Penerima</label>
                    <input type="text" id="wa_nama" class="form-control form-control-sm rounded-pill" placeholder="Contoh: Kak Sasa ✨">
                </div>
                <div class="mb-2">
                    <label class="form-label mb-1 small fw-bold">No. WhatsApp Utama</label>
                    <input type="tel" id="wa_telepon" class="form-control form-control-sm rounded-pill" placeholder="Contoh: 081234567xxx">
                </div>
                <div class="mb-2">
                    <label class="form-label mb-1 small fw-bold">Alamat Lengkap Rumah</label>
                    <textarea id="wa_alamat" class="form-control form-control-sm rounded-3" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, kecamatan..."></textarea>
                </div>
            </div>

            <div class="mb-4">
                <span class="fw-bold d-block mb-2 text-muted small">💳 METODE PEMBAYARAN:</span>
                <div class="d-flex gap-2">
                    <div class="form-check p-2 border rounded-3 flex-fill text-center bg-white">
                        <input class="form-check-input ms-0" type="radio" name="metode_bayar" id="bayar_cod" value="COD (Bayar di Tempat)" checked>
                        <label class="form-check-label d-block small fw-bold mt-1" for="bayar_cod">COD 💵</label>
                    </div>
                    <div class="form-check p-2 border rounded-3 flex-fill text-center bg-white">
                        <input class="form-check-input ms-0" type="radio" name="metode_bayar" id="bayar_qris" value="Transfer / QRIS">
                        <label class="form-check-label d-block small fw-bold mt-1" for="bayar_qris">QRIS 📱</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-3 border-top bg-white shadow-lg" style="z-index: 10;">
            <div class="d-flex justify-content-between align-items-center mb-1 small text-muted">
                <span>Ongkos Kirim Jasa Kurir:</span>
                <span>Rp 5.000</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold fs-5 text-dark">Total Pembayaran:</span>
                <span class="fw-bold fs-3 text-danger" id="wa_total_html">
                    Rp <?= number_format(($total_belanja > 0 ? $total_belanja + 5000 : 0), 0, ',', '.') ?>
                </span>
            </div>
            <button type="button" onclick="kirimKeWhatsApp()" class="btn w-100 rounded-pill py-2.5 fw-bold text-white shadow d-flex align-items-center justify-content-center gap-2" style="background-color: #FF8FAB; font-size: 1.05rem;">
                🎉 Selesaikan & Kirim Ke WhatsApp
            </button>
        </div>
    </div>
</div>

<footer class="text-center py-4 bg-white border-top text-muted mt-5">
    <p class="mb-0">© 2026 Toko Donat Kawaii. Dibuat dengan 💖 dan Teknologi Anti Galau.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById('searchInput');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const productItems = document.querySelectorAll('.product-card-item');

    function applyFilterAndSearch() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const activeBtn = document.querySelector('.filter-btn.active');
        const activeFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';

        productItems.forEach(item => {
            const itemKategori = item.getAttribute('data-kategori');
            const itemNama = item.getAttribute('data-nama');

            const matchesSearch = itemNama.includes(searchTerm);
            const matchesFilter = (activeFilter === 'all' || itemKategori === activeFilter);

            item.style.display = (matchesSearch && matchesFilter) ? "block" : "none";
        });
    }

    if(searchInput) {
        searchInput.addEventListener('input', applyFilterAndSearch);
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilterAndSearch();
        });
    });
});

function kirimKeWhatsApp() {
    const nama = document.getElementById('wa_nama').value.trim();
    const tlp = document.getElementById('wa_telepon').value.trim();
    const alamat = document.getElementById('wa_alamat').value.trim();
    const metode = document.querySelector('input[name="metode_bayar"]:checked').value;
    const totalBayar = document.getElementById('wa_total_html').innerText;

    const rows = document.querySelectorAll('.product-wa-row');
    if (rows.length === 0) {
        alert('Keranjang belanjaanmu masih kosong, yuk pilih donat dulu! 🍩');
        return;
    }

    if (!nama || !tlp || !alamat) {
        alert('Waduh, isi data Nama, No. WA, dan Alamat lengkap dulu ya Kak! 🍩');
        return;
    }

    let listDonat = "";
    rows.forEach((row, index) => {
        const namaDonat = row.getAttribute('data-nama');
        const qtyDonat = row.getAttribute('data-qty');
        listDonat += (index + 1) + ". " + namaDonat + " (" + qtyDonat + "x)\n";
    });

    // KIRIM DATA KE DATABASE (Menggunakan FormData agar lebih stabil dan anti-eror)
    const dataForm = new FormData();
    dataForm.append('nama', nama);
    dataForm.append('telepon', tlp);
    dataForm.append('alamat', alamat);
    dataForm.append('metode', metode);
    dataForm.append('total', totalBayar); // Mengirim teks total langsung apa adanya
    dataForm.append('detail_donat', listDonat);

    fetch('simpan_pesanan.php', {
        method: 'POST',
        body: dataForm
    })
    .then(() => {
        // MAU DATABASE EROR ATAU SUKSES, TETAP LEMPAR KE WA BIAR TOMBOL GA MACET
        const nomorAdmin = "6281324251404"; 

        const teksPesan = 
            "✨ *PESANAN BARU - DONUTSHOP* ✨\n" +
            "------------------------------------------\n\n" +
            "👤 *Data Penerima:*\n" +
            "• Nama: " + nama + "\n" +
            "• No. HP: " + tlp + "\n" +
            "• Alamat Kirim: " + alamat + "\n\n" +
            "🍩 *Daftar Menu Donat:*\n" + 
            listDonat + "\n" +
            "💰 *Metode Bayar:* " + metode + "\n" +
            "🛵 *Ongkir Jasa Kurir:* Rp 5.000\n" +
            "------------------------------------------\n" +
            "⭐ *TOTAL BAYAR:* *" + totalBayar + "*\n\n" +
            "Mohon segera diproses ya Admin baik! 🥰🥞";

        const urlWhatsApp = "https://api.whatsapp.com/send?phone=" + nomorAdmin + "&text=" + encodeURIComponent(teksPesan);
        
        window.open(urlWhatsApp, '_blank');
        window.location.href = 'index.php?status=checkout_success';
    });
}

document.addEventListener("DOMContentLoaded", function () {
    const menuLinks = document.querySelectorAll('.navbar-nav .nav-link');
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            menuLinks.forEach(item => item.classList.remove('active-warna'));
            this.classList.add('active-warna');
        });
    });
});
</script>

<div class="modal fade" id="modalKontakGemas" tabindex="-1" aria-labelledby="modalKontakLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content kontak-modal-box">
            
            <div class="modal-header border-0 pb-0">
                <h3 class="modal-title fw-bold w-100 text-center" id="modalKontakLabel" style="color: var(--accent-pink);">
                    🍩 Hubungi Kami 🍩
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-start px-4">
                <div class="kontak-item p-3 rounded-4" style="background-color: #FFF0F5;">
                    <h5 class="fw-bold mb-2" style="color: #6C4A4A;">✨ Kontak Utama</h5>
                    <p class="mb-1 small">📧 <b>Email:</b> donutdy@gmail.com</p>
                    <p class="mb-1 small">📞 <b>No. Telepon:</b> 0857-7053-7478</p>
                    <p class="mb-0 small">💬 <b>WhatsApp:</b> <a href="https://wa.me/6281324251404" target="_blank" class="text-decoration-none fw-bold" style="color: var(--accent-pink);">+62 813-2425-1404</a></p>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-kawaii px-4 py-2" data-bs-dismiss="modal" style="font-size: 0.9rem;">Tutup</button>
            </div>
            
        </div>
    </div>
</div>
</body>
</html>