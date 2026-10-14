<?php
require_once '../session.php';
require_once '../db.php';
requireAdmin();

$msg = '';
$editData = null;

// ═══════════════════════════════════════════
//  PROSES FORM
// ═══════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']        ?? '';
    $jid       = (int)($_POST['jurusan_id'] ?? 0);
    $namaJ     = trim($_POST['nama_jurusan']  ?? '');
    $namaF     = trim($_POST['nama_fakultas'] ?? '');

    // Validasi
    function validateJurusan($namaJ, $namaF) {
        if (strlen($namaJ) < 3 || strlen($namaJ) > 100) return 'Nama jurusan harus 3–100 karakter.';
        if (strlen($namaF) < 3 || strlen($namaF) > 100) return 'Nama fakultas harus 3–100 karakter.';
        return '';
    }

    if ($action === 'add') {
        $errV = validateJurusan($namaJ, $namaF);
        if ($errV) {
            $msg = 'error:' . $errV;
        } else {
            // Cek duplikat
            $ck = $conn->prepare("SELECT id FROM jurusan WHERE nama_jurusan=? AND nama_fakultas=? LIMIT 1");
            $ck->bind_param('ss', $namaJ, $namaF); $ck->execute();
            if ($ck->get_result()->num_rows > 0) {
                $msg = 'error:Jurusan ini sudah terdaftar di fakultas tersebut.';
            } else {
                $st = $conn->prepare("INSERT INTO jurusan (nama_jurusan, nama_fakultas) VALUES (?, ?)");
                $st->bind_param('ss', $namaJ, $namaF); $st->execute(); $st->close();
                $msg = 'ok:Jurusan <strong>' . htmlspecialchars($namaJ) . '</strong> berhasil ditambahkan.';
            }
            $ck->close();
        }
    }

    elseif ($action === 'edit' && $jid) {
        $errV = validateJurusan($namaJ, $namaF);
        if ($errV) {
            $msg = 'error:' . $errV;
        } else {
            // Cek duplikat kecuali diri sendiri
            $ck = $conn->prepare("SELECT id FROM jurusan WHERE nama_jurusan=? AND nama_fakultas=? AND id!=? LIMIT 1");
            $ck->bind_param('ssi', $namaJ, $namaF, $jid); $ck->execute();
            if ($ck->get_result()->num_rows > 0) {
                $msg = 'error:Jurusan ini sudah terdaftar di fakultas tersebut.';
            } else {
                $st = $conn->prepare("UPDATE jurusan SET nama_jurusan=?, nama_fakultas=? WHERE id=?");
                $st->bind_param('ssi', $namaJ, $namaF, $jid); $st->execute(); $st->close();
                $msg = 'ok:Jurusan berhasil diperbarui.';
            }
            $ck->close();
        }
    }

    elseif ($action === 'delete' && $jid) {
        // Cek apakah ada mahasiswa atau pelaku yang memakai jurusan ini
        $cekMhs    = $conn->query("SELECT COUNT(*) FROM mahasiswa WHERE id_jurusan=$jid")->fetch_row()[0];
        $cekPelaku = $conn->query("SELECT COUNT(*) FROM pelaku WHERE id_jurusan=$jid")->fetch_row()[0];
        $total = $cekMhs + $cekPelaku;
        if ($total > 0) {
            $msg = 'error:Tidak dapat dihapus. Jurusan ini masih digunakan oleh ' . $cekMhs . ' mahasiswa dan ' . $cekPelaku . ' data pelaku.';
        } else {
            $conn->query("DELETE FROM jurusan WHERE id=$jid");
            $msg = 'ok:Jurusan berhasil dihapus.';
        }
    }
}

