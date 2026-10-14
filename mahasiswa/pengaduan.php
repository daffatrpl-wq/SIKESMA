<?php
require_once '../session.php';
require_once '../db.php';
requireMahasiswa();

$mid = $_SESSION['user_id'];
$page = max(1,(int)($_GET['page'] ?? 1));
$perPage = 10; $offset = ($page-1)*$perPage;
$statusFilter = $_GET['status'] ?? '';

$where = "p.id_mahasiswa=$mid";
if ($statusFilter) $where .= " AND p.status='".$conn->real_escape_string($statusFilter)."'";

$total = $conn->query("SELECT COUNT(*) FROM pengaduan p WHERE $where")->fetch_row()[0];
$totalPages = ceil($total/$perPage);

$list = $conn->query("
    SELECT p.*, k.nama_kategori
    FROM pengaduan p
    LEFT JOIN kategori_pengaduan k ON p.id_kategori=k.id
    WHERE $where ORDER BY p.tanggal_pengaduan DESC LIMIT $perPage OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKESMA — Pengaduan Saya</title>
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
        .topbar{background:white;padding:16px 32px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e8f0de;position:sticky;top:0;z-index:50}
        .topbar-title{font-size:1.1rem;font-weight:700;color:#1a2e0d}
        .topbar-date{font-size:0.8rem;color:#6b8f52}
        .content{padding:28px 32px}
        .page-header{margin-bottom:22px;display:flex;align-items:center;justify-content:space-between}
        .page-header h1{font-family:'Fraunces',serif;font-size:1.7rem;color:#1a2e0d}
        .filter-bar{display:flex;gap:8px;align-items:center;margin-bottom:18px;flex-wrap:wrap}
        .filter-btn{padding:7px 16px;border-radius:20px;border:1.5px solid #e8f0de;background:white;color:#6b8f52;font-size:0.8rem;font-weight:600;cursor:pointer;font-family:'Plus Jakarta Sans';transition:all 0.2s;text-decoration:none}
        .filter-btn:hover,.filter-btn.active{background:var(--green-main);color:white;border-color:var(--green-main)}
        .section-card{background:white;border-radius:16px;border:1px solid #e8f0de;overflow:hidden}
        .section-card-header{padding:16px 22px;border-bottom:1px solid #e8f0de;display:flex;align-items:center;justify-content:space-between}
        .section-card-header h3{font-size:0.95rem;font-weight:700;color:#1a2e0d}
        .btn-sm{padding:8px 16px;border-radius:8px;font-size:0.82rem;font-weight:600;font-family:'Plus Jakarta Sans';cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all 0.2s}
        .btn-green{background:var(--green-main);color:white}
        /* Cards list view */
        .pengaduan-list{padding:16px}
        .pengaduan-card{background:white;border:1.5px solid #e8f0de;border-radius:12px;padding:18px;margin-bottom:12px;transition:all 0.2s;position:relative;overflow:hidden}
        .pengaduan-card:hover{box-shadow:0 4px 20px rgba(74,124,47,0.1);border-color:var(--green-pale);transform:translateY(-1px)}
        .pengaduan-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;border-radius:4px 0 0 4px}
        .pengaduan-card.status-baru::before{background:#3b82f6}
        .pengaduan-card.status-diproses::before{background:#f59e0b}
        .pengaduan-card.status-selesai::before{background:#10b981}
        .pengaduan-card.status-ditolak::before{background:#ef4444}
        .card-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:8px}
        .card-kode{font-family:monospace;font-size:0.72rem;background:#f0f7e8;padding:2px 8px;border-radius:4px;color:var(--green-main)}
        .card-badges{display:flex;gap:6px}
        .badge{padding:3px 10px;border-radius:20px;font-size:0.68rem;font-weight:700;display:inline-block}
        .badge-baru{background:#dbeafe;color:#1d4ed8}
        .badge-diproses{background:#fef3c7;color:#92400e}
        .badge-selesai{background:#d1fae5;color:#065f46}
        .badge-ditolak{background:#fee2e2;color:#991b1b}
        .badge-rendah{background:#d1fae5;color:#065f46}
        .badge-sedang{background:#fef3c7;color:#92400e}
        .badge-tinggi{background:#fee2e2;color:#991b1b}
        .badge-kritis{background:#fce7f3;color:#9d174d}
        .card-judul{font-size:1rem;font-weight:700;color:#1a2e0d;margin-bottom:4px}
        .card-meta{display:flex;gap:14px;font-size:0.75rem;color:#8ab06a}
        .card-meta span{display:flex;align-items:center;gap:4px}
        .card-link{display:inline-flex;align-items:center;gap:5px;color:var(--green-main);font-size:0.78rem;font-weight:600;text-decoration:none;margin-top:10px;padding:5px 10px;border-radius:6px;background:var(--green-bg);transition:all 0.2s}
        .card-link:hover{background:var(--green-pale)}
        .pagination{display:flex;align-items:center;justify-content:space-between;padding:14px 22px;border-top:1px solid #e8f0de}
        .pag-info{font-size:0.78rem;color:#6b8f52}
        .pag-links{display:flex;gap:5px}
        .pag-btn{width:30px;height:30px;border-radius:6px;display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:0.8rem;font-weight:600;border:1.5px solid #e8f0de;color:#4a7c2f;transition:all 0.2s}
        .pag-btn:hover{background:var(--green-bg);border-color:var(--green-main)}
        .pag-btn.active{background:var(--green-main);color:white;border-color:var(--green-main)}
        .pag-btn.disabled{opacity:0.4;pointer-events:none}
        .empty-state{text-align:center;padding:56px;color:#8ab06a}
        .empty-state i{font-size:3rem;margin-bottom:12px;display:block}
        .empty-state p{font-size:0.88rem}
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
        <div><div class="topbar-title">Pengaduan Saya</div><div class="topbar-date"><?= date('l, d F Y') ?></div></div>
    </div>
    <div class="content">
        <div class="page-header">
            <div><h1>Riwayat Pengaduan</h1><p>Total <?= $total ?> pengaduan</p></div>
            <a href="buat_pengaduan.php" class="btn-sm btn-green"><i class="fas fa-plus"></i> Buat Baru</a>
        </div>

        <!-- Filter -->
        <div class="filter-bar">
            <a href="pengaduan.php" class="filter-btn <?= !$statusFilter?'active':'' ?>">Semua</a>
            <a href="pengaduan.php?status=baru" class="filter-btn <?= $statusFilter==='baru'?'active':'' ?>">🔵 Baru</a>
            <a href="pengaduan.php?status=diproses" class="filter-btn <?= $statusFilter==='diproses'?'active':'' ?>">🟡 Diproses</a>
            <a href="pengaduan.php?status=selesai" class="filter-btn <?= $statusFilter==='selesai'?'active':'' ?>">🟢 Selesai</a>
            <a href="pengaduan.php?status=ditolak" class="filter-btn <?= $statusFilter==='ditolak'?'active':'' ?>">🔴 Ditolak</a>
        </div>

        <div class="section-card">
            <?php if ($list && $list->num_rows > 0): ?>
            <div class="pengaduan-list">
            <?php while ($row=$list->fetch_assoc()): ?>
            <div class="pengaduan-card status-<?= $row['status'] ?>">
                <div class="card-top">
                    <span class="card-kode"><?= htmlspecialchars($row['kode_pengaduan']) ?></span>
                    <div class="card-badges">
                        <span class="badge badge-<?= $row['tingkat_prioritas'] ?>"><?= ucfirst($row['tingkat_prioritas']) ?></span>
                        <span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
                    </div>
                </div>
                <div class="card-judul"><?= htmlspecialchars($row['judul_pengaduan']) ?></div>
                <div class="card-meta">
                    <?php if ($row['nama_kategori']): ?><span><i class="fas fa-tag"></i><?= htmlspecialchars($row['nama_kategori']) ?></span><?php endif; ?>
                    <span><i class="fas fa-calendar"></i><?= date('d F Y, H:i', strtotime($row['tanggal_pengaduan'])) ?></span>
                    <?php if ($row['anonim']): ?><span><i class="fas fa-user-secret"></i>Anonim</span><?php endif; ?>
                </div>
                <a href="detail_pengaduan.php?id=<?= $row['id'] ?>" class="card-link">
                    <i class="fas fa-eye"></i> Lihat Detail & Tindak Lanjut
                </a>
            </div>
            <?php endwhile; ?>
            </div>
            <div class="pagination">
                <span class="pag-info">Hal <?= $page ?>/<?= max(1,$totalPages) ?></span>
                <div class="pag-links">
                    <?php $qs='status='.urlencode($statusFilter); ?>
                    <a href="?<?= $qs ?>&page=<?= max(1,$page-1) ?>" class="pag-btn <?= $page<=1?'disabled':'' ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
                    <a href="?<?= $qs ?>&page=<?= $i ?>" class="pag-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?<?= $qs ?>&page=<?= min($totalPages,$page+1) ?>" class="pag-btn <?= $page>=$totalPages?'disabled':'' ?>"><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Belum ada pengaduan<?= $statusFilter ? ' dengan status "'.ucfirst($statusFilter).'"' : '' ?>.</p>
                <a href="buat_pengaduan.php" class="btn-sm btn-green" style="margin-top:14px;display:inline-flex"><i class="fas fa-plus"></i> Buat Pengaduan</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
