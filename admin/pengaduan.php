<?php
require_once '../session.php';
require_once '../db.php';
requireAdmin();

// Filter & Search
$search = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$statusFilter = $conn->real_escape_string($_GET['status'] ?? '');
$prioritasFilter = $conn->real_escape_string($_GET['prioritas'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = '1=1';
if ($search) $where .= " AND (p.judul_pengaduan LIKE '%$search%' OR p.kode_pengaduan LIKE '%$search%' OR m.nama_lengkap LIKE '%$search%')";
if ($statusFilter) $where .= " AND p.status = '$statusFilter'";
if ($prioritasFilter) $where .= " AND p.tingkat_prioritas = '$prioritasFilter'";

$total = $conn->query("SELECT COUNT(*) FROM pengaduan p JOIN mahasiswa m ON p.id_mahasiswa=m.id WHERE $where")->fetch_row()[0];
$totalPages = ceil($total / $perPage);

$pengaduanList = $conn->query("
    SELECT p.*, m.nama_lengkap, m.nim, k.nama_kategori
    FROM pengaduan p
    JOIN mahasiswa m ON p.id_mahasiswa = m.id
    LEFT JOIN kategori_pengaduan k ON p.id_kategori = k.id
    WHERE $where
    ORDER BY p.tanggal_pengaduan DESC
    LIMIT $perPage OFFSET $offset
");

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $pid = (int)$_POST['pengaduan_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $conn->query("UPDATE pengaduan SET status='$status' WHERE id=$pid");
    header('Location: pengaduan.php?' . http_build_query($_GET) . '&msg=updated');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKESMA - Kelola Pengaduan</title>
    <link rel="icon" href="img/image.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --green-dark:#2d5016;--green-main:#4a7c2f;--green-light:#7ab648;--green-pale:#c8e6a0;--green-bg:#f0f7e8;--sidebar-w:260px; }
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
        .page-header{margin-bottom:24px;display:flex;align-items:center;justify-content:space-between}
        .page-header h1{font-family:'Fraunces',serif;font-size:1.7rem;color:#1a2e0d}
        .page-header p{color:#6b8f52;font-size:0.88rem;margin-top:4px}

        /* FILTER BAR */
        .filter-bar{background:white;border-radius:14px;padding:18px 22px;border:1px solid #e8f0de;margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
        .filter-input{flex:1;min-width:200px;padding:9px 14px;border:1.5px solid #d8ecc0;border-radius:9px;font-size:0.85rem;font-family:'Plus Jakarta Sans';outline:none;background:#f8fff2;color:#1a2e0d}
        .filter-input:focus{border-color:var(--green-main);background:white}
        .filter-select{padding:9px 14px;border:1.5px solid #d8ecc0;border-radius:9px;font-size:0.85rem;font-family:'Plus Jakarta Sans';outline:none;background:#f8fff2;color:#1a2e0d;cursor:pointer}
        .filter-select:focus{border-color:var(--green-main);background:white}
        .btn-filter{padding:9px 18px;border-radius:9px;background:var(--green-main);color:white;border:none;font-size:0.85rem;font-weight:600;cursor:pointer;font-family:'Plus Jakarta Sans';display:flex;align-items:center;gap:6px}
        .btn-reset{padding:9px 14px;border-radius:9px;background:#f0f7e8;color:var(--green-main);border:none;font-size:0.85rem;font-weight:600;cursor:pointer;font-family:'Plus Jakarta Sans';text-decoration:none}

        /* TABLE */
        .section-card{background:white;border-radius:16px;border:1px solid #e8f0de;overflow:hidden}
        .section-card-header{padding:18px 24px;border-bottom:1px solid #e8f0de;display:flex;align-items:center;justify-content:space-between}
        .section-card-header h3{font-size:1rem;font-weight:700;color:#1a2e0d}
        table{width:100%;border-collapse:collapse}
        thead th{background:#f8fff2;padding:12px 16px;text-align:left;font-size:0.75rem;font-weight:700;color:#5c7a3e;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e8f0de}
        tbody tr{border-bottom:1px solid #f0f7e8;transition:background 0.15s}
        tbody tr:hover{background:#fafff5}
        tbody td{padding:12px 16px;font-size:0.83rem;color:#2d4a1a;vertical-align:middle}
        .kode{font-family:monospace;font-size:0.75rem;background:#f0f7e8;padding:2px 8px;border-radius:4px;color:#4a7c2f}
        .badge{padding:3px 10px;border-radius:20px;font-size:0.7rem;font-weight:700;display:inline-block}
        .badge-baru{background:#dbeafe;color:#1d4ed8}
        .badge-diproses{background:#fef3c7;color:#92400e}
        .badge-selesai{background:#d1fae5;color:#065f46}
        .badge-ditolak{background:#fee2e2;color:#991b1b}
        .badge-rendah{background:#d1fae5;color:#065f46}
        .badge-sedang{background:#fef3c7;color:#92400e}
        .badge-tinggi{background:#fee2e2;color:#991b1b}
        .badge-kritis{background:#fce7f3;color:#9d174d}
        .badge-anonim{background:#f3f4f6;color:#6b7280}
        .action-btns{display:flex;gap:6px}
        .btn-act{width:30px;height:30px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:0.78rem;cursor:pointer;border:none;text-decoration:none;transition:all 0.2s}
        .btn-view{background:rgba(52,152,219,0.1);color:#3498db}
        .btn-view:hover{background:rgba(52,152,219,0.2)}
        .btn-edit{background:rgba(230,126,34,0.1);color:#e67e22}
        .btn-edit:hover{background:rgba(230,126,34,0.2)}
        .nav-lbl { font-size:.65rem; color:rgba(255,255,255,.35); text-transform:uppercase; letter-spacing:1.5px; padding:0 10px; margin:16px 0 6px; }
        .ni { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; color:rgba(255,255,255,.7); text-decoration:none; font-size:.88rem; font-weight:500; transition:all .2s; margin-bottom:2px; }
        .ni:hover, .ni.active { background:rgba(255,255,255,.1); color:white; }
        .ni.active { background:rgba(122,182,72,.2); color:var(--gp); }
        .ni i { width:18px; text-align:center; }

        /* PAGINATION */
        .pagination{display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-top:1px solid #e8f0de}
        .pag-info{font-size:0.8rem;color:#6b8f52}
        .pag-links{display:flex;gap:6px}
        .pag-btn{width:32px;height:32px;border-radius:7px;display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:0.82rem;font-weight:600;border:1.5px solid #e8f0de;color:#4a7c2f;transition:all 0.2s}
        .pag-btn:hover{background:var(--green-bg);border-color:var(--green-main)}
        .pag-btn.active{background:var(--green-main);color:white;border-color:var(--green-main)}
        .pag-btn.disabled{opacity:0.4;pointer-events:none}

        /* MODAL */
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:200;display:none;align-items:center;justify-content:center}
        .modal-overlay.open{display:flex}
        .modal{background:white;border-radius:16px;width:90%;max-width:480px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,0.2)}
        .modal h3{font-size:1.1rem;font-weight:700;color:#1a2e0d;margin-bottom:16px}
        .modal label{font-size:0.82rem;font-weight:600;color:#3d5a28;margin-bottom:6px;display:block;margin-top:12px}
        .modal select,.modal textarea{width:100%;padding:10px 14px;border:1.5px solid #d8ecc0;border-radius:8px;font-size:0.88rem;font-family:'Plus Jakarta Sans';outline:none}
        .modal select:focus,.modal textarea:focus{border-color:var(--green-main)}
        .modal-actions{display:flex;gap:10px;margin-top:20px;justify-content:flex-end}
        .btn-cancel{background:#f0f7e8;color:#4a7c2f;border:none;padding:10px 20px;border-radius:8px;font-family:'Plus Jakarta Sans';font-weight:600;cursor:pointer}
        .btn-sm{padding:7px 14px;border-radius:7px;font-size:0.8rem;font-weight:600;font-family:'Plus Jakarta Sans';cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
        .btn-green{background:var(--green-main);color:white}

        .alert-msg{background:#f0fff4;border:1px solid #b2e5c5;color:#27ae60;border-radius:10px;padding:10px 16px;margin-bottom:18px;font-size:0.85rem;display:flex;align-items:center;gap:8px}
        .empty-state{text-align:center;padding:48px;color:#8ab06a}
        .empty-state i{font-size:3rem;margin-bottom:12px;display:block}
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
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)) ?></div>
            <div><div class="user-name"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></div><div class="user-role">Administrator</div></div>
        </div>
        <a href="../logout.php" class="btn-logout"><i class="fas fa-right-from-bracket"></i> Keluar</a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div><div class="topbar-title">Kelola Pengaduan</div><div class="topbar-date"><?= date('l, d F Y') ?></div></div>
    </div>
    <div class="content">
        <div class="page-header">
            <div><h1>Semua Pengaduan</h1><p>Total: <?= $total ?> pengaduan ditemukan</p></div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert-msg"><i class="fas fa-check-circle"></i> Status berhasil diperbarui.</div>
        <?php endif; ?>

        <!-- FILTER -->
        <form method="GET" class="filter-bar">
            <input type="text" name="search" class="filter-input" placeholder="Cari kode, judul, atau nama mahasiswa..." value="<?= htmlspecialchars($search) ?>">
            <select name="status" class="filter-select">
                <option value="">Semua Status</option>
                <option value="baru" <?= $statusFilter==='baru'?'selected':'' ?>>Baru</option>
                <option value="diproses" <?= $statusFilter==='diproses'?'selected':'' ?>>Diproses</option>
                <option value="selesai" <?= $statusFilter==='selesai'?'selected':'' ?>>Selesai</option>
                <option value="ditolak" <?= $statusFilter==='ditolak'?'selected':'' ?>>Ditolak</option>
            </select>
            <select name="prioritas" class="filter-select">
                <option value="">Semua Prioritas</option>
                <option value="rendah" <?= $prioritasFilter==='rendah'?'selected':'' ?>>Rendah</option>
                <option value="sedang" <?= $prioritasFilter==='sedang'?'selected':'' ?>>Sedang</option>
                <option value="tinggi" <?= $prioritasFilter==='tinggi'?'selected':'' ?>>Tinggi</option>
                <option value="kritis" <?= $prioritasFilter==='kritis'?'selected':'' ?>>Kritis</option>
            </select>
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Cari</button>
            <a href="pengaduan.php" class="btn-reset">Reset</a>
        </form>

        <div class="section-card">
            <div class="section-card-header">
                <h3><i class="fas fa-inbox" style="color:var(--green-main);margin-right:8px"></i>Daftar Pengaduan</h3>
            </div>
            <?php if ($pengaduanList && $pengaduanList->num_rows > 0): ?>
            <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr><th>#</th><th>Kode</th><th>Pelapor</th><th>Judul</th><th>Kategori</th><th>Prioritas</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php $no = $offset + 1; while ($row = $pengaduanList->fetch_assoc()): ?>
                <tr>
                    <td style="color:#aac890;font-size:0.78rem"><?= $no++ ?></td>
                    <td><span class="kode"><?= htmlspecialchars($row['kode_pengaduan']) ?></span></td>
                    <td>
                        <?php if ($row['anonim']): ?>
                            <span class="badge badge-anonim"><i class="fas fa-user-secret"></i> Anonim</span>
                        <?php else: ?>
                            <?= htmlspecialchars($row['nama_lengkap']) ?><br><small style="color:#8ab06a"><?= htmlspecialchars($row['nim']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($row['judul_pengaduan']) ?></td>
                    <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                    <td><span class="badge badge-<?= $row['tingkat_prioritas'] ?>"><?= ucfirst($row['tingkat_prioritas']) ?></span></td>
                    <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                    <td style="font-size:0.75rem;color:#8ab06a"><?= date('d/m/Y', strtotime($row['tanggal_pengaduan'])) ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="detail_pengaduan.php?id=<?= $row['id'] ?>" class="btn-act btn-view" title="Detail"><i class="fas fa-eye"></i></a>
                            <button class="btn-act btn-edit" title="Update Status" onclick="openModal(<?= $row['id'] ?>, '<?= $row['status'] ?>')"><i class="fas fa-pen"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <!-- PAGINATION -->
            <div class="pagination">
                <span class="pag-info">Halaman <?= $page ?> dari <?= max(1,$totalPages) ?></span>
                <div class="pag-links">
                    <?php
                    $q = $_GET; unset($q['page']);
                    $qStr = http_build_query($q);
                    ?>
                    <a href="?<?= $qStr ?>&page=<?= max(1,$page-1) ?>" class="pag-btn <?= $page<=1?'disabled':'' ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                    <a href="?<?= $qStr ?>&page=<?= $i ?>" class="pag-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?<?= $qStr ?>&page=<?= min($totalPages,$page+1) ?>" class="pag-btn <?= $page>=$totalPages?'disabled':'' ?>"><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state"><i class="fas fa-inbox"></i><p>Tidak ada pengaduan yang ditemukan.</p></div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <h3><i class="fas fa-pen" style="color:var(--green-main);margin-right:8px"></i>Update Status Pengaduan</h3>
        <form method="POST">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="pengaduan_id" id="modalPengaduanId">
            <label>Status Baru</label>
            <select name="status" id="modalStatus">
                <option value="baru">Baru</option>
                <option value="diproses">Diproses</option>
                <option value="selesai">Selesai</option>
                <option value="ditolak">Ditolak</option>
            </select>
            <label>Catatan</label>
            <textarea name="catatan" rows="3" placeholder="Tambahkan catatan penanganan..."></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-sm btn-green">Simpan</button>
            </div>
        </form>
    </div>
</div>
<script>
function openModal(id, status) {
    document.getElementById('modalPengaduanId').value = id;
    document.getElementById('modalStatus').value = status;
    document.getElementById('modalOverlay').classList.add('open');
}
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
document.getElementById('modalOverlay').addEventListener('click', function(e) { if(e.target===this) closeModal(); });
</script>
</body>
</html>