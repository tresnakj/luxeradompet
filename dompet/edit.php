<?php
/**
 * Edit Dompet - FIXED VERSION dengan Session Security
 * Tampilan sama dengan tambah.php, menyesuaikan form berdasarkan Jenis_Dompet
 */

require_once '../includes/encryption_fixed.php';  // FIXED: Gunakan encryption_fixed
require_once '../config/database.php';
define('ENCRYPTION_KEY', '@$%$=LuxeraDaoInternational=$%$@');

$id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Ambil data dompet
$stmt = $pdo->prepare("SELECT * FROM dompet WHERE id = ?");
$stmt->execute([$id]);
$dompet = $stmt->fetch();

if (!$dompet) {
    header("Location: index.php");
    exit;
}

// ============================================
// BARU: Security Check untuk dompet pribadi
// ============================================
$is_rekan = ($dompet['Jenis_Dompet'] === 'Rekan');

// Jika dompet pribadi, periksa apakah sudah unlock
if (!$is_rekan && !isWalletUnlocked($id)) {
    // Simpan pesan error di session untuk ditampilkan di detail.php
    $_SESSION['wallet_error'] = '🔒 Akses ditolak! Silakan verifikasi password dompet terlebih dahulu di halaman detail.';
    header("Location: detail.php?id=$id");
    exit;
}

// Jika sudah verified atau mode rekan, lanjutkan proses...

// Tentukan mode berdasarkan Jenis_Dompet di database (BUKAN dari isi frasa)
$mode = ($dompet['Jenis_Dompet'] === 'Rekan') ? 'rekan' : 'pribadi';
$is_rekan = ($mode === 'rekan');

// Dekripsi data untuk ditampilkan di form
$crypto = new WalletEncryption(ENCRYPTION_KEY);

// Dekripsi frasa jika ada
$decryptFrasa = '';
if (!empty($dompet['frasa_pemulihan'])) {
    $decryptFrasa = $crypto->decrypt(
        $dompet['frasa_pemulihan'],
        $dompet['frasa_iv'],
        $dompet['frasa_auth_tag']
    );
}
$frasa_array = ($decryptFrasa && !$is_rekan) ? explode(' ', $decryptFrasa) : array_fill(0, 12, '');

// Pastikan array frasa ada 12 item
while (count($frasa_array) < 12) {
    $frasa_array[] = '';
}

