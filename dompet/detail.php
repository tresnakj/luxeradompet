<?php
// ============================================
// DETAIL DOMPET - DENGAN ENKRIPSI & EDIT SECURITY
// ============================================

require_once '../includes/encryption_fixed.php';

// GANTI DENGAN KEY ANDA!
define('ENCRYPTION_KEY', '@$%$=LuxeraDaoInternational=$%$@');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Cek session unlock - TANPA memulai session baru
$isUnlocked = isWalletUnlocked($id);

// ============================================
// AJAX HANDLER UNTUK DEKRIPSI
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'decrypt') {
    header('Content-Type: application/json');

    $crypto = new WalletEncryption(ENCRYPTION_KEY);
    $walletId = isset($_POST['wallet_id']) ? intval($_POST['wallet_id']) : 0;
    $inputPassword = $_POST['password'] ?? '';

    require_once '../config/database.php';

    $stmt = $pdo->prepare("SELECT kata_sandi_dompet, sandi_iv, sandi_auth_tag,
                          frasa_pemulihan, frasa_iv, frasa_auth_tag,
                          Jenis_Dompet
                          FROM dompet WHERE id = ?");
    $stmt->execute([$walletId]);
    $data = $stmt->fetch();

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Wallet tidak ditemukan']);
        exit;
    }

    if ($data['Jenis_Dompet'] === 'Rekan' && empty($data['kata_sandi_dompet'])) {
        echo json_encode(['success' => true, 'frasa' => '', 'password' => '', 'is_rekan' => true]);
        exit;
    }

    $decryptedPassword = $crypto->decrypt(
        $data['kata_sandi_dompet'],
        $data['sandi_iv'],
        $data['sandi_auth_tag']
    );

    if ($decryptedPassword === $inputPassword) {
        $decryptedFrasa = '';
        if (!empty($data['frasa_pemulihan'])) {
            $decryptedFrasa = $crypto->decrypt(
                $data['frasa_pemulihan'],
                $data['frasa_iv'],
                $data['frasa_auth_tag']
            );
        }

        unlockWallet($walletId);

        echo json_encode([
            'success' => true,
            'frasa' => $decryptedFrasa,
            'password' => $decryptedPassword,
            'is_rekan' => false
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Password salah!']);
    }
    exit;
}

// ============================================
// LOAD HEADER & DATA
// ============================================

$title = 'Detail Dompet - Luxera Dompet Manager';
require_once '../includes/header.php';
cekLogin();

require_once '../config/database.php';

$stmt = $pdo->prepare("SELECT d.*, 
    (SELECT SUM(jumlah_bonus) FROM air_drop WHERE id_alamat_dompet = d.id_alamat_dompet) as total_airdrop,
    (SELECT SUM(jumlah_invest_rp) FROM xera_stacking WHERE id_alamat_dompet = d.id_alamat_dompet) as total_investasi
    FROM dompet d WHERE d.id = ?");
$stmt->execute([$id]);
$dompet = $stmt->fetch();

if (!$dompet) {
    header("Location: index.php");
    exit;
}

$is_rekan = ($dompet['Jenis_Dompet'] === 'Rekan');

// Ambil data stacking
$stacking = $pdo->prepare("SELECT * FROM xera_stacking WHERE id_alamat_dompet = ? ORDER BY created_at DESC");
$stacking->execute([$dompet['id_alamat_dompet']]);
$stacking_data = $stacking->fetchAll();

// Ambil data airdrop
$airdrop = $pdo->prepare("SELECT * FROM air_drop WHERE id_alamat_dompet = ? ORDER BY tanggal DESC, created_at DESC LIMIT 30");
$airdrop->execute([$dompet['id_alamat_dompet']]);
$airdrop_data = $airdrop->fetchAll();

// Ambil downline
$downline_list = $pdo->prepare("SELECT id, nama_dompet, nama_pemilik, kode_referal, id_alamat_dompet 
    FROM dompet WHERE jaringan_dari = ? ORDER BY created_at ASC");
$downline_list->execute([$dompet['kode_referal']]);
$downlines = $downline_list->fetchAll();

// Ambil parent
$parent = null;
$parent_is_rekan = false;
if ($dompet['jaringan_dari']) {
    $parent_stmt = $pdo->prepare("SELECT id, nama_dompet, nama_pemilik, kode_referal, id_alamat_dompet, Jenis_Dompet 
        FROM dompet WHERE kode_referal = ? LIMIT 1");
    $parent_stmt->execute([$dompet['jaringan_dari']]);
    $parent = $parent_stmt->fetch();
    if ($parent) {
        $parent_is_rekan = ($parent['Jenis_Dompet'] === 'Rekan');
    }
}

// Tampilkan error dari session jika ada
$session_error = '';
if (isset($_SESSION['wallet_error'])) {
    $session_error = $_SESSION['wallet_error'];
    unset($_SESSION['wallet_error']);
}
?>

<link rel="stylesheet" href="responsive.css">

<!-- ============================================
     MODAL PASSWORD
     ============================================ -->
<div id="passwordModal" class="modal-password">
    <div class="modal-content-password">
        <div class="modal-header-password">
            <div class="modal-icon">🔐</div>
            <h3>Verifikasi Keamanan</h3>
            <p>Masukkan kata sandi dompet untuk melihat data sensitif</p>
        </div>
        <div class="modal-body-password">
            <input type="password" id="modalPassword" placeholder="Kata Sandi Dompet" autocomplete="off">
            <div id="modalError" class="modal-error">❌ Password salah!</div>
            <div class="modal-buttons">
                <button type="button" onclick="closePasswordModal()" class="btn-modal-cancel">Batal</button>
                <button type="button" onclick="verifyPasswordAndDecrypt()" id="btnUnlock" class="btn-modal-unlock">🔓 Buka Kunci</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
     SESSION ERROR ALERT
     ============================================ -->
<?php if ($session_error): ?>
<div id="sessionError" style="position:fixed;top:20px;right:20px;background:#e74c3c;color:white;padding:20px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.2);z-index:10000;animation:slideInRight 0.3s ease;max-width:400px;">
    <strong>⚠️ Peringatan</strong><br>
    <?= htmlspecialchars($session_error) ?>
    <button onclick="this.parentElement.remove()" style="position:absolute;top:10px;right:10px;background:none;border:none;color:white;font-size:20px;cursor:pointer;">&times;</button>
</div>
<style>
@keyframes slideInRight { from { transform:translateX(100%);opacity:0; } to { transform:translateX(0);opacity:1; } }
</style>
<script>
setTimeout(() => {
    const el = document.getElementById('sessionError');
    if (el) { el.style.animation = 'slideInRight 0.3s ease reverse'; setTimeout(() => el.remove(), 300); }
}, 5000);
</script>
<?php endif; ?>

<!-- ============================================
     HEADER PEMILIK
     ============================================ -->
<div class="owner-header <?= $is_rekan ? 'rekan' : 'pribadi' ?>">
    <div class="mode-indicator">
        <?= $is_rekan ? '<span>👥</span><span>Jaringan Rekan</span>' : '<span>🔐</span><span>Dompet Pribadi</span>' ?>
    </div>
    <div class="owner-avatar">
        <?= strtoupper(substr($dompet['nama_pemilik'] ?: $dompet['nama_dompet'], 0, 1)) ?>
    </div>
    <div class="owner-info">
        <div class="owner-name">
            <?= htmlspecialchars($dompet['nama_pemilik'] ?: 'Tanpa Nama') ?>
            <span class="owner-badge"><?= $is_rekan ? '👥 Rekan' : '🔐 Pribadi' ?></span>
        </div>
        <div class="owner-wallet-name">💼 <?= htmlspecialchars($dompet['nama_dompet']) ?></div>
        <div class="owner-address" title="<?= $dompet['id_alamat_dompet'] ?>">
            <?= substr($dompet['id_alamat_dompet'], 0, 25) ?>...
        </div>
    </div>
</div>

<!-- ============================================
     NAVIGATION - WITH EDIT SECURITY
     ============================================ -->
<div class="nav-actions">
    <a href="index.php" class="btn btn-secondary">← Kembali</a>
    <?php if ($is_rekan): ?>
        <!-- Rekan mode: langsung edit tanpa password -->
        <a href="edit.php?id=<?= $dompet['id'] ?>" class="btn btn-warning">✏ Edit</a>
    <?php else: ?>
        <!-- Pribadi mode: perlu verifikasi password -->
        <button type="button" onclick="handleEditClick()" class="btn btn-warning">✏ Edit</button>
    <?php endif; ?>
</div>

<!-- ============================================
     PARENT INFO
     ============================================ -->
<?php if ($dompet['jaringan_dari'] && $parent): ?>
<div class="parent-box clickable" onclick="window.location.href='detail.php?id=<?= $parent['id'] ?>'">
    <div class="parent-icon">🔗</div>
    <div class="parent-info">
        <h4>
            Terhubung dari Jaringan
            <span class="parent-badge <?= $parent_is_rekan ? 'rekan' : 'pribadi' ?>">
                <?= $parent_is_rekan ? '👥 Rekan' : '🔐 Pribadi' ?>
            </span>
        </h4>
        <div class="parent-details">
            <div class="parent-name">
                <strong><?= htmlspecialchars($parent['nama_dompet']) ?></strong>
                <?php if (!empty($parent['nama_pemilik'])): ?>
                    <span class="parent-owner">(<?= htmlspecialchars($parent['nama_pemilik']) ?>)</span>
                <?php else: ?>
                    <span class="parent-owner unnamed">(Tanpa Nama)</span>
                <?php endif; ?>
            </div>
            <div class="parent-meta">
                <span class="parent-code"><?= substr($parent['kode_referal'], 0, 25) ?>...</span>
                <span class="click-hint">→ Klik untuk lihat detail</span>
            </div>
        </div>
    </div>
    <div class="parent-arrow">→</div>
</div>
<?php else: ?>
<div class="parent-box no-parent">
    <div class="parent-icon">🌟</div>
    <div class="parent-info no-parent">
        <h4>Dompet Utama (Root)</h4>
        <p>Dompet ini adalah titik awal jaringan tanpa referal parent</p>
    </div>
</div>
<?php endif; ?>

<!-- ============================================
     STATS
     ============================================ -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-value"><?= htmlspecialchars($dompet['nama_dompet']) ?></div>
        <div class="stat-label"><?= substr($dompet['id_alamat_dompet'], 0, 20) ?>...</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value"><?= rupiah($dompet['total_investasi'] ?? 0) ?></div>
        <div class="stat-label">Total Investasi</div>
    </div>
    <div class="stat-card info">
        <div class="stat-value"><?= formatKoin($dompet['total_airdrop'] ?? 0) ?></div>
        <div class="stat-label">Total Air Drop (XERA)</div>
    </div>
</div>

<!-- ============================================
     INFO DASAR
     ============================================ -->
<div class="section-card">
    <h3 class="section-title">📋 Informasi Dompet</h3>
    <div class="info-grid">
        <div class="info-item">
            <label>Nama Pemilik</label>
            <div class="info-value">
                <?= $dompet['nama_pemilik'] ? htmlspecialchars($dompet['nama_pemilik']) : '<span class="text-danger">Belum diisi</span>' ?>
            </div>
        </div>
        <div class="info-item">
            <label>Nama Dompet (Label)</label>
            <div class="info-value"><?= htmlspecialchars($dompet['nama_dompet']) ?></div>
        </div>
        <div class="info-item">
            <label>Alamat Dompet</label>
            <div class="info-value"><?= htmlspecialchars($dompet['id_alamat_dompet']) ?></div>
        </div>
        <div class="info-item">
            <label>Kode Referal</label>
            <div class="info-value"><?= htmlspecialchars($dompet['kode_referal']) ?></div>
        </div>
        <div class="info-item">
            <label>Jaringan Dari</label>
            <div class="info-value">
                <?= $dompet['jaringan_dari'] ? htmlspecialchars($dompet['jaringan_dari']) : '<span class="text-primary">Dompet Utama</span>' ?>
            </div>
        </div>
        <div class="info-item">
            <label>Jenis Dompet</label>
            <div class="info-value"><?= htmlspecialchars($dompet['Jenis_Dompet']) ?></div>
        </div>
        <div class="info-item">
            <label>Tanggal Dibuat</label>
            <div class="info-value"><?= date('d F Y H:i', strtotime($dompet['created_at'])) ?> WIB</div>
        </div>
    </div>
</div>

<!-- ============================================
     PASSWORD SECTION
     ============================================ -->
<div class="security-section">
    <div class="security-header">
        <div class="security-title">🔐 Kata Sandi Dompet <span class="security-badge">Private</span></div>
        <button type="button" class="toggle-btn" onclick="togglePassword()" id="btnPassword">
            <span id="iconPassword">👁</span><span id="textPassword">Tampilkan</span>
        </button>
    </div>
    <div id="passwordHidden" class="masked-text">
        <span class="masked-placeholder">🔒 PASSWORD TERSEMBUNYI • KLIK TOMBOL UNTUK MELIHAT</span>
    </div>
    <div id="passwordVisible" class="hidden-content">
        <?php if ($is_rekan && empty($dompet['kata_sandi_dompet'])): ?>
            <div class="rekan-warning">
                <div class="rekan-warning-icon">👥</div>
                <strong>Data Tidak Tersedia</strong>
                <p>Mode jaringan rekan - Password tidak dicatat</p>
            </div>
        <?php else: ?>
            <div class="password-field">
                <span id="passwordText" class="password-visible">••••••••</span>
                <button class="copy-btn" onclick="copyPassword()">📋 Copy</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================
     FRASA SECTION
     ============================================ -->
<div class="security-section frasa-section">
    <div class="security-header">
        <div class="security-title">📝 Frasa Pemulihan (12 Kata) <span class="security-badge">Critical</span></div>
        <button type="button" class="toggle-btn" onclick="toggleFrasa()" id="btnFrasa">
            <span id="iconFrasa">👁</span><span id="textFrasa">Tampilkan</span>
        </button>
    </div>
    <div id="frasaHidden" class="frasa-hidden-box">
        <div class="frasa-hidden-icon">🔐</div>
        <h3>12 Kata Frasa Pemulihan Tersembunyi</h3>
        <p>Klik tombol "Tampilkan" untuk melihat frasa pemulihan</p>
        <small>⚠️ Jangan bagikan ke siapapun!</small>
    </div>
    <div id="frasaVisible" class="hidden-content">
        <?php if ($is_rekan): ?>
            <div class="rekan-warning">
                <div class="rekan-warning-icon">👥</div>
                <strong>Mode Jaringan Rekan</strong>
                <p>Frasa pemulihan lengkap tidak tersedia untuk dompet rekanan</p>
            </div>
        <?php else: ?>
            <div class="frasa-grid-revealed" id="frasaGrid">
                <div class="frasa-loading">🔓 Membuka kunci...</div>
            </div>
            <div class="frasa-actions">
                <button class="copy-btn" onclick="copyFrasa()">📋 Copy Semua Frasa</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================
     QR CODE SECTION
     ============================================ -->
<?php if ($dompet['qr_code_pemulihan']): ?>
<div class="security-section qr-section">
    <div class="security-header">
        <div class="security-title">📱 QR Code Pemulihan <span class="security-badge">Backup</span></div>
        <button type="button" class="toggle-btn" onclick="toggleQR()" id="btnQR">
            <span id="iconQR">👁</span><span id="textQR">Tampilkan</span>
        </button>
    </div>
    <div id="qrHidden" class="qr-hidden-box">
        <div class="qr-hidden-icon">📷</div>
        <h3>Gambar QR Code Tersembunyi</h3>
        <p>Klik tombol "Tampilkan" untuk melihat QR code</p>
    </div>
    <div id="qrVisible" class="hidden-content qr-content">
        <img src="../<?= htmlspecialchars($dompet['qr_code_pemulihan']) ?>" class="qr-image-revealed" alt="QR Code"><br>
        <a href="../<?= htmlspecialchars($dompet['qr_code_pemulihan']) ?>" download class="btn btn-primary">⬇ Download QR Code</a>
    </div>
</div>
<?php endif; ?>

<!-- ============================================
     DOWNLINE SECTION
     ============================================ -->
<div class="downline-section">
    <div class="downline-header">
        <h3 class="section-title">🌐 Jaringan Downline</h3>
        <span class="downline-count"><?= count($downlines) ?> Rekan</span>
    </div>
    <?php if (empty($downlines)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🌱</div>
            <p>Belum ada jaringan rekanan dari dompet ini</p>
            <a href="tambah.php?mode=rekan&parent=<?= urlencode($dompet['kode_referal']) ?>" class="btn btn-success">+ Tambah Rekan</a>
        </div>
    <?php else: ?>
        <div class="downline-grid">
            <?php foreach ($downlines as $down): ?>
            <div class="downline-card">
                <div class="downline-card-header">
                    <div class="downline-avatar"><?= strtoupper(substr($down['nama_pemilik'] ?: $down['nama_dompet'], 0, 1)) ?></div>
                    <div>
                        <div class="downline-name"><?= htmlspecialchars($down['nama_dompet']) ?></div>
                        <div class="downline-owner"><?= htmlspecialchars($down['nama_pemilik'] ?: 'Tanpa Nama') ?></div>
                    </div>
                </div>
                <div class="downline-address"><?= substr($down['id_alamat_dompet'], 0, 20) ?>...</div>
                <a href="detail.php?id=<?= $down['id'] ?>" class="btn btn-primary btn-sm">Lihat Detail →</a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="downline-actions">
            <a href="tambah.php?mode=rekan&parent=<?= urlencode($dompet['kode_referal']) ?>" class="btn btn-success">+ Tambah Rekan Lagi</a>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================
     STACKING TABLE
     ============================================ -->
<div class="section-card">
    <div class="section-header">
        <h3 class="section-title">📊 Data Xera Stacking</h3>
        <a href="../xera_stacking/tambah.php?wallet=<?= urlencode($dompet['id_alamat_dompet']) ?>" class="btn btn-success">+ Tambah Stacking</a>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Xera Koin</th>
                    <th>Durasi</th>
                    <th>Stacking</th>
                    <th>Investasi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stacking_data)): ?>
                    <tr><td colspan="5" class="empty-cell">Belum ada data stacking</td></tr>
                <?php else: ?>
                    <?php foreach ($stacking_data as $s): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                        <td><?= formatKoin($s['xera_koin']) ?></td>
                        <td><span class="badge badge-blue"><?= $s['stacking_duration'] ?> Hari</span></td>
                        <td><?= formatKoin($s['stacking_xera']) ?></td>
                        <td><?= rupiah($s['jumlah_invest_rp']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================
     AIRDROP TABLE
     ============================================ -->
<div class="section-card">
    <div class="section-header">
        <h3 class="section-title">🎁 Riwayat Air Drop (30 Hari Terakhir)</h3>
        <a href="../airdrop/tambah.php?wallet=<?= urlencode($dompet['id_alamat_dompet']) ?>" class="btn btn-success">+ Catat Bonus</a>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal Bonus</th>
                    <th>Waktu Input</th>
                    <th>Jumlah Bonus</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($airdrop_data)): ?>
                    <tr><td colspan="3" class="empty-cell">Belum ada data air drop</td></tr>
                <?php else: ?>
                    <?php foreach ($airdrop_data as $a): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($a['tanggal'])) ?></td>
                        <td><?= $a['created_at'] ? date('d/m/Y H:i', strtotime($a['created_at'])) : '-' ?></td>
                        <td class="text-green">+<?= formatKoin($a['jumlah_bonus']) ?> XERA</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================
     JAVASCRIPT
     ============================================ -->
<script>
let currentDecryptTarget = null;
let isWalletUnlocked = <?= json_encode($isUnlocked) ?>;
let walletId = <?= json_encode($id) ?>;
let decryptedData = null;

// ============================================
// SESSION STORAGE HELPERS
// ============================================

const STORAGE_KEY = 'wallet_decrypt_' + walletId;

function saveDecryptedData(data) {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
            frasa: data.frasa,
            password: data.password,
            timestamp: Date.now()
        }));
    } catch (e) {
        console.error('Failed to save to sessionStorage:', e);
    }
}

