<?php
require_once '../session.php';
require_once '../db.php';

// Jika sudah login sebagai admin, redirect
if (isAdmin()) {
    header('Location: pages/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input
    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } elseif (strlen($username) > 50) {
        $error = 'Username terlalu panjang.';
    } elseif (strlen($password) > 100) {
        $error = 'Password terlalu panjang.';
    } else {
        $stmt = $conn->prepare("SELECT id, nama_pengguna, nama_lengkap, kata_sandi, jabatan, aktif FROM admin WHERE nama_pengguna = ? OR email = ? LIMIT 1");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && $admin['aktif'] && password_verify($password, $admin['kata_sandi'])) {
            // Set session admin
            session_regenerate_id(true);
            $_SESSION['user_id']      = $admin['id'];
            $_SESSION['nama_lengkap'] = $admin['nama_lengkap'];
            $_SESSION['role']         = 'admin';
            $_SESSION['username']     = $admin['nama_pengguna'];
            $_SESSION['jabatan']      = $admin['jabatan'];
            $_SESSION['login_time']   = time();

            // Log login (opsional - bisa diaktifkan jika ada tabel log)
            header('Location: pages/admin/dashboard.php');
            exit;
        } else {
            // Delay anti brute-force
            sleep(1);
            $error = 'Username atau password salah, atau akun tidak aktif.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKESMA — Login Administrator</title>
    <link rel="icon" href="img/image.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fraunces:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --g-dark:  #1a2e0d;
            --g-main:  #2d5016;
            --g-mid:   #4a7c2f;
            --g-light: #7ab648;
            --g-pale:  #c8e6a0;
            --g-bg:    #f0f7e8;
            --accent:  #e8b84b;   /* emas untuk panel admin */
            --white:   #ffffff;
        }

        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: #0e1f07;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated background mesh */
        .bg-mesh {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 30%, rgba(74,124,47,0.25) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 70%, rgba(45,80,22,0.3) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 50% 10%, rgba(122,182,72,0.1) 0%, transparent 60%);
            animation: meshMove 8s ease-in-out infinite alternate;
        }
        @keyframes meshMove {
            0%   { opacity: 0.8; transform: scale(1); }
            100% { opacity: 1;   transform: scale(1.05); }
        }

        /* Grid overlay */
        .bg-grid {
            position: fixed; inset: 0; z-index: 0;
            background-image:
                linear-gradient(rgba(74,124,47,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(74,124,47,0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Floating orbs */
        .orb {
            position: fixed; border-radius: 50%;
            filter: blur(60px); pointer-events: none; z-index: 0;
            animation: orbFloat 6s ease-in-out infinite alternate;
        }
        .orb-1 { width:300px; height:300px; background:rgba(74,124,47,0.18); top:-80px; left:-80px; animation-delay:0s; }
        .orb-2 { width:200px; height:200px; background:rgba(122,182,72,0.12); bottom:50px; right:-60px; animation-delay:2s; }
        .orb-3 { width:150px; height:150px; background:rgba(232,184,75,0.08); top:50%; left:60%; animation-delay:4s; }
        @keyframes orbFloat {
            0%   { transform: translate(0,0) scale(1); }
            100% { transform: translate(20px,20px) scale(1.1); }
        }

        /* ===== CARD ===== */
        .login-card {
            position: relative; z-index: 1;
            width: 100%; max-width: 440px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            backdrop-filter: blur(20px);
            padding: 44px 40px;
            box-shadow:
                0 0 0 1px rgba(74,124,47,0.15),
                0 20px 60px rgba(0,0,0,0.5),
                inset 0 1px 0 rgba(255,255,255,0.06);
            animation: cardIn 0.6s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes cardIn {
            from { opacity:0; transform: translateY(30px) scale(0.97); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }

        /* TOP BADGE */
        .admin-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: rgba(232,184,75,0.12);
            border: 1px solid rgba(232,184,75,0.25);
            color: var(--accent);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 24px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        /* LOGO */
        .logo-wrap {
            text-align: center;
            margin-bottom: 28px;
        }
        .logo-icon {
            width: 72px; height: 72px;
            margin: 0 auto 14px;
            background: linear-gradient(135deg, var(--g-mid), var(--g-light));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 8px 32px rgba(74,124,47,0.4), 0 0 0 4px rgba(74,124,47,0.15);
            position: relative;
        }
        .logo-icon::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(122,182,72,0.4), rgba(232,184,75,0.2));
            z-index: -1;
        }
        .logo-title {
            font-family: 'Fraunces', serif;
            font-size: 1.7rem;
            font-weight: 700;
            color: white;
            letter-spacing: -0.5px;
        }
        .logo-sub {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.45);
            margin-top: 4px;
        }

        /* DIVIDER */
        .divider-line {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.07);
            margin: 24px 0;
        }

        /* ALERT */
        .alert-error {
            background: rgba(231,76,60,0.12);
            border: 1px solid rgba(231,76,60,0.3);
            color: #ff8a80;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%,60%  { transform: translateX(-6px); }
            40%,80%  { transform: translateX(6px); }
        }

        /* FORM */
        .form-group { margin-bottom: 16px; }

        .form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: rgba(255,255,255,0.55);
            margin-bottom: 7px;
            letter-spacing: 0.3px;
        }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: rgba(122,182,72,0.6);
            font-size: 0.9rem;
            pointer-events: none;
            transition: color 0.2s;
        }

        .form-input {
            width: 100%;
            height: 48px;
            padding: 0 46px 0 42px;
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: 11px;
            font-size: 0.9rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: white;
            outline: none;
            transition: all 0.25s;
            caret-color: var(--g-light);
        }
        .form-input::placeholder { color: rgba(255,255,255,0.2); }
        .form-input:focus {
            border-color: rgba(122,182,72,0.6);
            background: rgba(255,255,255,0.09);
            box-shadow: 0 0 0 3px rgba(122,182,72,0.12), 0 0 20px rgba(74,124,47,0.15);
        }
        .form-input:focus + .input-icon,
        .input-wrap:focus-within .input-icon { color: var(--g-light); }

        .toggle-pass {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: rgba(255,255,255,0.3);
            cursor: pointer;
            font-size: 0.85rem;
            transition: color 0.2s;
            padding: 4px;
        }
        .toggle-pass:hover { color: var(--g-light); }

        /* Field error */
        .field-err {
            font-size: 0.7rem;
            color: #ff8a80;
            margin-top: 5px;
            display: none;
            padding-left: 2px;
        }
        .field-err.show { display: block; }

        /* Char counter */
        .char-counter {
            position: absolute;
            right: 46px; top: 50%;
            transform: translateY(-50%);
            font-size: 0.65rem;
            color: rgba(255,255,255,0.2);
            pointer-events: none;
            display: none;
        }
        .input-wrap:focus-within .char-counter { display: block; }
        .char-counter.warn { color: #e67e22; }
        .char-counter.over { color: #e74c3c; }

        /* SUBMIT */
        .btn-submit {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, var(--g-mid) 0%, var(--g-light) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(74,124,47,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
            position: relative;
            overflow: hidden;
        }
        .btn-submit::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .btn-submit:hover::before { opacity: 1; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(74,124,47,0.45); }
        .btn-submit:active { transform: translateY(0); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* LINKS */
        .card-footer {
            text-align: center;
            margin-top: 20px;
        }
        .card-footer a {
            color: rgba(122,182,72,0.7);
            text-decoration: none;
            font-size: 0.8rem;
            transition: color 0.2s;
        }
        .card-footer a:hover { color: var(--g-light); }
        .card-footer span { color: rgba(255,255,255,0.2); margin: 0 8px; }

        /* Strength bar */
        .strength-wrap {
            height: 2px;
            background: rgba(255,255,255,0.08);
            border-radius: 2px;
            margin-top: 6px;
            overflow: hidden;
            display: none;
        }
        .strength-fill {
            height: 100%;
            border-radius: 2px;
            width: 0;
            transition: all 0.3s;
        }

        /* Lockout notice */
        .lockout-notice {
            background: rgba(232,184,75,0.08);
            border: 1px solid rgba(232,184,75,0.2);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.75rem;
            color: rgba(232,184,75,0.8);
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 14px;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<div class="bg-mesh"></div>
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="login-card">

    <div class="admin-badge">
        <i class="fas fa-shield-halved"></i> Admin Panel
    </div>

    <div class="logo-wrap">
        <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="logo-title">SIKESMA</div>
        <div class="logo-sub">Sistem Kesejahteraan Mahasiswa</div>
    </div>

    <hr class="divider-line">

    <?php if ($error): ?>
    <div class="alert-error">
        <i class="fas fa-circle-exclamation" style="flex-shrink:0"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'logout'): ?>
    <div style="background:rgba(74,124,47,0.12);border:1px solid rgba(74,124,47,0.25);color:rgba(122,182,72,0.9);border-radius:10px;padding:10px 14px;font-size:0.8rem;display:flex;align-items:center;gap:7px;margin-bottom:16px">
        <i class="fas fa-check-circle"></i> Anda berhasil keluar dari sistem.
    </div>
    <?php endif; ?>

    <form method="POST" id="adminLoginForm" novalidate>

        <div class="form-group">
            <label class="form-label">Username / Email Admin</label>
            <div class="input-wrap">
                <i class="fas fa-user-shield input-icon"></i>
                <input
                    type="text"
                    name="username"
                    id="username"
                    class="form-input"
                    placeholder="Masukkan username atau email"
                    maxlength="50"
                    autocomplete="username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    autofocus>
                <span class="char-counter" id="usernameCounter"></span>
            </div>
            <span class="field-err" id="usernameErr">Username tidak boleh kosong (maks. 50 karakter).</span>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-wrap">
                <i class="fas fa-lock input-icon"></i>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-input"
                    placeholder="Masukkan password"
                    maxlength="100"
                    autocomplete="current-password">
                <span class="char-counter" id="passwordCounter"></span>
                <button type="button" class="toggle-pass" id="togglePassBtn" onclick="togglePassword()">
                    <i class="fas fa-eye" id="passIcon"></i>
                </button>
            </div>
            <span class="field-err" id="passwordErr">Password tidak boleh kosong (maks. 100 karakter).</span>
        </div>

        <button type="submit" class="btn-submit" id="loginBtn">
            <i class="fas fa-right-to-bracket"></i> Masuk ke Panel Admin
        </button>
    </form>

    <div class="lockout-notice">
        <i class="fas fa-triangle-exclamation"></i>
        Halaman ini hanya untuk Administrator. Akses tidak sah akan dicatat.
    </div>

    <div class="card-footer" style="margin-top:18px">
        <a href="index.php"><i class="fas fa-arrow-left" style="font-size:0.7rem"></i> Kembali ke Beranda</a>
        <span>|</span>
        <a href="login.php">Login Mahasiswa</a>
    </div>

</div>

<script>
// ===== Toggle Password =====
function togglePassword() {
    const inp  = document.getElementById('password');
    const icon = document.getElementById('passIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// ===== Char Counter =====
function setupCounter(inputId, counterId, max) {
    const inp = document.getElementById(inputId);
    const ctr = document.getElementById(counterId);
    if (!inp || !ctr) return;
    inp.addEventListener('input', () => {
        const len = inp.value.length;
        ctr.textContent = len + '/' + max;
        ctr.className = 'char-counter' + (len >= max ? ' over' : len > max * 0.8 ? ' warn' : '');
    });
}
setupCounter('username', 'usernameCounter', 50);
setupCounter('password', 'passwordCounter', 100);

// ===== Validasi Sisi Klien =====
document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
    let valid = true;
    document.querySelectorAll('.field-err').forEach(el => el.classList.remove('show'));

    const user = document.getElementById('username');
    const pass = document.getElementById('password');

    if (!user.value.trim() || user.value.length > 50) {
        document.getElementById('usernameErr').classList.add('show');
        user.focus();
        valid = false;
    }
    if (!pass.value || pass.value.length > 100) {
        document.getElementById('passwordErr').classList.add('show');
        if (valid) pass.focus();
        valid = false;
    }

    if (!valid) {
        e.preventDefault();
        return;
    }

    // Loading state
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memverifikasi...';
});

// ===== Enter key =====
document.addEventListener('keydown', e => {
    if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
        document.getElementById('adminLoginForm').dispatchEvent(new Event('submit'));
    }
});


</script>

</body>
</html>