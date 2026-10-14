<?php
require_once  '../session.php';
require_once '../db.php';
requireMahasiswa();

$mahasiswaId = $_SESSION['user_id'];

// Data mahasiswa
$stmt = $conn->prepare("SELECT m.*, j.nama_jurusan, j.nama_fakultas FROM mahasiswa m LEFT JOIN jurusan j ON m.id_jurusan = j.id WHERE m.id = ?");
$stmt->bind_param('i', $mahasiswaId);
$stmt->execute();
$mhsData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Stats pengaduan milik mahasiswa ini
$totalPengaduan = $conn->query("SELECT COUNT(*) FROM pengaduan WHERE id_mahasiswa=$mahasiswaId")->fetch_row()[0];
$pengaduanBaru = $conn->query("SELECT COUNT(*) FROM pengaduan WHERE id_mahasiswa=$mahasiswaId AND status='baru'")->fetch_row()[0];
$pengaduanProses = $conn->query("SELECT COUNT(*) FROM pengaduan WHERE id_mahasiswa=$mahasiswaId AND status='diproses'")->fetch_row()[0];
$pengaduanSelesai = $conn->query("SELECT COUNT(*) FROM pengaduan WHERE id_mahasiswa=$mahasiswaId AND status='selesai'")->fetch_row()[0];

