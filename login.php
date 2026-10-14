<?php
require_once 'session.php';
require_once 'db.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/index.php' : 'mahasiswa/index.php'));
    exit;
}

// Kode rahasia untuk registrasi admin — ganti sesuai kebutuhan
define('ADMIN_SECRET_CODE', 'SIKESMA2024');

$error   = '';
$success = '';
$mode    = $_GET['mode'] ?? 'login';       
$regTab  = $_GET['tab']  ?? 'mahasiswa';   

//  PROSES POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    /* ─── LOGIN ─── */
    if ($_POST['action'] === 'login') {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $error = 'Username/email dan password wajib diisi.';
        } elseif (strlen($identifier) > 100 || strlen($password) > 100) {
            $error = 'Input melebihi batas karakter yang diizinkan.';
        } else {
            // Cek tabel admin
            $stmt = $conn->prepare("SELECT id,nama_pengguna,nama_lengkap,kata_sandi,jabatan,aktif FROM admin WHERE nama_pengguna=? OR email=? LIMIT 1");
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($admin && $admin['aktif'] && password_verify($password, $admin['kata_sandi'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']      = $admin['id'];
                $_SESSION['nama_lengkap'] = $admin['nama_lengkap'];
                $_SESSION['role']         = 'admin';
                $_SESSION['username']     = $admin['nama_pengguna'];
                $_SESSION['jabatan']      = $admin['jabatan'];
                header('Location: admin/index.php'); exit;
            }

            // Cek tabel mahasiswa
            $stmt = $conn->prepare("SELECT id,nim,nama_lengkap,kata_sandi,aktif FROM mahasiswa WHERE nim=? OR email=? LIMIT 1");
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $mhs = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($mhs && $mhs['aktif'] && password_verify($password, $mhs['kata_sandi'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']      = $mhs['id'];
                $_SESSION['nama_lengkap'] = $mhs['nama_lengkap'];
                $_SESSION['role']         = 'mahasiswa';
                $_SESSION['nim']          = $mhs['nim'];
                header('Location: mahasiswa/index.php'); exit;
            }

            sleep(1);
            $error = 'Username/NIM atau password salah, atau akun tidak aktif.';
        }
    }

    /* ─── REGISTER MAHASISWA ─── */
    if ($_POST['action'] === 'register_mahasiswa') {
        $mode   = 'register';
        $regTab = 'mahasiswa';
        $nama    = trim($_POST['nama']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $pwd     = $_POST['password']     ?? '';
        $nim     = trim($_POST['nim']     ?? '');
        $jurusan = (int)($_POST['jurusan'] ?? 0);

        if (strlen($nama) < 3 || strlen($nama) > 100)       { $error = 'Nama harus 3–100 karakter.'; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))  { $error = 'Format email tidak valid.'; }
        elseif (strlen($pwd) < 6)                            { $error = 'Password minimal 6 karakter.'; }
        elseif (!preg_match('/^\d{8,12}$/', $nim))           { $error = 'NIM harus 8–12 digit angka.'; }
        else {
            $ck = $conn->prepare("SELECT id FROM mahasiswa WHERE nim=? OR email=? LIMIT 1");
            $ck->bind_param('ss', $nim, $email); $ck->execute();
            $dup = $ck->get_result()->fetch_assoc(); $ck->close();
            if ($dup) {
                $error = 'NIM atau email sudah terdaftar.';
            } else {
                $hash = password_hash($pwd, PASSWORD_DEFAULT);
                $st = $conn->prepare("INSERT INTO mahasiswa (nim,nama_lengkap,email,kata_sandi,id_jurusan) VALUES (?,?,?,?,?)");
                $st->bind_param('ssssi', $nim, $nama, $email, $hash, $jurusan);
                if ($st->execute()) {
                    $success = 'Akun mahasiswa berhasil dibuat! Silakan login.';
                    $mode = 'login';
                } else {
                    $error = 'Gagal mendaftarkan akun. Coba lagi.';
                }
                $st->close();
            }
        }
    }

    /* ─── REGISTER ADMIN ─── */
    if ($_POST['action'] === 'register_admin') {
        $mode   = 'register';
        $regTab = 'admin';
        $nama    = trim($_POST['admin_nama']     ?? '');
        $username= trim($_POST['admin_username'] ?? '');
        $email   = trim($_POST['admin_email']    ?? '');
        $jabatan = trim($_POST['admin_jabatan']  ?? '');
        $pwd     = $_POST['admin_password']      ?? '';
        $konfirm = $_POST['admin_konfirmasi']    ?? '';
        $kode    = trim($_POST['kode_rahasia']   ?? '');

        if ($kode !== ADMIN_SECRET_CODE) {
            $error = 'Kode rahasia tidak valid. Hubungi super admin untuk mendapatkan kode.';
        } elseif (strlen($nama) < 3 || strlen($nama) > 100)      { $error = 'Nama harus 3–100 karakter.'; }
        elseif (strlen($username) < 3 || strlen($username) > 50) { $error = 'Username harus 3–50 karakter.'; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))       { $error = 'Format email tidak valid.'; }
        elseif (strlen($pwd) < 8)                                  { $error = 'Password admin minimal 8 karakter.'; }
        elseif ($pwd !== $konfirm)                                 { $error = 'Konfirmasi password tidak cocok.'; }
        else {
            $ck = $conn->prepare("SELECT id FROM admin WHERE nama_pengguna=? OR email=? LIMIT 1");
            $ck->bind_param('ss', $username, $email); $ck->execute();
            $dup = $ck->get_result()->fetch_assoc(); $ck->close();
            if ($dup) {
                $error = 'Username atau email admin sudah terdaftar.';
            } else {
                $hash = password_hash($pwd, PASSWORD_DEFAULT);
                $st = $conn->prepare("INSERT INTO admin (nama_pengguna,nama_lengkap,email,kata_sandi,jabatan,aktif) VALUES (?,?,?,?,?,1)");
                $st->bind_param('sssss', $username, $nama, $email, $hash, $jabatan);
                if ($st->execute()) {
                    $success = 'Akun admin berhasil dibuat! Silakan login.';
                    $mode = 'login';
                } else {
                    $error = 'Gagal mendaftarkan akun admin. Coba lagi.';
                }
                $st->close();
            }
        }
    }
}

$jurusanList = $conn->query("SELECT id,nama_jurusan,nama_fakultas FROM jurusan ORDER BY nama_fakultas,nama_jurusan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIKESMA — <?= $mode==='login' ? 'Masuk' : 'Daftar Akun' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fraunces:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
    --gd:#2d5016;--gm:#4a7c2f;--gl:#7ab648;--gp:#c8e6a0;--gb:#f0f7e8;
    --gold:#e8b84b;--gold-bg:rgba(232,184,75,.08);--gold-border:rgba(232,184,75,.25);
    --white:#fff;--text:#1a2e0d;--muted:#6b8f52;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}

/* BG */
body{
    font-family:'Plus Jakarta Sans',sans-serif;
    min-height:100vh;background:#0b1a05;
    display:flex;align-items:center;justify-content:center;
    padding:20px;overflow:hidden;position:relative;
}
.bg-layer{position:fixed;inset:0;z-index:0;pointer-events:none}
.bg-mesh{position:absolute;inset:0;background:radial-gradient(ellipse 90% 70% at 15% 25%,rgba(74,124,47,.22) 0%,transparent 55%),radial-gradient(ellipse 60% 80% at 85% 75%,rgba(45,80,22,.28) 0%,transparent 55%),radial-gradient(ellipse 50% 50% at 55% 5%,rgba(122,182,72,.1) 0%,transparent 55%);animation:meshPulse 9s ease-in-out infinite alternate}
@keyframes meshPulse{0%{opacity:.7;transform:scale(1)}100%{opacity:1;transform:scale(1.06)}}
.bg-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(74,124,47,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(74,124,47,.04) 1px,transparent 1px);background-size:44px 44px}
.orb{position:absolute;border-radius:50%;filter:blur(70px);animation:orbDrift 7s ease-in-out infinite alternate}
.o1{width:320px;height:320px;background:rgba(74,124,47,.16);top:-90px;left:-90px;animation-delay:0s}
.o2{width:220px;height:220px;background:rgba(122,182,72,.11);bottom:30px;right:-60px;animation-delay:2.5s}
.o3{width:160px;height:160px;background:rgba(200,230,160,.06);top:45%;right:10%;animation-delay:5s}
@keyframes orbDrift{0%{transform:translate(0,0) scale(1)}100%{transform:translate(18px,22px) scale(1.12)}}

/* Back home */
.back-home{position:fixed;top:20px;left:24px;z-index:10;display:flex;align-items:center;gap:7px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.8);padding:7px 14px;border-radius:20px;font-size:.78rem;font-weight:600;text-decoration:none;backdrop-filter:blur(10px);transition:all .25s}
.back-home:hover{background:rgba(255,255,255,.18);color:white}

