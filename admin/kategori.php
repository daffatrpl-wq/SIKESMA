<?php
require_once '../session.php';
require_once '../db.php';
requireAdmin();

$msg = '';
// CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $nama = $conn->real_escape_string(trim($_POST['nama_kategori'] ?? ''));
    $desc = $conn->real_escape_string(trim($_POST['deskripsi'] ?? ''));
    $kid = (int)($_POST['kategori_id'] ?? 0);

    if ($action === 'add' && $nama) {
        $conn->query("INSERT INTO kategori_pengaduan (nama_kategori, deskripsi) VALUES ('$nama','$desc')");
        $msg = 'Kategori berhasil ditambahkan.';
    } elseif ($action === 'edit' && $kid && $nama) {
        $conn->query("UPDATE kategori_pengaduan SET nama_kategori='$nama', deskripsi='$desc' WHERE id=$kid");
        $msg = 'Kategori berhasil diperbarui.';
    } elseif ($action === 'delete' && $kid) {
        // Cek apakah ada pengaduan dengan kategori ini
        $used = $conn->query("SELECT COUNT(*) FROM pengaduan WHERE id_kategori=$kid")->fetch_row()[0];
        if ($used > 0) {
            $msg = 'error:Tidak bisa dihapus. Kategori ini digunakan oleh ' . $used . ' pengaduan.';
        } else {
            $conn->query("DELETE FROM kategori_pengaduan WHERE id=$kid");
            $msg = 'Kategori berhasil dihapus.';
        }
    }
}

