<?php
/**
 * Tambah Dompet - FIXED
 * Konsisten menggunakan binary storage untuk enkripsi (sama dengan reencrypt.php)
 */

require_once '../includes/encryption.php';
require_once '../config/database.php'; // FIXED: Pastikan $pdo tersedia

// GANTI INI dengan key yang sama dengan yang digunakan di SQL!
define('ENCRYPTION_KEY', '@$%$=LuxeraDaoInternational=$%$@');

$mode = $_GET['mode'] ?? 'pribadi';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mode_post = $_POST['mode'] ?? 'pribadi';
    $crypto = new WalletEncryption(ENCRYPTION_KEY);

    $id_alamat = $_POST['id_alamat_dompet'] ?? '';
    $nama_dompet = $_POST['nama_dompet'] ?? '';
    $nama_pemilik = $_POST['nama_pemilik'] ?? '';
    $kode_referal = $_POST['kode_referal'] ?? '';
    $jaringan_dari = $_POST['jaringan_dari'] ?: null;
    $jenis_dompet = ($mode_post === 'pribadi') ? 'Pribadi' : 'Rekan';

    if ($mode_post === 'pribadi') {
        // Gabungkan 12 kata
        $frasa = [];
        for ($i = 1; $i <= 12; $i++) {
            $frasa[] = trim($_POST['kata'.$i] ?? '');
        }
        $frasa_pemulihan = implode(' ', $frasa);
        $kata_sandi = $_POST['kata_sandi_dompet'] ?? '';

        // ENKRIPSI FRASA PEMULIHAN
        $encryptedFrasa = $crypto->encrypt($frasa_pemulihan);

        // ENKRIPSI KATA SANDI
        $encryptedPassword = $crypto->encrypt($kata_sandi);

        // QR Code upload
        $qr_path = null;
        if (!empty($_FILES['qr_code']['name'])) {
            $upload_dir = '../assets/uploads/qr_codes/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . basename($_FILES['qr_code']['name']);
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $target)) {
                $qr_path = 'assets/uploads/qr_codes/' . $filename;
            }
        }
    } else {
        // Mode rekan - bisa kosong atau terenkripsi juga
        $frasa_pemulihan = $_POST['frasa_rekan'] ?? '';
        $kata_sandi = $_POST['password_rekan'] ?? '';

        // Enkripsi jika ada data
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

        $qr_path = null;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO dompet 
            (id_alamat_dompet, nama_dompet, nama_pemilik, 
             frasa_pemulihan, frasa_iv, frasa_auth_tag,
             qr_code_pemulihan, 
             kata_sandi_dompet, sandi_iv, sandi_auth_tag,
             kode_referal, jaringan_dari, Jenis_Dompet,
             enkripsi_version, enkripsi_algorithm) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // FIXED: Gunakan base64_decode untuk menyimpan sebagai binary (sama dengan reencrypt.php)
        $stmt->execute([
            $id_alamat, 
            $nama_dompet, 
            $nama_pemilik,
            base64_decode($encryptedFrasa['ciphertext']),  // FIXED: Binary storage
            base64_decode($encryptedFrasa['iv']),          // FIXED: Binary storage
            base64_decode($encryptedFrasa['tag']),         // FIXED: Binary storage
            $qr_path,
            base64_decode($encryptedPassword['ciphertext']), // FIXED: Binary storage
            base64_decode($encryptedPassword['iv']),         // FIXED: Binary storage
            base64_decode($encryptedPassword['tag']),        // FIXED: Binary storage
            $kode_referal, 
            $jaringan_dari,
            $jenis_dompet,
            1,
            'AES256GCM'
        ]);

        $msg = $mode_post === 'pribadi' ? 'Dompet pribadi' : 'Jaringan rekan';
        $success = "$msg berhasil ditambahkan dan terenkripsi! <a href='index.php'>Lihat daftar</a>";
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$title = 'Tambah Dompet - Luxera Dompet Manager';
require_once '../includes/header.php';
cekLogin();

$dompet_list = $pdo->query("SELECT kode_referal, nama_dompet, nama_pemilik FROM dompet ORDER BY nama_dompet")->fetchAll();
?>

<style>
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

    /* Form Rekan - Sederhana */
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
    .optional-field input {
        background: white;
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

    @media (max-width: 768px) {
        .frasa-grid { grid-template-columns: repeat(3, 1fr); }
        .form-grid-2 { grid-template-columns: 1fr; }
    }
    .pemilik-input {
        background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
        border: 2px solid #ffc107 !important;
    }
    .pemilik-input:focus {
        border-color: #ff9800 !important;
        box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.2) !important;
    }
</style>

<h2>➕ Tambah Dompet Baru</h2>

<?php if($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
<?php endif; ?>

<!-- Mode Selector -->
<div class="mode-selector">
    <button type="button" class="mode-btn <?= $mode === 'pribadi' ? 'active' : '' ?>" onclick="switchMode('pribadi')">
        <span style="font-size: 20px;">🔐</span>
        <span>Dompet Pribadi</span>
    </button>
    <button type="button" class="mode-btn <?= $mode === 'rekan' ? 'active' : '' ?>" onclick="switchMode('rekan')">
        <span style="font-size: 20px;">👥</span>
        <span>Jaringan Rekan</span>
    </button>
</div>

<!-- MODE PRIBADI -->
<div id="formPribadi" class="form-section <?= $mode === 'pribadi' ? 'active' : '' ?>">
    <div class="info-box">
        <strong>🔐 Mode Dompet Pribadi:</strong> Masukkan data lengkap dompet milik Anda sendiri.
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
                    <input type="text" name="kata<?= $i ?>" id="kata<?= $i ?>" class="frasa-input" placeholder="kata <?= $i ?>" required autocomplete="off">
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Data Dompet -->
        <div class="form-grid-2" style="margin-top: 20px;">
            <div class="form-group">
                <label>ID Alamat Dompet</label>
                <input type="text" name="id_alamat_dompet" placeholder="0x..." required style="font-family: monospace;">
            </div>
            <div class="form-group">
                <label>Nama Dompet (Label)</label>
                <input type="text" name="nama_dompet" placeholder="Contoh: Tabungan Utama" required>
            </div>
        </div>

        <!-- BARU: Nama Pemilik -->
        <div class="form-group" style="margin-bottom: 20px;">
            <label>👤 Nama Pemilik Dompet <span style="color: #e74c3c;">*</span></label>
            <input type="text" name="nama_pemilik" class="pemilik-input" placeholder="Nama lengkap pemilik wallet" required>
            <small style="color: #ff9800; font-size: 12px;">💡 Nama asli pemilik dompet (bukan nama wallet)</small>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Kata Sandi Dompet</label>
                <input type="password" name="kata_sandi_dompet" placeholder="Password" required>
            </div>
            <div class="form-group">
                <label>QR Code Pemulihan</label>
                <input type="file" name="qr_code" accept="image/*" style="padding: 10px; background: #f8f9fa;">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Kode Referal</label>
                <input type="text" name="kode_referal" placeholder="REF001" required style="text-transform: uppercase;">
            </div>
            <div class="form-group">
                <label>Jaringan Dari (Opsional)</label>
                <select name="jaringan_dari">
                    <option value="">-- Dompet Utama --</option>
                    <?php foreach($dompet_list as $d): ?>
                    <option value="<?= $d['kode_referal'] ?>"><?= htmlspecialchars($d['nama_dompet']) ?> - <?= htmlspecialchars($d['nama_pemilik'] ?? 'Tanpa Nama') ?> (<?= $d['kode_referal'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: right;">
            <a href="index.php" class="btn btn-secondary" style="padding: 12px 25px; margin-right: 10px;">← Batal</a>
            <button type="submit" class="btn-submit pribadi">💾 Simpan Dompet Pribadi</button>
        </div>
    </form>
</div>

<!-- MODE JARINGAN REKAN -->
<div id="formRekan" class="form-section <?= $mode === 'rekan' ? 'active' : '' ?>">
    <div class="info-box success">
        <strong>👥 Mode Jaringan Rekan:</strong> Catat dompet dari rekan Anda.
    </div>

    <form method="POST">
        <input type="hidden" name="mode" value="rekan">

        <div class="rekan-simple">
            <div class="rekan-header">
                <div class="rekan-icon">👤</div>
                <div style="font-size: 20px; color: #2c3e50; font-weight: 600; margin-bottom: 10px;">Data Dompet Rekan</div>
            </div>

            <!-- 5 Field Wajib (ditambah nama_pemilik) -->
            <div class="form-grid-2">
                <div class="form-group">
                    <label>ID Alamat Dompet <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="id_alamat_dompet" placeholder="0x..." required style="font-family: monospace;">
                </div>
                <div class="form-group">
                    <label>Nama Dompet (Label) <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="nama_dompet" placeholder="Wallet Budi" required>
                </div>
            </div>

            <!-- BARU: Nama Pemilik Rekan -->
            <div class="form-group" style="margin-bottom: 20px;">
                <label>👤 Nama Pemilik Rekan <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama_pemilik" class="pemilik-input" placeholder="Nama lengkap pemilik wallet" required>
                <small style="color: #ff9800; font-size: 12px;">💡 Nama asli orang yang memiliki wallet ini</small>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Kode Referal <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="kode_referal" placeholder="REF-BUDI-001" required>
                </div>
                <div class="form-group">
                    <label>Jaringan Dari <span style="color: #e74c3c;">*</span></label>
                    <select name="jaringan_dari" required>
                        <option value="">-- Pilih Referal Parent --</option>
                        <?php foreach($dompet_list as $d): ?>
                        <option value="<?= $d['kode_referal'] ?>"><?= htmlspecialchars($d['nama_dompet']) ?> - <?= htmlspecialchars($d['nama_pemilik'] ?? 'Tanpa Nama') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Field Opsional -->
            <div class="optional-field">
                <div style="font-weight: 600; color: #e65100; margin-bottom: 15px;">
                    📝 Data Opsional <span class="optional-badge">BOLEH KOSONG</span>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Frasa Pemulihan</label>
                        <textarea name="frasa_rekan" rows="3" placeholder="Kosongkan jika tidak tahu..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Password Dompet</label>
                        <input type="text" name="password_rekan" placeholder="Kosongkan jika tidak tahu...">
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: right;">
            <a href="index.php" class="btn btn-secondary" style="padding: 12px 25px; margin-right: 10px;">← Batal</a>
            <button type="submit" class="btn-submit rekan">👥 Simpan Jaringan Rekan</button>
        </div>
    </form>
</div>

<script>
function switchMode(mode) {
    const url = new URL(window.location.href);
    url.searchParams.set('mode', mode);
    window.history.pushState({}, '', url);

    document.querySelectorAll('.mode-btn').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');

    document.querySelectorAll('.form-section').forEach(form => form.classList.remove('active'));
    document.getElementById(mode === 'pribadi' ? 'formPribadi' : 'formRekan').classList.add('active');
}

document.getElementById('pasteFrasa')?.addEventListener('paste', function(e) {
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

<?php require_once '../includes/footer.php'; ?>