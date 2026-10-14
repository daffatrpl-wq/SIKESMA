<?php
require_once '../session.php';
require_once '../db.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: pengaduan.php'); exit; }

// Ambil data pengaduan lengkap
$stmt = $conn->prepare("
    SELECT p.*, m.nama_lengkap, m.nim, m.email as email_mhs, m.nomor_hp,
           k.nama_kategori, j.nama_jurusan, j.nama_fakultas
    FROM pengaduan p
    JOIN mahasiswa m ON p.id_mahasiswa = m.id
    LEFT JOIN kategori_pengaduan k ON p.id_kategori = k.id
    LEFT JOIN jurusan j ON m.id_jurusan = j.id
    WHERE p.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$peng = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$peng) { header('Location: pengaduan.php'); exit; }

// Ambil pelaku
$pelaku = $conn->query("SELECT pl.*, j.nama_jurusan FROM pelaku pl LEFT JOIN jurusan j ON pl.id_jurusan=j.id WHERE pl.id_pengaduan=$id");

// Ambil komentar
$komentar = $conn->query("
    SELECT k.*, a.nama_lengkap as nama_admin, m.nama_lengkap as nama_mhs
    FROM komentar k
    LEFT JOIN admin a ON k.id_admin = a.id
    LEFT JOIN mahasiswa m ON k.id_mahasiswa = m.id
    WHERE k.id_pengaduan = $id
    ORDER BY k.dibuat_pada ASC
");

// Proses update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $status = $conn->real_escape_string($_POST['status']);
        $conn->query("UPDATE pengaduan SET status='$status' WHERE id=$id");
    }
    if (isset($_POST['add_komentar'])) {
        $pesan = $conn->real_escape_string(trim($_POST['pesan']));
        $internal = isset($_POST['internal']) ? 1 : 0;
        $adminId = $_SESSION['user_id'];
        if ($pesan) {
            $conn->query("INSERT INTO komentar (id_pengaduan, id_admin, pesan, jenis, internal) VALUES ($id, $adminId, '$pesan', 'umum', $internal)");
        }
    }
    header("Location: detail_pengaduan.php?id=$id&msg=ok");
    exit;
}