$kategoriList = $conn->query("
    SELECT k.*, (SELECT COUNT(*) FROM pengaduan WHERE id_kategori=k.id) as jml
    FROM kategori_pengaduan k ORDER BY k.nama_kategori
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKESMA — Kategori Pengaduan</title>
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
        .topbar{background:white;padding:16px 32px;border-bottom:1px solid #e8f0de;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
        .topbar-title{font-size:1.1rem;font-weight:700;color:#1a2e0d}
        .topbar-date{font-size:0.8rem;color:#6b8f52}
        .content{padding:28px 32px}
        .page-header{margin-bottom:22px}
        .page-header h1{font-family:'Fraunces',serif;font-size:1.7rem;color:#1a2e0d}
        .grid{display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start}
        .card{background:white;border-radius:16px;border:1px solid #e8f0de;overflow:hidden}
        .card-header{padding:16px 22px;border-bottom:1px solid #e8f0de;display:flex;align-items:center;gap:8px}
        .card-header h3{font-size:0.95rem;font-weight:700;color:#1a2e0d}
        .card-header .icon{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:0.82rem;background:var(--green-bg);color:var(--green-main)}
        .card-body{padding:22px}
        table{width:100%;border-collapse:collapse}
        thead th{background:#f8fff2;padding:11px 16px;text-align:left;font-size:0.73rem;font-weight:700;color:#5c7a3e;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e8f0de}
        tbody tr{border-bottom:1px solid #f0f7e8}
        tbody tr:hover{background:#fafff5}
        tbody td{padding:12px 16px;font-size:0.85rem;color:#2d4a1a;vertical-align:middle}
        .form-group{margin-bottom:14px}
        .form-label{display:block;font-size:0.78rem;font-weight:600;color:#3d5a28;margin-bottom:6px}
        .form-input{width:100%;padding:10px 14px;border:1.5px solid #d8ecc0;border-radius:9px;font-size:0.88rem;font-family:'Plus Jakarta Sans';outline:none;color:#1a2e0d;background:#f8fff2;transition:all 0.2s}
        .form-input:focus{border-color:var(--green-main);background:white}
        textarea.form-input{resize:vertical;min-height:80px}
        .btn-full{width:100%}
        .btn-submit{height:42px;background:linear-gradient(135deg,var(--green-main),var(--green-light));color:white;border:none;border-radius:9px;font-size:0.88rem;font-weight:700;font-family:'Plus Jakarta Sans';cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:all 0.2s}
        .btn-submit:hover{opacity:0.9;transform:translateY(-1px)}
        .btn-act{width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;cursor:pointer;border:none;text-decoration:none;transition:all 0.2s}
        .btn-edit{background:rgba(230,126,34,0.1);color:#e67e22}
        .btn-edit:hover{background:rgba(230,126,34,0.2)}
        .btn-del{background:rgba(231,76,60,0.1);color:#e74c3c}
        .btn-del:hover{background:rgba(231,76,60,0.2)}
        .alert-ok{background:#f0fff4;border:1px solid #b2e5c5;color:#27ae60;border-radius:10px;padding:10px 14px;font-size:0.82rem;margin-bottom:14px;display:flex;align-items:center;gap:7px}
        .alert-err{background:#fff0f0;border:1px solid #f5c6c6;color:#c0392b;border-radius:10px;padding:10px 14px;font-size:0.82rem;margin-bottom:14px;display:flex;align-items:center;gap:7px}
        .field-err{font-size:0.72rem;color:#c0392b;margin-top:3px;display:none}
        .field-err.show{display:block}
        .char-hint{font-size:0.7rem;color:#aac890;text-align:right;margin-top:3px}
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
        <a href="mahasiswa.php" class="nav-item"><i class="fas fa-users"></i> Data Mahasiswa</a>
        <span class="nav-section-label">Pengaturan</span>
        <a href="kategori.php" class="nav-item active"><i class="fas fa-tags"></i> Kategori</a>
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
        <div><div class="topbar-title">Kategori Pengaduan</div><div class="topbar-date"><?= date('l, d F Y') ?></div></div>
    </div>
    <div class="content">
        <div class="page-header"><h1>Kelola Kategori</h1></div>
        <?php if ($msg && !str_starts_with($msg,'error:')): ?>
        <div class="alert-ok"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
        <?php elseif (str_starts_with($msg,'error:')): ?>
        <div class="alert-err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars(substr($msg,6)) ?></div>
        <?php endif; ?>
        <div class="grid">
            <!-- LIST -->
            <div class="card">
                <div class="card-header"><div class="icon"><i class="fas fa-tags"></i></div><h3>Daftar Kategori</h3></div>
                <table>
                    <thead><tr><th>#</th><th>Nama Kategori</th><th>Deskripsi</th><th>Jml Pengaduan</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php $no=1; while ($k=$kategoriList->fetch_assoc()): ?>
                    <tr>
                        <td style="color:#aac890"><?= $no++ ?></td>
                        <td style="font-weight:600"><?= htmlspecialchars($k['nama_kategori']) ?></td>
                        <td style="font-size:0.8rem;color:#6b8f52;max-width:200px"><?= htmlspecialchars($k['deskripsi'] ?: '-') ?></td>
                        <td style="text-align:center">
                            <span style="background:var(--green-bg);color:var(--green-main);padding:2px 10px;border-radius:12px;font-size:0.72rem;font-weight:700"><?= $k['jml'] ?></span>
                        </td>
                        <td>
                            <div style="display:flex;gap:5px">
                                <button class="btn-act btn-edit" onclick="editKategori(<?= $k['id'] ?>, '<?= addslashes($k['nama_kategori']) ?>', '<?= addslashes($k['deskripsi']) ?>')" title="Edit"><i class="fas fa-pen"></i></button>
                                <?php if ($k['jml'] == 0): ?>
                                <button class="btn-act btn-del" onclick="hapusKategori(<?= $k['id'] ?>, '<?= addslashes($k['nama_kategori']) ?>')" title="Hapus"><i class="fas fa-trash"></i></button>
                                <?php else: ?>
                                <button class="btn-act" style="background:#f3f4f6;color:#aaa;cursor:not-allowed" title="Tidak bisa dihapus"><i class="fas fa-lock"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- FORM -->
            <div class="card">
                <div class="card-header"><div class="icon"><i class="fas fa-plus"></i></div><h3 id="formTitle">Tambah Kategori Baru</h3></div>
                <div class="card-body">
                    <form method="POST" id="kategoriForm" novalidate>
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="kategori_id" id="formId" value="">
                        <div class="form-group">
                            <label class="form-label">Nama Kategori <span style="color:#e74c3c">*</span></label>
                            <input type="text" name="nama_kategori" id="formNama" class="form-input" placeholder="Contoh: Fasilitas Kampus" maxlength="100">
                            <div class="char-hint" id="namaHint">0/100</div>
                            <span class="field-err" id="namaErr">Nama kategori wajib diisi.</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" id="formDesc" class="form-input" placeholder="Deskripsi singkat kategori..." maxlength="500"></textarea>
                            <div class="char-hint" id="descHint">0/500</div>
                        </div>
                        <div style="display:flex;gap:8px">
                            <button type="submit" class="btn-submit btn-full"><i class="fas fa-save" id="formIcon"></i> <span id="formBtnText">Tambah Kategori</span></button>
                            <button type="button" id="cancelBtn" onclick="resetForm()" style="display:none;padding:0 16px;border-radius:9px;background:#f0f7e8;color:var(--green-main);border:none;font-family:'Plus Jakarta Sans';font-weight:600;cursor:pointer">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- HIDDEN FORM DELETE -->
<form method="POST" id="deleteForm" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="kategori_id" id="deleteId">
</form>

<script>
function editKategori(id, nama, desc) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = id;
    document.getElementById('formNama').value = nama;
    document.getElementById('formDesc').value = desc;
    document.getElementById('formTitle').textContent = 'Edit Kategori';
    document.getElementById('formBtnText').textContent = 'Simpan Perubahan';
    document.getElementById('formIcon').className = 'fas fa-check';
    document.getElementById('cancelBtn').style.display = 'block';
    document.getElementById('formNama').focus();
    updateHints();
}
function resetForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('formNama').value = '';
    document.getElementById('formDesc').value = '';
    document.getElementById('formTitle').textContent = 'Tambah Kategori Baru';
    document.getElementById('formBtnText').textContent = 'Tambah Kategori';
    document.getElementById('formIcon').className = 'fas fa-plus';
    document.getElementById('cancelBtn').style.display = 'none';
    updateHints();
}
function hapusKategori(id, nama) {
    if (confirm('Yakin hapus kategori "' + nama + '"?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
function updateHints() {
    const nama = document.getElementById('formNama');
    const desc = document.getElementById('formDesc');
    document.getElementById('namaHint').textContent = nama.value.length + '/100';
    document.getElementById('descHint').textContent = desc.value.length + '/500';
}
document.getElementById('formNama').addEventListener('input', updateHints);
document.getElementById('formDesc').addEventListener('input', updateHints);
document.getElementById('kategoriForm').addEventListener('submit', function(e) {
    const nama = document.getElementById('formNama').value.trim();
    document.getElementById('namaErr').classList.remove('show');
    if (!nama) { document.getElementById('namaErr').classList.add('show'); e.preventDefault(); }
});
</script>
</body>
</html>