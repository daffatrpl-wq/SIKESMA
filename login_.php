<?php
require_once 'session.php';
require_once 'db.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/index.php' : 'mahasiswa/index.php'));
    exit;
}

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'login') {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $error = 'Username/email dan password wajib diisi.';
        } else {
            // Cek admin
            $stmt = $conn->prepare("SELECT id, nama_pengguna, nama_lengkap, kata_sandi, aktif FROM admin WHERE nama_pengguna = ? OR email = ? LIMIT 1");
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($admin && $admin['aktif'] && password_verify($password, $admin['kata_sandi'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['nama_lengkap'] = $admin['nama_lengkap'];
                $_SESSION['role'] = 'admin';
                $_SESSION['username'] = $admin['nama_pengguna'];
                header('Location: admin/index.php');
                exit;
            }

            // Cek mahasiswa
            $stmt = $conn->prepare("SELECT id, nim, nama_lengkap, kata_sandi, aktif FROM mahasiswa WHERE nim = ? OR email = ? LIMIT 1");
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $mhs = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($mhs && $mhs['aktif'] && password_verify($password, $mhs['kata_sandi'])) {
                $_SESSION['user_id'] = $mhs['id'];
                $_SESSION['nama_lengkap'] = $mhs['nama_lengkap'];
                $_SESSION['role'] = 'mahasiswa';
                $_SESSION['nim'] = $mhs['nim'];
                header('Location: mahasiswa/index.php');
                exit;
            }

            $error = 'Username/NIM atau password salah, atau akun tidak aktif.';
        }
    }

    if ($_POST['action'] === 'register') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $nim = trim($_POST['nim'] ?? '');
        $jurusan = (int)($_POST['jurusan'] ?? 0);

        if (empty($nama) || empty($email) || empty($password) || empty($nim)) {
            $error = 'Semua field wajib diisi.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            // Cek duplikat
            $stmt = $conn->prepare("SELECT id FROM mahasiswa WHERE nim = ? OR email = ? LIMIT 1");
            $stmt->bind_param('ss', $nim, $email);
            $stmt->execute();
            $dup = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($dup) {
                $error = 'NIM atau email sudah terdaftar.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO mahasiswa (nim, nama_lengkap, email, kata_sandi, id_jurusan) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssi', $nim, $nama, $email, $hash, $jurusan);
                if ($stmt->execute()) {
                    $success = 'Akun berhasil dibuat! Silakan login.';
                    $mode = 'login';
                } else {
                    $error = 'Gagal mendaftarkan akun. Coba lagi.';
                }
                $stmt->close();
            }
        }
    }
}