// Ambil data jurusan dengan statistik
$jurusanList = $conn->query("
    SELECT j.*,
        (SELECT COUNT(*) FROM mahasiswa WHERE id_jurusan = j.id) AS jml_mahasiswa,
        (SELECT COUNT(*) FROM pelaku WHERE id_jurusan = j.id) AS jml_pelaku
    FROM jurusan j
    ORDER BY j.nama_fakultas ASC, j.nama_jurusan ASC
");

// Statistik global
$totalJurusan  = $conn->query("SELECT COUNT(*) FROM jurusan")->fetch_row()[0];
$totalFakultas = $conn->query("SELECT COUNT(DISTINCT nama_fakultas) FROM jurusan")->fetch_row()[0];
$totalMhsJur   = $conn->query("SELECT COUNT(*) FROM mahasiswa WHERE id_jurusan IS NOT NULL")->fetch_row()[0];

// Daftar unik fakultas untuk autocomplete
$fakultasList  = $conn->query("SELECT DISTINCT nama_fakultas FROM jurusan ORDER BY nama_fakultas");

// Data untuk edit (jika ada ?edit=id)
$editId = (int)($_GET['edit'] ?? 0);
if ($editId) {
    $stEdit = $conn->prepare("SELECT * FROM jurusan WHERE id=?");
    $stEdit->bind_param('i', $editId); $stEdit->execute();
    $editData = $stEdit->get_result()->fetch_assoc();
    $stEdit->close();
}

// Kelompokkan per fakultas untuk tampilan tabel
$byFakultas = [];
$jurusanList->data_seek(0);
while ($row = $jurusanList->fetch_assoc()) {
    $byFakultas[$row['nama_fakultas']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIKESMA — Kelola Jurusan</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --gd:#2d5016; --gm:#4a7c2f; --gl:#7ab648; --gp:#c8e6a0; --gb:#f0f7e8;
    --sw:260px;
}
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Plus Jakarta Sans',sans-serif; background:#f5f8f2; display:flex; min-height:100vh; }

/* ── Sidebar ── */
.sb { width:var(--sw); background:linear-gradient(180deg,var(--gd) 0%,#1a3a08 100%); min-height:100vh; position:fixed; left:0; top:0; display:flex; flex-direction:column; z-index:100; }
.sb-logo { padding:28px 24px; border-bottom:1px solid rgba(255,255,255,.08); display:flex; align-items:center; gap:12px; }
.sb-logo-ic { width:42px; height:42px; background:rgba(255,255,255,.12); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:1.2rem; }
.sb-logo-tx { font-family:'Fraunces',serif; color:white; font-size:1.3rem; font-weight:700; }
.sb-logo-tx small { display:block; font-size:.58rem; color:rgba(255,255,255,.5); font-family:'Plus Jakarta Sans'; font-weight:400; }
.sb-nav { flex:1; padding:20px 14px; }
.nav-lbl { font-size:.65rem; color:rgba(255,255,255,.35); text-transform:uppercase; letter-spacing:1.5px; padding:0 10px; margin:16px 0 6px; }
.ni { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; color:rgba(255,255,255,.7); text-decoration:none; font-size:.88rem; font-weight:500; transition:all .2s; margin-bottom:2px; }
.ni:hover, .ni.active { background:rgba(255,255,255,.1); color:white; }
.ni.active { background:rgba(122,182,72,.2); color:var(--gp); }
.ni i { width:18px; text-align:center; }
.sb-foot { padding:16px 14px; border-top:1px solid rgba(255,255,255,.08); }
.u-inf { display:flex; align-items:center; gap:10px; padding:10px; border-radius:10px; background:rgba(255,255,255,.06); margin-bottom:8px; }
.u-av { width:36px; height:36px; background:var(--gl); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size:.9rem; flex-shrink:0; }
.u-nm { font-size:.82rem; color:white; font-weight:600; }
.u-rl { font-size:.68rem; color:rgba(255,255,255,.45); }
.btn-out { display:flex; align-items:center; gap:8px; width:100%; padding:8px 14px; border-radius:8px; background:rgba(231,76,60,.15); color:#ff8a80; text-decoration:none; font-size:.82rem; font-weight:600; border:none; cursor:pointer; font-family:'Plus Jakarta Sans'; transition:all .2s; }
.btn-out:hover { background:rgba(231,76,60,.3); }

/* ── Main ── */
.main { margin-left:var(--sw); flex:1; }
.topbar { background:white; padding:16px 32px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e8f0de; position:sticky; top:0; z-index:50; }
.tb-title { font-size:1.1rem; font-weight:700; color:#1a2e0d; }
.tb-date { font-size:.8rem; color:#6b8f52; }
.content { padding:28px 32px; }
.page-header { margin-bottom:24px; }
.page-header h1 { font-family:'Fraunces',serif; font-size:1.7rem; color:#1a2e0d; }
.page-header p { color:#6b8f52; font-size:.88rem; margin-top:4px; }

/* ── Stats ── */
.stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
.stat-card { background:white; border-radius:14px; border:1px solid #e8f0de; padding:18px 20px; display:flex; align-items:center; gap:14px; transition:all .2s; }
.stat-card:hover { box-shadow:0 4px 20px rgba(74,124,47,.1); transform:translateY(-2px); }
.stat-ic { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
.ic-green { background:rgba(74,124,47,.1); color:var(--gm); }
.ic-blue  { background:rgba(52,152,219,.1); color:#3498db; }
.ic-purple{ background:rgba(155,89,182,.1); color:#9b59b6; }
.stat-num { font-size:1.7rem; font-weight:800; color:#1a2e0d; line-height:1; }
.stat-lbl { font-size:.78rem; color:#6b8f52; margin-top:3px; font-weight:500; }

/* ── Layout ── */
.layout { display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start; }

/* ── Form Card ── */
.form-card { background:white; border-radius:16px; border:1px solid #e8f0de; overflow:hidden; position:sticky; top:84px; }
.card-hdr { padding:17px 22px; border-bottom:1px solid #e8f0de; display:flex; align-items:center; gap:10px; }
.card-hdr-icon { width:32px; height:32px; border-radius:8px; background:var(--gb); color:var(--gm); display:flex; align-items:center; justify-content:center; font-size:.88rem; }
.card-hdr-title { font-size:.97rem; font-weight:700; color:#1a2e0d; }
.card-body { padding:22px; }
.fg { margin-bottom:14px; }
.fl { display:block; font-size:.78rem; font-weight:700; color:#3d5a28; margin-bottom:6px; }
.fl span { color:#e74c3c; margin-left:2px; }
.fi {
    width:100%; height:46px; padding:0 14px;
    border:1.5px solid #d8ecc0; border-radius:10px;
    font-size:.87rem; font-family:'Plus Jakarta Sans',sans-serif;
    color:#1a2e0d; background:#f8fff2; outline:none; transition:all .2s;
}
.fi:focus { border-color:var(--gm); background:white; box-shadow:0 0 0 3px rgba(74,124,47,.08); }
.fi::placeholder { color:#aac890; }
.cc-hint { font-size:.68rem; text-align:right; color:#b0cca0; margin-top:3px; }
.cc-hint.warn { color:#e67e22; } .cc-hint.over { color:#e74c3c; }
.fe { font-size:.7rem; color:#c0392b; margin-top:3px; display:none; }
.fe.show { display:block; }

/* Autocomplete dropdown */
.autocomplete-wrap { position:relative; }
.autocomplete-list {
    position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:200;
    background:white; border:1.5px solid #d8ecc0; border-radius:10px;
    box-shadow:0 8px 24px rgba(74,124,47,.15);
    max-height:180px; overflow-y:auto; display:none;
}
.autocomplete-list.show { display:block; }
.ac-item {
    padding:9px 14px; font-size:.85rem; color:#2d4a1a; cursor:pointer;
    display:flex; align-items:center; gap:8px;
    border-bottom:1px solid #f0f7e8; transition:background .15s;
}
.ac-item:last-child { border-bottom:none; }
.ac-item:hover { background:var(--gb); }
.ac-item i { color:var(--gm); font-size:.75rem; }

.edit-banner {
    background:rgba(230,126,34,.07);
    border:1px solid rgba(230,126,34,.2);
    border-radius:9px; padding:10px 14px;
    display:flex; align-items:center; gap:8px;
    font-size:.78rem; color:#92400e; margin-bottom:14px;
}
.edit-banner i { flex-shrink:0; }

.form-actions { display:flex; gap:8px; margin-top:18px; }
.btn-save {
    flex:1; height:44px;
    background:linear-gradient(135deg,var(--gm),var(--gl));
    color:white; border:none; border-radius:10px;
    font-size:.9rem; font-weight:700; font-family:'Plus Jakarta Sans',sans-serif;
    cursor:pointer; transition:all .3s;
    display:flex; align-items:center; justify-content:center; gap:7px;
    box-shadow:0 4px 16px rgba(74,124,47,.28);
}
.btn-save:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(74,124,47,.38); }
.btn-cancel {
    height:44px; padding:0 18px;
    background:#f0f7e8; color:var(--gm);
    border:1.5px solid #d8ecc0; border-radius:10px;
    font-size:.88rem; font-weight:600; font-family:'Plus Jakarta Sans',sans-serif;
    cursor:pointer; transition:all .2s; text-decoration:none;
    display:inline-flex; align-items:center; gap:6px;
}
.btn-cancel:hover { background:var(--gp); }

/* ── Table Card ── */
.table-card { background:white; border-radius:16px; border:1px solid #e8f0de; overflow:hidden; }
.table-card-hdr { padding:16px 22px; border-bottom:1px solid #e8f0de; display:flex; align-items:center; justify-content:space-between; }
.table-card-hdr h3 { font-size:.97rem; font-weight:700; color:#1a2e0d; }

/* Search bar */
.search-bar { padding:14px 22px; border-bottom:1px solid #e8f0de; display:flex; gap:10px; }
.search-inp {
    flex:1; height:38px; padding:0 14px 0 36px;
    border:1.5px solid #d8ecc0; border-radius:9px;
    font-size:.84rem; font-family:'Plus Jakarta Sans',sans-serif;
    color:#1a2e0d; background:#f8fff2; outline:none; transition:all .2s;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%238ab06a'%3E%3Cpath d='M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z' stroke='%238ab06a' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:10px center; background-size:16px;
}
.search-inp:focus { border-color:var(--gm); background-color:white; box-shadow:0 0 0 3px rgba(74,124,47,.08); }
.search-inp::placeholder { color:#aac890; }
.filter-sel {
    height:38px; padding:0 12px;
    border:1.5px solid #d8ecc0; border-radius:9px;
    font-size:.84rem; font-family:'Plus Jakarta Sans',sans-serif;
    color:#1a2e0d; background:#f8fff2; outline:none; cursor:pointer;
}
.filter-sel:focus { border-color:var(--gm); }

/* Fakultas group */
.fakultas-group { border-bottom:2px solid #e8f0de; }
.fakultas-group:last-child { border-bottom:none; }
.fakultas-header {
    padding:11px 22px;
    background:linear-gradient(90deg,#f8fff2,#f0f7e8);
    display:flex; align-items:center; gap:10px;
    cursor:pointer; user-select:none;
    transition:background .15s;
}
.fakultas-header:hover { background:linear-gradient(90deg,#f0f7e8,#e8f5d5); }
.fak-icon { width:28px; height:28px; border-radius:7px; background:var(--gm); color:white; display:flex; align-items:center; justify-content:center; font-size:.72rem; flex-shrink:0; }
.fak-name { font-size:.88rem; font-weight:700; color:#1a2e0d; flex:1; }
.fak-count { font-size:.72rem; background:var(--gm); color:white; padding:2px 9px; border-radius:10px; font-weight:700; }
.fak-toggle { color:#8ab06a; font-size:.75rem; transition:transform .2s; }
.fak-toggle.open { transform:rotate(180deg); }

.jurusan-rows { display:block; }
.jurusan-rows.collapsed { display:none; }

table { width:100%; border-collapse:collapse; }
thead th {
    background:#f8fff2; padding:10px 16px;
    text-align:left; font-size:.72rem; font-weight:700;
    color:#5c7a3e; text-transform:uppercase; letter-spacing:.5px;
    border-bottom:1px solid #e8f0de;
}
tbody tr { border-bottom:1px solid #f0f7e8; transition:background .15s; }
tbody tr:hover { background:#fafff5; }
tbody td { padding:11px 16px; font-size:.84rem; color:#2d4a1a; vertical-align:middle; }
.row-num { color:#b0cca0; font-size:.75rem; width:30px; }
.jurusan-name { font-weight:700; color:#1a2e0d; }
.badge-mhs {
    display:inline-flex; align-items:center; gap:4px;
    background:rgba(74,124,47,.08); color:var(--gm);
    padding:3px 9px; border-radius:10px; font-size:.7rem; font-weight:700;
}
.badge-zero { background:#f3f4f6; color:#9ca3af; }

.btn-act { width:30px; height:30px; border-radius:7px; display:inline-flex; align-items:center; justify-content:center; font-size:.78rem; cursor:pointer; border:none; text-decoration:none; transition:all .2s; }
.btn-edit-act { background:rgba(230,126,34,.1); color:#e67e22; }
.btn-edit-act:hover { background:rgba(230,126,34,.2); }
.btn-del-act { background:rgba(231,76,60,.1); color:#e74c3c; }
.btn-del-act:hover { background:rgba(231,76,60,.2); }
.btn-del-act.disabled { background:#f3f4f6; color:#ccc; cursor:not-allowed; pointer-events:none; }

/* Alerts */
.alert { padding:11px 15px; border-radius:10px; font-size:.83rem; margin-bottom:18px; display:flex; align-items:flex-start; gap:8px; }
.a-ok  { background:#f0fff4; border:1px solid #b2e5c5; color:#27ae60; }
.a-err { background:#fff0f0; border:1px solid #f5c6c6; color:#c0392b; }

/* Empty state */
.empty-state { text-align:center; padding:48px; color:#8ab06a; }
.empty-state i { font-size:2.8rem; margin-bottom:12px; display:block; }
.empty-state p { font-size:.85rem; }

/* Confirm Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:300; display:none; align-items:center; justify-content:center; }
.modal-overlay.open { display:flex; }
.modal { background:white; border-radius:16px; width:90%; max-width:420px; padding:28px; box-shadow:0 20px 60px rgba(0,0,0,.2); }
.modal-icon { width:52px; height:52px; background:#fff0f0; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.3rem; color:#e74c3c; margin:0 auto 14px; }
.modal h3 { font-size:1.05rem; font-weight:700; color:#1a2e0d; text-align:center; margin-bottom:8px; }
.modal p { font-size:.83rem; color:#6b8f52; text-align:center; line-height:1.6; margin-bottom:18px; }
.modal-actions { display:flex; gap:10px; }
.btn-cancel-modal { flex:1; height:40px; background:#f0f7e8; color:var(--gm); border:1.5px solid #d8ecc0; border-radius:9px; font-size:.88rem; font-weight:600; font-family:'Plus Jakarta Sans'; cursor:pointer; transition:all .2s; }
.btn-cancel-modal:hover { background:var(--gp); }
.btn-confirm-del { flex:1; height:40px; background:#e74c3c; color:white; border:none; border-radius:9px; font-size:.88rem; font-weight:700; font-family:'Plus Jakarta Sans'; cursor:pointer; transition:all .2s; display:flex; align-items:center; justify-content:center; gap:6px; }
.btn-confirm-del:hover { background:#c0392b; }

/* Highlight edit row */
tr.editing-row td { background:#fff8e8 !important; }
</style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
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
        <a href="jurusan.php"  class="ni active"><i class="fas fa-building-columns"></i> Jurusan</a>
        <span class="nav-lbl">Akun Saya</span>
        <a href="profil.php" class="ni"><i class="fas fa-user-shield"></i> Profil Admin</a>
        <a href="ubah_password.php" class="ni"><i class="fas fa-key"></i> Ubah Password</a>
    </nav>
    <div class="sb-foot">
        <div class="u-inf">
            <div class="u-av"><?= strtoupper(substr($_SESSION['nama_lengkap'],0,1)) ?></div>
            <div>
                <div class="u-nm"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></div>
                <div class="u-rl">Administrator</div>
            </div>
        </div>
        <a href="../logout.php" class="btn-out"><i class="fas fa-right-from-bracket"></i> Keluar</a>
    </div>
</aside>

<!-- ══ MAIN ══ -->
<main class="main">
    <div class="topbar">
        <div>
            <div class="tb-title">Kelola Jurusan &amp; Fakultas</div>
            <div class="tb-date"><?= date('l, d F Y') ?></div>
        </div>
    </div>

    <div class="content">
        <div class="page-header">
            <h1>Jurusan &amp; Fakultas</h1>
            <p>Kelola data program studi dan fakultas yang tersedia di sistem SIKESMA.</p>
        </div>

        <!-- ALERTS -->
        <?php if ($msg && str_starts_with($msg,'ok:')): ?>
        <div class="alert a-ok"><i class="fas fa-circle-check" style="flex-shrink:0;margin-top:1px"></i><span><?= substr($msg,3) ?></span></div>
        <?php elseif ($msg && str_starts_with($msg,'error:')): ?>
        <div class="alert a-err"><i class="fas fa-circle-exclamation" style="flex-shrink:0;margin-top:1px"></i><span><?= htmlspecialchars(substr($msg,6)) ?></span></div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-ic ic-green"><i class="fas fa-building-columns"></i></div>
                <div><div class="stat-num"><?= $totalJurusan ?></div><div class="stat-lbl">Total Jurusan / Prodi</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-ic ic-blue"><i class="fas fa-university"></i></div>
                <div><div class="stat-num"><?= $totalFakultas ?></div><div class="stat-lbl">Total Fakultas</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-ic ic-purple"><i class="fas fa-user-graduate"></i></div>
                <div><div class="stat-num"><?= $totalMhsJur ?></div><div class="stat-lbl">Mahasiswa Terdaftar</div></div>
            </div>
        </div>

        <div class="layout">
            <!-- ══ TABEL KIRI ══ -->
            <div class="table-card">
                <div class="table-card-hdr">
                    <h3><i class="fas fa-list" style="color:var(--gm);margin-right:7px"></i>Daftar Jurusan</h3>
                    <span style="font-size:.78rem;color:#8ab06a"><?= $totalJurusan ?> jurusan di <?= $totalFakultas ?> fakultas</span>
                </div>

                <!-- Search -->
                <div class="search-bar">
                    <input type="text" id="searchInput" class="search-inp" placeholder="Cari nama jurusan atau fakultas..." oninput="filterTable()">
                    <select id="filterFakultas" class="filter-sel" onchange="filterTable()">
                        <option value="">Semua Fakultas</option>
                        <?php foreach (array_keys($byFakultas) as $fak): ?>
                        <option value="<?= htmlspecialchars($fak) ?>"><?= htmlspecialchars($fak) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Table grouped by fakultas -->
                <div id="tableWrapper">
                <?php if (empty($byFakultas)): ?>
                <div class="empty-state">
                    <i class="fas fa-building-columns"></i>
                    <p>Belum ada data jurusan. Tambahkan menggunakan form di samping.</p>
                </div>
                <?php else: ?>
                <?php $globalNo = 1; foreach ($byFakultas as $fakName => $rows): ?>
                <div class="fakultas-group" data-fak="<?= htmlspecialchars($fakName) ?>">
                    <div class="fakultas-header" onclick="toggleGroup(this)">
                        <div class="fak-icon"><i class="fas fa-university"></i></div>
                        <span class="fak-name"><?= htmlspecialchars($fakName) ?></span>
                        <span class="fak-count"><?= count($rows) ?> Prodi</span>
                        <i class="fas fa-chevron-down fak-toggle open"></i>
                    </div>
                    <div class="jurusan-rows" id="rows-<?= md5($fakName) ?>">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:36px">#</th>
                                    <th>Nama Jurusan / Prodi</th>
                                    <th style="text-align:center">Mahasiswa</th>
                                    <th style="width:80px;text-align:center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($rows as $row): ?>
                            <tr id="row-<?= $row['id'] ?>" class="jurusan-row <?= $editData && $editData['id']==$row['id'] ? 'editing-row' : '' ?>"
                                data-name="<?= strtolower($row['nama_jurusan']) ?>"
                                data-fak="<?= strtolower($fakName) ?>">
                                <td class="row-num"><?= $globalNo++ ?></td>
                                <td>
                                    <div class="jurusan-name"><?= htmlspecialchars($row['nama_jurusan']) ?></div>
                                    <div style="font-size:.7rem;color:#8ab06a;margin-top:2px">
                                        ID: <?= $row['id'] ?>
                                    </div>
                                </td>
                                <td style="text-align:center">
                                    <span class="badge-mhs <?= $row['jml_mahasiswa']==0?'badge-zero':'' ?>">
                                        <i class="fas fa-user-graduate" style="font-size:.6rem"></i>
                                        <?= $row['jml_mahasiswa'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex;gap:5px;justify-content:center">
                                        <a href="?edit=<?= $row['id'] ?>" class="btn-act btn-edit-act" title="Edit jurusan ini">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <?php if ($row['jml_mahasiswa'] == 0 && $row['jml_pelaku'] == 0): ?>
                                        <button class="btn-act btn-del-act"
                                                title="Hapus jurusan"
                                                onclick="confirmDelete(<?= $row['id'] ?>, '<?= addslashes($row['nama_jurusan']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn-act btn-del-act disabled"
                                                title="Tidak bisa dihapus — masih digunakan">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div>

            <!-- ══ FORM KANAN ══ -->
            <div class="form-card">
                <div class="card-hdr">
                    <div class="card-hdr-icon">
                        <i class="fas <?= $editData ? 'fa-pen' : 'fa-plus' ?>"></i>
                    </div>
                    <div class="card-hdr-title" id="formTitle">
                        <?= $editData ? 'Edit Jurusan' : 'Tambah Jurusan Baru' ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($editData): ?>
                    <div class="edit-banner">
                        <i class="fas fa-pen-to-square"></i>
                        Anda sedang mengedit: <strong><?= htmlspecialchars($editData['nama_jurusan']) ?></strong>
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="jurusanForm" novalidate>
                        <input type="hidden" name="action"     id="formAction" value="<?= $editData ? 'edit' : 'add' ?>">
                        <input type="hidden" name="jurusan_id" id="formId"     value="<?= $editData ? $editData['id'] : '' ?>">

                        <div class="fg">
                            <label class="fl">Nama Jurusan / Program Studi <span>*</span></label>
                            <input type="text"
                                   name="nama_jurusan"
                                   id="fNamaJ"
                                   class="fi"
                                   placeholder="Contoh: Teknik Informatika"
                                   maxlength="100"
                                   value="<?= htmlspecialchars($editData ? $editData['nama_jurusan'] : ($_POST['nama_jurusan'] ?? '')) ?>"
                                   autocomplete="off">
                            <div class="cc-hint" id="namaJCc">0/100</div>
                            <span class="fe" id="namaJErr">Nama jurusan harus 3–100 karakter.</span>
                        </div>

                        <div class="fg">
                            <label class="fl">Nama Fakultas <span>*</span></label>
                            <div class="autocomplete-wrap">
                                <input type="text"
                                       name="nama_fakultas"
                                       id="fNamaF"
                                       class="fi"
                                       placeholder="Contoh: Fakultas Teknik"
                                       maxlength="100"
                                       value="<?= htmlspecialchars($editData ? $editData['nama_fakultas'] : ($_POST['nama_fakultas'] ?? '')) ?>"
                                       autocomplete="off">
                                <div class="autocomplete-list" id="acList"></div>
                            </div>
                            <div class="cc-hint" id="namaFCc">0/100</div>
                            <span class="fe" id="namaFErr">Nama fakultas harus 3–100 karakter.</span>
                        </div>

                        <!-- Preview -->
                        <div id="previewBox" style="display:none;background:var(--gb);border:1px solid #d8ecc0;border-radius:10px;padding:12px 14px;margin-bottom:14px">
                            <div style="font-size:.7rem;font-weight:700;color:#8ab06a;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Preview</div>
                            <div style="font-size:.9rem;font-weight:700;color:#1a2e0d" id="previewJ">—</div>
                            <div style="font-size:.75rem;color:#6b8f52;margin-top:2px" id="previewF">—</div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save" id="formSubmitBtn">
                                <i class="fas <?= $editData ? 'fa-floppy-disk' : 'fa-plus' ?>" id="formSubmitIcon"></i>
                                <span id="formSubmitText"><?= $editData ? 'Simpan Perubahan' : 'Tambah Jurusan' ?></span>
                            </button>
                            <?php if ($editData): ?>
                            <a href="jurusan.php" class="btn-cancel">
                                <i class="fas fa-xmark"></i> Batal
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if (!$editData): ?>
                    <!-- Tips -->
                    <div style="margin-top:18px;padding-top:18px;border-top:1px solid #e8f0de">
                        <div style="font-size:.72rem;font-weight:700;color:#8ab06a;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">
                            <i class="fas fa-lightbulb"></i> Tips
                        </div>
                        <ul style="list-style:none;display:flex;flex-direction:column;gap:7px">
                            <li style="font-size:.75rem;color:#5c7a3e;display:flex;align-items:flex-start;gap:7px">
                                <i class="fas fa-check" style="color:var(--gm);margin-top:2px;font-size:.65rem;flex-shrink:0"></i>
                                Gunakan nama lengkap, misal <em>"Teknik Informatika"</em> bukan <em>"TI"</em>
                            </li>
                            <li style="font-size:.75rem;color:#5c7a3e;display:flex;align-items:flex-start;gap:7px">
                                <i class="fas fa-check" style="color:var(--gm);margin-top:2px;font-size:.65rem;flex-shrink:0"></i>
                                Nama fakultas yang sama akan dikelompokkan otomatis
                            </li>
                            <li style="font-size:.75rem;color:#5c7a3e;display:flex;align-items:flex-start;gap:7px">
                                <i class="fas fa-check" style="color:var(--gm);margin-top:2px;font-size:.65rem;flex-shrink:0"></i>
                                Jurusan yang sudah dipakai mahasiswa tidak bisa dihapus
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ══ CONFIRM DELETE MODAL ══ -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <div class="modal-icon"><i class="fas fa-trash"></i></div>
        <h3>Hapus Jurusan?</h3>
        <p id="deleteModalText">Apakah Anda yakin ingin menghapus jurusan ini? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="modal-actions">
            <button class="btn-cancel-modal" onclick="closeDeleteModal()">Batal</button>
            <form method="POST" style="flex:1" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="jurusan_id" id="deleteId">
                <button type="submit" class="btn-confirm-del" style="width:100%">
                    <i class="fas fa-trash"></i> Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Data fakultas untuk autocomplete (JSON) -->
<script>
const FAKULTAS_LIST = <?= json_encode(array_column($fakultasList ? $fakultasList->fetch_all(MYSQLI_ASSOC) : [], 'nama_fakultas')) ?>;

/* ── Char counters ── */
function setupCc(inputId, ccId, max) {
    const inp = document.getElementById(inputId);
    const cc  = document.getElementById(ccId);
    if (!inp || !cc) return;
    const update = () => {
        const l = inp.value.length;
        cc.textContent = l + '/' + max;
        cc.className = 'cc-hint' + (l >= max ? ' over' : l > max * .8 ? ' warn' : '');
    };
    inp.addEventListener('input', update);
    update();
}
setupCc('fNamaJ', 'namaJCc', 100);
setupCc('fNamaF', 'namaFCc', 100);

/* ── Preview ── */
function updatePreview() {
    const j = document.getElementById('fNamaJ').value.trim();
    const f = document.getElementById('fNamaF').value.trim();
    const box = document.getElementById('previewBox');
    if (j || f) {
        document.getElementById('previewJ').textContent = j || '—';
        document.getElementById('previewF').textContent = f ? ('📍 ' + f) : '—';
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}
document.getElementById('fNamaJ').addEventListener('input', updatePreview);
document.getElementById('fNamaF').addEventListener('input', updatePreview);
updatePreview();

/* ── Autocomplete Fakultas ── */
const fNamaF  = document.getElementById('fNamaF');
const acList  = document.getElementById('acList');

fNamaF.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    acList.innerHTML = '';
    if (!q) { acList.classList.remove('show'); return; }
    const matches = FAKULTAS_LIST.filter(f => f.toLowerCase().includes(q));
    if (matches.length === 0) { acList.classList.remove('show'); return; }
    matches.forEach(fak => {
        const item = document.createElement('div');
        item.className = 'ac-item';
        item.innerHTML = '<i class="fas fa-university"></i>' + fak;
        item.addEventListener('mousedown', (e) => {
            e.preventDefault();
            fNamaF.value = fak;
            acList.classList.remove('show');
            updatePreview();
        });
        acList.appendChild(item);
    });
    acList.classList.add('show');
});

fNamaF.addEventListener('blur', () => setTimeout(() => acList.classList.remove('show'), 150));
fNamaF.addEventListener('focus', () => { if (fNamaF.value.trim()) fNamaF.dispatchEvent(new Event('input')); });

/* ── Validasi form ── */
document.getElementById('jurusanForm').addEventListener('submit', function(e) {
    let ok = true;
    document.querySelectorAll('.fe').forEach(el => el.classList.remove('show'));
    const namaJ = document.getElementById('fNamaJ').value.trim();
    const namaF = document.getElementById('fNamaF').value.trim();
    if (namaJ.length < 3 || namaJ.length > 100) { document.getElementById('namaJErr').classList.add('show'); ok = false; }
    if (namaF.length < 3 || namaF.length > 100) { document.getElementById('namaFErr').classList.add('show'); ok = false; }
    if (!ok) e.preventDefault();
});

/* ── Toggle fakultas group ── */
function toggleGroup(header) {
    const toggle = header.querySelector('.fak-toggle');
    const rows   = header.nextElementSibling;
    if (rows.classList.contains('collapsed')) {
        rows.classList.remove('collapsed');
        toggle.classList.add('open');
    } else {
        rows.classList.add('collapsed');
        toggle.classList.remove('open');
    }
}

/* ── Filter / Search ── */
function filterTable() {
    const q   = document.getElementById('searchInput').value.toLowerCase();
    const fak = document.getElementById('filterFakultas').value.toLowerCase();

    document.querySelectorAll('.jurusan-row').forEach(row => {
        const name    = row.dataset.name  || '';
        const rowFak  = row.dataset.fak   || '';
        const matchQ  = !q   || name.includes(q) || rowFak.includes(q);
        const matchF  = !fak || rowFak === fak;
        row.style.display = (matchQ && matchF) ? '' : 'none';
    });

    // Hide empty groups
    document.querySelectorAll('.fakultas-group').forEach(group => {
        const visible = [...group.querySelectorAll('.jurusan-row')].some(r => r.style.display !== 'none');
        group.style.display = visible ? '' : 'none';
    });
}

/* ── Delete Modal ── */
function confirmDelete(id, nama) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModalText').textContent = 'Anda akan menghapus jurusan "' + nama + '". Tindakan ini tidak dapat dibatalkan.';
    document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

/* ── Scroll to edit row if editing ── */
<?php if ($editData): ?>
window.addEventListener('load', () => {
    const row = document.getElementById('row-<?= $editData['id'] ?>');
    if (row) row.scrollIntoView({ behavior:'smooth', block:'center' });
});
<?php endif; ?>
</script>

</body>
</html>