/* CARD */
.card{
    position:relative;z-index:1;
    width:100%;max-width:940px;
    display:grid;grid-template-columns:1fr 1.2fr;
    border-radius:26px;overflow:hidden;
    box-shadow:0 24px 80px rgba(0,0,0,.55),0 0 0 1px rgba(74,124,47,.18);
    animation:cardIn .65s cubic-bezier(.22,1,.36,1) both;
}
@keyframes cardIn{from{opacity:0;transform:translateY(32px) scale(.97)}to{opacity:1;transform:none}}

/* LEFT */
.panel-left{
    background:linear-gradient(155deg,var(--gm) 0%,var(--gd) 100%);
    padding:44px 36px;
    display:flex;flex-direction:column;justify-content:space-between;
    position:relative;overflow:hidden;
}
.lc{position:absolute;border-radius:50%;background:rgba(255,255,255,.06)}
.lc1{width:180px;height:180px;top:-60px;right:-60px}
.lc2{width:120px;height:120px;bottom:-30px;left:-30px}
.lc3{width:80px;height:80px;bottom:60px;left:70px}
.left-logo{display:flex;flex-direction:column;align-items:center;gap:10px;z-index:1}
.logo-icon{width:76px;height:76px;background:rgba(255,255,255,.14);border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:1.9rem;color:white;border:2px solid rgba(255,255,255,.22);box-shadow:0 8px 28px rgba(0,0,0,.22)}
.logo-name{font-family:'Fraunces',serif;font-size:1.7rem;font-weight:700;color:white;letter-spacing:.5px}
.logo-tagline{font-size:.7rem;color:rgba(255,255,255,.55);text-align:center;max-width:160px;line-height:1.5}
.left-body{z-index:1}
.left-body h2{font-family:'Fraunces',serif;font-size:1.65rem;color:white;font-weight:700;margin-bottom:10px;line-height:1.25}
.left-body p{font-size:.85rem;color:rgba(255,255,255,.72);line-height:1.75;margin-bottom:22px}
.info-pills{display:flex;flex-direction:column;gap:7px;margin-bottom:22px}
.pill{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:8px 13px;font-size:.77rem;color:rgba(255,255,255,.85)}
.pill i{color:var(--gp);width:14px;text-align:center}
.pill.gold-pill{background:var(--gold-bg);border-color:var(--gold-border)}
.pill.gold-pill i{color:var(--gold)}
.btn-switch{display:inline-block;background:transparent;color:white;border:2px solid rgba(255,255,255,.45);padding:10px 26px;border-radius:9px;font-size:.88rem;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;text-decoration:none;transition:all .3s}
.btn-switch:hover{background:rgba(255,255,255,.14);border-color:white}