function loadDecryptedData() {
    try {
        const stored = sessionStorage.getItem(STORAGE_KEY);
        if (stored) {
            const data = JSON.parse(stored);
            // Check if data is still valid (5 minutes = 300000 ms)
            if (Date.now() - data.timestamp < 300000) {
                return data;
            } else {
                sessionStorage.removeItem(STORAGE_KEY);
            }
        }
    } catch (e) {
        console.error('Failed to load from sessionStorage:', e);
    }
    return null;
}

function clearDecryptedData() {
    try {
        sessionStorage.removeItem(STORAGE_KEY);
    } catch (e) {
        console.error('Failed to clear sessionStorage:', e);
    }
}

// ============================================
// EDIT VERIFICATION - BARU
// ============================================

function handleEditClick() {
    // Jika sudah unlocked (dari session sebelumnya), langsung ke edit
    if (isWalletUnlocked && decryptedData) {
        window.location.href = 'edit.php?id=' + walletId;
        return;
    }
    
    // Jika belum, tampilkan modal password
    currentDecryptTarget = 'edit';
    openPasswordModal();
    
    // Ubah teks modal untuk konteks Edit
    const modalTitle = document.querySelector('#passwordModal h3');
    const modalDesc = document.querySelector('#passwordModal p');
    if (modalTitle) modalTitle.textContent = 'Verifikasi Edit Dompet';
    if (modalDesc) modalDesc.textContent = 'Masukkan kata sandi dompet untuk mengedit data';
}

