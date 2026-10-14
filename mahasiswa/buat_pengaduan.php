<?php
require_once '../session.php';
require_once '../db.php';
requireMahasiswa();

$mid = $_SESSION['user_id'];
$err = '';

$kategoriList = $conn->query("SELECT * FROM kategori_pengaduan ORDER BY nama_kategori");

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_pengaduan'])) {
    $judul    = trim($_POST['judul'] ?? '');
    $deskripsi= trim($_POST['deskripsi'] ?? '');
    $lokasi   = trim($_POST['lokasi'] ?? '');
    $kategori = (int)($_POST['kategori'] ?? 0);
    $prioritas= $_POST['prioritas'] ?? 'sedang';
    $anonim   = isset($_POST['anonim']) ? 1 : 0;

    if (strlen($judul)<5||strlen($judul)>200) { $err='Judul harus 5–200 karakter.'; }
    elseif (strlen($deskripsi)<20)             { $err='Deskripsi minimal 20 karakter.'; }
    else {
        $kode = 'ADU-'.date('Ymd').'-'.strtoupper(substr(uniqid(),-5));
        $fileBukti = null;
        if (isset($_FILES['file_bukti'])&&$_FILES['file_bukti']['size']>0) {
            $allowed = ['image/jpeg','image/png','image/gif','application/pdf'];
            if (!in_array($_FILES['file_bukti']['type'],$allowed)) { $err='File hanya JPG, PNG, GIF, atau PDF.'; }
            elseif ($_FILES['file_bukti']['size']>5*1024*1024)    { $err='Ukuran file maks 5MB.'; }
            else { $fileBukti = file_get_contents($_FILES['file_bukti']['tmp_name']); }
        }
        if (!$err) {
            $stmt = $conn->prepare("INSERT INTO pengaduan (kode_pengaduan,id_mahasiswa,judul_pengaduan,deskripsi,lokasi,tingkat_prioritas,anonim,file_bukti,id_kategori) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sissssisi',$kode,$mid,$judul,$deskripsi,$lokasi,$prioritas,$anonim,$fileBukti,$kategori);
            if ($stmt->execute()) { header('Location: pengaduan.php?msg=success&kode='.urlencode($kode)); exit; }
            else { $err='Gagal menyimpan. Coba lagi.'; }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SIKESMA — Buat Pengaduan</title>
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
.content{padding:28px 32px}
.page-header{margin-bottom:24px}
.page-header h1{font-family:'Fraunces',serif;font-size:1.7rem;color:#1a2e0d}
.page-header p{color:#6b8f52;font-size:.88rem;margin-top:4px}
.layout{display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start}
.card{background:white;border-radius:18px;border:1px solid #e8f0de;overflow:hidden}
.card-header{padding:18px 24px;border-bottom:1px solid #e8f0de;display:flex;align-items:center;gap:10px}
.card-icon{width:34px;height:34px;border-radius:8px;background:var(--gb);color:var(--gm);display:flex;align-items:center;justify-content:center;font-size:.9rem}
.card-title{font-size:1rem;font-weight:700;color:#1a2e0d}
.card-body{padding:26px}
.divider-label{font-size:.72rem;font-weight:700;color:#8ab06a;text-transform:uppercase;letter-spacing:1px;margin:4px 0 14px;display:flex;align-items:center;gap:8px}
.divider-label::after{content:'';flex:1;height:1px;background:#e8f0de}
.fg{margin-bottom:14px}
.fl{display:block;font-size:.78rem;font-weight:700;color:#3d5a28;margin-bottom:6px}
.fl span{color:#e74c3c;margin-left:2px}
.fi{width:100%;height:46px;padding:0 14px;border:1.5px solid #d8ecc0;border-radius:10px;font-size:.87rem;font-family:'Plus Jakarta Sans',sans-serif;color:#1a2e0d;background:#f8fff2;outline:none;transition:all .2s}
.fi:focus{border-color:var(--gm);background:white;box-shadow:0 0 0 3px rgba(74,124,47,.08)}
.fi::placeholder{color:#aac890}
textarea.fi{height:auto;padding:12px 14px;resize:vertical;min-height:130px;line-height:1.7}
.fi-sel{width:100%;height:46px;padding:0 14px;border:1.5px solid #d8ecc0;border-radius:10px;font-size:.87rem;font-family:'Plus Jakarta Sans',sans-serif;color:#1a2e0d;background:#f8fff2;outline:none;appearance:none;cursor:pointer;transition:all .2s}
.fi-sel:focus{border-color:var(--gm);background:white;box-shadow:0 0 0 3px rgba(74,124,47,.08)}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cc-hint{font-size:.68rem;text-align:right;margin-top:3px;color:#aac890}
.cc-hint.warn{color:#e67e22}.cc-hint.over{color:#e74c3c}
.fe{font-size:.7rem;color:#c0392b;margin-top:3px;display:none}
.fe.show{display:block}
.check-row{display:flex;align-items:flex-start;gap:9px;padding:12px 14px;background:var(--gb);border-radius:10px;border:1px solid #d8ecc0;cursor:pointer}
.check-row input{width:16px;height:16px;accent-color:var(--gm);cursor:pointer;flex-shrink:0;margin-top:2px}
.check-row .cl{font-size:.82rem;color:#2d4a1a;line-height:1.5}
.check-row .cl strong{color:var(--gm)}
.file-input-wrap{position:relative}
.fi-file{width:100%;padding:11px 14px;border:1.5px dashed #d8ecc0;border-radius:10px;font-size:.85rem;font-family:'Plus Jakarta Sans',sans-serif;color:#1a2e0d;background:#f8fff2;cursor:pointer;transition:all .2s}
.fi-file:hover{border-color:var(--gm);background:white}
.alert{padding:12px 16px;border-radius:10px;font-size:.83rem;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.a-err{background:#fff0f0;border:1px solid #f5c6c6;color:#c0392b}
.btn-submit{width:100%;height:50px;background:linear-gradient(135deg,var(--gm),var(--gl));color:white;border:none;border-radius:11px;font-size:.95rem;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;transition:all .3s;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 18px rgba(74,124,47,.3);margin-top:8px}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(74,124,47,.42)}
.btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}
/* info sidebar */
.info-card{background:white;border-radius:18px;border:1px solid #e8f0de;overflow:hidden}
.info-item{padding:14px 18px;border-bottom:1px solid #f0f7e8;display:flex;align-items:flex-start;gap:12px}
.info-item:last-child{border-bottom:none}
.info-ic{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
.ic-blue{background:rgba(52,152,219,.1);color:#3498db}
.ic-green{background:rgba(39,174,96,.1);color:#27ae60}
.ic-orange{background:rgba(230,126,34,.1);color:#e67e22}
.ic-purple{background:rgba(155,89,182,.1);color:#9b59b6}
.info-text h4{font-size:.82rem;font-weight:700;color:#1a2e0d;margin-bottom:2px}
.info-text p{font-size:.72rem;color:#6b8f52;line-height:1.5}
.pri-list{display:flex;flex-direction:column;gap:6px;padding:14px 18px}
.pri-item{display:flex;align-items:center;gap:8px;font-size:.75rem}
.pri-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
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
        <a href="buat_pengaduan.php" class="ni active"><i class="fas fa-plus-circle"></i> Buat Pengaduan</a>
        <span class="nav-lbl">Akun</span>
        <a href="profil.php" class="ni"><i class="fas fa-user-circle"></i> Profil Saya</a>
        <a href="ubah_password.php" class="ni"><i class="fas fa-key"></i> Ubah Password</a>
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
        <div><div class="tb-title">Buat Pengaduan</div><div class="tb-date"><?= date('l, d F Y') ?></div></div>
    </div>
    <div class="content">
        <div class="page-header">
            <h1>Buat Pengaduan Baru</h1>
            <p>Sampaikan permasalahan Anda dengan jelas dan lengkap agar dapat ditindaklanjuti segera.</p>
        </div>
        <?php if ($err): ?><div class="alert a-err"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

        <div class="layout">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-bullhorn"></i></div>
                    <div class="card-title">Detail Pengaduan</div>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="pengForm" novalidate>
                        <input type="hidden" name="submit_pengaduan" value="1">

                        <div class="divider-label">Informasi Utama</div>
                        <div class="fg">
                            <label class="fl">Judul Pengaduan <span>*</span></label>
                            <input type="text" name="judul" id="fJudul" class="fi"
                                   placeholder="Deskripsikan singkat permasalahan Anda" maxlength="200"
                                   value="<?= htmlspecialchars($_POST['judul']??'') ?>">
                            <div class="cc-hint" id="judulCc">0/200</div>
                            <span class="fe" id="judulErr">Judul harus 5–200 karakter.</span>
                        </div>

                        <div class="row2">
                            <div class="fg">
                                <label class="fl">Kategori <span>*</span></label>
                                <select name="kategori" class="fi-sel">
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php while ($k=$kategoriList->fetch_assoc()): ?>
                                    <option value="<?= $k['id'] ?>" <?= ($_POST['kategori']??'')==$k['id']?'selected':'' ?>><?= htmlspecialchars($k['nama_kategori']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="fg">
                                <label class="fl">Tingkat Prioritas</label>
                                <select name="prioritas" class="fi-sel">
                                    <option value="rendah">🟢 Rendah</option>
                                    <option value="sedang" selected>🟡 Sedang</option>
                                    <option value="tinggi">🔴 Tinggi</option>
                                    <option value="kritis">🚨 Kritis / Darurat</option>
                                </select>
                            </div>
                        </div>

                        <div class="fg">
                            <label class="fl">Lokasi Kejadian</label>
                            <input type="text" name="lokasi" class="fi"
                                   placeholder="Contoh: Gedung A lantai 3, Perpustakaan, dll."
                                   maxlength="100" value="<?= htmlspecialchars($_POST['lokasi']??'') ?>">
                        </div>

                        <div class="divider-label">Uraian Kejadian</div>
                        <div class="fg">
                            <label class="fl">Deskripsi Lengkap <span>*</span></label>
                            <textarea name="deskripsi" id="fDesk" class="fi"
                                      placeholder="Ceritakan secara rinci:&#10;• Apa yang terjadi?&#10;• Kapan terjadi?&#10;• Siapa yang terlibat?&#10;• Dampak yang dirasakan?"
                                      maxlength="5000" rows="6"><?= htmlspecialchars($_POST['deskripsi']??'') ?></textarea>
                            <div class="cc-hint" id="deskCc">0/5000 (min. 20 karakter)</div>
                            <span class="fe" id="deskErr">Deskripsi minimal 20 karakter.</span>
                        </div>

                        <div class="divider-label">Lampiran & Pengaturan</div>
                        <div class="fg">
                            <label class="fl">File Bukti / Dokumentasi (Opsional)</label>
                            <input type="file" name="file_bukti" class="fi-file" accept=".jpg,.jpeg,.png,.gif,.pdf">
                            <div class="cc-hint">Format: JPG, PNG, GIF, PDF — Maks. 5MB</div>
                        </div>

                        <div class="fg">
                            <label class="check-row">
                                <input type="checkbox" name="anonim" id="cbAnonim">
                                <div class="cl">
                                    <strong>Kirim secara Anonim</strong><br>
                                    Identitas Anda akan disembunyikan dari tampilan publik. Admin tetap dapat melihat data Anda untuk keperluan tindak lanjut.
                                </div>
                            </label>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-paper-plane"></i> Kirim Pengaduan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div>
                <div class="info-card" style="margin-bottom:16px">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-circle-info"></i></div>
                        <div class="card-title" style="font-size:.9rem">Panduan Pengaduan</div>
                    </div>
                    <div class="info-item">
                        <div class="info-ic ic-blue"><i class="fas fa-pencil"></i></div>
                        <div class="info-text"><h4>Judul yang Jelas</h4><p>Buat judul singkat yang menggambarkan permasalahan dengan tepat.</p></div>
                    </div>
                    <div class="info-item">
                        <div class="info-ic ic-green"><i class="fas fa-list-ul"></i></div>
                        <div class="info-text"><h4>Deskripsi Rinci</h4><p>Jelaskan kronologi, pihak terlibat, waktu, dan dampak secara detail.</p></div>
                    </div>
                    <div class="info-item">
                        <div class="info-ic ic-orange"><i class="fas fa-paperclip"></i></div>
                        <div class="info-text"><h4>Lampirkan Bukti</h4><p>Foto, screenshot, atau dokumen akan memperkuat pengaduan Anda.</p></div>
                    </div>
                    <div class="info-item">
                        <div class="info-ic ic-purple"><i class="fas fa-user-secret"></i></div>
                        <div class="info-text"><h4>Mode Anonim</h4><p>Pilih anonim jika Anda tidak ingin identitas ditampilkan secara publik.</p></div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-flag"></i></div>
                        <div class="card-title" style="font-size:.9rem">Tingkat Prioritas</div>
                    </div>
                    <div class="pri-list">
                        <div class="pri-item"><div class="pri-dot" style="background:#10b981"></div><div><strong>Rendah:</strong> Masukan atau saran perbaikan</div></div>
                        <div class="pri-item"><div class="pri-dot" style="background:#f59e0b"></div><div><strong>Sedang:</strong> Permasalahan umum yang perlu ditindaklanjuti</div></div>
                        <div class="pri-item"><div class="pri-dot" style="background:#ef4444"></div><div><strong>Tinggi:</strong> Permasalahan serius yang mendesak</div></div>
                        <div class="pri-item"><div class="pri-dot" style="background:#9333ea"></div><div><strong>Kritis:</strong> Darurat, kekerasan, atau ancaman keselamatan</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function ccUpdate(inputId, hintId, max, min) {
    const el = document.getElementById(inputId);
    const hint = document.getElementById(hintId);
    if (!el||!hint) return;
    el.addEventListener('input', () => {
        const l = el.value.length;
        hint.textContent = l+'/'+max+(min?' (min. '+min+' karakter)':'');
        hint.className = 'cc-hint'+(l>=max?' over':l>max*.85?' warn':'');
    });
}
ccUpdate('fJudul','judulCc',200);
ccUpdate('fDesk','deskCc',5000,20);

document.getElementById('pengForm').addEventListener('submit', function(e) {
    let ok = true;
    document.querySelectorAll('.fe').forEach(el=>el.classList.remove('show'));
    const judul = document.getElementById('fJudul').value.trim();
    const desk  = document.getElementById('fDesk').value.trim();
    if (judul.length<5||judul.length>200) {document.getElementById('judulErr').classList.add('show');ok=false;}
    if (desk.length<20)                   {document.getElementById('deskErr').classList.add('show');ok=false;}
    if (!ok) {
        e.preventDefault();
        window.scrollTo({top:0,behavior:'smooth'});
        return;
    }
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
});
</script>
</body>
</html>