/* RIGHT */
.panel-right{background:rgba(255,255,255,.97);padding:40px 40px;display:flex;flex-direction:column;justify-content:center;overflow-y:auto;max-height:100vh}
.form-title{font-family:'Fraunces',serif;font-size:1.7rem;font-weight:700;color:var(--text);margin-bottom:3px}
.form-sub{font-size:.78rem;color:var(--muted);margin-bottom:16px}

/* Role hint */
.role-hint{display:flex;align-items:center;gap:7px;background:rgba(74,124,47,.07);border:1px solid rgba(74,124,47,.15);border-radius:8px;padding:8px 13px;font-size:.74rem;color:var(--gm);font-weight:600;margin-bottom:14px}

/* Register tabs */
.reg-tabs{display:flex;gap:0;margin-bottom:18px;border:1.5px solid #d8ecc0;border-radius:11px;overflow:hidden}
.reg-tab{flex:1;padding:10px 0;text-align:center;font-size:.82rem;font-weight:700;cursor:pointer;transition:all .2s;border:none;font-family:'Plus Jakarta Sans',sans-serif;display:flex;align-items:center;justify-content:center;gap:6px}
.reg-tab.mhs-tab{background:#f8fff2;color:var(--muted)}
.reg-tab.mhs-tab.active{background:var(--gm);color:white}
.reg-tab.adm-tab{background:#f8fff2;color:var(--muted)}
.reg-tab.adm-tab.active{background:linear-gradient(135deg,#1a3a08,var(--gm));color:white}
.reg-tab.adm-tab.active i{color:var(--gold)}

/* Social */
.social-row{display:flex;gap:8px;margin-bottom:13px}
.social-btn{flex:1;height:40px;border:1.5px solid #e0eed0;border-radius:9px;background:white;display:flex;align-items:center;justify-content:center;font-size:1.1rem;cursor:pointer;transition:all .2s;color:#555}
.social-btn:hover{border-color:var(--gm);background:var(--gb)}
.social-btn .fa-facebook{color:#1877f2}
.social-btn .fa-google{color:#ea4335}
.divider{display:flex;align-items:center;gap:9px;margin-bottom:14px;color:#a0b890;font-size:.71rem}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e0eed0}

/* Alert */
.alert{padding:10px 13px;border-radius:9px;font-size:.8rem;margin-bottom:13px;display:flex;align-items:center;gap:8px}
.a-err{background:#fff0f0;color:#c0392b;border:1px solid #f5c6c6}
.a-ok {background:#f0fff4;color:#27ae60;border:1px solid #b2e5c5}

/* Inputs */
.ig{position:relative;margin-bottom:11px}
.ii{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#8ab06a;font-size:.87rem;pointer-events:none;transition:color .2s}
.fi{
    width:100%;height:45px;
    padding:0 44px 0 39px;
    border:1.5px solid #d8ecc0;border-radius:10px;
    font-size:.86rem;font-family:'Plus Jakarta Sans',sans-serif;
    color:var(--text);background:#f8fff2;outline:none;transition:all .2s;
}
.fi:focus{border-color:var(--gm);background:white;box-shadow:0 0 0 3px rgba(74,124,47,.08)}
.fi::placeholder{color:#aac890}
.fi:focus~.ii{color:var(--gm)}
.fi-sel{
    width:100%;height:45px;padding:0 12px 0 39px;
    border:1.5px solid #d8ecc0;border-radius:10px;
    font-size:.86rem;font-family:'Plus Jakarta Sans',sans-serif;
    color:var(--text);background:#f8fff2;outline:none;
    appearance:none;cursor:pointer;transition:all .2s;
}
.fi-sel:focus{border-color:var(--gm);background:white;box-shadow:0 0 0 3px rgba(74,124,47,.08)}
.toggle-pass{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#8ab06a;cursor:pointer;font-size:.84rem;padding:3px;transition:color .2s}
.toggle-pass:hover{color:var(--gm)}
.cc{position:absolute;right:38px;top:50%;transform:translateY(-50%);font-size:.6rem;color:#c0d8a0;pointer-events:none;display:none}
.ig:focus-within .cc{display:block}
.cc.warn{color:#e67e22}.cc.over{color:#e74c3c}
.fe{font-size:.69rem;color:#c0392b;margin:-7px 0 8px;padding-left:2px;display:none}
.fe.show{display:block}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:9px}

/* Password strength */
.sbar{height:3px;background:#e0eed0;border-radius:2px;margin:-7px 0 9px;overflow:hidden}
.sfill{height:100%;border-radius:2px;width:0;transition:all .3s}

/* Secret code field — special styling */
.secret-box{
    background:var(--gold-bg);
    border:1.5px solid var(--gold-border);
    border-radius:10px;
    padding:11px 14px;
    margin-bottom:11px;
}
.secret-box-label{font-size:.73rem;font-weight:700;color:#8a6a00;display:flex;align-items:center;gap:6px;margin-bottom:7px}
.secret-box .ig{margin-bottom:0}
.fi-secret{
    width:100%;height:44px;
    padding:0 44px 0 39px;
    border:1.5px solid var(--gold-border);border-radius:8px;
    font-size:.86rem;font-family:'Plus Jakarta Sans',sans-serif;
    color:#5a4000;background:rgba(255,248,220,.6);outline:none;transition:all .2s;
    letter-spacing:2px;font-weight:700;
}
.fi-secret:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(232,184,75,.12)}
.fi-secret::placeholder{letter-spacing:0;font-weight:400;color:#c9a060}
.secret-note{font-size:.68rem;color:#9a7820;margin-top:6px;display:flex;align-items:center;gap:5px}

/* Admin register info banner */
.admin-reg-banner{
    background:linear-gradient(135deg,rgba(26,58,8,.08),rgba(74,124,47,.06));
    border:1px solid rgba(74,124,47,.2);
    border-radius:10px;
    padding:10px 13px;
    margin-bottom:13px;
    display:flex;align-items:center;gap:9px;
    font-size:.74rem;color:#2d4a1a;
    line-height:1.5;
}
.admin-reg-banner i{color:var(--gm);font-size:1rem;flex-shrink:0}

/* Submit */
.btn-submit{
    width:100%;height:47px;margin-top:5px;
    background:linear-gradient(135deg,var(--gm),var(--gl));
    color:white;border:none;border-radius:11px;
    font-size:.92rem;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;
    cursor:pointer;letter-spacing:.4px;
    transition:all .3s;box-shadow:0 4px 18px rgba(74,124,47,.3);
    display:flex;align-items:center;justify-content:center;gap:8px;
}
.btn-submit.admin-submit{background:linear-gradient(135deg,#1a3a08,var(--gm))}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(74,124,47,.42)}
.btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}

/* Footer */
.card-foot{text-align:center;margin-top:12px;font-size:.77rem;color:var(--muted)}
.card-foot a{color:var(--gm);text-decoration:none;font-weight:700}
.card-foot a:hover{text-decoration:underline}
.card-foot .sep{margin:0 6px;color:#cde0bb}

/* Form panels */
.form-panel{display:none}
.form-panel.active{display:block}

@media(max-width:700px){
    .card{grid-template-columns:1fr;max-width:420px}
    .panel-left{display:none}
    .panel-right{padding:28px 22px}
    .row2{grid-template-columns:1fr}
}
</style>
</head>
<body>
<div class="bg-layer">
    <div class="bg-mesh"></div>
    <div class="bg-grid"></div>
    <div class="orb o1"></div>
    <div class="orb o2"></div>
    <div class="orb o3"></div>
</div>

<a href="index.php" class="back-home"><i class="fas fa-arrow-left"></i> Beranda</a>

<div class="card">

<!-- ════════ LEFT PANEL ════════ -->
<div class="panel-left">
    <div class="lc lc1"></div><div class="lc lc2"></div><div class="lc lc3"></div>

    <div class="left-logo">
        <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <span class="logo-name">SIKESMA</span>
        <span class="logo-tagline">Sistem Kesejahteraan Mahasiswa</span>
    </div>

    <?php if ($mode === 'login'): ?>
    <div class="left-body">
        <h2>Selamat<br>Datang!</h2>
        <p>Satu halaman untuk semua pengguna — Admin maupun Mahasiswa masuk dari sini.</p>
        <div class="info-pills">
            <div class="pill"><i class="fas fa-user-shield"></i> Admin: gunakan username atau email</div>
            <div class="pill"><i class="fas fa-user-graduate"></i> Mahasiswa: gunakan NIM atau email</div>
            <div class="pill"><i class="fas fa-wand-magic-sparkles"></i> Peran terdeteksi otomatis</div>
        </div>
        <a href="login.php?mode=register&tab=mahasiswa" class="btn-switch">Belum punya akun? Daftar</a>
    </div>
    <?php else: ?>
    <div class="left-body">
        <h2>Daftar<br>Akun Baru</h2>
        <p>Pilih tipe akun yang ingin Anda buat — Mahasiswa atau Administrator.</p>
        <div class="info-pills">
            <div class="pill"><i class="fas fa-user-graduate"></i> Mahasiswa: NIM + email aktif</div>
            <div class="pill gold-pill"><i class="fas fa-shield-halved"></i> Admin: butuh kode rahasia</div>
            <div class="pill"><i class="fas fa-lock"></i> Password aman & terenkripsi</div>
        </div>
        <a href="login.php?mode=login" class="btn-switch">Sudah punya akun? Masuk</a>
    </div>
    <?php endif; ?>
</div>

<!-- ════════ RIGHT PANEL ════════ -->
<div class="panel-right">

<?php if ($mode === 'login'): ?>
<!-- ═══ FORM LOGIN ═══ -->
<div class="form-title">Masuk</div>
<div class="form-sub">Berlaku untuk Admin &amp; Mahasiswa</div>

<div class="role-hint">
    <i class="fas fa-circle-info"></i>
    Sistem mendeteksi peran Anda secara otomatis setelah login
</div>

<div class="social-row">
    <button class="social-btn" title="Facebook"><i class="fab fa-facebook"></i></button>
    <button class="social-btn" title="Google"><i class="fab fa-google"></i></button>
    <button class="social-btn" title="Lainnya"><i class="fas fa-at"></i></button>
</div>
<div class="divider">atau masuk dengan akun SIKESMA</div>

<?php if ($error): ?><div class="alert a-err"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert a-ok"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg']==='logout'): ?><div class="alert a-ok"><i class="fas fa-circle-check"></i> Anda berhasil keluar dari sistem.</div><?php endif; ?>

<form method="POST" id="loginForm" novalidate>
    <input type="hidden" name="action" value="login">
    <div class="ig">
        <i class="fas fa-user ii"></i>
        <input type="text" name="identifier" id="identifier" class="fi"
               placeholder="NIM / Username / Email"
               maxlength="100" autocomplete="username"
               value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>">
        <span class="cc" id="idCc"></span>
    </div>
    <span class="fe" id="idErr">Field ini wajib diisi.</span>

    <div class="ig">
        <i class="fas fa-lock ii"></i>
        <input type="password" name="password" id="loginPwd" class="fi"
               placeholder="Password" maxlength="100" autocomplete="current-password">
        <span class="cc" id="pwdCc"></span>
        <button type="button" class="toggle-pass" onclick="tp('loginPwd','lpIcon')"><i class="fas fa-eye" id="lpIcon"></i></button>
    </div>
    <span class="fe" id="pwdErr">Password wajib diisi.</span>

    <button type="submit" class="btn-submit" id="loginBtn">
        <i class="fas fa-right-to-bracket"></i> Masuk ke SIKESMA
    </button>
</form>
<div class="card-foot">
    Belum punya akun?
    <a href="login.php?mode=register&tab=mahasiswa">Daftar Mahasiswa</a>
    <span class="sep">|</span>
    <a href="login.php?mode=register&tab=admin">Daftar Admin</a>
</div>

<?php else: ?>
<!-- ═══ FORM REGISTER ═══ -->
<div class="form-title">Buat Akun</div>
<div class="form-sub">Pilih tipe akun yang ingin didaftarkan</div>

<!-- TABS -->
<div class="reg-tabs">
    <button type="button" class="reg-tab mhs-tab <?= $regTab==='mahasiswa'?'active':'' ?>"
            onclick="switchTab('mahasiswa')">
        <i class="fas fa-user-graduate"></i> Mahasiswa
    </button>
    <button type="button" class="reg-tab adm-tab <?= $regTab==='admin'?'active':'' ?>"
            onclick="switchTab('admin')">
        <i class="fas fa-shield-halved"></i> Administrator
    </button>
</div>

<?php if ($error): ?><div class="alert a-err"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- ── PANEL MAHASISWA ── -->
<div class="form-panel <?= $regTab==='mahasiswa'?'active':'' ?>" id="panel-mahasiswa">
    <div class="social-row">
        <button class="social-btn" title="Facebook"><i class="fab fa-facebook"></i></button>
        <button class="social-btn" title="Google"><i class="fab fa-google"></i></button>
        <button class="social-btn" title="Lainnya"><i class="fas fa-at"></i></button>
    </div>
    <div class="divider">atau daftar dengan email</div>

    <form method="POST" id="mhsForm" novalidate>
        <input type="hidden" name="action" value="register_mahasiswa">

        <div class="ig">
            <i class="fas fa-user ii"></i>
            <input type="text" name="nama" id="mNama" class="fi"
                   placeholder="Nama Lengkap" maxlength="100"
                   value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
            <span class="cc" id="mNamaCc"></span>
        </div>
        <span class="fe" id="mNamaErr">Nama minimal 3 karakter.</span>

        <div class="row2">
            <div>
                <div class="ig">
                    <i class="fas fa-id-card ii"></i>
                    <input type="text" name="nim" id="mNim" class="fi"
                           placeholder="NIM (8–12 digit)" maxlength="12"
                           value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>">
                    <span class="cc" id="mNimCc"></span>
                </div>
                <span class="fe" id="mNimErr">NIM harus 8–12 digit angka.</span>
            </div>
            <div>
                <div class="ig">
                    <i class="fas fa-layer-group ii"></i>
                    <select name="jurusan" class="fi-sel">
                        <option value="">Jurusan</option>
                        <?php while ($j=$jurusanList->fetch_assoc()): ?>
                        <option value="<?= $j['id'] ?>" <?= ($_POST['jurusan']??'')==$j['id']?'selected':'' ?>>
                            <?= htmlspecialchars($j['nama_jurusan']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="ig">
            <i class="fas fa-envelope ii"></i>
            <input type="email" name="email" id="mEmail" class="fi"
                   placeholder="Email aktif" maxlength="100"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <span class="fe" id="mEmailErr">Format email tidak valid.</span>

        <div class="ig">
            <i class="fas fa-lock ii"></i>
            <input type="password" name="password" id="mPwd" class="fi"
                   placeholder="Password (min. 6 karakter)" maxlength="100">
            <button type="button" class="toggle-pass" onclick="tp('mPwd','mpIcon')"><i class="fas fa-eye" id="mpIcon"></i></button>
        </div>
        <div class="sbar"><div class="sfill" id="mSfill"></div></div>
        <span class="fe" id="mPwdErr">Password minimal 6 karakter.</span>

        <button type="submit" class="btn-submit" id="mBtn">
            <i class="fas fa-user-plus"></i> Daftar sebagai Mahasiswa
        </button>
    </form>
</div>

<!-- ── PANEL ADMIN ── -->
<div class="form-panel <?= $regTab==='admin'?'active':'' ?>" id="panel-admin">
    <div class="admin-reg-banner">
        <i class="fas fa-shield-halved"></i>
        <span>Pendaftaran Admin memerlukan <strong>kode rahasia</strong> dari Super Admin. Pastikan Anda berwenang sebelum mendaftar.</span>
    </div>

    <form method="POST" id="admForm" novalidate>
        <input type="hidden" name="action" value="register_admin">

        <div class="row2">
            <div>
                <div class="ig">
                    <i class="fas fa-user ii"></i>
                    <input type="text" name="admin_nama" id="aNama" class="fi"
                           placeholder="Nama Lengkap" maxlength="100"
                           value="<?= htmlspecialchars($_POST['admin_nama'] ?? '') ?>">
                    <span class="cc" id="aNamaCc"></span>
                </div>
                <span class="fe" id="aNamaErr">Nama minimal 3 karakter.</span>
            </div>
            <div>
                <div class="ig">
                    <i class="fas fa-at ii"></i>
                    <input type="text" name="admin_username" id="aUser" class="fi"
                           placeholder="Username" maxlength="50"
                           value="<?= htmlspecialchars($_POST['admin_username'] ?? '') ?>">
                    <span class="cc" id="aUserCc"></span>
                </div>
                <span class="fe" id="aUserErr">Username 3–50 karakter.</span>
            </div>
        </div>

        <div class="row2">
            <div>
                <div class="ig">
                    <i class="fas fa-envelope ii"></i>
                    <input type="email" name="admin_email" id="aEmail" class="fi"
                           placeholder="Email admin" maxlength="100"
                           value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>">
                </div>
                <span class="fe" id="aEmailErr">Email tidak valid.</span>
            </div>
            <div>
                <div class="ig">
                    <i class="fas fa-briefcase ii"></i>
                    <input type="text" name="admin_jabatan" class="fi"
                           placeholder="Jabatan (opsional)" maxlength="50"
                           value="<?= htmlspecialchars($_POST['admin_jabatan'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="ig">
            <i class="fas fa-lock ii"></i>
            <input type="password" name="admin_password" id="aPwd" class="fi"
                   placeholder="Password (min. 8 karakter)" maxlength="100">
            <button type="button" class="toggle-pass" onclick="tp('aPwd','apIcon')"><i class="fas fa-eye" id="apIcon"></i></button>
        </div>
        <div class="sbar"><div class="sfill" id="aSfill"></div></div>
        <span class="fe" id="aPwdErr">Password admin minimal 8 karakter.</span>

        <div class="ig">
            <i class="fas fa-lock ii"></i>
            <input type="password" name="admin_konfirmasi" id="aKonfirm" class="fi"
                   placeholder="Konfirmasi password" maxlength="100">
            <button type="button" class="toggle-pass" onclick="tp('aKonfirm','akIcon')"><i class="fas fa-eye" id="akIcon"></i></button>
        </div>
        <span class="fe" id="aKonfirmErr">Konfirmasi password tidak cocok.</span>

        <!-- KODE RAHASIA -->
        <div class="secret-box">
            <div class="secret-box-label">
                <i class="fas fa-key"></i> Kode Rahasia Admin
            </div>
            <div class="ig">
                <i class="fas fa-shield-halved ii" style="color:#c9a060"></i>
                <input type="password" name="kode_rahasia" id="aKode" class="fi-secret"
                       placeholder="Masukkan kode rahasia" maxlength="30">
                <button type="button" class="toggle-pass" style="color:#c9a060" onclick="tp('aKode','akodeIcon')"><i class="fas fa-eye" id="akodeIcon"></i></button>
            </div>
            <div class="secret-note">
                <i class="fas fa-circle-info"></i>
                Hubungi Super Admin untuk mendapatkan kode ini
            </div>
            <span class="fe" id="aKodeErr">Kode rahasia wajib diisi.</span>
        </div>

        <button type="submit" class="btn-submit admin-submit" id="aBtn">
            <i class="fas fa-shield-halved"></i> Daftar sebagai Administrator
        </button>
    </form>
</div>

<div class="card-foot">
    Sudah punya akun? <a href="login.php?mode=login">Masuk di sini</a>
</div>
<?php endif; ?>

</div><!-- /panel-right -->
</div><!-- /card -->

<script>
/* ── Tab switcher ── */
function switchTab(tab) {
    document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.reg-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    document.querySelector('.' + (tab === 'mahasiswa' ? 'mhs' : 'adm') + '-tab').classList.add('active');
    history.replaceState(null, '', '?mode=register&tab=' + tab);
}

/* ── Toggle password ── */
function tp(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    if (!inp || !ico) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.className = inp.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye';
}

/* ── Char counter ── */
function setupCc(inputId, ccId, max) {
    const inp = document.getElementById(inputId);
    const cc  = document.getElementById(ccId);
    if (!inp || !cc) return;
    inp.addEventListener('input', () => {
        const l = inp.value.length;
        cc.textContent = l + '/' + max;
        cc.className = 'cc' + (l >= max ? ' over' : l > max * .8 ? ' warn' : '');
    });
}
setupCc('identifier', 'idCc', 100);
setupCc('loginPwd',   'pwdCc', 100);
setupCc('mNama',      'mNamaCc', 100);
setupCc('mNim',       'mNimCc', 12);
setupCc('aNama',      'aNamaCc', 100);
setupCc('aUser',      'aUserCc', 50);

/* ── NIM digits only ── */
const nimEl = document.getElementById('mNim');
if (nimEl) nimEl.addEventListener('input', () => { nimEl.value = nimEl.value.replace(/\D/g, ''); });

/* ── Password strength ── */
function strength(val, fillId, minLen) {
    const fill = document.getElementById(fillId);
    if (!fill) return;
    let s = 0;
    if (val.length >= minLen)  s++;
    if (val.length >= minLen + 4) s++;
    if (/[A-Z]/.test(val))    s++;
    if (/\d/.test(val))        s++;
    if (/[^A-Za-z0-9]/.test(val)) s++;
    const pct = (s / 5) * 100;
    fill.style.width = pct + '%';
    fill.style.background = pct < 40 ? '#e74c3c' : pct < 70 ? '#e67e22' : '#27ae60';
}
const mPwd = document.getElementById('mPwd');
if (mPwd) mPwd.addEventListener('input', () => strength(mPwd.value, 'mSfill', 6));
const aPwd = document.getElementById('aPwd');
if (aPwd) aPwd.addEventListener('input', () => strength(aPwd.value, 'aSfill', 8));

/* ── Validasi LOGIN ── */
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        let ok = true;
        document.querySelectorAll('.fe').forEach(el => el.classList.remove('show'));
        if (!document.getElementById('identifier').value.trim()) { document.getElementById('idErr').classList.add('show'); ok = false; }
        if (!document.getElementById('loginPwd').value) { document.getElementById('pwdErr').classList.add('show'); ok = false; }
        if (!ok) { e.preventDefault(); return; }
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memverifikasi...';
    });
}

/* ── Validasi REGISTER MAHASISWA ── */
const mhsForm = document.getElementById('mhsForm');
if (mhsForm) {
    mhsForm.addEventListener('submit', function(e) {
        let ok = true;
        document.querySelectorAll('.fe').forEach(el => el.classList.remove('show'));
        const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!document.getElementById('mNama').value.trim() || document.getElementById('mNama').value.trim().length < 3)
            { document.getElementById('mNamaErr').classList.add('show'); ok = false; }
        if (!/^\d{8,12}$/.test(document.getElementById('mNim').value))
            { document.getElementById('mNimErr').classList.add('show'); ok = false; }
        if (!emailRx.test(document.getElementById('mEmail').value.trim()))
            { document.getElementById('mEmailErr').classList.add('show'); ok = false; }
        if (document.getElementById('mPwd').value.length < 6)
            { document.getElementById('mPwdErr').classList.add('show'); ok = false; }
        if (!ok) { e.preventDefault(); return; }
        const btn = document.getElementById('mBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendaftarkan...';
    });
}

/* ── Validasi REGISTER ADMIN ── */
const admForm = document.getElementById('admForm');
if (admForm) {
    admForm.addEventListener('submit', function(e) {
        let ok = true;
        document.querySelectorAll('.fe').forEach(el => el.classList.remove('show'));
        const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!document.getElementById('aNama').value.trim() || document.getElementById('aNama').value.trim().length < 3)
            { document.getElementById('aNamaErr').classList.add('show'); ok = false; }
        if (!document.getElementById('aUser').value.trim() || document.getElementById('aUser').value.trim().length < 3)
            { document.getElementById('aUserErr').classList.add('show'); ok = false; }
        if (!emailRx.test(document.getElementById('aEmail').value.trim()))
            { document.getElementById('aEmailErr').classList.add('show'); ok = false; }
        if (document.getElementById('aPwd').value.length < 8)
            { document.getElementById('aPwdErr').classList.add('show'); ok = false; }
        if (document.getElementById('aPwd').value !== document.getElementById('aKonfirm').value)
            { document.getElementById('aKonfirmErr').classList.add('show'); ok = false; }
        if (!document.getElementById('aKode').value.trim())
            { document.getElementById('aKodeErr').classList.add('show'); ok = false; }
        if (!ok) { e.preventDefault(); return; }
        const btn = document.getElementById('aBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendaftarkan...';
    });
}
</script>
</body>
</html>