// ============================================
// TOGGLE FUNCTIONS
// ============================================

function togglePassword() {
    if (isWalletUnlocked && decryptedData) { 
        doTogglePassword(); 
        startHideTimer(); 
        return; 
    }
    <?php if ($is_rekan): ?>alert('👥 Mode Rekan: Data tidak tersedia'); return;<?php endif; ?>
    currentDecryptTarget = 'password';
    openPasswordModal();
}

function toggleFrasa() {
    if (isWalletUnlocked && decryptedData) { 
        doToggleFrasa(); 
        startHideTimer(); 
        return; 
    }
    <?php if ($is_rekan): ?>doToggleFrasa(); return;<?php endif; ?>
    currentDecryptTarget = 'frasa';
    openPasswordModal();
}

function toggleQR() {
    if (isWalletUnlocked && decryptedData) { 
        doToggleQR(); 
        startHideTimer(); 
        return; 
    }
    currentDecryptTarget = 'qr';
    openPasswordModal();
}

function openPasswordModal() {
    document.getElementById('passwordModal').style.display = 'block';
    document.getElementById('modalPassword').value = '';
    document.getElementById('modalError').style.display = 'none';
    setTimeout(() => document.getElementById('modalPassword').focus(), 100);
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
    currentDecryptTarget = null;
    // Reset teks modal ke default setelah delay
    setTimeout(() => {
        const modalTitle = document.querySelector('#passwordModal h3');
        const modalDesc = document.querySelector('#passwordModal p');
        if (modalTitle) modalTitle.textContent = 'Verifikasi Keamanan';
        if (modalDesc) modalDesc.textContent = 'Masukkan kata sandi dompet untuk melihat data sensitif';
    }, 300);
}