$jurusanList = $conn->query("SELECT id, nama_jurusan, nama_fakultas FROM jurusan ORDER BY nama_fakultas, nama_jurusan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKESMA - <?= $mode === 'login' ? 'Login' : 'Daftar Akun' ?></title>
    <link rel="icon" href="img/image.png" type="image/x-icon">
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: #f0f7e8;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -30%; left: -20%;
            width: 70vw; height: 70vw;
            border-radius: 50%;
            background: rgba(74,124,47,0.06);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -20%; right: -20%;
            width: 50vw; height: 50vw;
            border-radius: 50%;
            background: rgba(122,182,72,0.08);
            pointer-events: none;
        }

        /* Decorative bg circles */
        .bg-decor { position: fixed; pointer-events: none; z-index: 0; }
        .bg-decor-1 { width: 18vw; height: 18vw; background: rgba(74,124,47,0.12); border-radius: 50%; top: 5%; left: 5%; }
        .bg-decor-2 { width: 12vw; height: 12vw; background: rgba(74,124,47,0.09); border-radius: 50%; top: 18%; left: 14%; }
        .bg-decor-3 { width: 8vw; height: 8vw; background: rgba(74,124,47,0.07); border-radius: 50%; bottom: 15%; left: 8%; }

        .auth-card {
            background: white;
            border-radius: 24px;
            width: 100%;
            max-width: 880px;
            display: grid;
            grid-template-columns: 1fr 1.1fr;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(45,80,22,0.18);
            position: relative;
            z-index: 1;
        }

        /* LEFT PANEL */
        .panel-left {
            background: linear-gradient(160deg, var(--green-main) 0%, var(--green-dark) 100%);
            padding: 48px 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .panel-left::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }

        .panel-left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -40px;
            width: 260px; height: 260px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }

        /* Decorative circles bottom left */
        .left-deco { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.07); }
        .left-deco-1 { width: 140px; height: 140px; bottom: 30px; left: -40px; }
        .left-deco-2 { width: 90px; height: 90px; bottom: 60px; left: 60px; }
        .left-deco-3 { width: 60px; height: 60px; bottom: 100px; left: 130px; }

        .left-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
        }

        .logo-shield {
            width: 80px; height: 80px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255,255,255,0.3);
            margin-bottom: 12px;
        }

        .logo-shield i { font-size: 2.2rem; color: white; }

        .left-logo span {
            font-family: 'Fraunces', serif;
            font-size: 1.6rem;
            color: white;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .left-logo small {
            color: rgba(255,255,255,0.7);
            font-size: 0.65rem;
            text-align: center;
            max-width: 140px;
            line-height: 1.4;
            margin-top: 4px;
        }

        .left-content {
            z-index: 1;
        }

        .left-content h2 {
            font-family: 'Fraunces', serif;
            font-size: 2rem;
            color: white;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .left-content p {
            color: rgba(255,255,255,0.75);
            font-size: 0.88rem;
            line-height: 1.7;
            margin-bottom: 28px;
        }

        .btn-switch {
            display: inline-block;
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.5);
            padding: 10px 26px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .btn-switch:hover {
            background: rgba(255,255,255,0.15);
            border-color: white;
        }

        /* RIGHT PANEL */
        .panel-right {
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-title {
            font-family: 'Fraunces', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a2e0d;
            margin-bottom: 6px;
        }

        .form-sub {
            font-size: 0.82rem;
            color: #6b8f52;
            margin-bottom: 24px;
        }

        .social-row {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
        }

        .social-btn {
            flex: 1;
            height: 40px;
            border: 1.5px solid #e0eed0;
            border-radius: 8px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s;
            color: #555;
        }

        .social-btn:hover { border-color: var(--green-main); background: var(--green-bg); }
        .social-btn .fa-facebook { color: #1877f2; }
        .social-btn .fa-google { color: #ea4335; }

        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            color: #a0b890;
            font-size: 0.75rem;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0eed0;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.83rem;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error { background: #fff0f0; color: #c0392b; border: 1px solid #f5c6c6; }
        .alert-success { background: #f0fff4; color: #27ae60; border: 1px solid #b2e5c5; }

        .input-group {
            position: relative;
            margin-bottom: 14px;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #8ab06a;
            font-size: 0.95rem;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            height: 46px;
            padding: 0 14px 0 42px;
            border: 1.5px solid #d8ecc0;
            border-radius: 10px;
            font-size: 0.88rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1a2e0d;
            background: #f8fff2;
            outline: none;
            transition: all 0.2s;
        }

        .form-input:focus {
            border-color: var(--green-main);
            background: white;
            box-shadow: 0 0 0 3px rgba(74,124,47,0.08);
        }

        .form-input::placeholder { color: #aac890; }

        .form-select {
            width: 100%;
            height: 46px;
            padding: 0 14px 0 42px;
            border: 1.5px solid #d8ecc0;
            border-radius: 10px;
            font-size: 0.88rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1a2e0d;
            background: #f8fff2;
            outline: none;
            transition: all 0.2s;
            appearance: none;
            cursor: pointer;
        }

        .form-select:focus {
            border-color: var(--green-main);
            background: white;
            box-shadow: 0 0 0 3px rgba(74,124,47,0.08);
        }

        .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #8ab06a;
            cursor: pointer;
            font-size: 0.9rem;
            background: none;
            border: none;
            padding: 0;
        }

        .char-count {
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.68rem;
            color: #aac890;
        }

        .char-count.warn { color: #e67e22; }
        .char-count.over { color: #c0392b; }

        .field-error {
            font-size: 0.72rem;
            color: #c0392b;
            margin-top: -10px;
            margin-bottom: 8px;
            display: none;
            padding-left: 4px;
        }

        .field-error.show { display: block; }

        .btn-submit {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, var(--green-main), var(--green-light));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            margin-top: 6px;
            box-shadow: 0 4px 16px rgba(74,124,47,0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(74,124,47,0.4);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .back-link {
            text-align: center;
            margin-top: 14px;
            font-size: 0.8rem;
            color: #6b8f52;
        }

        .back-link a {
            color: var(--green-main);
            text-decoration: none;
            font-weight: 600;
        }

        .strength-bar {
            height: 3px;
            border-radius: 2px;
            margin-top: -10px;
            margin-bottom: 10px;
            background: #e0eed0;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            border-radius: 2px;
            transition: all 0.3s;
            width: 0;
        }

        @media (max-width: 640px) {
            .auth-card { grid-template-columns: 1fr; }
            .panel-left { display: none; }
            .panel-right { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<div class="bg-decor bg-decor-1"></div>
<div class="bg-decor bg-decor-2"></div>
<div class="bg-decor bg-decor-3"></div>

<div class="auth-card">
    <!-- LEFT PANEL -->
    <div class="panel-left">
        <div class="left-deco left-deco-1"></div>
        <div class="left-deco left-deco-2"></div>
        <div class="left-deco left-deco-3"></div>

        <div class="left-logo">
            <div class="logo-shield">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <span>SIKESMA</span>
            <small>Sistem Kesejahteraan Mahasiswa</small>
        </div>

        <?php if ($mode === 'login'): ?>
        <div class="left-content">
            <h2>Selamat Datang!</h2>
            <p>Belum punya akun? Daftar sekarang dan mulai sampaikan aspirasimu kepada kami.</p>
            <a href="login.php?mode=register" class="btn-switch" id="switchBtn">DAFTAR</a>
        </div>
        <?php else: ?>
        <div class="left-content">
            <h2>Sudah Punya Akun?</h2>
            <p>Masuk dengan akun yang sudah ada untuk melanjutkan aktivitas Anda di SIKESMA.</p>
            <a href="login.php?mode=login" class="btn-switch" id="switchBtn">MASUK</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT PANEL -->
    <div class="panel-right">
        <?php if ($mode === 'login'): ?>
        <!-- ===== FORM LOGIN ===== -->
        <h2 class="form-title">Masuk</h2>
        <p class="form-sub">Gunakan NIM/username atau email Anda</p>

        <div class="social-row">
            <button class="social-btn" title="Login dengan Facebook"><i class="fab fa-facebook"></i></button>
            <button class="social-btn" title="Login dengan Google"><i class="fab fa-google"></i></button>
            <button class="social-btn" title="Login dengan Threads"><i class="fas fa-at"></i></button>
        </div>
        <div class="divider">atau gunakan email / NIM untuk login</div>

        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm" novalidate>
            <input type="hidden" name="action" value="login">

            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="identifier" id="identifier" class="form-input"
                    placeholder="NIM / Username / Email"
                    maxlength="100"
                    value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                    autocomplete="username">
                <span class="char-count" id="identifierCount"></span>
            </div>
            <span class="field-error" id="identifierErr">NIM/Username tidak boleh kosong.</span>

            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="password" class="form-input"
                    placeholder="Password"
                    maxlength="100"
                    autocomplete="current-password">
                <button type="button" class="toggle-pass" onclick="togglePass('password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <span class="field-error" id="passwordErr">Password tidak boleh kosong.</span>

            <button type="submit" class="btn-submit" id="loginBtn">MASUK</button>
        </form>

        <div class="back-link">
            Belum punya akun? <a href="login.php?mode=register">Daftar di sini</a>
        </div>

        <?php else: ?>
        <!-- ===== FORM REGISTER ===== -->
        <h2 class="form-title">Buat Akun</h2>
        <p class="form-sub">Daftar sebagai mahasiswa baru SIKESMA</p>
        <div class="social-row">
            <button class="social-btn" title="Daftar dengan Facebook"><i class="fab fa-facebook"></i></button>
            <button class="social-btn" title="Daftar dengan Google"><i class="fab fa-google"></i></button>
            <button class="social-btn" title="Daftar dengan Threads"><i class="fas fa-at"></i></button>
        </div>
        <div class="divider">atau gunakan email untuk registrasi</div>

        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm" novalidate>
            <input type="hidden" name="action" value="register">

            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="nama" id="regNama" class="form-input"
                    placeholder="Nama Lengkap"
                    maxlength="100"
                    value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                <span class="char-count" id="namaCount"></span>
            </div>
            <span class="field-error" id="namaErr">Nama lengkap wajib diisi.</span>

            <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" id="regEmail" class="form-input"
                    placeholder="Email"
                    maxlength="100"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <span class="field-error" id="emailErr">Email tidak valid.</span>

            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="regPass" class="form-input"
                    placeholder="Password (min 6 karakter)"
                    maxlength="100">
                <button type="button" class="toggle-pass" onclick="togglePass('regPass', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <span class="field-error" id="passErr">Password minimal 6 karakter.</span>

            <div class="input-group">
                <i class="fas fa-id-card input-icon"></i>
                <input type="text" name="nim" id="regNim" class="form-input"
                    placeholder="NIM"
                    maxlength="12"
                    value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>">
                <span class="char-count" id="nimCount"></span>
            </div>
            <span class="field-error" id="nimErr">NIM harus 8-12 digit angka.</span>

            <div class="input-group">
                <i class="fas fa-building-columns input-icon"></i>
                <select name="jurusan" id="regJurusan" class="form-select">
                    <option value="">-- Pilih Jurusan --</option>
                    <?php while ($j = $jurusanList->fetch_assoc()): ?>
                    <option value="<?= $j['id'] ?>" <?= ($_POST['jurusan'] ?? '') == $j['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($j['nama_jurusan']) ?> — <?= htmlspecialchars($j['nama_fakultas']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit" id="regBtn">DAFTAR</button>
        </form>
        <div class="back-link">
            Sudah punya akun? <a href="login.php?mode=login">Masuk di sini</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePass(id, btn) {
    const inp = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// ===== Char Counter =====
function setupCharCounter(inputId, counterId, max) {
    const inp = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    if (!inp || !counter) return;
    inp.addEventListener('input', () => {
        const len = inp.value.length;
        counter.textContent = len + '/' + max;
        counter.className = 'char-count' + (len > max * 0.85 ? (len >= max ? ' over' : ' warn') : '');
    });
}

setupCharCounter('identifier', 'identifierCount', 100);
setupCharCounter('regNama', 'namaCount', 100);
setupCharCounter('regNim', 'nimCount', 12);

// ===== Password Strength =====
const regPass = document.getElementById('regPass');
const fill = document.getElementById('strengthFill');
if (regPass && fill) {
    regPass.addEventListener('input', () => {
        const v = regPass.value;
        let strength = 0;
        if (v.length >= 6) strength++;
        if (v.length >= 10) strength++;
        if (/[A-Z]/.test(v)) strength++;
        if (/[0-9]/.test(v)) strength++;
        if (/[^A-Za-z0-9]/.test(v)) strength++;
        const pct = (strength / 5) * 100;
        fill.style.width = pct + '%';
        fill.style.background = pct < 40 ? '#e74c3c' : pct < 70 ? '#e67e22' : '#27ae60';
    });
}

// ===== Validasi NIM hanya angka =====
const regNim = document.getElementById('regNim');
if (regNim) {
    regNim.addEventListener('input', () => {
        regNim.value = regNim.value.replace(/\D/g, '');
    });
}

// ===== Validasi Form Login =====
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        let valid = true;

        const id = document.getElementById('identifier');
        const pw = document.getElementById('password');
        const idErr = document.getElementById('identifierErr');
        const pwErr = document.getElementById('passwordErr');

        idErr.classList.remove('show');
        pwErr.classList.remove('show');

        if (!id.value.trim()) {
            idErr.classList.add('show');
            valid = false;
        }
        if (!pw.value) {
            pwErr.classList.add('show');
            valid = false;
        }

        if (!valid) e.preventDefault();
    });
}

// ===== Validasi Form Register =====
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
        let valid = true;

        const nama = document.getElementById('regNama');
        const email = document.getElementById('regEmail');
        const pass = document.getElementById('regPass');
        const nim = document.getElementById('regNim');

        document.querySelectorAll('.field-error').forEach(el => el.classList.remove('show'));

        if (!nama || nama.value.trim().length < 3) {
            document.getElementById('namaErr').textContent = 'Nama minimal 3 karakter.';
            document.getElementById('namaErr').classList.add('show');
            valid = false;
        }

        const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email || !emailRx.test(email.value.trim())) {
            document.getElementById('emailErr').classList.add('show');
            valid = false;
        }

        if (!pass || pass.value.length < 6) {
            document.getElementById('passErr').classList.add('show');
            valid = false;
        }

        if (!nim || nim.value.length < 8 || nim.value.length > 12) {
            document.getElementById('nimErr').classList.add('show');
            valid = false;
        }

        if (!valid) e.preventDefault();
    });
}

// ===== Enter key on inputs =====
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const form = document.getElementById('loginForm') || document.getElementById('registerForm');
        if (form) form.submit();
    }
});
</script>
</body>
</html>