$statusColors = [
    'baru' => ['bg' => '#dbeafe', 'color' => '#1d4ed8', 'label' => 'Baru'],
    'diproses' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Diproses'],
    'selesai' => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Selesai'],
    'ditolak' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Ditolak'],
];
$priColors = [
    'rendah' => ['bg' => '#d1fae5', 'color' => '#065f46'],
    'sedang' => ['bg' => '#fef3c7', 'color' => '#92400e'],
    'tinggi' => ['bg' => '#fee2e2', 'color' => '#991b1b'],
    'kritis' => ['bg' => '#fce7f3', 'color' => '#9d174d'],
];
$sc = $statusColors[$peng['status']] ?? $statusColors['baru'];
$pc = $priColors[$peng['tingkat_prioritas']] ?? $priColors['sedang'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKESMA — Detail Pengaduan <?= htmlspecialchars($peng['kode_pengaduan']) ?></title>
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
        .topbar-code{font-family:monospace;font-size:0.78rem;background:var(--green-bg);padding:3px 10px;border-radius:5px;color:var(--green-main);margin-left:8px}
        .content{padding:28px 32px}

        /* GRID */
        .detail-grid{display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start}

        /* CARDS */
        .card{background:white;border-radius:16px;border:1px solid #e8f0de;overflow:hidden;margin-bottom:20px}
        .card:last-child{margin-bottom:0}
        .card-header{padding:16px 22px;border-bottom:1px solid #e8f0de;display:flex;align-items:center;gap:8px}
        .card-header h3{font-size:0.95rem;font-weight:700;color:#1a2e0d}
        .card-header .icon{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:0.82rem;background:var(--green-bg);color:var(--green-main)}
        .card-body{padding:22px}

        /* INFO ROWS */
        .info-row{display:flex;margin-bottom:14px;gap:12px}
        .info-row:last-child{margin-bottom:0}
        .info-lbl{width:140px;font-size:0.78rem;font-weight:600;color:#6b8f52;flex-shrink:0;padding-top:2px}
        .info-val{font-size:0.88rem;color:#1a2e0d;font-weight:500;flex:1;word-break:break-word}

        .badge{padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:700;display:inline-block}

        /* DESKRIPSI */
        .deskripsi-box{background:#f8fff2;border:1px solid #e8f0de;border-radius:10px;padding:16px;font-size:0.88rem;color:#1a2e0d;line-height:1.75;white-space:pre-wrap;word-break:break-word}

        /* STATUS UPDATE */
        .status-card select{width:100%;padding:9px 14px;border:1.5px solid #d8ecc0;border-radius:8px;font-size:0.88rem;font-family:'Plus Jakarta Sans';outline:none;color:#1a2e0d;background:#f8fff2;cursor:pointer}
        .status-card select:focus{border-color:var(--green-main)}
        .btn-update{width:100%;height:40px;background:linear-gradient(135deg,var(--green-main),var(--green-light));color:white;border:none;border-radius:8px;font-size:0.88rem;font-weight:700;font-family:'Plus Jakarta Sans';cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:all 0.2s;margin-top:10px}
        .btn-update:hover{opacity:0.9;transform:translateY(-1px)}

        /* KOMENTAR */
        .comment-list{display:flex;flex-direction:column;gap:12px;margin-bottom:16px;max-height:360px;overflow-y:auto;padding-right:4px}
        .comment-item{background:#f8fff2;border-radius:10px;padding:12px 14px;border:1px solid #e8f0de}
        .comment-item.internal{background:#fff8e8;border-color:#f0e0a0}
        .comment-meta{display:flex;align-items:center;gap:8px;margin-bottom:6px}
        .comment-avatar{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.68rem;font-weight:700;color:white;flex-shrink:0}
        .comment-avatar.admin{background:var(--green-main)}
        .comment-name{font-size:0.78rem;font-weight:700;color:#1a2e0d}
        .comment-time{font-size:0.68rem;color:#8ab06a;margin-left:auto}
        .internal-badge{font-size:0.62rem;background:#fef3c7;color:#92400e;border-radius:4px;padding:1px 5px;font-weight:700}
        .comment-text{font-size:0.83rem;color:#2d4a1a;line-height:1.6}
        textarea.comment-input{width:100%;padding:10px 12px;border:1.5px solid #d8ecc0;border-radius:8px;font-size:0.85rem;font-family:'Plus Jakarta Sans';outline:none;resize:vertical;min-height:80px;background:#f8fff2;color:#1a2e0d}
        textarea.comment-input:focus{border-color:var(--green-main);background:white}
        .check-label{display:flex;align-items:center;gap:7px;font-size:0.78rem;color:#3d5a28;cursor:pointer;margin:8px 0}
        .check-label input{accent-color:var(--green-main);width:14px;height:14px;cursor:pointer}
        .char-hint-txt{font-size:0.7rem;color:#aac890;text-align:right;margin-top:3px}
        .char-hint-txt.warn{color:#e67e22}

        /* PELAPOR */
        .pelapor-avatar{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--green-main),var(--green-light));display:flex;align-items:center;justify-content:center;color:white;font-size:1.2rem;font-weight:700;margin-bottom:12px}

        /* ALERT */
        .alert-ok{background:#f0fff4;border:1px solid #b2e5c5;color:#27ae60;border-radius:10px;padding:10px 14px;font-size:0.82rem;margin-bottom:16px;display:flex;align-items:center;gap:7px}

        .empty-comment{text-align:center;padding:20px;color:#8ab06a;font-size:0.82rem}
        .empty-comment i{display:block;font-size:1.8rem;margin-bottom:6px}
        .nav-lbl { font-size:.65rem; color:rgba(255,255,255,.35); text-transform:uppercase; letter-spacing:1.5px; padding:0 10px; margin:16px 0 6px; }
        .ni { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; color:rgba(255,255,255,.7); text-decoration:none; font-size:.88rem; font-weight:500; transition:all .2s; margin-bottom:2px; }
        .ni:hover, .ni.active { background:rgba(255,255,255,.1); color:white; }
        .ni.active { background:rgba(122,182,72,.2); color:var(--gp); }
        .ni i { width:18px; text-align:center; }
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="sidebar-logo-text">SIKESMA<small>Admin Panel</small></div>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Menu Utama</span>
        <a href="index.php" class="nav-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="pengaduan.php" class="nav-item active"><i class="fas fa-inbox"></i> Kelola Pengaduan</a>
        <a href="mahasiswa.php" class="nav-item"><i class="fas fa-users"></i> Data Mahasiswa</a>
        <span class="nav-section-label">Pengaturan</span>
        <a href="kategori.php" class="nav-item"><i class="fas fa-tags"></i> Kategori</a>
        <a href="jurusan.php" class="nav-item"><i class="fas fa-building-columns"></i> Jurusan</a>
        <span class="nav-lbl">Akun Saya</span>
        <a href="profil.php" class="ni"><i class="fas fa-user-shield"></i> Profil Admin</a>
        <a href="ubah_password.php" class="ni"><i class="fas fa-key"></i> Ubah Password</a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama_lengkap'],0,1)) ?></div>
            <div><div class="user-name"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></div><div class="user-role">Administrator</div></div>
        </div>
        <a href="../logout.php" class="btn-logout"><i class="fas fa-right-from-bracket"></i> Keluar</a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <a href="pengaduan.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <div>
            <span class="topbar-title">Detail Pengaduan</span>
            <span class="topbar-code"><?= htmlspecialchars($peng['kode_pengaduan']) ?></span>
        </div>
    </div>

    <div class="content">
        <?php if (isset($_GET['msg'])): ?>
        <div class="alert-ok"><i class="fas fa-check-circle"></i> Perubahan berhasil disimpan.</div>
        <?php endif; ?>

        <div class="detail-grid">
            <!-- KIRI -->
            <div>
                <!-- Info Utama -->
                <div class="card">
                    <div class="card-header">
                        <div class="icon"><i class="fas fa-file-alt"></i></div>
                        <h3>Informasi Pengaduan</h3>
                        <div style="margin-left:auto;display:flex;gap:6px">
                            <span style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:700"><?= ucfirst($peng['status']) ?></span>
                            <span style="background:<?= $pc['bg'] ?>;color:<?= $pc['color'] ?>;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:700"><?= ucfirst($peng['tingkat_prioritas']) ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-lbl">Kode Pengaduan</span>
                            <span class="info-val"><code style="background:#f0f7e8;padding:2px 8px;border-radius:4px;color:var(--green-main);font-size:0.85rem"><?= htmlspecialchars($peng['kode_pengaduan']) ?></code></span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Judul</span>
                            <span class="info-val" style="font-weight:700;font-size:1rem"><?= htmlspecialchars($peng['judul_pengaduan']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Kategori</span>
                            <span class="info-val"><?= htmlspecialchars($peng['nama_kategori'] ?? 'Tidak dikategorikan') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Lokasi</span>
                            <span class="info-val"><?= htmlspecialchars($peng['lokasi'] ?: '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Tanggal</span>
                            <span class="info-val"><?= date('d F Y, H:i', strtotime($peng['tanggal_pengaduan'])) ?> WIB</span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Mode Pelaporan</span>
                            <span class="info-val">
                                <?php if ($peng['anonim']): ?>
                                <span style="background:#f3f4f6;color:#6b7280;padding:3px 10px;border-radius:12px;font-size:0.72rem;font-weight:700"><i class="fas fa-user-secret"></i> Anonim</span>
                                <?php else: ?>
                                <span style="background:#f0f7e8;color:#4a7c2f;padding:3px 10px;border-radius:12px;font-size:0.72rem;font-weight:700"><i class="fas fa-user"></i> Terbuka</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div style="margin-top:16px">
                            <div class="info-lbl" style="margin-bottom:8px">Deskripsi Pengaduan</div>
                            <div class="deskripsi-box"><?= htmlspecialchars($peng['deskripsi']) ?></div>
                        </div>
                        <?php if ($peng['file_bukti']): ?>
                        <div style="margin-top:14px">
                            <div class="info-lbl" style="margin-bottom:8px">File Bukti</div>
                            <a href="get_bukti.php?id=<?= $peng['id'] ?>" target="_blank"
                               style="display:inline-flex;align-items:center;gap:6px;background:var(--green-bg);color:var(--green-main);padding:8px 14px;border-radius:8px;text-decoration:none;font-size:0.82rem;font-weight:600">
                                <i class="fas fa-download"></i> Unduh File Bukti
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pelaku -->
                <?php if ($pelaku && $pelaku->num_rows > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="icon"><i class="fas fa-user-xmark"></i></div>
                        <h3>Data Pelaku / Pihak Terlibat</h3>
                    </div>
                    <div class="card-body">
                        <?php while ($p = $pelaku->fetch_assoc()): ?>
                        <div style="background:#fff8e8;border:1px solid #f0e0a0;border-radius:10px;padding:14px;margin-bottom:10px">
                            <div class="info-row"><span class="info-lbl">Nama</span><span class="info-val"><?= htmlspecialchars($p['nama_pelaku']) ?></span></div>
                            <div class="info-row"><span class="info-lbl">NIM/NIDN</span><span class="info-val"><?= htmlspecialchars($p['nim_nidn'] ?: '-') ?></span></div>
                            <div class="info-row"><span class="info-lbl">Status</span><span class="info-val"><?= ucfirst($p['status_jabatan'] ?: '-') ?></span></div>
                            <?php if ($p['keterangan']): ?>
                            <div class="info-row"><span class="info-lbl">Keterangan</span><span class="info-val"><?= htmlspecialchars($p['keterangan']) ?></span></div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Komentar / Tindak Lanjut -->
                <div class="card">
                    <div class="card-header">
                        <div class="icon"><i class="fas fa-comments"></i></div>
                        <h3>Tindak Lanjut & Komentar</h3>
                    </div>
                    <div class="card-body">
                        <div class="comment-list" id="commentList">
                        <?php if ($komentar->num_rows === 0): ?>
                            <div class="empty-comment"><i class="fas fa-comment-slash"></i>Belum ada komentar atau tindak lanjut.</div>
                        <?php else: while ($c = $komentar->fetch_assoc()): ?>
                        <div class="comment-item <?= $c['internal'] ? 'internal' : '' ?>">
                            <div class="comment-meta">
                                <div class="comment-avatar admin"><?= strtoupper(substr($c['id_admin'] ? $c['nama_admin'] : $c['nama_mhs'], 0, 1)) ?></div>
                                <span class="comment-name"><?= htmlspecialchars($c['id_admin'] ? $c['nama_admin'] : $c['nama_mhs']) ?></span>
                                <?php if ($c['id_admin']): ?><span style="font-size:0.62rem;background:#d1fae5;color:#065f46;border-radius:4px;padding:1px 5px;font-weight:700">Admin</span><?php endif; ?>
                                <?php if ($c['internal']): ?><span class="internal-badge">Internal</span><?php endif; ?>
                                <span class="comment-time"><?= date('d/m/Y H:i', strtotime($c['dibuat_pada'])) ?></span>
                            </div>
                            <div class="comment-text"><?= nl2br(htmlspecialchars($c['pesan'])) ?></div>
                        </div>
                        <?php endwhile; endif; ?>
                        </div>

                        <!-- Form tambah komentar -->
                        <form method="POST">
                            <input type="hidden" name="add_komentar" value="1">
                            <textarea name="pesan" id="komentarInput" class="comment-input"
                                placeholder="Tulis tindak lanjut atau balasan pengaduan ini..." maxlength="1000"></textarea>
                            <div class="char-hint-txt" id="komentarHint">0/1000</div>
                            <label class="check-label">
                                <input type="checkbox" name="internal">
                                <span>Tandai sebagai catatan internal (tidak terlihat oleh mahasiswa)</span>
                            </label>
                            <button type="submit" class="btn-update">
                                <i class="fas fa-paper-plane"></i> Kirim Tindak Lanjut
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- KANAN -->
            <div>
                <!-- Status Update -->
                <div class="card status-card">
                    <div class="card-header">
                        <div class="icon"><i class="fas fa-sliders"></i></div>
                        <h3>Update Status</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="update_status" value="1">
                            <label style="font-size:0.78rem;font-weight:600;color:#6b8f52;margin-bottom:6px;display:block">Status Pengaduan</label>
                            <select name="status">
                                <option value="baru" <?= $peng['status']==='baru'?'selected':'' ?>>🔵 Baru</option>
                                <option value="diproses" <?= $peng['status']==='diproses'?'selected':'' ?>>🟡 Diproses</option>
                                <option value="selesai" <?= $peng['status']==='selesai'?'selected':'' ?>>🟢 Selesai</option>
                                <option value="ditolak" <?= $peng['status']==='ditolak'?'selected':'' ?>>🔴 Ditolak</option>
                            </select>
                            <button type="submit" class="btn-update">
                                <i class="fas fa-check"></i> Simpan Status
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Info Pelapor -->
                <div class="card">
                    <div class="card-header">
                        <div class="icon"><i class="fas fa-user"></i></div>
                        <h3>Data Pelapor</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($peng['anonim']): ?>
                        <div style="text-align:center;padding:12px 0;color:#8ab06a">
                            <i class="fas fa-user-secret" style="font-size:2.5rem;margin-bottom:8px;display:block;color:#aaa"></i>
                            <span style="font-size:0.85rem;font-weight:600">Pelapor memilih anonim</span>
                        </div>
                        <?php else: ?>
                        <div class="pelapor-avatar"><?= strtoupper(substr($peng['nama_lengkap'],0,1)) ?></div>
                        <div class="info-row"><span class="info-lbl">Nama</span><span class="info-val"><?= htmlspecialchars($peng['nama_lengkap']) ?></span></div>
                        <div class="info-row"><span class="info-lbl">NIM</span><span class="info-val"><?= htmlspecialchars($peng['nim']) ?></span></div>
                        <div class="info-row"><span class="info-lbl">Email</span><span class="info-val"><?= htmlspecialchars($peng['email_mhs']) ?></span></div>
                        <div class="info-row"><span class="info-lbl">No. HP</span><span class="info-val"><?= htmlspecialchars($peng['nomor_hp'] ?: '-') ?></span></div>
                        <div class="info-row"><span class="info-lbl">Jurusan</span><span class="info-val"><?= htmlspecialchars($peng['nama_jurusan'] ?: '-') ?></span></div>
                        <div class="info-row"><span class="info-lbl">Fakultas</span><span class="info-val"><?= htmlspecialchars($peng['nama_fakultas'] ?: '-') ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Timeline Status -->
                <div class="card">
                    <div class="card-header">
                        <div class="icon"><i class="fas fa-timeline"></i></div>
                        <h3>Alur Status</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $steps = ['baru','diproses','selesai'];
                        $current = $peng['status'];
                        $currentIdx = array_search($current, $steps);
                        $labels = ['Diterima','Diproses','Selesai'];
                        ?>
                        <?php foreach ($steps as $i => $step): ?>
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:<?= $i < count($steps)-1 ? '4px' : '0' ?>">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:0">
                                <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:700;flex-shrink:0;
                                    background:<?= ($current==='ditolak'&&$i===0)?'#fee2e2':($i<=$currentIdx?'var(--green-main)':'#e8f0de') ?>;
                                    color:<?= ($current==='ditolak'&&$i===0)?'#991b1b':($i<=$currentIdx?'white':'#aac890') ?>">
                                    <?= $i < $currentIdx ? '<i class="fas fa-check"></i>' : ($i===$currentIdx ? '<i class="fas fa-circle" style="font-size:0.5rem"></i>' : ($i+1)) ?>
                                </div>
                                <?php if ($i < count($steps)-1): ?>
                                <div style="width:2px;height:20px;background:<?= $i<$currentIdx?'var(--green-main)':'#e8f0de' ?>"></div>
                                <?php endif; ?>
                            </div>
                            <span style="font-size:0.82rem;font-weight:<?= $i===$currentIdx?'700':'500' ?>;color:<?= $i===$currentIdx?'#1a2e0d':'#8ab06a' ?>"><?= $labels[$i] ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($current === 'ditolak'): ?>
                        <div style="margin-top:8px;background:#fee2e2;border-radius:8px;padding:8px 12px;font-size:0.78rem;color:#991b1b;font-weight:600"><i class="fas fa-times-circle"></i> Pengaduan ini ditolak</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
const komentarInput = document.getElementById('komentarInput');
const komentarHint = document.getElementById('komentarHint');
if (komentarInput) {
    komentarInput.addEventListener('input', () => {
        const len = komentarInput.value.length;
        komentarHint.textContent = len + '/1000';
        komentarHint.className = 'char-hint-txt' + (len > 900 ? ' warn' : '');
    });
}
// Scroll to bottom of comment list
const list = document.getElementById('commentList');
if (list) list.scrollTop = list.scrollHeight;
</script>
</body>
</html>