function verifyPasswordAndDecrypt() {
    const password = document.getElementById('modalPassword').value;
    const errorDiv = document.getElementById('modalError');
    const btn = document.getElementById('btnUnlock');

    if (!password) { errorDiv.textContent = '❌ Password tidak boleh kosong!'; errorDiv.style.display = 'block'; return; }

    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Memverifikasi...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'decrypt');
    formData.append('wallet_id', walletId);
    formData.append('password', password);

    fetch('detail.php?id=' + walletId, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;

        if (data.success) {
            isWalletUnlocked = true;
            decryptedData = { frasa: data.frasa, password: data.password };
            
            // Save to sessionStorage
            saveDecryptedData(decryptedData);
            
            closePasswordModal();
            
            // ============================================
            // BARU: Handler untuk redirect ke edit
            // ============================================
            if (currentDecryptTarget === 'edit') {
                showToast('✅ Verifikasi berhasil! Membuka halaman edit...');
                setTimeout(() => {
                    window.location.href = 'edit.php?id=' + walletId;
                }, 500);
                return;
            }
            
            updateDecryptedUI();
            if (currentDecryptTarget === 'password') doTogglePassword();
            else if (currentDecryptTarget === 'frasa') doToggleFrasa();
            else if (currentDecryptTarget === 'qr') doToggleQR();
            startHideTimer();
        } else {
            errorDiv.textContent = data.message || '❌ Password salah!';
            errorDiv.style.display = 'block';
        }
    })
    .catch(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        errorDiv.textContent = '❌ Terjadi kesalahan koneksi!';
        errorDiv.style.display = 'block';
    });
}