// Dekripsi password jika ada
$decryptPassword = '';
if (!empty($dompet['kata_sandi_dompet'])) {
    $decryptPassword = $crypto->decrypt(
        $dompet['kata_sandi_dompet'],
        $dompet['sandi_iv'],
        $dompet['sandi_auth_tag']
    );
}
$decryptPassword = $decryptPassword ?: '';

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_alamat = $_POST['id_alamat_dompet'] ?? '';
    $nama_dompet = $_POST['nama_dompet'] ?? '';
    $nama_pemilik = $_POST['nama_pemilik'] ?? '';
    $kode_referal = $_POST['kode_referal'] ?? '';
    $jaringan_dari = $_POST['jaringan_dari'] ?: null;

    if ($is_rekan) {
        // Mode rekan
        $frasa_pemulihan = $_POST['frasa_rekan'] ?? '';
        $kata_sandi = $_POST['password_rekan'] ?? '';

        if (!empty($frasa_pemulihan)) {
            $encryptedFrasa = $crypto->encrypt($frasa_pemulihan);
        } else {
            $encryptedFrasa = ['ciphertext' => '', 'iv' => '', 'tag' => ''];
        }

        if (!empty($kata_sandi)) {
            $encryptedPassword = $crypto->encrypt($kata_sandi);
        } else {
            $encryptedPassword = ['ciphertext' => '', 'iv' => '', 'tag' => ''];
        }
    } else {
        // Mode pribadi
        $frasa = [];
        for ($i = 1; $i <= 12; $i++) {
            $frasa[] = trim($_POST['kata'.$i] ?? '');
        }
        $frasa_pemulihan = implode(' ', $frasa);
        $kata_sandi = $_POST['kata_sandi_dompet'] ?? '';

        $encryptedFrasa = $crypto->encrypt($frasa_pemulihan);
        $encryptedPassword = $crypto->encrypt($kata_sandi);
    }

    // Handle QR Code
    $qr_path = $dompet['qr_code_pemulihan'];
    if (!empty($_FILES['qr_code']['name'])) {
        $upload_dir = '../assets/uploads/qr_codes/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        if ($qr_path && file_exists('../' . $qr_path)) {
            unlink('../' . $qr_path);
        }
        $filename = time() . '_' . basename($_FILES['qr_code']['name']);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $target)) {
            $qr_path = 'assets/uploads/qr_codes/' . $filename;
        }
    }

    if (isset($_POST['hapus_qr']) && $dompet['qr_code_pemulihan']) {
        if (file_exists('../' . $dompet['qr_code_pemulihan'])) {
            unlink('../' . $dompet['qr_code_pemulihan']);
        }
        $qr_path = null;
    }

    try {
        $stmt = $pdo->prepare("UPDATE dompet SET 
            id_alamat_dompet = ?, 
            nama_dompet = ?, 
            nama_pemilik = ?,
            frasa_pemulihan = ?, 
            frasa_iv = ?,
            frasa_auth_tag = ?,
            qr_code_pemulihan = ?, 
            kata_sandi_dompet = ?, 
            sandi_iv = ?,
            sandi_auth_tag = ?,
            kode_referal = ?, 
            jaringan_dari = ? 
            WHERE id = ?");

        $stmt->execute([
            $id_alamat, 
            $nama_dompet, 
            $nama_pemilik,
            base64_decode($encryptedFrasa['ciphertext']),
            base64_decode($encryptedFrasa['iv']),
            base64_decode($encryptedFrasa['tag']),
            $qr_path, 
            base64_decode($encryptedPassword['ciphertext']),
            base64_decode($encryptedPassword['iv']),
            base64_decode($encryptedPassword['tag']),
            $kode_referal, 
            $jaringan_dari, 
            $id
        ]);

        $success = "Dompet berhasil diperbarui!";

        // Refresh data
        $stmt = $pdo->prepare("SELECT * FROM dompet WHERE id = ?");
        $stmt->execute([$id]);
        $dompet = $stmt->fetch();

        // Refresh decrypted data
        if (!empty($dompet['frasa_pemulihan'])) {
            $decryptFrasa = $crypto->decrypt(
                $dompet['frasa_pemulihan'],
                $dompet['frasa_iv'],
                $dompet['frasa_auth_tag']
            );
        }
        $frasa_array = ($decryptFrasa && !$is_rekan) ? explode(' ', $decryptFrasa) : array_fill(0, 12, '');
        while (count($frasa_array) < 12) {
            $frasa_array[] = '';
        }

        if (!empty($dompet['kata_sandi_dompet'])) {
            $decryptPassword = $crypto->decrypt(
                $dompet['kata_sandi_dompet'],
                $dompet['sandi_iv'],
                $dompet['sandi_auth_tag']
            );
        }
        $decryptPassword = $decryptPassword ?: '';

    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$title = 'Edit Dompet - Luxera Dompet Manager';
require_once '../includes/header.php';
cekLogin();

$dompet_list = $pdo->query("SELECT kode_referal, nama_dompet, nama_pemilik FROM dompet WHERE id != $id ORDER BY nama_dompet")->fetchAll();
?>

<style>
    /* SAMA PERSIS DENGAN TAMBAH.PHP */
    .mode-selector {
        display: flex;
        gap: 0;
        margin-bottom: 25px;
        background: #f0f2f5;
        padding: 5px;
        border-radius: 12px;
    }
    .mode-btn {
        flex: 1;
        padding: 15px 25px;
        border: none;
        background: transparent;
        cursor: pointer;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        color: #7f8c8d;
    }
    .mode-btn.active {
        background: white;
        color: #2c3e50;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .form-section { display: none; }
    .form-section.active { display: block; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .info-box {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 15px 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        color: #1565c0;
        font-size: 14px;
    }
    .info-box.success {
        background: #e8f5e9;
        border-left-color: #4caf50;
        color: #2e7d32;
    }

    /* Form Pribadi */
    .paste-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 20px;
        text-align: center;
    }
    .paste-input {
        width: 100%;
        max-width: 600px;
        padding: 15px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        text-align: center;
        font-family: monospace;
    }

    .frasa-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 25px;
        border-radius: 15px;
        margin: 20px 0;
        border: 2px solid #e0e6ed;
    }
    .frasa-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 12px;
    }
    .frasa-item {
        position: relative;
        background: white;
        border-radius: 10px;
        padding: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .frasa-number {
        position: absolute;
        top: -10px;
        left: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }
    .frasa-input {
        width: 100%;
        margin-top: 8px;
        padding: 10px;
        border: 2px solid #e0e6ed;
        border-radius: 8px;
        font-size: 14px;
        text-align: center;
        text-transform: lowercase;
    }

    /* Form Rekan */
    .rekan-simple {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 15px;
        border: 2px dashed #bdc3c7;
    }
    .rekan-header {
        text-align: center;
        margin-bottom: 25px;
    }
    .rekan-icon {
        font-size: 64px;
        margin-bottom: 15px;
    }
    .optional-field {
        background: #fff3e0;
        border: 2px dashed #ffcc80;
        padding: 15px;
        border-radius: 10px;
        margin-top: 15px;
    }
    .optional-field label {
        color: #e65100;
        font-size: 13px;
    }
    .optional-badge {
        display: inline-block;
        background: #ff9800;
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        margin-left: 5px;
    }

    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
        font-size: 14px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
    }

    .btn-submit {
        padding: 14px 35px;
        font-size: 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-submit.pribadi {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-submit.rekan {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }

    .pemilik-input {
        background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
        border: 2px solid #ffc107 !important;
    }
    .pemilik-input:focus {
        border-color: #ff9800 !important;
        box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.2) !important;
    }

    .current-qr {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 15px;
        text-align: center;
    }
    .current-qr img {
        max-width: 150px;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    @media (max-width: 768px) {
        .frasa-grid { grid-template-columns: repeat(3, 1fr); }
        .form-grid-2 { grid-template-columns: 1fr; }
    }
</style>

<h2>✏️ Edit Dompet</h2>

<?php if($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
<?php endif; ?>

<!-- Mode Selector (Read Only) -->
<div class="mode-selector">
    <button type="button" class="mode-btn <?= $mode === 'pribadi' ? 'active' : '' ?>" disabled>
        <span style="font-size: 20px;">🔐</span>
        <span>Dompet Pribadi</span>
    </button>
    <button type="button" class="mode-btn <?= $mode === 'rekan' ? 'active' : '' ?>" disabled>
        <span style="font-size: 20px;">👥</span>
        <span>Jaringan Rekan</span>
    </button>
</div>

<?php if ($is_rekan): ?>
<!-- MODE REKAN -->
<div id="formRekan" class="form-section active">
    <div class="info-box success">
        <strong>👥 Mode Jaringan Rekan:</strong> Edit data dompet rekan.
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="rekan-simple">
            <div class="rekan-header">
                <div class="rekan-icon">👤</div>
                <div style="font-size: 20px; color: #2c3e50; font-weight: 600; margin-bottom: 10px;">Data Dompet Rekan</div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>ID Alamat Dompet <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="id_alamat_dompet" value="<?= htmlspecialchars($dompet['id_alamat_dompet']) ?>" required style="font-family: monospace;">
                </div>
                <div class="form-group">
                    <label>Nama Dompet (Label) <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="nama_dompet" value="<?= htmlspecialchars($dompet['nama_dompet']) ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label>👤 Nama Pemilik Rekan <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama_pemilik" class="pemilik-input" value="<?= htmlspecialchars($dompet['nama_pemilik'] ?? '') ?>" required>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Kode Referal <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="kode_referal" value="<?= htmlspecialchars($dompet['kode_referal']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Jaringan Dari <span style="color: #e74c3c;">*</span></label>
                    <select name="jaringan_dari" required>
                        <option value="">-- Pilih Referal Parent --</option>
                        <?php foreach($dompet_list as $d): ?>
                        <option value="<?= $d['kode_referal'] ?>" <?= ($dompet['jaringan_dari'] == $d['kode_referal']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nama_dompet']) ?> - <?= htmlspecialchars($d['nama_pemilik'] ?? 'Tanpa Nama') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="optional-field">
                <div style="font-weight: 600; color: #e65100; margin-bottom: 15px;">
                    📝 Data Opsional <span class="optional-badge">BOLEH KOSONG</span>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Frasa Pemulihan</label>
                        <textarea name="frasa_rekan" rows="3" placeholder="Kosongkan jika tidak tahu..."><?= htmlspecialchars($decryptFrasa) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Password Dompet</label>
                        <input type="text" name="password_rekan" value="<?= htmlspecialchars($decryptPassword) ?>" placeholder="Kosongkan jika tidak tahu...">
                    </div>
                </div>
            </div>

            <!-- QR Code -->
            <div class="form-group" style="margin-top: 20px;">
                <label>QR Code Pemulihan</label>
                <?php if ($dompet['qr_code_pemulihan']): ?>
                <div class="current-qr">
                    <img src="../<?= $dompet['qr_code_pemulihan'] ?>" alt="Current QR">
                    <label style="display: block; margin-top: 10px; color: #e74c3c;">
                        <input type="checkbox" name="hapus_qr" value="1"> Hapus QR Code
                    </label>
                </div>
                <?php endif; ?>
                <input type="file" name="qr_code" accept="image/*" style="padding: 10px; background: #f8f9fa;">
            </div>
        </div>

        <div style="margin-top: 30px; text-align: right;">
            <a href="index.php" class="btn btn-secondary" style="padding: 12px 25px; margin-right: 10px;">← Batal</a>
            <a href="detail.php?id=<?= $dompet['id'] ?>" class="btn btn-secondary" style="padding: 12px 25px; margin-right: 10px;">👁 Detail</a>
            <button type="submit" class="btn-submit rekan">💾 Simpan Perubahan</button>
        </div>
    </form>
</div>

<?php else: ?>
<!-- MODE PRIBADI -->
<div id="formPribadi" class="form-section active">
    <div class="info-box">
        <strong>🔐 Mode Dompet Pribadi:</strong> Edit data lengkap dompet milik Anda.
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="mode" value="pribadi">

        <!-- Paste Frasa -->
        <div class="paste-box">
            <h3>📋 Paste 12 Kata Sekaligus</h3>
            <input type="text" id="pasteFrasa" class="paste-input" placeholder="Paste 12 kata di sini..." autocomplete="off">
            <br>
            <button type="button" onclick="clearFrasa()" style="margin-top: 10px; padding: 8px 20px; background: rgba(255,255,255,0.2); color: white; border: 1px solid white; border-radius: 5px; cursor: pointer;">🗑️ Kosongkan</button>
        </div>

        <!-- Frasa 12 Kata -->
        <div class="frasa-container">
            <div style="text-align: center; color: #2c3e50; margin-bottom: 20px; font-weight: 600;">
                🔐 Frasa Pemulihan (12 Kata)
            </div>
            <div class="frasa-grid">
                <?php for($i=1; $i<=12; $i++): ?>
                <div class="frasa-item">
                    <div class="frasa-number"><?= $i ?></div>
                    <input type="text" name="kata<?= $i ?>" id="kata<?= $i ?>" class="frasa-input" 
                           placeholder="kata <?= $i ?>" 
                           value="<?= htmlspecialchars($frasa_array[$i-1]) ?>" required autocomplete="off">
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Data Dompet -->
        <div class="form-grid-2" style="margin-top: 20px;">
            <div class="form-group">
                <label>ID Alamat Dompet</label>
                <input type="text" name="id_alamat_dompet" value="<?= htmlspecialchars($dompet['id_alamat_dompet']) ?>" required style="font-family: monospace;">
            </div>
            <div class="form-group">
                <label>Nama Dompet (Label)</label>
                <input type="text" name="nama_dompet" value="<?= htmlspecialchars($dompet['nama_dompet']) ?>" required>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label>👤 Nama Pemilik Dompet <span style="color: #e74c3c;">*</span></label>
            <input type="text" name="nama_pemilik" class="pemilik-input" value="<?= htmlspecialchars($dompet['nama_pemilik'] ?? '') ?>" required>
            <small style="color: #ff9800; font-size: 12px;">💡 Nama asli pemilik dompet (bukan nama wallet)</small>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Kata Sandi Dompet</label>
                <input type="text" name="kata_sandi_dompet" value="<?= htmlspecialchars($decryptPassword) ?>" required>
            </div>
            <div class="form-group">
                <label>QR Code Pemulihan</label>
                <?php if ($dompet['qr_code_pemulihan']): ?>
                <div class="current-qr">
                    <img src="../<?= $dompet['qr_code_pemulihan'] ?>" alt="Current QR">
                    <label style="display: block; margin-top: 10px; color: #e74c3c;">
                        <input type="checkbox" name="hapus_qr" value="1"> Hapus QR Code
                    </label>
                </div>
                <?php endif; ?>
                <input type="file" name="qr_code" accept="image/*" style="padding: 10px; background: #f8f9fa;">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Kode Referal</label>
                <input type="text" name="kode_referal" value="<?= htmlspecialchars($dompet['kode_referal']) ?>" required style="text-transform: uppercase;">
            </div>
            <div class="form-group">
                <label>Jaringan Dari (Opsional)</label>
                <select name="jaringan_dari">
                    <option value="">-- Dompet Utama --</option>
                    <?php foreach($dompet_list as $d): ?>
                    <option value="<?= $d['kode_referal'] ?>" <?= ($dompet['jaringan_dari'] == $d['kode_referal']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['nama_dompet']) ?> - <?= htmlspecialchars($d['nama_pemilik'] ?? 'Tanpa Nama') ?> (<?= $d['kode_referal'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: right;">
            <a href="index.php" class="btn btn-secondary" style="padding: 12px 25px; margin-right: 10px;">← Batal</a>
            <a href="detail.php?id=<?= $dompet['id'] ?>" class="btn btn-secondary" style="padding: 12px 25px; margin-right: 10px;">👁 Detail</a>
            <button type="submit" class="btn-submit pribadi">💾 Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
document.getElementById('pasteFrasa').addEventListener('paste', function(e) {
    e.preventDefault();
    let pastedText = (e.clipboardData || window.clipboardData).getData('text');
    let words = pastedText.trim().replace(/\s+/g, ' ').split(' ').filter(w => w.length > 0);

    for (let i = 0; i < 12; i++) {
        let input = document.getElementById('kata' + (i + 1));
        if (input && words[i]) {
            input.value = words[i].toLowerCase().trim();
            input.style.background = '#d4edda';
            input.style.borderColor = '#28a745';
        }
    }

    if (words.length < 12) alert('⚠️ Hanya ' + words.length + ' kata. Seharusnya 12.');
    else if (words.length > 12) alert('⚠️ Terdeteksi ' + words.length + ' kata. Hanya 12 pertama digunakan.');

    this.value = '';
});

function clearFrasa() {
    document.getElementById('pasteFrasa').value = '';
    for (let i = 1; i <= 12; i++) {
        let input = document.getElementById('kata' + i);
        if (input) {
            input.value = '';
            input.style.background = '';
            input.style.borderColor = '';
        }
    }
}
</script>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>