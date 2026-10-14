<?php
require_once '../session.php';
require_once '../db.php';
requireAdmin();

$aid = $_SESSION['user_id'];
$msg = ''; $err = '';

$stmt = $conn->prepare("SELECT * FROM admin WHERE id=?");
$stmt->bind_param('i',$aid); $stmt->execute();
$adm = $stmt->get_result()->fetch_assoc(); $stmt->close();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profil'])) {
    $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
    $namaUser    = trim($_POST['nama_pengguna'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $jabatan     = trim($_POST['jabatan'] ?? '');

    if (strlen($namaLengkap)<3||strlen($namaLengkap)>100) { $err='Nama harus 3–100 karakter.'; }
    elseif (strlen($namaUser)<3||strlen($namaUser)>50)     { $err='Username harus 3–50 karakter.'; }
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL))      { $err='Format email tidak valid.'; }
    else {
        // Cek duplikat username & email
        $chk = $conn->prepare("SELECT id FROM admin WHERE (nama_pengguna=? OR email=?) AND id!=?");
        $chk->bind_param('ssi',$namaUser,$email,$aid); $chk->execute();
        if ($chk->get_result()->num_rows>0) { $err='Username atau email sudah digunakan.'; }
        else {
            $stmt = $conn->prepare("UPDATE admin SET nama_lengkap=?,nama_pengguna=?,email=?,jabatan=? WHERE id=?");
            $stmt->bind_param('ssssi',$namaLengkap,$namaUser,$email,$jabatan,$aid);
            $stmt->execute(); $stmt->close();
            $_SESSION['nama_lengkap'] = $namaLengkap;
            $_SESSION['username']     = $namaUser;
            $msg = 'Profil berhasil diperbarui.';
            // Reload
            $stmt2 = $conn->prepare("SELECT * FROM admin WHERE id=?");
            $stmt2->bind_param('i',$aid); $stmt2->execute();
            $adm = $stmt2->get_result()->fetch_assoc(); $stmt2->close();
        }
        $chk->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SIKESMA — Profil Admin</title>
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
.nb{margin-left:auto;background:#e74c3c;color:white;border-radius:10px;padding:1px 7px;font-size:.7rem;font-weight:700}
.sb-foot{padding:16px 14px;border-top:1px solid rgba(255,255,255,.08)}
.u-inf{display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;background:rgba(255,255,255,.06);margin-bottom:8px}
.u-av{width:36px;height:36px;background:var(--gl);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:.9rem;flex-shrink:0}
.u-nm{font-size:.82rem;color:white;font-weight:600}
.u-rl{font-size:.68rem;color:rgba(255,255,255,.45)}
.btn-out{display:flex;align-items:center;gap:8px;width:100%;padding:8px 14px;border-radius:8px;background:rgba(231,76,60,.15);color:#ff8a80;text-decoration:none;font-size:.82rem;font-weight:600;border:none;cursor:pointer;font-family:'Plus Jakarta Sans';transition:all .2s}
.btn-out:hover{background:rgba(231,76,60,.3)}
.main{margin-left:var(--sw);flex:1}
.topbar{background:white;padding:16px 32px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e8f0de;position:sticky;top:0;z-index:50}
.tb-title{font-size:1.1rem;font-weight:700;color:#1a2e0d}
.tb-date{font-size:.8rem;color:#6b8f52}
.content{padding:28px 32px}
.prof-grid{display:grid;grid-template-columns:280px 1fr;gap:24px;align-items:start}
/* avatar card */
.av-card{background:white;border-radius:18px;border:1px solid #e8f0de;padding:28px;text-align:center}
.av-ring{width:100px;height:100px;margin:0 auto 16px;border-radius:50%;background:linear-gradient(135deg,#1a3a08,var(--gm));display:flex;align-items:center;justify-content:center;font-size:2.4rem;font-weight:800;color:white;position:relative;box-shadow:0 8px 28px rgba(45,80,22,.35)}
.av-crown{position:absolute;top:-8px;left:50%;transform:translateX(-50%);font-size:1rem;filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))}
.admin-badge-big{display:inline-flex;align-items:center;gap:6px;background:rgba(232,184,75,.12);border:1px solid rgba(232,184,75,.3);color:#b8860b;padding:4px 12px;border-radius:20px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px}
.av-name{font-size:1.05rem;font-weight:800;color:#1a2e0d;margin-bottom:3px}
.av-user{font-size:.8rem;color:#8ab06a;margin-bottom:14px}
.av-divider{border:none;border-top:1px solid #e8f0de;margin:14px 0}
.av-row{display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:8px}
.av-row .lbl{color:#8ab06a;font-weight:500}
.av-row .val{color:#1a2e0d;font-weight:600;text-align:right;max-width:150px;word-break:break-all}
.av-stat{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:14px}
.av-stat-box{background:var(--gb);border-radius:9px;padding:10px;text-align:center}
.av-stat-num{font-size:1.2rem;font-weight:800;color:var(--gm)}
.av-stat-lbl{font-size:.65rem;color:#6b8f52;font-weight:500}
/* form */
.form-card{background:white;border-radius:18px;border:1px solid #e8f0de;overflow:hidden}
.fc-header{padding:18px 26px;border-bottom:1px solid #e8f0de;display:flex;align-items:center;gap:10px}
.fc-icon{width:34px;height:34px;border-radius:8px;background:var(--gb);color:var(--gm);display:flex;align-items:center;justify-content:center;font-size:.9rem}
.fc-title{font-size:1rem;font-weight:700;color:#1a2e0d}
.fc-body{padding:26px}
.divider-label{font-size:.72rem;font-weight:700;color:#8ab06a;text-transform:uppercase;letter-spacing:1px;margin:4px 0 14px;display:flex;align-items:center;gap:8px}
.divider-label::after{content:'';flex:1;height:1px;background:#e8f0de}
.fg{margin-bottom:14px}
.fl{display:block;font-size:.78rem;font-weight:700;color:#3d5a28;margin-bottom:6px}
.fl span{color:#e74c3c}
.fi{width:100%;height:46px;padding:0 14px;border:1.5px solid #d8ecc0;border-radius:10px;font-size:.87rem;font-family:'Plus Jakarta Sans',sans-serif;color:#1a2e0d;background:#f8fff2;outline:none;transition:all .2s}
.fi:focus{border-color:var(--gm);background:white;box-shadow:0 0 0 3px rgba(74,124,47,.08)}
.fi::placeholder{color:#aac890}
.fi:disabled{background:#f0f0f0;color:#aaa;cursor:not-allowed}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.cc{font-size:.68rem;color:#aac890;text-align:right;margin-top:3px}
.cc.warn{color:#e67e22}.cc.over{color:#e74c3c}
.fe{font-size:.7rem;color:#c0392b;display:none;margin-top:3px}
.fe.show{display:block}
.alert{padding:11px 15px;border-radius:10px;font-size:.83rem;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.a-ok{background:#f0fff4;border:1px solid #b2e5c5;color:#27ae60}
.a-err{background:#fff0f0;border:1px solid #f5c6c6;color:#c0392b}
.form-actions{display:flex;gap:10px;margin-top:20px}
.btn-save{height:46px;padding:0 28px;background:linear-gradient(135deg,var(--gm),var(--gl));color:white;border:none;border-radius:10px;font-size:.9rem;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(74,124,47,.28)}
.btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(74,124,47,.38)}
.btn-secondary{height:46px;padding:0 20px;background:#f0f7e8;color:var(--gm);border:1.5px solid #d8ecc0;border-radius:10px;font-size:.9rem;font-weight:600;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
.btn-secondary:hover{background:var(--gp)}
.security-info{background:rgba(232,184,75,.06);border:1px solid rgba(232,184,75,.2);border-radius:10px;padding:13px 16px;display:flex;align-items:flex-start;gap:10px;font-size:.78rem;color:#8a6a00;margin-top:16px;line-height:1.5}
.security-info i{color:#c9a227;flex-shrink:0;margin-top:1px}
</style>
</head>
<body>
<aside class="sb">
    <div class="sb-logo">
        <div class="sb-logo-ic"><i class="fas fa-graduation-cap"></i></div>
        <div class="sb-logo-tx">SIKESMA<small>Admin Panel</small></div>
    </div>
    <nav class="sb-nav">
        <span class="nav-lbl">Menu Utama</span>
        <a href="index.php" class="ni"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="pengaduan.php" class="ni"><i class="fas fa-inbox"></i> Kelola Pengaduan</a>
        <a href="mahasiswa.php" class="ni"><i class="fas fa-users"></i> Data Mahasiswa</a>
        <span class="nav-lbl">Pengaturan</span>
        <a href="kategori.php" class="ni"><i class="fas fa-tags"></i> Kategori</a>
        <a href="jurusan.php" class="ni"><i class="fas fa-building-columns"></i> Jurusan</a>
        <span class="nav-lbl">Akun Saya</span>
        <a href="profil.php" class="ni active"><i class="fas fa-user-shield"></i> Profil Admin</a>
        <a href="ubah_password.php" class="ni"><i class="fas fa-key"></i> Ubah Password</a>
    </nav>
    <div class="sb-foot">
        <div class="u-inf">
            <div class="u-av"><?= strtoupper(substr($_SESSION['nama_lengkap'],0,1)) ?></div>
            <div><div class="u-nm"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></div><div class="u-rl">Administrator</div></div>
        </div>
        <a href="../logout.php" class="btn-out"><i class="fas fa-right-from-bracket"></i> Keluar</a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div><div class="tb-title">Profil Administrator</div><div class="tb-date"><?= date('l, d F Y') ?></div></div>
    </div>
    <div class="content">
        <?php if ($msg): ?><div class="alert a-ok"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert a-err"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

        <div class="prof-grid">
            <!-- Avatar Card -->
            <div class="av-card">
                <div class="av-ring">
                    <?= strtoupper(substr($adm['nama_lengkap'],0,1)) ?>
                    <span class="av-crown">👑</span>
                </div>
                <div class="admin-badge-big"><i class="fas fa-shield-halved"></i> Administrator</div>
                <div class="av-name"><?= htmlspecialchars($adm['nama_lengkap']) ?></div>
                <div class="av-user">@<?= htmlspecialchars($adm['nama_pengguna']) ?></div>
                <hr class="av-divider">
                <div class="av-row"><span class="lbl">Email</span><span class="val"><?= htmlspecialchars($adm['email']) ?></span></div>
                <div class="av-row"><span class="lbl">Jabatan</span><span class="val"><?= htmlspecialchars($adm['jabatan']??'-') ?></span></div>
                <div class="av-row"><span class="lbl">Status</span><span class="val"><?= $adm['aktif'] ? '✅ Aktif' : '❌ Nonaktif' ?></span></div>
                <hr class="av-divider">
                <?php
                $jmlP = $conn->query("SELECT COUNT(*) FROM pengaduan")->fetch_row()[0];
                $jmlM = $conn->query("SELECT COUNT(*) FROM mahasiswa WHERE aktif=1")->fetch_row()[0];
                ?>
                <div class="av-stat">
                    <div class="av-stat-box"><div class="av-stat-num"><?= $jmlP ?></div><div class="av-stat-lbl">Total Pengaduan</div></div>
                    <div class="av-stat-box"><div class="av-stat-num"><?= $jmlM ?></div><div class="av-stat-lbl">Mahasiswa Aktif</div></div>
                </div>
                <div style="font-size:.68rem;color:#aac890;margin-top:12px"><i class="fas fa-calendar"></i> Bergabung <?= date('d F Y',strtotime($adm['dibuat_pada'])) ?></div>
            </div>

            <!-- Form Edit -->
            <div class="form-card">
                <div class="fc-header">
                    <div class="fc-icon"><i class="fas fa-pen"></i></div>
                    <div class="fc-title">Edit Informasi Profil</div>
                </div>
                <div class="fc-body">
                    <form method="POST" id="adminProfForm" novalidate>
                        <input type="hidden" name="update_profil" value="1">

                        <div class="divider-label">Identitas</div>
                        <div class="fg">
                            <label class="fl">Nama Lengkap <span>*</span></label>
                            <input type="text" name="nama_lengkap" id="fNama" class="fi"
                                   value="<?= htmlspecialchars($adm['nama_lengkap']) ?>" maxlength="100">
                            <div class="cc" id="namaCc"></div>
                            <span class="fe" id="namaErr">Nama harus 3–100 karakter.</span>
                        </div>

                        <div class="row2">
                            <div class="fg">
                                <label class="fl">Username <span>*</span></label>
                                <input type="text" name="nama_pengguna" id="fUser" class="fi"
                                       value="<?= htmlspecialchars($adm['nama_pengguna']) ?>" maxlength="50">
                                <div class="cc" id="userCc"></div>
                                <span class="fe" id="userErr">Username 3–50 karakter.</span>
                            </div>
                            <div class="fg">
                                <label class="fl">Jabatan</label>
                                <input type="text" name="jabatan" class="fi"
                                       value="<?= htmlspecialchars($adm['jabatan']??'') ?>"
                                       placeholder="Contoh: Kepala BEM" maxlength="50">
                            </div>
                        </div>

                        <div class="divider-label">Kontak</div>
                        <div class="fg">
                            <label class="fl">Email <span>*</span></label>
                            <input type="email" name="email" id="fEmail" class="fi"
                                   value="<?= htmlspecialchars($adm['email']) ?>" maxlength="100">
                            <span class="fe" id="emailErr">Format email tidak valid.</span>
                        </div>

                        <div class="security-info">
                            <i class="fas fa-triangle-exclamation"></i>
                            <span>Perubahan username akan mempengaruhi cara login Anda berikutnya. Pastikan Anda mengingat username baru sebelum menyimpan.</span>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Perubahan</button>
                            <a href="ubah_password.php" class="btn-secondary"><i class="fas fa-key"></i> Ubah Password</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function cc(inputId, counterId, max) {
    const inp = document.getElementById(inputId);
    const ctr = document.getElementById(counterId);
    if (!inp||!ctr) return;
    const update = () => {
        const l = inp.value.length;
        ctr.textContent = l+'/'+max;
        ctr.className = 'cc'+(l>=max?' over':l>max*.8?' warn':'');
    };
    inp.addEventListener('input', update);
    update();
}
cc('fNama','namaCc',100);
cc('fUser','userCc',50);

document.getElementById('adminProfForm').addEventListener('submit', function(e) {
    let ok = true;
    document.querySelectorAll('.fe').forEach(el=>el.classList.remove('show'));
    const nama  = document.getElementById('fNama').value.trim();
    const user  = document.getElementById('fUser').value.trim();
    const email = document.getElementById('fEmail').value.trim();
    const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (nama.length<3||nama.length>100) {document.getElementById('namaErr').classList.add('show');ok=false;}
    if (user.length<3||user.length>50)  {document.getElementById('userErr').classList.add('show');ok=false;}
    if (!emailRx.test(email))           {document.getElementById('emailErr').classList.add('show');ok=false;}
    if (!ok) e.preventDefault();
});
</script>
</body>
</html>