function updateDecryptedUI() {
    if (!decryptedData) return;
    const passEl = document.getElementById('passwordText');
    if (passEl && decryptedData.password) passEl.textContent = decryptedData.password;

    if (decryptedData.frasa) {
        const words = decryptedData.frasa.split(' ');
        const grid = document.getElementById('frasaGrid');
        if (grid) {
            grid.innerHTML = words.map((w, i) => `
                <div class="frasa-item-reveal">
                    <div class="frasa-num">${i + 1}</div>
                    <div class="frasa-word">${w.replace(/</g, '&lt;')}</div>
                </div>
            `).join('');
        }
    }
}

function doTogglePassword() { toggleElement('passwordHidden', 'passwordVisible', 'btnPassword', 'iconPassword', 'textPassword'); }
function doToggleFrasa() { toggleElement('frasaHidden', 'frasaVisible', 'btnFrasa', 'iconFrasa', 'textFrasa'); }
function doToggleQR() { toggleElement('qrHidden', 'qrVisible', 'btnQR', 'iconQR', 'textQR'); }

function toggleElement(hiddenId, visibleId, btnId, iconId, textId) {
    const hidden = document.getElementById(hiddenId);
    const visible = document.getElementById(visibleId);
    const btn = document.getElementById(btnId);
    const icon = document.getElementById(iconId);
    const text = document.getElementById(textId);

    if (!hidden || !visible) return;

    if (visible.classList.contains('show')) {
        visible.classList.remove('show');
        hidden.style.display = 'block';
        btn?.classList.remove('revealed');
        if (icon) icon.textContent = '👁';
        if (text) text.textContent = 'Tampilkan';
    } else {
        hidden.style.display = 'none';
        visible.classList.add('show');
        btn?.classList.add('revealed');
        if (icon) icon.textContent = '🙈';
        if (text) text.textContent = 'Sembunyikan';
    }
}

