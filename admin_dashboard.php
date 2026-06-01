<?php
session_start();
// Proteksi Login: Jika belum login admin, lempar balik ke halaman login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Menghubungkan ke koneksi PDO yang sama dengan index.php
require_once 'koneksi.php';

// --- LOGIKA PROSES CRUD ---
// Fungsi upload gambar
function uploadGambar($file_input) {
    if (!isset($file_input) || $file_input['error'] !== UPLOAD_ERR_OK) return null;
    $upload_dir = __DIR__ . '/assets/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $ext = strtolower(pathinfo($file_input['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return null;
    $filename = uniqid('produk_') . '.' . $ext;
    move_uploaded_file($file_input['tmp_name'], $upload_dir . $filename);
    return $filename;
}

// 1. Tambah Produk Baru
if (isset($_POST['add_product'])) {
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $harga = $_POST['harga'];
    $deskripsi = $_POST['deskripsi'];
    $kelebihan = $_POST['kelebihan'];
    $gambar = uploadGambar($_FILES['gambar'] ?? null);

    $stmt = $pdo->prepare("INSERT INTO products (nama, kategori, harga, deskripsi, kelebihan_varian, gambar) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nama, $kategori, $harga, $deskripsi, $kelebihan, $gambar]);
    header("Location: admin_dashboard.php?status=success");
    exit();
}

// 2. Edit/Update Produk
if (isset($_POST['edit_product'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $harga = $_POST['harga'];
    $deskripsi = $_POST['deskripsi'];
    $kelebihan = $_POST['kelebihan'];
    $gambar_baru = uploadGambar($_FILES['gambar'] ?? null);

    if ($gambar_baru) {
        $stmt = $pdo->prepare("UPDATE products SET nama=?, kategori=?, harga=?, deskripsi=?, kelebihan_varian=?, gambar=? WHERE id=?");
        $stmt->execute([$nama, $kategori, $harga, $deskripsi, $kelebihan, $gambar_baru, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE products SET nama=?, kategori=?, harga=?, deskripsi=?, kelebihan_varian=? WHERE id=?");
        $stmt->execute([$nama, $kategori, $harga, $deskripsi, $kelebihan, $id]);
    }
    header("Location: admin_dashboard.php?status=updated");
    exit();
}

// 3. Hapus Produk
if (isset($_GET['delete_product'])) {
    $id = $_GET['delete_product'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
    $stmt->execute([$id]);
    header("Location: admin_dashboard.php?status=deleted");
    exit();
}

// 4. Update Banner Promo Harian
if (isset($_POST['update_promo'])) {
    $judul_promo = $_POST['judul_promo'];
    $check = $pdo->query("SELECT id FROM daily_promos LIMIT 1")->fetch();
    
    if ($check) {
        $stmt = $pdo->prepare("UPDATE daily_promos SET judul_promo=? WHERE id=?");
        $stmt->execute([$judul_promo, $check['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO daily_promos (judul_promo, status_aktif) VALUES (?, 1)");
        $stmt->execute([$judul_promo]);
    }
    header("Location: admin_dashboard.php?status=promo_updated");
    exit();
}

// 5. Hapus Ulasan
if (isset($_GET['delete_review'])) {
    $id = $_GET['delete_review'];
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id=?");
    $stmt->execute([$id]);
    header("Location: admin_dashboard.php?tab=ulasan&status=review_deleted");
    exit();
}

// 6. Approve Ulasan
if (isset($_GET['approve_review'])) {
    $id = $_GET['approve_review'];
    $stmt = $pdo->prepare("UPDATE reviews SET status='approved' WHERE id=?");
    $stmt->execute([$id]);
    header("Location: admin_dashboard.php?tab=ulasan&status=review_approved");
    exit();
}

// 7. Reject Ulasan
if (isset($_GET['reject_review'])) {
    $id = $_GET['reject_review'];
    $stmt = $pdo->prepare("UPDATE reviews SET status='rejected' WHERE id=?");
    $stmt->execute([$id]);
    header("Location: admin_dashboard.php?tab=ulasan&status=review_rejected");
    exit();
}

// 8. Update Status Pesanan
if (isset($_GET['update_order']) && isset($_GET['status_order'])) {
    $id = $_GET['update_order'];
    $status_baru = $_GET['status_order'];
    $allowed_status = ['Pending', 'Diproses', 'Dikirim', 'Selesai'];
    if (in_array($status_baru, $allowed_status)) {
        $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->execute([$status_baru, $id]);
    }
    header("Location: admin_dashboard.php?tab=pesanan&status=order_updated");
    exit();
}

// --- PENGAMBILAN DATA VIA PDO (UNTUK STATISTIK & TABEL) ---
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$reviews = $pdo->query("SELECT * FROM reviews ORDER BY FIELD(status,'pending','approved','rejected'), id DESC")->fetchAll();
$pending_reviews = array_filter($reviews, fn($r) => ($r['status'] ?? 'pending') === 'pending');
$buku_tamu = $pdo->query("SELECT * FROM buku_tamu ORDER BY waktu_kunjungan DESC")->fetchAll();
$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();

// Ambil data promo harian
$promo_data = $pdo->query("SELECT judul_promo FROM daily_promos LIMIT 1")->fetch();
$text_promo = $promo_data ? $promo_data['judul_promo'] : 'Diskon Opening 50% All Variant!';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - DonutShop 👑</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --pink-utama: #FF8FAB; 
            --cokelat: #6C4A4A; 
            --bg-pastel: #FFF8F9;
            --pink-light: #FFE5EC;
            --pink-dark: #FB6F92;
        }
        body { 
            background-color: var(--bg-pastel); 
            font-family: 'Outfit', sans-serif; 
            overflow-x: hidden;
            color: var(--cokelat);
        }
        
        /* LAYOUT SIDEBAR KIRI */
        .sidebar-admin {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 280px;
            background-color: #FFFFFF;
            border-right: 2px dashed #FFB6C1;
            box-shadow: 4px 0 25px rgba(255, 143, 171, 0.08);
            z-index: 1000;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s ease;
        }
        .sidebar-brand {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            background: linear-gradient(135deg, var(--pink-dark) 0%, var(--pink-utama) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }
        
        /* TOMBOL MENU SIDEBAR */
        .nav-pills .nav-link {
            color: var(--cokelat);
            font-weight: 600;
            border-radius: 15px;
            margin-bottom: 8px;
            padding: 12px 18px;
            transition: all 0.25s ease;
            border: 1px solid transparent;
            font-size: 14px;
        }
        .nav-pills .nav-link:hover {
            background-color: var(--pink-light);
            color: var(--pink-dark);
            padding-left: 24px;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--pink-dark) 0%, var(--pink-utama) 100%) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(251, 111, 146, 0.35);
        }

        .btn-danger-custom {
            background: linear-gradient(135deg, #ff4757 0%, #ff6b81 100%);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 12px;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.25s ease;
            width: 100%;
            text-align: center;
            display: block;
            text-decoration: none;
        }
        .btn-danger-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 71, 87, 0.3);
            color: white;
        }
        
        /* AREA KONTEN KANAN */
        .content-area {
            margin-left: 280px;
            padding: 40px;
            min-height: 100vh;
            background-color: var(--bg-pastel);
            transition: margin-left 0.3s ease, padding 0.3s ease;
        }
        .card-custom { 
            border-radius: 24px; 
            border: 1px solid rgba(255, 143, 171, 0.12); 
            box-shadow: 0 10px 30px rgba(255, 143, 171, 0.04); 
            margin-bottom: 30px; 
            background: white; 
            padding: 30px; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-custom:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 35px rgba(255, 143, 171, 0.08);
        }

        /* Top Header Navigation style */
        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 35px;
            background: white;
            padding: 18px 30px;
            border-radius: 20px;
            border: 1px solid rgba(255, 143, 171, 0.12);
            box-shadow: 0 4px 20px rgba(255, 143, 171, 0.03);
        }

        /* Sidebar Toggle Button for Mobile */
        .sidebar-toggle {
            display: none;
            background: white;
            border: 1px solid rgba(255, 143, 171, 0.2);
            color: var(--pink-dark);
            font-size: 20px;
            padding: 8px 15px;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(255, 143, 171, 0.05);
            transition: all 0.2s ease;
        }
        .sidebar-toggle:hover {
            background-color: var(--pink-light);
        }

        /* Form styling */
        .form-control, .form-select {
            border-radius: 12px;
            border: 1.5px solid #E2E8F0;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--pink-utama);
            box-shadow: 0 0 0 4px rgba(255, 143, 171, 0.15);
        }
        .form-control-sm, .form-select-sm {
            padding: 8px 12px;
            font-size: 13px;
            border-radius: 10px;
        }
        .form-label {
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 6px;
            color: var(--cokelat);
        }
        
        /* Buttons custom */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--pink-dark) 0%, var(--pink-utama) 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 15px;
            padding: 10px 24px;
            transition: all 0.25s ease;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(251, 111, 146, 0.35);
            color: white;
        }

        /* RESPONSIVE LAYOUT */
        @media (max-width: 991.98px) {
            .sidebar-admin {
                transform: translateX(-100%);
            }
            .sidebar-admin.show {
                transform: translateX(0);
            }
            .content-area {
                margin-left: 0;
                padding: 20px;
            }
            .sidebar-toggle {
                display: inline-block;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar Admin -->
<div class="sidebar-admin" id="sidebarAdmin">
    <div>
        <div class="text-center mb-4">
            <h4 class="sidebar-brand mb-1">🍩 DonutShop</h4>
            <span class="badge bg-light text-muted border">Panel Admin</span>
            <hr style="border-top: 2px dashed rgba(108,74,74,0.1);">
        </div>
        
        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
            <button class="nav-link active text-start" id="menu-ringkasan-tab" data-bs-toggle="pill" data-bs-target="#menu-ringkasan" type="button" role="tab">📊 Ringkasan</button>
            <button class="nav-link text-start" id="menu-promo-tab" data-bs-toggle="pill" data-bs-target="#menu-promo" type="button" role="tab">📢 Atur Promo</button>
            <button class="nav-link text-start" id="menu-daftar-tab" data-bs-toggle="pill" data-bs-target="#menu-daftar" type="button" role="tab">📜 Daftar Menu</button>
            <button class="nav-link text-start" id="menu-varian-tab" data-bs-toggle="pill" data-bs-target="#menu-varian" type="button" role="tab">➕ Tambah Varian</button>
            <button class="nav-link text-start" id="menu-ulasan-tab" data-bs-toggle="pill" data-bs-target="#menu-ulasan" type="button" role="tab">⭐ Opsi Ulasan</button>
            <button class="nav-link text-start" id="menu-tamu-tab" data-bs-toggle="pill" data-bs-target="#menu-tamu" type="button" role="tab">📖 Buku Tamu</button>
            <button class="nav-link text-start" id="menu-pesanan-tab" data-bs-toggle="pill" data-bs-target="#menu-pesanan" type="button" role="tab">🛍️ Pesanan</button>
        </div>
    </div>

    <div>
        <a href="admin_logout.php" class="btn-danger-custom">🚪 Keluar Admin</a>
    </div>
</div>

<!-- Main Content Area -->
<div class="content-area">
    
    <!-- Top Navbar / Header -->
    <div class="header-top">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggleBtn" type="button">
                ☰
            </button>
            <h5 class="fw-bold mb-0" style="color: var(--cokelat)">🍩 DonutShop Panel Admin</h5>
        </div>
        <div class="d-none d-sm-block">
            <span class="text-muted small">Waktu Server: <strong><?= date('H:i') ?> WIB</strong></span>
        </div>
    </div>
            
            <?php if(isset($_GET['status'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-pill text-center py-2 mb-4" role="alert">
                    ✨ Operasi Database Berhasil Dieksekusi! 🎉
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="tab-content" id="v-pills-tabContent">
                
                <div class="tab-pane fade show active" id="menu-ringkasan" role="tabpanel">
                    <h3 class="fw-bold mb-4" style="color: var(--cokelat)">📊 Ringkasan Dashboard Utama</h3>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card-custom text-center border-top border-5 border-primary">
                                <h6 class="text-muted fw-bold">TOTAL VARIAN MENU</h6>
                                <h1 class="display-5 fw-bold text-primary"><?= count($products) ?></h1>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card-custom text-center border-top border-5 border-warning">
                                <h6 class="text-muted fw-bold">ULASAN PELANGGAN</h6>
                                <h1 class="display-5 fw-bold text-warning"><?= count($reviews) ?></h1>
                                <?php if(count($pending_reviews) > 0): ?>
                                <span class="badge bg-danger"><?= count($pending_reviews) ?> menunggu persetujuan</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card-custom text-center border-top border-5 border-info">
                                <h6 class="text-muted fw-bold">PENGGUNA AKTIF</h6>
                                <h1 class="display-5 fw-bold text-warning"><?= count($buku_tamu) ?></h1>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="menu-promo" role="tabpanel">
                    <div class="card-custom border-start border-4 border-warning mx-auto" style="max-width: 600px;">
                        <h5 class="fw-bold mb-3 text-warning">📢 Atur Promo Harian Berjalan</h5>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Teks Berjalan Saat Ini</label>
                                <input type="text" name="judul_promo" class="form-control" value="<?= htmlspecialchars($text_promo) ?>" required>
                            </div>
                            <button type="submit" name="update_promo" class="btn btn-warning text-white fw-bold btn-sm rounded-pill px-4">Simpan & Tayangkan 🚀</button>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade" id="menu-daftar" role="tabpanel">
                    <div class="card-custom">
                        <h5 class="fw-bold mb-3" style="color: var(--cokelat)">📦 Daftar Menu Produk Aktif</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Foto</th>
                                        <th>Nama Donat</th>
                                        <th>Kategori</th>
                                        <th>Harga</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($products) > 0): ?>
                                        <?php foreach($products as $row): ?>
                                        <tr>
                                            <td>
                                                <?php if(!empty($row['gambar'])): ?>
                                                    <img src="assets/uploads/<?= htmlspecialchars($row['gambar']) ?>" alt="foto" style="width:50px;height:50px;object-fit:cover;border-radius:10px;">
                                                <?php else: ?>
                                                    <span style="font-size:2rem;">🍩</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-bold"><?= htmlspecialchars($row['nama']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['kategori']) ?></span></td>
                                            <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                            <td class="text-center" style="white-space:nowrap;">
                                                <button type="button" class="btn btn-sm btn-warning rounded-pill px-2 py-0 text-white btn-edit-product"
                                                         data-id="<?= $row['id'] ?>"
                                                         data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?>"
                                                         data-kategori="<?= htmlspecialchars($row['kategori'], ENT_QUOTES, 'UTF-8') ?>"
                                                         data-harga="<?= $row['harga'] ?>"
                                                         data-deskripsi="<?= htmlspecialchars($row['deskripsi'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                         data-kelebihan="<?= htmlspecialchars($row['kelebihan_varian'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                     ✏️ Edit
                                                 </button>
                                                <a href="admin_dashboard.php?delete_product=<?= $row['id'] ?>" class="btn btn-sm btn-danger rounded-pill px-2 py-0" onclick="return confirm('Yakin ingin membuang donat ini dari etalase?')">🗑️ Hapus</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center text-muted">Etalase kosong. Silakan tambah menu donat baru!</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="menu-varian" role="tabpanel">
                    <div class="card-custom border-start border-4 border-primary mx-auto" style="max-width: 600px;">
                        <h5 class="fw-bold mb-3 text-primary">🍩 Tambah Varian Donat Baru</h5>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-2">
                                <label class="small fw-bold">Nama Donat</label>
                                <input type="text" name="nama" class="form-control form-control-sm" required placeholder="Contoh: Matcha Tiramisu">
                            </div>
                            <div class="mb-2">
                                <label class="small fw-bold">Kategori Rasa</label>
                                <select name="kategori" class="form-select form-select-sm" required>
                                    <option value="Matcha">Matcha</option>
                                    <option value="Strawberry">Strawberry</option>
                                    <option value="Choco">Choco</option>
                                    <option value="Red Velvet">Red Velvet</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="small fw-bold">Harga Jual (Rp)</label>
                                <input type="number" name="harga" class="form-control form-control-sm" required placeholder="Contoh: 7000">
                            </div>
                            <div class="mb-2">
                                <label class="small fw-bold">Deskripsi Singkat</label>
                                <textarea name="deskripsi" class="form-control form-control-sm" rows="2" placeholder="Donat dengan kelembutan..."></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="small fw-bold">Kelebihan / Tagline</label>
                                <input type="text" name="kelebihan" class="form-control form-control-sm" placeholder="Contoh: Lumer di mulut!">
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold">Foto Produk 📷 <span class="text-muted fw-normal">(JPG/PNG/WebP, maks 2MB)</span></label>
                                <input type="file" name="gambar" class="form-control form-control-sm" accept="image/*">
                                <div class="form-text">Jika tidak upload foto, akan tampil ilustrasi donat otomatis.</div>
                            </div>
                             <button type="submit" name="add_product" class="btn-primary-custom w-100 mt-2 py-2">Masukkan ke Etalase Toko ✨</button>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade" id="menu-ulasan" role="tabpanel">
                    <div class="card-custom border-start border-4 border-info">
                        <h5 class="fw-bold mb-3 text-info">💬 Kotak Ulasan Pelanggan Masuk</h5>
                        <p class="small text-muted mb-3">Ulasan dengan status <span class="badge bg-warning text-dark">Menunggu</span> harus disetujui dulu sebelum tampil di halaman depan.</p>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle small">
                                <thead class="table-info">
                                    <tr>
                                        <th>Nama Pelanggan</th>
                                        <th>Isi Komentar / Review</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($reviews) > 0): ?>
                                        <?php foreach($reviews as $rev):
                                            $rev_status = $rev['status'] ?? 'pending';
                                        ?>
                                        <tr class="<?= $rev_status === 'pending' ? 'table-warning' : '' ?>">
                                            <td class="fw-bold"><?= htmlspecialchars($rev['nama_pelanggan'] ?? 'Anonim') ?></td>
                                            <td class="text-muted">
                                                <span class="text-warning">★ <?= htmlspecialchars($rev['rating'] ?? '5') ?></span> |
                                                "<?= htmlspecialchars($rev['komentar'] ?? '') ?>"
                                            </td>
                                            <td class="text-center">
                                                <?php if($rev_status === 'approved'): ?>
                                                    <span class="badge bg-success">✅ Disetujui</span>
                                                <?php elseif($rev_status === 'rejected'): ?>
                                                    <span class="badge bg-secondary">❌ Ditolak</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">⏳ Menunggu</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center" style="white-space: nowrap;">
                                                <?php if($rev_status !== 'approved'): ?>
                                                <a href="admin_dashboard.php?approve_review=<?= $rev['id'] ?>" class="btn btn-sm btn-success py-0 px-2 rounded-pill me-1">✅ Setujui</a>
                                                <?php endif; ?>
                                                <?php if($rev_status !== 'rejected'): ?>
                                                <a href="admin_dashboard.php?reject_review=<?= $rev['id'] ?>" class="btn btn-sm btn-secondary py-0 px-2 rounded-pill me-1" onclick="return confirm('Tolak ulasan ini?')">❌ Tolak</a>
                                                <?php endif; ?>
                                                <a href="admin_dashboard.php?delete_review=<?= $rev['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill" onclick="return confirm('Hapus ulasan ini permanen?')">🗑️ Hapus</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center text-muted">Belum ada ulasan dari pembeli. 🥞</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="menu-tamu" role="tabpanel">
                    <div class="card-custom border-start border-4 border-primary">
                        <h5 class="fw-bold mb-4 text-primary">📖 Buku Tamu (Riwayat Login Pelanggan)</h5>
                        <div class="guest-timeline">
                            <?php if(count($buku_tamu) > 0): ?>
                                <?php foreach($buku_tamu as $tamu): ?>
                                <div class="guest-item d-flex align-items-center gap-3">
                                    <div class="guest-marker"></div>
                                    <div class="guest-avatar">
                                        <?= strtoupper(substr(htmlspecialchars($tamu['nama_tamu']), 0, 1)) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-1 text-dark">👋 <?= htmlspecialchars($tamu['nama_tamu']) ?></h6>
                                        <span class="text-muted small">
                                            <i class="far fa-clock me-1"></i> <?= date('d M Y - H:i', strtotime($tamu['waktu_kunjungan'])) ?> WIB
                                        </span>
                                    </div>
                                    <span class="badge bg-light text-muted border rounded-pill px-3 py-1">Tamu Masuk</span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">Belum ada rekaman data buku tamu harian. 🍩</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="menu-pesanan" role="tabpanel">
    <div class="card-custom">
        <h4 class="fw-bold mb-3" style="color: var(--cokelat)">🛍️ Daftar Pesanan Masuk (Real-Time)</h4>
        <div class="table-responsive">
            <table class="table table-hover align-middle small">
                <thead class="table-danger" style="background-color: var(--pink-utama); color: white;">
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Nama Pembeli</th>
                        <th>Total Bayar</th>
                        <th>Status</th>
                        <th>Detail Pengiriman & Donat</th>
                    </tr>
                </thead>
                <tbody>
    <?php if(count($orders) > 0): ?>
        <?php foreach($orders as $ord): ?>
        <tr>
            <td class="fw-bold">#<?= $ord['id'] ?></td>
            <td>
                <span class="badge bg-light text-dark border">
                    👤 <?= htmlspecialchars($ord['nama_penerima'] ?? 'Pelanggan Toko') ?>
                </span>
            </td>
            <td class="fw-bold text-success">Rp <?= number_format($ord['total_harga'], 0, ',', '.') ?></td>
            <td>
                <?php
                    $s = $ord['status'] ?? 'Pending';
                    $badge = match(strtolower($s)) {
                        'pending'  => 'bg-warning text-dark',
                        'diproses' => 'bg-info text-dark',
                        'dikirim'  => 'bg-primary',
                        'selesai'  => 'bg-success',
                        default    => 'bg-secondary'
                    };
                ?>
                <div class="d-flex flex-column gap-1">
                    <span class="badge rounded-pill <?= $badge ?>"><?= htmlspecialchars($s) ?></span>
                    <select class="form-select form-select-sm rounded-pill" style="font-size:11px;"
                            onchange="location.href='admin_dashboard.php?tab=pesanan&update_order=<?= $ord['id'] ?>&status_order='+this.value">
                        <option value="">-- Ubah Status --</option>
                        <option value="Pending">Pending</option>
                        <option value="Diproses">Diproses</option>
                        <option value="Dikirim">Dikirim</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>
            </td>
            <td>
                <div class="p-2 bg-light rounded text-muted border" style="max-width: 450px; font-size: 11px; white-space: pre-line;">
                    <?= htmlspecialchars($ord['catatan']) ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="5" class="text-center text-muted py-4">Belum ada pesanan masuk.</td>
        </tr>
    <?php endif; ?>
</tbody>
            </table>
        </div>
    </div>
</div> <!-- End of tab-content -->
</div> <!-- End of content-area -->

<div class="modal fade" id="modalEditProduk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="" class="modal-content rounded-4" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">✏️ Edit Varian Donat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-2">
                    <label class="small fw-bold">Nama Donat</label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="small fw-bold">Kategori Rasa</label>
                    <select name="kategori" id="edit_kategori" class="form-select" required>
                        <option value="Matcha">Matcha</option>
                        <option value="Strawberry">Strawberry</option>
                        <option value="Choco">Choco</option>
                        <option value="Red Velvet">Red Velvet</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="small fw-bold">Harga Jual (Rp)</label>
                    <input type="number" name="harga" id="edit_harga" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="small fw-bold">Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-2">
                    <label class="small fw-bold">Kelebihan / Tagline</label>
                    <input type="text" name="kelebihan" id="edit_kelebihan" class="form-control">
                </div>
                <div class="mb-2">
                    <label class="small fw-bold">Ganti Foto Produk 📷 <span class="text-muted fw-normal">(kosongkan jika tidak ganti)</span></label>
                    <input type="file" name="gambar" id="edit_gambar" class="form-control" accept="image/*">
                    <img id="edit_foto_preview" src="" alt="preview" style="display:none; width:80px; height:80px; object-fit:cover; border-radius:10px; margin-top:6px;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit_product" class="btn btn-sm btn-success rounded-pill px-3">Simpan Perubahan ✅</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function bukaModalEdit(id, nama, kategori, harga, deskripsi, kelebihan) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_kategori').value = kategori;
        document.getElementById('edit_harga').value = harga;
        document.getElementById('edit_deskripsi').value = deskripsi;
        document.getElementById('edit_kelebihan').value = kelebihan;
        document.getElementById('edit_gambar').value = '';
        document.getElementById('edit_foto_preview').style.display = 'none';
        var modal = new bootstrap.Modal(document.getElementById('modalEditProduk'));
        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-edit-product').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');
                const kategori = this.getAttribute('data-kategori');
                const harga = this.getAttribute('data-harga');
                const deskripsi = this.getAttribute('data-deskripsi');
                const kelebihan = this.getAttribute('data-kelebihan');
                bukaModalEdit(id, nama, kategori, harga, deskripsi, kelebihan);
            });
        });
    });

    // Preview foto saat dipilih di modal edit
    document.getElementById('edit_gambar').addEventListener('change', function() {
        const preview = document.getElementById('edit_foto_preview');
        if (this.files && this.files[0]) {
            preview.src = URL.createObjectURL(this.files[0]);
            preview.style.display = 'block';
        }
    });

    // Auto-buka tab dari URL parameter ?tab=
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        const tabEl = document.querySelector('[data-bs-target="#menu-' + tabParam + '"]');
        if (tabEl) new bootstrap.Tab(tabEl).show();
    }

    // Sidebar toggle logic for mobile responsiveness
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('sidebarAdmin');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992) {
                if (!sidebar.contains(e.target) && e.target !== toggleBtn) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
</script>
</body>
</html>