// Pengaduan terbaru
$myPengaduan = $conn->query("
    SELECT p.id, p.kode_pengaduan, p.judul_pengaduan, p.status, p.tingkat_prioritas,
           p.tanggal_pengaduan, p.anonim,
           k.nama_kategori
    FROM pengaduan p
    LEFT JOIN kategori_pengaduan k ON p.id_kategori = k.id
    WHERE p.id_mahasiswa = $mahasiswaId
    ORDER BY p.tanggal_pengaduan DESC
    LIMIT 5
");

// Kategori untuk form
$kategoriList = $conn->query("SELECT * FROM kategori_pengaduan ORDER BY nama_kategori");

// Proses submit pengaduan baru
$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_pengaduan'])) {
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $kategori = (int)($_POST['kategori'] ?? 0);
    $prioritas = $_POST['prioritas'] ?? 'sedang';
    $anonim = isset($_POST['anonim']) ? 1 : 0;

    // Validasi
    if (strlen($judul) < 5 || strlen($judul) > 200) {
        $formError = 'Judul pengaduan harus 5-200 karakter.';
    } elseif (strlen($deskripsi) < 20) {
        $formError = 'Deskripsi minimal 20 karakter.';
    } else {
        // Generate kode unik
        $kode = 'ADU-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // Handle file upload
        $fileBukti = null;
        if (isset($_FILES['file_bukti']) && $_FILES['file_bukti']['size'] > 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            if (!in_array($_FILES['file_bukti']['type'], $allowedTypes)) {
                $formError = 'File hanya boleh JPG, PNG, GIF, atau PDF.';
            } elseif ($_FILES['file_bukti']['size'] > 5 * 1024 * 1024) {
                $formError = 'Ukuran file maksimal 5MB.';
            } else {
                $fileBukti = file_get_contents($_FILES['file_bukti']['tmp_name']);
            }
        }

        if (!$formError) {
            $stmt = $conn->prepare("INSERT INTO pengaduan (kode_pengaduan, id_mahasiswa, judul_pengaduan, deskripsi, lokasi, tingkat_prioritas, anonim, file_bukti, id_kategori) VALUES (?,?,?,?,?,?,?,?,?)");
            $null = null;
            $stmt->bind_param('sissssisi', $kode, $mahasiswaId, $judul, $deskripsi, $lokasi, $prioritas, $anonim, $fileBukti, $kategori);

            if ($stmt->execute()) {
                $formSuccess = "Pengaduan berhasil dikirim! Kode pengaduan Anda: <strong>$kode</strong>";
                header("Location: index.php?msg=success&kode=" . urlencode($kode));
                exit;
            } else {
                $formError = 'Gagal menyimpan pengaduan. Silakan coba lagi.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKESMA - Dashboard Mahasiswa</title>
    <link rel="icon" href="img/image.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fraunces:ital,wght@0,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --green-dark: #2d5016;
            --green-main: #4a7c2f;
            --green-light: #7ab648;
            --green-pale: #c8e6a0;
            --green-bg: #f0f7e8;
            --sidebar-w: 260px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f5f8f2; display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-w);
            background: linear-gradient(180deg, var(--green-dark) 0%, #1a3a08 100%);
            min-height: 100vh;
            position: fixed;
            left: 0; top: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }
        .sidebar-logo {
            padding: 28px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-logo-icon {
            width: 42px; height: 42px;
            background: rgba(255,255,255,0.12);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.2rem;
        }
        .sidebar-logo-text { font-family: 'Fraunces', serif; color: white; font-size: 1.3rem; font-weight: 700; }
        .sidebar-logo-text small { display: block; font-size: 0.58rem; color: rgba(255,255,255,0.5); font-family: 'Plus Jakarta Sans'; font-weight: 400; }
        .sidebar-nav { flex: 1; padding: 20px 14px; }
        .nav-section-label { font-size: 0.65rem; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 1.5px; padding: 0 10px; margin: 16px 0 6px; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; border-radius: 10px;
            color: rgba(255,255,255,0.7); text-decoration: none;
            font-size: 0.88rem; font-weight: 500;
            transition: all 0.2s; margin-bottom: 2px;
        }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.active { background: rgba(122,182,72,0.2); color: var(--green-pale); }
        .nav-item i { width: 18px; text-align: center; }
        .sidebar-footer { padding: 16px 14px; border-top: 1px solid rgba(255,255,255,0.08); }
        .user-info { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; background: rgba(255,255,255,0.06); margin-bottom: 8px; }
        .user-avatar { width: 36px; height: 36px; background: var(--green-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem; flex-shrink: 0; }
        .user-name { font-size: 0.82rem; color: white; font-weight: 600; }
        .user-role { font-size: 0.68rem; color: rgba(255,255,255,0.45); }
        .btn-logout { display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 14px; border-radius: 8px; background: rgba(231,76,60,0.15); color: #ff8a80; text-decoration: none; font-size: 0.82rem; font-weight: 600; border: none; cursor: pointer; font-family: 'Plus Jakarta Sans'; transition: all 0.2s; }
        .btn-logout:hover { background: rgba(231,76,60,0.3); }

        /* MAIN */
        .main { margin-left: var(--sidebar-w); flex: 1; }
        .topbar { background: white; padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e8f0de; position: sticky; top: 0; z-index: 50; }
        .topbar-title { font-size: 1.1rem; font-weight: 700; color: #1a2e0d; }
        .topbar-date { font-size: 0.8rem; color: #6b8f52; }
        .content { padding: 28px 32px; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-family: 'Fraunces', serif; font-size: 1.7rem; color: #1a2e0d; }
        .page-header p { color: #6b8f52; font-size: 0.88rem; margin-top: 4px; }

        /* STATS */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: white; border-radius: 14px; padding: 20px; border: 1px solid #e8f0de; transition: all 0.2s; }
        .stat-card:hover { box-shadow: 0 6px 24px rgba(74,124,47,0.12); transform: translateY(-2px); }
        .stat-icon-box { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-bottom: 12px; }
        .bg-green { background: rgba(74,124,47,0.1); color: #4a7c2f; }
        .bg-blue { background: rgba(52,152,219,0.1); color: #3498db; }
        .bg-orange { background: rgba(230,126,34,0.1); color: #e67e22; }
        .bg-teal { background: rgba(39,174,96,0.1); color: #27ae60; }
        .stat-num { font-size: 1.8rem; font-weight: 800; color: #1a2e0d; line-height: 1; }
        .stat-lbl { font-size: 0.78rem; color: #6b8f52; margin-top: 4px; font-weight: 500; }

        /* GRID LAYOUT */
        .content-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; }

        .section-card { background: white; border-radius: 16px; border: 1px solid #e8f0de; overflow: hidden; }
        .section-card-header { padding: 18px 24px; border-bottom: 1px solid #e8f0de; display: flex; align-items: center; justify-content: space-between; }
        .section-card-header h3 { font-size: 1rem; font-weight: 700; color: #1a2e0d; }
        .btn-sm { padding: 7px 14px; border-radius: 7px; font-size: 0.8rem; font-weight: 600; font-family: 'Plus Jakarta Sans'; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-green { background: var(--green-main); color: white; }
        .btn-green:hover { background: var(--green-dark); }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #f8fff2; padding: 11px 16px; text-align: left; font-size: 0.75rem; font-weight: 700; color: #5c7a3e; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e8f0de; }
        tbody tr { border-bottom: 1px solid #f0f7e8; }
        tbody tr:hover { background: #fafff5; }
        tbody td { padding: 12px 16px; font-size: 0.83rem; color: #2d4a1a; vertical-align: middle; }
        .kode { font-family: monospace; font-size: 0.75rem; background: #f0f7e8; padding: 2px 8px; border-radius: 4px; color: #4a7c2f; }
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; display: inline-block; }
        .badge-baru { background: #dbeafe; color: #1d4ed8; }
        .badge-diproses { background: #fef3c7; color: #92400e; }
        .badge-selesai { background: #d1fae5; color: #065f46; }
        .badge-ditolak { background: #fee2e2; color: #991b1b; }
        .badge-rendah { background: #d1fae5; color: #065f46; }
        .badge-sedang { background: #fef3c7; color: #92400e; }
        .badge-tinggi { background: #fee2e2; color: #991b1b; }
        .badge-kritis { background: #fce7f3; color: #9d174d; }

        .btn-act { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; cursor: pointer; border: none; text-decoration: none; }
        .btn-view { background: rgba(52,152,219,0.1); color: #3498db; }

        /* PROFILE CARD */
        .profile-content { padding: 24px; }
        .profile-avatar { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, var(--green-main), var(--green-light)); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.6rem; font-weight: 700; margin: 0 auto 16px; }
        .profile-name { text-align: center; font-weight: 700; font-size: 1rem; color: #1a2e0d; margin-bottom: 4px; }
        .profile-nim { text-align: center; font-size: 0.8rem; color: #8ab06a; margin-bottom: 16px; }
        .profile-divider { border: none; border-top: 1px solid #e8f0de; margin: 16px 0; }
        .profile-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.82rem; }
        .profile-row .lbl { color: #8ab06a; font-weight: 500; }
        .profile-row .val { color: #1a2e0d; font-weight: 600; text-align: right; max-width: 160px; }

        /* FORM PENGADUAN */
        .form-section { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 0.82rem; font-weight: 600; color: #3d5a28; margin-bottom: 6px; }
        .form-label span.req { color: #e74c3c; }
        .form-control {
            width: 100%; padding: 10px 14px;
            border: 1.5px solid #d8ecc0; border-radius: 9px;
            font-size: 0.88rem; font-family: 'Plus Jakarta Sans'; color: #1a2e0d;
            background: #f8fff2; outline: none; transition: all 0.2s;
        }
        .form-control:focus { border-color: var(--green-main); background: white; box-shadow: 0 0 0 3px rgba(74,124,47,0.08); }
        .form-control::placeholder { color: #aac890; }
        textarea.form-control { resize: vertical; min-height: 100px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .char-hint { font-size: 0.7rem; color: #aac890; margin-top: 4px; text-align: right; }
        .char-hint.warn { color: #e67e22; }
        .char-hint.over { color: #e74c3c; }
        .check-row { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #3d5a28; cursor: pointer; }
        .check-row input { width: 16px; height: 16px; accent-color: var(--green-main); cursor: pointer; }
        .field-err { font-size: 0.72rem; color: #c0392b; margin-top: 3px; display: none; }
        .field-err.show { display: block; }

        .alert { padding: 12px 16px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 8px; }
        .alert-error { background: #fff0f0; color: #c0392b; border: 1px solid #f5c6c6; }
        .alert-success { background: #f0fff4; color: #27ae60; border: 1px solid #b2e5c5; }

        .btn-full { width: 100%; }

        .empty-state { text-align: center; padding: 36px; color: #8ab06a; }
        .empty-state i { font-size: 2.5rem; margin-bottom: 10px; display: block; }
        .empty-state p { font-size: 0.85rem; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="sidebar-logo-text">SIKESMA<small>Portal Mahasiswa</small></div>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Menu</span>
        <a href="index.php" class="nav-item active"><i class="fas fa-home"></i> Beranda</a>
        <a href="pengaduan.php" class="nav-item"><i class="fas fa-bullhorn"></i> Pengaduan Saya</a>
        <a href="buat_pengaduan.php" class="nav-item"><i class="fas fa-plus-circle"></i> Buat Pengaduan</a>
        <span class="nav-section-label">Akun</span>
        <a href="profil.php" class="nav-item"><i class="fas fa-user-circle"></i> Profil Saya</a>
        <a href="ubah_password.php" class="nav-item"><i class="fas fa-key"></i> Ubah Password</a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars(substr($_SESSION['nama_lengkap'], 0, 25)) ?></div>
                <div class="user-role"><?= htmlspecialchars($_SESSION['nim']) ?></div>
            </div>
        </div>
        <a href="../logout.php" class="btn-logout"><i class="fas fa-right-from-bracket"></i> Keluar</a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title">Dashboard Mahasiswa</div>
            <div class="topbar-date"><?= date('l, d F Y') ?></div>
        </div>
    </div>

    <div class="content">
        <div class="page-header">
            <h1>Halo, <?= htmlspecialchars(explode(' ', $_SESSION['nama_lengkap'])[3]) ?>!</h1>
            <p>Selamat datang di portal mahasiswa SIKESMA.</p>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle" style="margin-top:2px;flex-shrink:0"></i>
            <span>Pengaduan berhasil dikirim! Kode: <strong><?= htmlspecialchars($_GET['kode'] ?? '') ?></strong>. Simpan kode ini untuk melacak status pengaduan Anda.</span>
        </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon-box bg-green"><i class="fas fa-folder-open"></i></div>
                <div class="stat-num"><?= $totalPengaduan ?></div>
                <div class="stat-lbl">Total Pengaduan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-blue"><i class="fas fa-envelope"></i></div>
                <div class="stat-num"><?= $pengaduanBaru ?></div>
                <div class="stat-lbl">Baru / Belum Diproses</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-orange"><i class="fas fa-spinner"></i></div>
                <div class="stat-num"><?= $pengaduanProses ?></div>
                <div class="stat-lbl">Sedang Diproses</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-teal"><i class="fas fa-check-circle"></i></div>
                <div class="stat-num"><?= $pengaduanSelesai ?></div>
                <div class="stat-lbl">Selesai</div>
            </div>
        </div>

        <!-- CONTENT GRID -->
        <div class="content-grid">
            <!-- KIRI: RIWAYAT + FORM -->
            <div style="display:flex;flex-direction:column;gap:24px">
                <!-- Riwayat Pengaduan -->
                <div class="section-card">
                    <div class="section-card-header">
                        <h3><i class="fas fa-clock" style="color:var(--green-main);margin-right:8px"></i>Pengaduan Terbaru</h3>
                        <a href="pengaduan.php" class="btn-sm btn-green"><i class="fas fa-list"></i> Semua</a>
                    </div>
                    <?php if ($myPengaduan->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Kode</th><th>Judul</th><th>Prioritas</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $myPengaduan->fetch_assoc()): ?>
                        <tr>
                            <td><span class="kode"><?= htmlspecialchars($row['kode_pengaduan']) ?></span></td>
                            <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($row['judul_pengaduan']) ?></td>
                            <td><span class="badge badge-<?= $row['tingkat_prioritas'] ?>"><?= ucfirst($row['tingkat_prioritas']) ?></span></td>
                            <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                            <td><a href="detail_pengaduan.php?id=<?= $row['id'] ?>" class="btn-act btn-view"><i class="fas fa-eye"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Belum ada pengaduan. Gunakan form di bawah untuk membuat pengaduan.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Form Buat Pengaduan -->
                <div class="section-card">
                    <div class="section-card-header">
                        <h3><i class="fas fa-plus-circle" style="color:var(--green-main);margin-right:8px"></i>Buat Pengaduan Baru</h3>
                    </div>
                    <div class="form-section">
                        <?php if ($formError): ?>
                        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($formError) ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" id="pengaduanForm" novalidate>
                            <input type="hidden" name="submit_pengaduan" value="1">

                            <div class="form-group">
                                <label class="form-label">Judul Pengaduan <span class="req">*</span></label>
                                <input type="text" name="judul" id="judul" class="form-control"
                                    placeholder="Contoh: Fasilitas kamar mandi rusak di gedung A"
                                    maxlength="200"
                                    value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>">
                                <div class="char-hint" id="judulHint">0/200</div>
                                <span class="field-err" id="judulErr">Judul harus 5-200 karakter.</span>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Kategori <span class="req">*</span></label>
                                    <select name="kategori" class="form-control">
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php while ($k = $kategoriList->fetch_assoc()): ?>
                                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tingkat Prioritas</label>
                                    <select name="prioritas" class="form-control">
                                        <option value="rendah">Rendah</option>
                                        <option value="sedang" selected>Sedang</option>
                                        <option value="tinggi">Tinggi</option>
                                        <option value="kritis">Kritis / Darurat</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Lokasi Kejadian</label>
                                <input type="text" name="lokasi" class="form-control"
                                    placeholder="Contoh: Gedung A lantai 2, Perpustakaan"
                                    maxlength="100"
                                    value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Deskripsi Lengkap <span class="req">*</span></label>
                                <textarea name="deskripsi" id="deskripsi" class="form-control"
                                    placeholder="Ceritakan permasalahan Anda secara detail. Jelaskan kronologi, siapa yang terlibat, kapan terjadi, dan dampaknya..."
                                    maxlength="5000"
                                    rows="5"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                                <div class="char-hint" id="deskripsiHint">0/5000 (min. 20 karakter)</div>
                                <span class="field-err" id="deskripsiErr">Deskripsi minimal 20 karakter.</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Lampiran / Bukti (Opsional)</label>
                                <input type="file" name="file_bukti" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf">
                                <div class="char-hint">Format: JPG, PNG, GIF, PDF — Maks. 5MB</div>
                            </div>

                            <div class="form-group">
                                <label class="check-row">
                                    <input type="checkbox" name="anonim" id="anonim">
                                    <span>Kirim sebagai <strong>anonim</strong> (nama Anda tidak akan ditampilkan)</span>
                                </label>
                            </div>

                            <button type="submit" class="btn-sm btn-green btn-full" id="submitBtn" style="height:44px;font-size:0.92rem">
                                <i class="fas fa-paper-plane"></i> Kirim Pengaduan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- KANAN: PROFIL -->
            <div>
                <div class="section-card">
                    <div class="section-card-header">
                        <h3><i class="fas fa-user-circle" style="color:var(--green-main);margin-right:8px"></i>Profil Saya</h3>
                    </div>
                    <div class="profile-content">
                        <div class="profile-avatar"><?= strtoupper(substr($mhsData['nama_lengkap'], 0, 1)) ?></div>
                        <div class="profile-name"><?= htmlspecialchars($mhsData['nama_lengkap']) ?></div>
                        <div class="profile-nim">NIM: <?= htmlspecialchars($mhsData['nim']) ?></div>
                        <hr class="profile-divider">
                        <div class="profile-row">
                            <span class="lbl">Email</span>
                            <span class="val"><?= htmlspecialchars($mhsData['email']) ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="lbl">No. HP</span>
                            <span class="val"><?= htmlspecialchars($mhsData['nomor_hp'] ?? '-') ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="lbl">Semester</span>
                            <span class="val"><?= $mhsData['semester'] ?? '-' ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="lbl">Jurusan</span>
                            <span class="val"><?= htmlspecialchars($mhsData['nama_jurusan'] ?? '-') ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="lbl">Fakultas</span>
                            <span class="val"><?= htmlspecialchars($mhsData['nama_fakultas'] ?? '-') ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="lbl">Bergabung</span>
                            <span class="val"><?= date('d M Y', strtotime($mhsData['dibuat_pada'])) ?></span>
                        </div>
                        <hr class="profile-divider">
                        <a href="profil.php" class="btn-sm btn-green btn-full" style="justify-content:center;height:38px">
                            <i class="fas fa-pen"></i> Edit Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Char counters
function charCounter(inputId, hintId, max, min) {
    const el = document.getElementById(inputId);
    const hint = document.getElementById(hintId);
    if (!el || !hint) return;
    el.addEventListener('input', () => {
        const len = el.value.length;
        hint.textContent = len + '/' + max + (min ? ' (min. ' + min + ' karakter)' : '');
        hint.className = 'char-hint' + (len >= max ? ' over' : len > max * 0.85 ? ' warn' : '');
    });
}
charCounter('judul', 'judulHint', 200);
charCounter('deskripsi', 'deskripsiHint', 5000, 20);

// Validasi form
document.getElementById('pengaduanForm').addEventListener('submit', function(e) {
    let valid = true;
    document.querySelectorAll('.field-err').forEach(el => el.classList.remove('show'));

    const judul = document.getElementById('judul').value.trim();
    const deskripsi = document.getElementById('deskripsi').value.trim();

    if (judul.length < 5 || judul.length > 200) {
        document.getElementById('judulErr').classList.add('show');
        valid = false;
    }
    if (deskripsi.length < 20) {
        document.getElementById('deskripsiErr').classList.add('show');
        valid = false;
    }

    if (!valid) {
        e.preventDefault();
        window.scrollTo({ top: document.getElementById('pengaduanForm').offsetTop - 100, behavior: 'smooth' });
    } else {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
    }
});
</script>
</body>
</html>