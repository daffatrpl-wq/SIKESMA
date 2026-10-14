<?php
require_once '../session.php';
require_once '../db.php';
requireMahasiswa();

$mid = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: pengaduan.php'); exit; }

$stmt = $conn->prepare("
    SELECT p.*, k.nama_kategori FROM pengaduan p
    LEFT JOIN kategori_pengaduan k ON p.id_kategori=k.id
    WHERE p.id=? AND p.id_mahasiswa=?
");
$stmt->bind_param('ii',$id,$mid); $stmt->execute();
$peng = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$peng) { header('Location: pengaduan.php'); exit; }

// Komentar publik saja
$komentar = $conn->query("
    SELECT k.*, a.nama_lengkap as nama_admin, m.nama_lengkap as nama_mhs
    FROM komentar k
    LEFT JOIN admin a ON k.id_admin=a.id
    LEFT JOIN mahasiswa m ON k.id_mahasiswa=m.id
    WHERE k.id_pengaduan=$id AND k.internal=0
    ORDER BY k.dibuat_pada ASC
");

$sc = ['baru'=>['bg'=>'#dbeafe','c'=>'#1d4ed8'],'diproses'=>['bg'=>'#fef3c7','c'=>'#92400e'],'selesai'=>['bg'=>'#d1fae5','c'=>'#065f46'],'ditolak'=>['bg'=>'#fee2e2','c'=>'#991b1b']];
$s = $sc[$peng['status']] ?? $sc['baru'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKESMA — Detail Pengaduan</title>
    <link rel="icon" href="img/image.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--green-dark:#2d5016;--green-main:#4a7c2f;--green-light:#7ab648;--green-pale:#c8e6a0;--green-bg:#f0f7e8;--sidebar-w:260px}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Plus Jakarta Sans',sans-serif;background:#f5f8f2;display:flex;min-height:100vh}
        .sidebar{width:var(--sidebar-w);background:linear-gradient(180deg,var(--green-dark) 0%,#1a3a08 100%);min-height:100vh;position:fixed;left:0;top:0;display:flex;flex-direction:column;z-index:100}
        .sidebar-logo{padding:28px 24px;border-bottom:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:12px}
        .sidebar-logo-icon{width:42px;height:42px;background:rgba(255,255,255,0.12);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.2rem}
        .sidebar-logo-text{font-family:'Fraunces',serif;color:white;font-size:1.3rem;font-weight:700}
        .sidebar-logo-text small{display:block;font-size:0.58rem;color:rgba(255,255,255,0.5);font-family:'Plus Jakarta Sans';font-weight:400}
        .sidebar-nav{flex:1;padding:20px 14px}
        .nav-section-label{font-size:0.65rem;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:1.5px;padding:0 10px;margin:16px 0 6px}
        .nav-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.88rem;font-weight:500;transition:all 0.2s;margin-bottom:2px}
        .nav-item:hover,.nav-item.active{background:rgba(255,255,255,0.1);color:white}
        .nav-item.active{background:rgba(122,182,72,0.2);color:var(--green-pale)}
        .nav-item i{width:18px;text-align:center}
        .sidebar-footer{padding:16px 14px;border-top:1px solid rgba(255,255,255,0.08)}
        .user-info{display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;background:rgba(255,255,255,0.06);margin-bottom:8px}
        .user-avatar{width:36px;height:36px;background:var(--green-light);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.9rem;flex-shrink:0}
        .user-name{font-size:0.82rem;color:white;font-weight:600}
        .user-role{font-size:0.68rem;color:rgba(255,255,255,0.45)}
        .btn-logout{display:flex;align-items:center;gap:8px;width:100%;padding:8px 14px;border-radius:8px;background:rgba(231,76,60,0.15);color:#ff8a80;text-decoration:none;font-size:0.82rem;font-weight:600;border:none;cursor:pointer;font-family:'Plus Jakarta Sans';transition:all 0.2s}
        .btn-logout:hover{background:rgba(231,76,60,0.3)}
        .main{margin-left:var(--sidebar-w);flex:1}
        .topbar{background:white;padding:14px 32px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #e8f0de;position:sticky;top:0;z-index:50}
        .back-btn{display:flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;background:var(--green-bg);color:var(--green-main);text-decoration:none;font-size:0.85rem;transition:all 0.2s;flex-shrink:0}
        .back-btn:hover{background:var(--green-pale)}
        .topbar-title{font-size:1rem;font-weight:700;color:#1a2e0d}
        .content{padding:28px 32px;max-width:760px}
        .card{background:white;border-radius:16px;border:1px solid #e8f0de;margin-bottom:20px;overflow:hidden}
        .card-header{padding:16px 22px;border-bottom:1px solid #e8f0de;display:flex;align-items:center;gap:8px}
        .card-header h3{font-size:0.95rem;font-weight:700;color:#1a2e0d}
        .card-header .icon{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:0.82rem;background:var(--green-bg);color:var(--green-main)}
        .card-body{padding:22px}
        .info-row{display:flex;margin-bottom:12px;gap:12px}
        .info-lbl{width:130px;font-size:0.78rem;font-weight:600;color:#6b8f52;flex-shrink:0;padding-top:1px}
        .info-val{font-size:0.88rem;color:#1a2e0d;flex:1}
        .deskripsi-box{background:#f8fff2;border:1px solid #e8f0de;border-radius:10px;padding:16px;font-size:0.88rem;color:#1a2e0d;line-height:1.8;white-space:pre-wrap;word-break:break-word;margin-top:10px}
        .badge{padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:700}
        .comment-item{background:#f8fff2;border-radius:10px;padding:12px 14px;border:1px solid #e8f0de;margin-bottom:10px}
        .comment-meta{display:flex;align-items:center;gap:8px;margin-bottom:6px}
        .c-avatar{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:white;flex-shrink:0}
        .c-avatar.admin{background:var(--green-main)}
        .c-avatar.mhs{background:#7ab648}
        .c-name{font-size:0.78rem;font-weight:700;color:#1a2e0d}
        .c-time{font-size:0.68rem;color:#8ab06a;margin-left:auto}
        .c-text{font-size:0.83rem;color:#2d4a1a;line-height:1.6}
        .empty-comment{text-align:center;padding:20px;color:#8ab06a;font-size:0.82rem}
        .empty-comment i{display:block;font-size:1.8rem;margin-bottom:6px}
        /* Progress tracker */
        .tracker{display:flex;align-items:center;gap:0;margin:16px 0}
        .t-step{display:flex;flex-direction:column;align-items:center;flex:1;position:relative}
        .t-step:not(:last-child)::after{content:'';position:absolute;top:14px;left:50%;right:-50%;height:2px;z-index:0}
        .t-step:not(:last-child).done::after{background:var(--green-main)}
        .t-step:not(:last-child)::after{background:#e8f0de}
        .t-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:700;z-index:1;position:relative;border:2px solid}
        .t-dot.done{background:var(--green-main);color:white;border-color:var(--green-main)}
        .t-dot.active{background:white;color:var(--green-main);border-color:var(--green-main);box-shadow:0 0 0 3px rgba(74,124,47,0.15)}
        .t-dot.pending{background:#f0f7e8;color:#aac890;border-color:#e8f0de}
        .t-dot.rejected{background:#fee2e2;color:#991b1b;border-color:#f5c6c6}
        .t-label{font-size:0.7rem;font-weight:600;color:#6b8f52;margin-top:6px;text-align:center}
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="sidebar-logo-text">SIKESMA<small>Portal Mahasiswa</small></div>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Menu</span>
        <a href="index.php" class="nav-item"><i class="fas fa-home"></i> Beranda</a>
        <a href="pengaduan.php" class="nav-item active"><i class="fas fa-bullhorn"></i> Pengaduan Saya</a>
        <a href="buat_pengaduan.php" class="nav-item"><i class="fas fa-plus-circle"></i> Buat Pengaduan</a>
        <span class="nav-section-label">Akun</span>
        <a href="profil.php" class="nav-item"><i class="fas fa-user-circle"></i> Profil Saya</a>
        <a href="ubah_password.php" class="nav-item"><i class="fas fa-key"></i> Ubah Password</a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama_lengkap'],0,1)) ?></div>
            <div><div class="user-name"><?= htmlspecialchars(substr($_SESSION['nama_lengkap'],0,25)) ?></div><div class="user-role"><?= htmlspecialchars($_SESSION['nim']) ?></div></div>
        </div>
        <a href="../logout.php" class="btn-logout"><i class="fas fa-right-from-bracket"></i> Keluar</a>
    </div>
</aside>
<main class="main">
    <div class="topbar">
        <a href="pengaduan.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <span class="topbar-title">Detail Pengaduan — <code style="background:#f0f7e8;padding:2px 8px;border-radius:4px;color:var(--green-main);font-size:0.8rem"><?= htmlspecialchars($peng['kode_pengaduan']) ?></code></span>
    </div>
    <div class="content">
        <!-- STATUS TRACKER -->
        <div class="card">
            <div class="card-header"><div class="icon"><i class="fas fa-map-pin"></i></div><h3>Status Pengaduan</h3>
                <span style="margin-left:auto;background:<?= $s['bg'] ?>;color:<?= $s['c'] ?>;padding:4px 14px;border-radius:20px;font-size:0.72rem;font-weight:700"><?= ucfirst($peng['status']) ?></span>
            </div>
            <div class="card-body">
                <?php
                $steps = [
                    ['key'=>'baru','label'=>'Diterima','icon'=>'fa-inbox'],
                    ['key'=>'diproses','label'=>'Diproses','icon'=>'fa-gear'],
                    ['key'=>'selesai','label'=>'Selesai','icon'=>'fa-check'],
                ];
                $statusOrder = ['baru'=>0,'diproses'=>1,'selesai'=>2,'ditolak'=>-1];
                $curOrder = $statusOrder[$peng['status']] ?? 0;
                ?>
                <?php if ($peng['status'] === 'ditolak'): ?>
                <div style="background:#fff0f0;border:1px solid #f5c6c6;border-radius:10px;padding:14px;display:flex;align-items:center;gap:10px;color:#991b1b;font-size:0.88rem;font-weight:600">
                    <i class="fas fa-times-circle" style="font-size:1.3rem"></i> Pengaduan ini ditolak oleh tim SIKESMA. Silakan hubungi admin untuk informasi lebih lanjut.
                </div>
                <?php else: ?>
                <div class="tracker">
                    <?php foreach ($steps as $i => $step):
                        $stepOrder = $statusOrder[$step['key']];
                        $cls = $stepOrder < $curOrder ? 'done' : ($stepOrder === $curOrder ? 'active' : 'pending');
                    ?>
                    <div class="t-step <?= $cls ?>">
                        <div class="t-dot <?= $cls ?>">
                            <?= $cls === 'done' ? '<i class="fas fa-check" style="font-size:0.65rem"></i>' : '<i class="fas '.$step['icon'].'" style="font-size:0.65rem"></i>' ?>
                        </div>
                        <span class="t-label"><?= $step['label'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- INFO -->
        <div class="card">
            <div class="card-header"><div class="icon"><i class="fas fa-file-lines"></i></div><h3>Informasi Pengaduan</h3></div>
            <div class="card-body">
                <div class="info-row"><span class="info-lbl">Kode</span><code style="background:#f0f7e8;padding:2px 8px;border-radius:4px;color:var(--green-main);font-size:0.85rem"><?= htmlspecialchars($peng['kode_pengaduan']) ?></code></div>
                <div class="info-row"><span class="info-lbl">Judul</span><span class="info-val" style="font-weight:700"><?= htmlspecialchars($peng['judul_pengaduan']) ?></span></div>
                <div class="info-row"><span class="info-lbl">Kategori</span><span class="info-val"><?= htmlspecialchars($peng['nama_kategori'] ?? '-') ?></span></div>
                <div class="info-row"><span class="info-lbl">Lokasi</span><span class="info-val"><?= htmlspecialchars($peng['lokasi'] ?: '-') ?></span></div>
                <div class="info-row"><span class="info-lbl">Prioritas</span><span class="badge" style="background:<?= ['rendah'=>'#d1fae5','sedang'=>'#fef3c7','tinggi'=>'#fee2e2','kritis'=>'#fce7f3'][$peng['tingkat_prioritas']] ?>;color:<?= ['rendah'=>'#065f46','sedang'=>'#92400e','tinggi'=>'#991b1b','kritis'=>'#9d174d'][$peng['tingkat_prioritas']] ?>"><?= ucfirst($peng['tingkat_prioritas']) ?></span></div>
                <div class="info-row"><span class="info-lbl">Tanggal</span><span class="info-val"><?= date('d F Y, H:i', strtotime($peng['tanggal_pengaduan'])) ?> WIB</span></div>
                <div class="info-row"><span class="info-lbl">Mode</span><span class="info-val"><?= $peng['anonim'] ? '🕵️ Anonim' : '👤 Terbuka' ?></span></div>
                <div style="margin-top:10px"><div class="info-lbl" style="margin-bottom:6px">Deskripsi</div>
                <div class="deskripsi-box"><?= htmlspecialchars($peng['deskripsi']) ?></div></div>
            </div>
        </div>

        <!-- TINDAK LANJUT -->
        <div class="card">
            <div class="card-header"><div class="icon"><i class="fas fa-comments"></i></div><h3>Tindak Lanjut dari Admin</h3></div>
            <div class="card-body">
                <?php if ($komentar->num_rows === 0): ?>
                <div class="empty-comment"><i class="fas fa-hourglass-half"></i>Menunggu tindak lanjut dari tim SIKESMA...</div>
                <?php else: while ($c=$komentar->fetch_assoc()): ?>
                <div class="comment-item">
                    <div class="comment-meta">
                        <div class="c-avatar <?= $c['id_admin']?'admin':'mhs' ?>"><?= strtoupper(substr($c['id_admin']?$c['nama_admin']:$c['nama_mhs'],0,1)) ?></div>
                        <span class="c-name"><?= htmlspecialchars($c['id_admin']?$c['nama_admin']:$c['nama_mhs']) ?></span>
                        <?php if ($c['id_admin']): ?><span style="font-size:0.62rem;background:#d1fae5;color:#065f46;border-radius:4px;padding:1px 5px;font-weight:700">Admin SIKESMA</span><?php endif; ?>
                        <span class="c-time"><?= date('d/m/Y H:i', strtotime($c['dibuat_pada'])) ?></span>
                    </div>
                    <div class="c-text"><?= nl2br(htmlspecialchars($c['pesan'])) ?></div>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
    </div>
</main>
</body>
</html>