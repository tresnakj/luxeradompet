<?php
$title = 'Tambah/Edit Xera Stacking - Luxera Dompet Manager';
require_once '../includes/header.php';
cekLogin();

$wallet = $_GET['wallet'] ?? '';
$id_edit = $_GET['id'] ?? ''; // ID untuk edit mode
$error = '';
$success = '';

// Mode edit - ambil data existing
$data_edit = null;
if ($id_edit) {
    $stmt = $pdo->prepare("SELECT * FROM xera_stacking WHERE id = ?");
    $stmt->execute([$id_edit]);
    $data_edit = $stmt->fetch();
    if (!$data_edit) {
        $error = "Data stacking tidak ditemukan!";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_data = $_POST['id'] ?? ''; // Hidden field untuk ID saat edit
    $alamat = $_POST['id_alamat_dompet'];
    $tanggal = $_POST['tanggal_input'];
    $koin = $_POST['xera_koin'];
    $durasi = $_POST['stacking_duration'];
    $stacking = $_POST['stacking_xera'];
    $invest = $_POST['jumlah_invest_rp'];
    
    try {
        if ($id_data) {
            // UPDATE mode
            $stmt = $pdo->prepare("UPDATE xera_stacking SET 
                id_alamat_dompet = ?, 
                xera_koin = ?, 
                stacking_duration = ?, 
                stacking_xera = ?, 
                jumlah_invest_rp = ?, 
                created_at = ? 
                WHERE id = ?");
            $stmt->execute([$alamat, $koin, $durasi, $stacking, $invest, $tanggal, $id_data]);
            $success = "Data stacking berhasil diperbarui! <a href='index.php'>Lihat daftar</a>";
        } else {
            // INSERT mode
            $stmt = $pdo->prepare("INSERT INTO xera_stacking 
                (id_alamat_dompet, xera_koin, stacking_duration, stacking_xera, jumlah_invest_rp, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$alamat, $koin, $durasi, $stacking, $invest, $tanggal]);
            $success = "Data stacking berhasil ditambahkan! <a href='index.php'>Lihat daftar</a>";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$dompet_list = $pdo->query("SELECT id_alamat_dompet, nama_dompet FROM dompet ORDER BY nama_dompet")->fetchAll();

// Set default value untuk form
$default_tanggal = $data_edit ? date('Y-m-d\TH:i', strtotime($data_edit['created_at'])) : date('Y-m-d\TH:i');
$default_alamat = $data_edit ? $data_edit['id_alamat_dompet'] : $wallet;
?>

<style>
    .datetime-input {
        font-family: 'Segoe UI', monospace;
        font-size: 14px;
    }
    .info-box {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 12px 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        color: #1565c0;
        font-size: 13px;
    }
    .mode-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 15px;
    }
    .mode-add { background: #e8f5e9; color: #2e7d32; }
    .mode-edit { background: #fff3e0; color: #ef6c00; }
</style>

<h2>📊 <?= $data_edit ? 'Edit' : 'Tambah' ?> Data Xera Stacking</h2>

<?php if($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
<?php endif; ?>

<div class="info-box">
    💡 <strong>Tip:</strong> Tanggal input digunakan untuk mencatat kapan stacking dimulai. 
    Default adalah waktu sekarang, tapi Anda bisa ubah jika mencatat data historis.
</div>

<div class="card">
    <span class="mode-badge <?= $data_edit ? 'mode-edit' : 'mode-add' ?>">
        <?= $data_edit ? '✏️ MODE EDIT' : '➕ MODE TAMBAH' ?>
    </span>
    
    <form method="POST">
        <!-- Hidden field untuk ID saat edit -->
        <?php if ($data_edit): ?>
            <input type="hidden" name="id" value="<?= $data_edit['id'] ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Pilih Dompet</label>
                <select name="id_alamat_dompet" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                    <option value="">-- Pilih Dompet --</option>
                    <?php foreach($dompet_list as $d): ?>
                    <option value="<?= $d['id_alamat_dompet'] ?>" 
                        <?= $d['id_alamat_dompet'] == $default_alamat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['nama_dompet']) ?> (<?= substr($d['id_alamat_dompet'], 0, 20) ?>...)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tanggal & Waktu Input</label>
                <input type="datetime-local" 
                       name="tanggal_input" 
                       value="<?= $default_tanggal ?>" 
                       required 
                       class="datetime-input"
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                <small style="color: #7f8c8d;">Format: YYYY-MM-DD HH:MM</small>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
            <div class="form-group">
                <label>Jumlah Xera Koin</label>
                <input type="number" step="0.00000001" name="xera_koin" 
                       placeholder="0.00000000" 
                       value="<?= $data_edit ? $data_edit['xera_koin'] : '' ?>" 
                       required 
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
            </div>
            
            <div class="form-group">
                <label>Durasi Stacking</label>
                <select name="stacking_duration" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; background: white;">
                    <?php 
                    $durations = [30, 90, 120, 180, 360];
                    foreach ($durations as $d): 
                        $selected = ($data_edit && $data_edit['stacking_duration'] == $d) ? 'selected' : '';
                    ?>
                    <option value="<?= $d ?>" <?= $selected ?>><?= $d ?> Hari</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
            <div class="form-group">
                <label>Jumlah Stacking</label>
                <input type="number" step="0.00000001" name="stacking_xera" 
                       placeholder="0.00000000" 
                       value="<?= $data_edit ? $data_edit['stacking_xera'] : '' ?>" 
                       required 
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
            </div>
            
            <div class="form-group">
                <label>Jumlah Investasi (Rp)</label>
                <input type="number" step="0.01" name="jumlah_invest_rp" 
                       placeholder="0.00" 
                       value="<?= $data_edit ? $data_edit['jumlah_invest_rp'] : '' ?>" 
                       required 
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
            </div>
        </div>
        
        <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
            <a href="index.php" class="btn btn-secondary" style="padding: 12px 25px;">← Kembali</a>
            <button type="submit" class="btn btn-success" style="padding: 12px 30px;">
                💾 <?= $data_edit ? 'Update' : 'Simpan' ?> Stacking
            </button>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>