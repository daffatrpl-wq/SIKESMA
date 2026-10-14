<?php
require_once '../session.php';
require_once '../db.php';
requireMahasiswa();

$mid = $_SESSION['user_id'];
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ubah_password'])) {
    $lama = $_POST['password_lama'] ?? '';
    $baru = $_POST['password_baru'] ?? '';
    $konfirm = $_POST['konfirmasi'] ?? '';

    if (empty($lama)||empty($baru)||empty($konfirm)) {
        $err = 'Semua field wajib diisi.';
    } elseif (strlen($baru)<6) {
        $err = 'Password baru minimal 6 karakter.';
    } elseif (strlen($baru)>100) {
        $err = 'Password baru maksimal 100 karakter.';
    } elseif ($baru !== $konfirm) {
        $err = 'Konfirmasi password tidak cocok.';
    } else {
        $stmt = $conn->prepare("SELECT kata_sandi FROM mahasiswa WHERE id=?");
        $stmt->bind_param('i',$mid); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc(); $stmt->close();

        if (!password_verify($lama, $row['kata_sandi'])) {
            $err = 'Password lama tidak sesuai.';
        } else {
            $hash = password_hash($baru, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE mahasiswa SET kata_sandi=? WHERE id=?");
            $stmt->bind_param('si',$hash,$mid); $stmt->execute(); $stmt->close();
            $msg = 'Password berhasil diubah. Silakan login ulang jika diperlukan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SIKESMA — Ubah Password</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--gd:#2d5016;--gm:#4a7c2f;--gl:#7ab648;--gp:#c8e6a0;--gb:#f0f7e8;--sw:260px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f5f8f2;display:flex;min-height:100vh}
.sb{width:var(--sw);background:linear-gradient(180deg,var(--gd) 0%,#1a3a08 100%);min-height:100vh;position:fixed;left:0;top:0;display:flex;flex-direction:column;z-index:100}
.sb-logo{padding:28px 24px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:12px}
.sb-logo-ic{width:42px;height:42px;background:rgba(255,255,255,.12);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.2rem}
.sb-logo-tx{font-family:'Fraunces',serif;color:white;font-size:1.3rem;font-weight:700}
.sb-logo-tx small{display:block;font-size:.58rem;color:rgba(255,255,255,.5);font-family:'Plus Jakarta Sans';font-weight:400}
.sb-nav{flex:1;padding:20px 14px}
.nav-lbl{font-size:.65rem;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:1.5px;padding:0 10px;margin:16px 0 6px}
.ni{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;color:rgba(255,255,255,.7);text-decoration:none;font-size:.88rem;font-weight:500;transition:all .2s;margin-bottom:2px}
.ni:hover,.ni.active{background:rgba(255,255,255,.1);color:white}
.ni.active{background:rgba(122,182,72,.2);color:var(--gp)}
.ni i{width:18px;text-align:center}
.sb-foot{padding:16px 14px;border-top:1px solid rgba(255,255,255,.08)}
.u-inf{display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;background:rgba(255,255,255,.06);margin-bottom:8px}
.u-av{width:36px;height:36px;background:var(--gl);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:.9rem;flex-shrink:0}
.u-nm{font-size:.82rem;color:white;font-weight:600}
.u-rl{font-size:.68rem;color:rgba(255,255,255,.45)}
.btn-out{display:flex;align-items:center;gap:8px;width:100%;padding:8px 14px;border-radius:8px;background:rgba(231,76,60,.15);color:#ff8a80;text-decoration:none;font-size:.82rem;font-weight:600;border:none;cursor:pointer;font-family:'Plus Jakarta Sans';transition:all .2s}
.btn-out:hover{background:rgba(231,76,60,.3)}
.main{margin-left:var(--sw);flex:1}
.topbar{background:white;padding:16px 32px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #e8f0de;position:sticky;top:0;z-index:50}
.tb-title{font-size:1.1rem;font-weight:700;color:#1a2e0d}
.tb-date{font-size:.8rem;color:#6b8f52}
.content{padding:28px 32px;max-width:600px}
.card{background:white;border-radius:18px;border:1px solid #e8f0de;overflow:hidden}
.card-header{padding:20px 26px;border-bottom:1px solid #e8f0de;display:flex;align-items:center;gap:10px}
.card-icon{width:36px;height:36px;border-radius:9px;background:var(--gb);color:var(--gm);display:flex;align-items:center;justify-content:center;font-size:.95rem}
.card-title{font-size:1rem;font-weight:700;color:#1a2e0d}
.card-body{padding:28px}
.fg{margin-bottom:16px}
.fl{display:block;font-size:.78rem;font-weight:700;color:#3d5a28;margin-bottom:6px}
.fl span{color:#e74c3c;margin-left:2px}
.ig{position:relative}
.fi{width:100%;height:48px;padding:0 46px 0 14px;border:1.5px solid #d8ecc0;border-radius:10px;font-size:.88rem;font-family:'Plus Jakarta Sans',sans-serif;color:#1a2e0d;background:#f8fff2;outline:none;transition:all .2s}
.fi:focus{border-color:var(--gm);background:white;box-shadow:0 0 0 3px rgba(74,124,47,.08)}
.fi::placeholder{color:#aac890}
.toggle-pass{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:#8ab06a;cursor:pointer;font-size:.88rem;padding:3px;transition:color .2s}
.toggle-pass:hover{color:var(--gm)}
.fe{font-size:.71rem;color:#c0392b;margin-top:4px;display:none}
.fe.show{display:block}
.cc-bar{margin-top:6px}
.cc-track{height:4px;background:#e0eed0;border-radius:2px;overflow:hidden}
.cc-fill{height:100%;border-radius:2px;transition:all .3s;width:0}
.cc-label{font-size:.68rem;color:#8ab06a;margin-top:3px}
.tips{background:var(--gb);border-radius:10px;padding:14px 16px;margin-bottom:20px;border:1px solid #d8ecc0}
.tips h4{font-size:.78rem;font-weight:700;color:var(--gm);margin-bottom:8px}
.tips ul{list-style:none;display:flex;flex-direction:column;gap:5px}
.tips li{font-size:.75rem;color:#5c7a3e;display:flex;align-items:center;gap:6px}
.tips li i{color:var(--gm);font-size:.65rem;width:10px}
.alert{padding:12px 16px;border-radius:10px;font-size:.83rem;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.a-ok{background:#f0fff4;border:1px solid #b2e5c5;color:#27ae60}
.a-err{background:#fff0f0;border:1px solid #f5c6c6;color:#c0392b}
.form-actions{display:flex;gap:10px;margin-top:22px}
.btn-save{height:46px;padding:0 28px;background:linear-gradient(135deg,var(--gm),var(--gl));color:white;border:none;border-radius:10px;font-size:.9rem;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(74,124,47,.28)}
.btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(74,124,47,.38)}
.btn-back{height:46px;padding:0 20px;background:#f0f7e8;color:var(--gm);border:1.5px solid #d8ecc0;border-radius:10px;font-size:.9rem;font-weight:600;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
.btn-back:hover{background:var(--gp)}
</style>
</head>
<body>
<aside class="sb">
    <div class="sb-logo">
        <div class="sb-logo-ic"><i class="fas fa-graduation-cap"></i></div>
        <div class="sb-logo-tx">SIKESMA<small>Portal Mahasiswa</small></div>
    </div>
    <nav class="sb-nav">
        <span class="nav-lbl">Menu</span>
        <a href="index.php" class="ni"><i class="fas fa-home"></i> Beranda</a>
        <a href="pengaduan.php" class="ni"><i class="fas fa-bullhorn"></i> Pengaduan Saya</a>
        <a href="buat_pengaduan.php" class="ni"><i class="fas fa-plus-circle"></i> Buat Pengaduan</a>
        <span class="nav-lbl">Akun</span>
        <a href="profil.php" class="ni"><i class="fas fa-user-circle"></i> Profil Saya</a>
        <a href="ubah_password.php" class="ni active"><i class="fas fa-key"></i> Ubah Password</a>
    </nav>
    <div class="sb-foot">
        <div class="u-inf">
            <div class="u-av"><?= strtoupper(substr($_SESSION['nama_lengkap'],0,1)) ?></div>
            <div><div class="u-nm"><?= htmlspecialchars(substr($_SESSION['nama_lengkap'],0,25)) ?></div><div class="u-rl"><?= htmlspecialchars($_SESSION['nim']) ?></div></div>
        </div>
        <a href="../logout.php" class="btn-out"><i class="fas fa-right-from-bracket"></i> Keluar</a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div><div class="tb-title">Ubah Password</div><div class="tb-date"><?= date('l, d F Y') ?></div></div>
    </div>
    <div class="content">
        <?php if ($msg): ?><div class="alert a-ok"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert a-err"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-key"></i></div>
                <div class="card-title">Ubah Password Akun</div>
            </div>
            <div class="card-body">
                <div class="tips">
                    <h4><i class="fas fa-shield-halved"></i> Tips Password Aman</h4>
                    <ul>
                        <li><i class="fas fa-check"></i> Minimal 6 karakter</li>
                        <li><i class="fas fa-check"></i> Kombinasikan huruf besar, huruf kecil, dan angka</li>
                        <li><i class="fas fa-check"></i> Tambahkan karakter khusus (!@#$%) untuk lebih aman</li>
                        <li><i class="fas fa-check"></i> Jangan gunakan tanggal lahir atau NIM sebagai password</li>
                    </ul>
                </div>

                <form method="POST" id="pwdForm" novalidate>
                    <input type="hidden" name="ubah_password" value="1">

                    <div class="fg">
                        <label class="fl">Password Lama <span>*</span></label>
                        <div class="ig">
                            <input type="password" name="password_lama" id="pLama" class="fi"
                                   placeholder="Masukkan password saat ini" maxlength="100">
                            <button type="button" class="toggle-pass" onclick="tp('pLama','icLama')"><i class="fas fa-eye" id="icLama"></i></button>
                        </div>
                        <span class="fe" id="lamaErr">Password lama wajib diisi.</span>
                    </div>

                    <div class="fg">
                        <label class="fl">Password Baru <span>*</span></label>
                        <div class="ig">
                            <input type="password" name="password_baru" id="pBaru" class="fi"
                                   placeholder="Masukkan password baru (min. 6 karakter)" maxlength="100">
                            <button type="button" class="toggle-pass" onclick="tp('pBaru','icBaru')"><i class="fas fa-eye" id="icBaru"></i></button>
                        </div>
                        <div class="cc-bar">
                            <div class="cc-track"><div class="cc-fill" id="sFill"></div></div>
                            <div class="cc-label" id="sLabel">Masukkan password baru</div>
                        </div>
                        <span class="fe" id="baruErr">Password baru minimal 6 karakter.</span>
                    </div>

                    <div class="fg">
                        <label class="fl">Konfirmasi Password Baru <span>*</span></label>
                        <div class="ig">
                            <input type="password" name="konfirmasi" id="pKonfirm" class="fi"
                                   placeholder="Ulangi password baru" maxlength="100">
                            <button type="button" class="toggle-pass" onclick="tp('pKonfirm','icKonfirm')"><i class="fas fa-eye" id="icKonfirm"></i></button>
                        </div>
                        <span class="fe" id="konfirmErr">Konfirmasi password tidak cocok.</span>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Password</button>
                        <a href="profil.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
function tp(id, iconId) {
    const el = document.getElementById(id);
    const ic = document.getElementById(iconId);
    if (el.type==='password'){el.type='text';ic.className='fas fa-eye-slash';}
    else{el.type='password';ic.className='fas fa-eye';}
}
// Strength
const pBaru = document.getElementById('pBaru');
const sFill = document.getElementById('sFill');
const sLabel = document.getElementById('sLabel');
const levels = ['','Sangat Lemah','Lemah','Cukup','Kuat','Sangat Kuat'];
const colors = ['','#e74c3c','#e67e22','#f1c40f','#2ecc71','#27ae60'];
pBaru.addEventListener('input', () => {
    const v = pBaru.value; let s = 0;
    if (v.length>=6)s++; if(v.length>=10)s++;
    if(/[A-Z]/.test(v))s++; if(/\d/.test(v))s++;
    if(/[^A-Za-z0-9]/.test(v))s++;
    const pct = (s/5)*100;
    sFill.style.width = pct+'%';
    sFill.style.background = colors[s]||'#e0eed0';
    sLabel.textContent = v ? 'Kekuatan: '+(levels[s]||'') : 'Masukkan password baru';
    sLabel.style.color = colors[s]||'#8ab06a';
});
// Validasi
document.getElementById('pwdForm').addEventListener('submit', function(e) {
    let ok = true;
    document.querySelectorAll('.fe').forEach(el=>el.classList.remove('show'));
    if (!document.getElementById('pLama').value) {document.getElementById('lamaErr').classList.add('show');ok=false;}
    if (document.getElementById('pBaru').value.length<6) {document.getElementById('baruErr').classList.add('show');ok=false;}
    if (document.getElementById('pBaru').value !== document.getElementById('pKonfirm').value) {document.getElementById('konfirmErr').classList.add('show');ok=false;}
    if (!ok) e.preventDefault();
});
</script>
</body>
</html>