function copyPassword() {
    if (!decryptedData?.password) { 
        alert('Password belum terdekripsi! Silakan tampilkan password terlebih dahulu.'); 
        return; 
    }
    copyToClipboard(decryptedData.password, 'Password');
}

function copyFrasa() {
    if (!decryptedData?.frasa) { 
        alert('Frasa belum terdekripsi! Silakan tampilkan frasa terlebih dahulu.'); 
        return; 
    }
    copyToClipboard(decryptedData.frasa, '12 kata frasa');
}

function copyToClipboard(text, label) {
    // Coba metode modern terlebih dahulu
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text)
            .then(() => showToast(`✅ ${label} berhasil dicopy!`))
            .catch((err) => {
                console.log('Clipboard API gagal, mencoba fallback...', err);
                fallbackCopyText(text, label);
            });
    } else {
        // Browser tidak support Clipboard API
        fallbackCopyText(text, label);
    }
}

function fallbackCopyText(text, label) {
    // Buat textarea yang visible untuk iOS Safari
    const textArea = document.createElement("textarea");
    textArea.value = text;
    
    // Styling: harus dalam viewport tapi tidak terlihat
    textArea.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        width: 1px;
        height: 1px;
        opacity: 0.01;
        pointer-events: none;
        z-index: 1000;
        margin: 0;
        padding: 0;
        border: none;
    `;
    
    // Untuk iOS: perlu contentEditable dan readOnly
    textArea.contentEditable = true;
    textArea.readOnly = true;
    
    document.body.appendChild(textArea);
    
    // iOS Safari memerlukan range selection
    const isIOS = /ipad|iphone|ipod/i.test(navigator.userAgent);
    
    if (isIOS) {
        const range = document.createRange();
        range.selectNodeContents(textArea);
        
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        
        textArea.setSelectionRange(0, 999999); // For iOS
    } else {
        textArea.select();
    }
    
    let successful = false;
    try {
        successful = document.execCommand('copy');
    } catch (err) {
        console.error('execCommand gagal:', err);
    }
    
    // Cleanup
    document.body.removeChild(textArea);
    window.getSelection().removeAllRanges();
    
    if (successful) {
        showToast(`✅ ${label} berhasil dicopy!`);
    } else {
        // Jika semua gagal, tampilkan alert dengan teks
        alert(`Tidak dapat copy otomatis. Silakan copy manual:\n\n${text}`);
    }
}

function showToast(msg) {
    const existing = document.getElementById('toast-notification');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.id = 'toast-notification';
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => { 
        toast.style.animation = 'slideDown 0.3s ease'; 
        setTimeout(() => toast.remove(), 300); 
    }, 3000);
}

let hideTimer;
function startHideTimer() {
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => {
        if (document.getElementById('passwordVisible')?.classList.contains('show')) doTogglePassword();
        if (document.getElementById('frasaVisible')?.classList.contains('show')) doToggleFrasa();
        if (document.getElementById('qrVisible')?.classList.contains('show')) doToggleQR();
        decryptedData = null;
        isWalletUnlocked = false;
        
        // Clear sessionStorage on auto-hide
        clearDecryptedData();

        const alertBox = document.createElement('div');
        alertBox.style.cssText = 'position:fixed;top:20px;right:20px;background:#e74c3c;color:white;padding:20px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.2);z-index:10000;animation:slideInRight 0.3s ease;';
        alertBox.innerHTML = '<strong>🔒 Keamanan</strong><br>Data otomatis disembunyikan.';
        document.body.appendChild(alertBox);
        setTimeout(() => { 
            alertBox.style.animation = 'slideOutRight 0.3s ease'; 
            setTimeout(() => alertBox.remove(), 300); 
        }, 5000);
    }, 30000);
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Try to load decrypted data from sessionStorage
    const storedData = loadDecryptedData();
    if (storedData && isWalletUnlocked) {
        decryptedData = storedData;
        updateDecryptedUI();
    }
    
    document.getElementById('modalPassword')?.addEventListener('keypress', e => { 
        if (e.key === 'Enter') verifyPasswordAndDecrypt(); 
    });
    document.getElementById('passwordModal')?.addEventListener('click', e => { 
        if (e.target.id === 'passwordModal') closePasswordModal(); 
    });
    document.addEventListener('keydown', e => { 
        if (e.key === 'Escape') closePasswordModal(); 
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>