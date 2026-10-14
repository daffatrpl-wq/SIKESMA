<?php
require_once 'session.php';

// Redirect berdasarkan role jika sudah login
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: mahasiswa/index.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKESMA - Sistem Kesejahteraan Mahasiswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fraunces:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --green-dark: #2d5016;
            --green-main: #4a7c2f;
            --green-mid: #5c8f3a;
            --green-light: #7ab648;
            --green-pale: #c8e6a0;
            --green-bg: #f0f7e8;
            --white: #ffffff;
            --text-dark: #1a2e0d;
            --text-mid: #3d5a28;
            --text-light: #6b8f52;
            --shadow: rgba(45, 80, 22, 0.15);
            --shadow-deep: rgba(45, 80, 22, 0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            background: var(--white);
            overflow-x: hidden;
        }

        /* ===== NAVBAR ===== */
        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(74,124,47,0.1);
            padding: 0 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 68px;
            transition: all 0.3s ease;
        }

        nav.scrolled {
            box-shadow: 0 4px 24px var(--shadow);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--green-main), var(--green-light));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .nav-logo-text {
            font-family: 'Fraunces', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--green-dark);
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-mid);
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover { color: var(--green-main); }

        .btn-login {
            background: var(--green-main);
            color: white !important;
            padding: 8px 22px;
            border-radius: 8px;
            font-weight: 600 !important;
            transition: background 0.2s, transform 0.2s !important;
        }

        .btn-login:hover {
            background: var(--green-dark) !important;
            transform: translateY(-1px);
        }

        /* ===== HERO ===== */
        .hero {
            min-height: 100vh;
            padding-top: 68px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 4rem;
            padding-left: 8%;
            padding-right: 5%;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fff2 0%, #edf7e0 50%, #f0f7e8 100%);
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -200px; right: -200px;
            width: 700px; height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(122,182,72,0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -150px; left: -150px;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(74,124,47,0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-content { z-index: 1; }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(74,124,47,0.1);
            color: var(--green-main);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(74,124,47,0.2);
        }

        .hero-title {
            font-family: 'Fraunces', serif;
            font-size: 3.2rem;
            font-weight: 700;
            line-height: 1.15;
            color: var(--text-dark);
            margin-bottom: 1.2rem;
        }

        .hero-title span {
            color: var(--green-main);
            font-style: italic;
        }

        .hero-desc {
            font-size: 1.05rem;
            color: var(--text-light);
            line-height: 1.7;
            margin-bottom: 2.2rem;
            max-width: 480px;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--green-main), var(--green-light));
            color: white;
            padding: 13px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(74,124,47,0.35);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(74,124,47,0.45);
        }

        .btn-outline {
            background: transparent;
            color: var(--green-main);
            padding: 13px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            border: 2px solid var(--green-pale);
            transition: all 0.3s;
        }

        .btn-outline:hover {
            border-color: var(--green-main);
            background: rgba(74,124,47,0.05);
        }

        .hero-visual {
            position: relative;
            z-index: 1;
        }

        .hero-img-wrap {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px var(--shadow-deep);
        }

        .hero-img-wrap img {
            width: 100%;
            height: 420px;
            object-fit: cover;
            display: block;
        }

        .hero-img-placeholder {
            width: 100%;
            height: 420px;
            background: linear-gradient(135deg, var(--green-main) 0%, var(--green-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 1rem;
            color: white;
        }

        .hero-img-placeholder i {
            font-size: 5rem;
            opacity: 0.8;
        }

        .hero-img-placeholder p {
            font-size: 1.1rem;
            font-weight: 600;
            opacity: 0.9;
        }

        .hero-stat-card {
            position: absolute;
            background: white;
            border-radius: 14px;
            padding: 14px 18px;
            box-shadow: 0 8px 30px var(--shadow);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .hero-stat-card.card-1 { bottom: -20px; left: -30px; }
        .hero-stat-card.card-2 { top: 20px; right: -20px; }

        .stat-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .stat-icon.green { background: rgba(74,124,47,0.1); color: var(--green-main); }
        .stat-icon.orange { background: rgba(255,150,50,0.1); color: #e8820c; }

        .stat-info .num { font-size: 1.3rem; font-weight: 800; color: var(--text-dark); }
        .stat-info .lbl { font-size: 0.72rem; color: var(--text-light); font-weight: 500; }

        /* ===== ABOUT SECTION ===== */
        .about {
            background: var(--green-main);
            padding: 80px 8%;
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-logo-big {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .logo-circle {
            width: 120px; height: 120px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid rgba(255,255,255,0.3);
        }

        .logo-circle i { font-size: 3rem; color: white; }

        .about-logo-big span {
            font-family: 'Fraunces', serif;
            font-size: 1.8rem;
            color: white;
            font-weight: 700;
        }

        .about-title {
            font-family: 'Fraunces', serif;
            font-size: 1.6rem;
            color: white;
            margin-bottom: 1.2rem;
            font-weight: 700;
        }

        .about-desc {
            color: rgba(255,255,255,0.85);
            line-height: 1.8;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .about-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .feature-box {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
            border: 1px solid rgba(255,255,255,0.15);
        }

        .feature-box p {
            color: rgba(255,255,255,0.9);
            font-size: 0.82rem;
            line-height: 1.6;
        }

        /* ===== LAYANAN ===== */
        .layanan {
            padding: 80px 8%;
            background: #fafffe;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title {
            font-family: 'Fraunces', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
        }

        .section-desc {
            color: var(--text-light);
            font-size: 0.95rem;
            max-width: 560px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .layanan-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .layanan-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid rgba(74,124,47,0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .layanan-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--green-main), var(--green-light));
            transform: scaleX(0);
            transition: transform 0.3s;
        }

        .layanan-card:hover {
            box-shadow: 0 10px 40px var(--shadow);
            transform: translateY(-4px);
            border-color: var(--green-pale);
        }

        .layanan-card:hover::before { transform: scaleX(1); }

        .card-icon {
            width: 56px; height: 56px;
            background: var(--green-bg);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--green-main);
            margin-bottom: 1.2rem;
        }

        .card-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.6rem;
        }

        .card-desc {
            font-size: 0.85rem;
            color: var(--text-light);
            line-height: 1.7;
        }

        .layanan-img-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, var(--green-bg), var(--green-pale));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1.2rem;
        }

        .layanan-img-placeholder i {
            font-size: 3rem;
            color: var(--green-main);
            opacity: 0.4;
        }

        /* ===== FOOTER ===== */
        footer {
            background: var(--green-dark);
            color: rgba(255,255,255,0.7);
            text-align: center;
            padding: 28px;
            font-size: 0.85rem;
        }

        footer strong { color: white; }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-up {
            animation: fadeUp 0.7s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; opacity: 0; }
        .delay-2 { animation-delay: 0.2s; opacity: 0; }
        .delay-3 { animation-delay: 0.3s; opacity: 0; }
        .delay-4 { animation-delay: 0.4s; opacity: 0; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .hero { grid-template-columns: 1fr; padding: 100px 5% 60px; text-align: center; }
            .hero-title { font-size: 2.2rem; }
            .hero-actions { justify-content: center; flex-wrap: wrap; }
            .hero-visual { display: none; }
            .about { grid-template-columns: 1fr; padding: 60px 5%; }
            .about-logo-big { display: none; }
            .about-features { grid-template-columns: 1fr; }
            .layanan-grid { grid-template-columns: 1fr; }
            .nav-links { display: none; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav id="navbar">
    <a href="index.php" class="nav-logo">
        <div class="nav-logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <span class="nav-logo-text">SIKESMA</span>
    </a>
    <ul class="nav-links">
        <li><a href="index.php">Beranda</a></li>
        <li><a href="#pengaduan">Pengaduan</a></li>
        <li><a href="#layanan">Informasi</a></li>
        <li><a href="login.php" class="btn-login">Login</a></li>
    </ul>
</nav>

<!-- HERO -->
<section class="hero" id="beranda">
    <div class="hero-content">
        <div class="hero-badge animate-up delay-1">
            <i class="fas fa-shield-heart"></i>
            Platform Kesejahteraan Kampus
        </div>
        <h1 class="hero-title animate-up delay-2">
            Sistem<br><span>Kesejahteraan</span><br>Mahasiswa
        </h1>
        <p class="hero-desc animate-up delay-3">
            Mendukung Kesejahteraan dan Kesuksesan Mahasiswa di Kampus. Sampaikan aspirasimu, laporkan permasalahan, dan dapatkan solusi terbaik bersama kami.
        </p>
        <div class="hero-actions animate-up delay-4">
            <a href="login.php" class="btn-primary">
                <i class="fas fa-paper-plane"></i>
                Buat Pengaduan
            </a>
            <a href="#layanan" class="btn-outline">Pelajari Selengkapnya</a>
        </div>
    </div>
    <div class="hero-visual animate-up delay-3">
        <div class="hero-img-wrap">
            <div class="hero-img-placeholder">
                <i class="fas fa-users"></i>
                <p>Konseling Mahasiswa</p>
            </div>
        </div>
        <div class="hero-stat-card card-1">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="num">98%</div>
                <div class="lbl">Terselesaikan</div>
            </div>
        </div>
        <div class="hero-stat-card card-2">
            <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <div class="num">24h</div>
                <div class="lbl">Respon Cepat</div>
            </div>
        </div>
    </div>
</section>

<!-- ABOUT -->
<section class="about" id="tentang">
    <div class="about-logo-big">
        <div class="logo-circle">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <span>SIKESMA</span>
    </div>
    <div>
        <h2 class="about-title">Tentang SIKESMA</h2>
        <p class="about-desc">
            SIKESMA (Sistem Kesejahteraan Mahasiswa) adalah platform digital yang dirancang untuk memfasilitasi mahasiswa dalam menyampaikan pengaduan, aspirasi, dan permasalahan yang dihadapi selama menempuh pendidikan. Kami berkomitmen untuk menciptakan lingkungan kampus yang aman, nyaman, dan kondusif bagi seluruh civitas akademika.
        </p>
        <div class="about-features">
            <div class="feature-box">
                <p><strong style="color:white">🔒 Kerahasiaan Terjamin</strong><br>Identitas pelapor terlindungi dan dijaga kerahasiaannya</p>
            </div>
            <div class="feature-box">
                <p><strong style="color:white">⚡ Respon Cepat</strong><br>Tim kami siap menindaklanjuti setiap laporan dalam 24 jam</p>
            </div>
            <div class="feature-box">
                <p><strong style="color:white">📊 Transparan</strong><br>Pantau perkembangan pengaduan Anda secara real-time</p>
            </div>
        </div>
    </div>
</section>

<!-- LAYANAN -->
<section class="layanan" id="layanan">
    <div class="section-header">
        <h2 class="section-title">Layanan SIKESMA</h2>
        <p class="section-desc">Kami menyediakan berbagai layanan untuk mendukung kesejahteraan dan keberhasilan mahasiswa selama masa studi.</p>
    </div>
    <div class="layanan-grid">
        <div class="layanan-card">
            <div class="card-icon"><i class="fas fa-bullhorn"></i></div>
            <div class="card-title">Layanan Pengaduan</div>
            <p class="card-desc">Sampaikan pengaduan terkait permasalahan kampus, pelecehan, kekerasan, atau ketidaknyamanan dengan aman dan terpercaya.</p>
            <div class="layanan-img-placeholder"><i class="fas fa-bullhorn"></i></div>
        </div>
        <div class="layanan-card">
            <div class="card-icon"><i class="fas fa-heart-pulse"></i></div>
            <div class="card-title">Konseling & Dukungan</div>
            <p class="card-desc">Akses layanan konseling psikologi dan dukungan kesejahteraan mental untuk membantu mahasiswa menghadapi tantangan.</p>
            <div class="layanan-img-placeholder"><i class="fas fa-heart-pulse"></i></div>
        </div>
        <div class="layanan-card">
            <div class="card-icon"><i class="fas fa-chart-line"></i></div>
            <div class="card-title">Monitoring & Evaluasi</div>
            <p class="card-desc">Pantau status pengaduan secara real-time dan dapatkan notifikasi perkembangan tindak lanjut dari tim SIKESMA.</p>
            <div class="layanan-img-placeholder"><i class="fas fa-chart-line"></i></div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <p>&copy; 2024 <strong>SIKESMA</strong> — Sistem Kesejahteraan Mahasiswa. Hak cipta dilindungi.</p>
</footer>

<script>
    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 20);
    });
</script>
</body>
</html>