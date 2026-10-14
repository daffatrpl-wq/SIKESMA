<?php
require_once '../session.php';
require_once '../db.php';
requireAdmin();

$search = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$jurusanFilter = (int)($_GET['jurusan'] ?? 0);
$page = max(1,(int)($_GET['page'] ?? 1));
$perPage = 20; $offset = ($page-1)*$perPage;

$where = '1=1';
if ($search) $where .= " AND (m.nim LIKE '%$search%' OR m.nama_lengkap LIKE '%$search%' OR m.email LIKE '%$search%')";
if ($jurusanFilter) $where .= " AND m.id_jurusan=$jurusanFilter";

$total = $conn->query("SELECT COUNT(*) FROM mahasiswa m WHERE $where")->fetch_row()[0];
$totalPages = ceil($total/$perPage);

$list = $conn->query("
    SELECT m.*, j.nama_jurusan,
           (SELECT COUNT(*) FROM pengaduan WHERE id_mahasiswa=m.id) as jml_pengaduan
    FROM mahasiswa m
    LEFT JOIN jurusan j ON m.id_jurusan=j.id
    WHERE $where ORDER BY m.dibuat_pada DESC LIMIT $perPage OFFSET $offset
");
$jurusanList = $conn->query("SELECT * FROM jurusan ORDER BY nama_jurusan");

// Toggle aktif
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_aktif'])) {
    $mid=(int)$_POST['mhs_id']; $aktif=(int)$_POST['aktif'];
    $conn->query("UPDATE mahasiswa SET aktif=$aktif WHERE id=$mid");
    header('Location: mahasiswa.php?'.http_build_query($_GET).'&msg=ok'); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKESMA — Data Mahasiswa</title>
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
        .page-header{margin-bottom:22px}
        .page-header h1{font-family:'Fraunces',serif;font-size:1.7rem;color:#1a2e0d}
        .page-header p{color:#6b8f52;font-size:0.88rem;margin-top:4px}
        .filter-bar{background:white;border-radius:14px;padding:16px 20px;border:1px solid #e8f0de;margin-bottom:18px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .filter-input{flex:1;min-width:200px;padding:9px 14px;border:1.5px solid #d8ecc0;border-radius:9px;font-size:0.85rem;font-family:'Plus Jakarta Sans';outline:none;background:#f8fff2;color:#1a2e0d}
        .filter-input:focus{border-color:var(--green-main)}
        .filter-select{padding:9px 14px;border:1.5px solid #d8ecc0;border-radius:9px;font-size:0.85rem;font-family:'Plus Jakarta Sans';outline:none;background:#f8fff2;color:#1a2e0d}
        .btn-sm{padding:8px 16px;border-radius:8px;font-size:0.82rem;font-weight:600;font-family:'Plus Jakarta Sans';cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all 0.2s}
        .btn-green{background:var(--green-main);color:white}
        .btn-green:hover{background:var(--green-dark)}
        .btn-outline{background:#f0f7e8;color:var(--green-main)}
        .section-card{background:white;border-radius:16px;border:1px solid #e8f0de;overflow:hidden}
        .section-card-header{padding:16px 22px;border-bottom:1px solid #e8f0de;display:flex;align-items:center;justify-content:space-between}
        .section-card-header h3{font-size:0.95rem;font-weight:700;color:#1a2e0d}
        table{width:100%;border-collapse:collapse}
        thead th{background:#f8fff2;padding:11px 16px;text-align:left;font-size:0.73rem;font-weight:700;color:#5c7a3e;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e8f0de}
        tbody tr{border-bottom:1px solid #f0f7e8;transition:background 0.15s}
        tbody tr:hover{background:#fafff5}
        tbody td{padding:11px 16px;font-size:0.83rem;color:#2d4a1a;vertical-align:middle}
        .mhs-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--green-main),var(--green-light));display:flex;align-items:center;justify-content:center;color:white;font-size:0.78rem;font-weight:700;flex-shrink:0}
        .badge-aktif{background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:20px;font-size:0.68rem;font-weight:700;display:inline-block}
        .badge-nonaktif{background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:0.68rem;font-weight:700;display:inline-block}
        .btn-act{width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;cursor:pointer;border:none;text-decoration:none;transition:all 0.2s}
        .btn-view{background:rgba(52,152,219,0.1);color:#3498db}
        .btn-view:hover{background:rgba(52,152,219,0.2)}
        .btn-red{background:rgba(231,76,60,0.1);color:#e74c3c}
        .btn-red:hover{background:rgba(231,76,60,0.2)}
        .btn-teal{background:rgba(39,174,96,0.1);color:#27ae60}
        .btn-teal:hover{background:rgba(39,174,96,0.2)}
        .pagination{display:flex;align-items:center;justify-content:space-between;padding:14px 22px;border-top:1px solid #e8f0de}
        .pag-info{font-size:0.78rem;color:#6b8f52}
        .pag-links{display:flex;gap:5px}
        .pag-btn{width:30px;height:30px;border-radius:6px;display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:0.8rem;font-weight:600;border:1.5px solid #e8f0de;color:#4a7c2f;transition:all 0.2s}
        .pag-btn:hover{background:var(--green-bg);border-color:var(--green-main)}
        .pag-btn.active{background:var(--green-main);color:white;border-color:var(--green-main)}
        .pag-btn.disabled{opacity:0.4;pointer-events:none}
        .alert-ok{background:#f0fff4;border:1px solid #b2e5c5;color:#27ae60;border-radius:10px;padding:10px 14px;font-size:0.82rem;margin-bottom:14px;display:flex;align-items:center;gap:7px}
        .empty-state{text-align:center;padding:40px;color:#8ab06a}
        .empty-state i{font-size:2.5rem;margin-bottom:10px;display:block}
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
        <a href="pengaduan.php" class="nav-item"><i class="fas fa-inbox"></i> Kelola Pengaduan</a>
        <a href="mahasiswa.php" class="nav-item active"><i class="fas fa-users"></i> Data Mahasiswa</a>
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
        <div><div class="topbar-title">Data Mahasiswa</div><div class="topbar-date"><?= date('l, d F Y') ?></div></div>
    </div>
    <div class="content">
        <div class="page-header"><h1>Kelola Mahasiswa</h1><p>Total: <?= $total ?> mahasiswa terdaftar</p></div>
        <?php if (isset($_GET['msg'])): ?><div class="alert-ok"><i class="fas fa-check-circle"></i> Perubahan berhasil disimpan.</div><?php endif; ?>
        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="filter-input" placeholder="Cari NIM, nama, atau email..." value="<?= htmlspecialchars($search) ?>">
            <select name="jurusan" class="filter-select">
                <option value="">Semua Jurusan</option>
                <?php $jurusanList->data_seek(0); while ($j=$jurusanList->fetch_assoc()): ?>
                <option value="<?= $j['id'] ?>" <?= $jurusanFilter==$j['id']?'selected':'' ?>><?= htmlspecialchars($j['nama_jurusan']) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn-sm btn-green"><i class="fas fa-search"></i> Cari</button>
            <a href="mahasiswa.php" class="btn-sm btn-outline">Reset</a>
        </form>
        <div class="section-card">
            <div class="section-card-header">
                <h3><i class="fas fa-users" style="color:var(--green-main);margin-right:7px"></i>Daftar Mahasiswa</h3>
            </div>
            <?php if ($list && $list->num_rows > 0): ?>
            <div style="overflow-x:auto">
            <table>
                <thead><tr><th>#</th><th>Mahasiswa</th><th>NIM</th><th>Email</th><th>Jurusan</th><th>Semester</th><th>Pengaduan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php $no=$offset+1; while ($row=$list->fetch_assoc()): ?>
                <tr>
                    <td style="color:#aac890;font-size:0.75rem"><?= $no++ ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="mhs-avatar"><?= strtoupper(substr($row['nama_lengkap'],0,1)) ?></div>
                            <div>
                                <div style="font-weight:700"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                <div style="font-size:0.72rem;color:#8ab06a"><?= date('d/m/Y', strtotime($row['dibuat_pada'])) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><code style="background:#f0f7e8;padding:2px 7px;border-radius:4px;color:var(--green-main);font-size:0.78rem"><?= htmlspecialchars($row['nim']) ?></code></td>
                    <td style="font-size:0.8rem"><?= htmlspecialchars($row['email']) ?></td>
                    <td style="font-size:0.8rem"><?= htmlspecialchars($row['nama_jurusan'] ?? '-') ?></td>
                    <td style="text-align:center"><?= $row['semester'] ?: '-' ?></td>
                    <td style="text-align:center">
                        <span style="background:var(--green-bg);color:var(--green-main);padding:3px 10px;border-radius:12px;font-size:0.72rem;font-weight:700"><?= $row['jml_pengaduan'] ?></span>
                    </td>
                    <td><?= $row['aktif'] ? '<span class="badge-aktif">Aktif</span>' : '<span class="badge-nonaktif">Nonaktif</span>' ?></td>
                    <td>
                        <div style="display:flex;gap:5px">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="toggle_aktif" value="1">
                                <input type="hidden" name="mhs_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="aktif" value="<?= $row['aktif'] ? 0 : 1 ?>">
                                <button type="submit" class="btn-act <?= $row['aktif'] ? 'btn-red' : 'btn-teal' ?>" title="<?= $row['aktif']?'Nonaktifkan':'Aktifkan' ?>">
                                    <i class="fas <?= $row['aktif'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <div class="pagination">
                <span class="pag-info">Hal <?= $page ?>/<?= max(1,$totalPages) ?></span>
                <div class="pag-links">
                    <?php $q=$_GET;unset($q['page']);$qs=http_build_query($q); ?>
                    <a href="?<?= $qs ?>&page=<?= max(1,$page-1) ?>" class="pag-btn <?= $page<=1?'disabled':'' ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
                    <a href="?<?= $qs ?>&page=<?= $i ?>" class="pag-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?<?= $qs ?>&page=<?= min($totalPages,$page+1) ?>" class="pag-btn <?= $page>=$totalPages?'disabled':'' ?>"><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state"><i class="fas fa-users-slash"></i><p>Tidak ada mahasiswa ditemukan.</p></div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>