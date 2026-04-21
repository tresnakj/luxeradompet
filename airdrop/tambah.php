<?php
$title = 'Tambah Air Drop - Luxera Dompet Manager';
require_once '../includes/header.php';
cekLogin();

$wallet = $_GET['wallet'] ?? '';
$error = '';
$success = '';

// Get total airdrop for selected wallet
$total_airdrop = 0;
$selected_wallet_id = $wallet;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $alamat = $_POST['id_alamat_dompet'];
    $tanggal_bonus = $_POST['tanggal_bonus'];
    $waktu_input = $_POST['waktu_input'];
    $bonus = $_POST['jumlah_bonus'];

    try {
        $stmt = $pdo->prepare("INSERT INTO air_drop 
            (id_alamat_dompet, tanggal, jumlah_bonus, created_at) 
            VALUES (?, ?, ?, ?)");
        $stmt->execute([$alamat, $tanggal_bonus, $bonus, $waktu_input]);
        $success = "Bonus harian berhasil dicatat! <a href='index.php'>Lihat daftar</a>";
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get wallet list
$dompet_list = $pdo->query("SELECT id_alamat_dompet, nama_dompet FROM dompet ORDER BY nama_dompet")->fetchAll();

// Calculate total airdrop for selected wallet
if (!empty($selected_wallet_id)) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bonus), 0) as total FROM air_drop WHERE id_alamat_dompet = ?");
    $stmt->execute([$selected_wallet_id]);
    $result = $stmt->fetch();
    $total_airdrop_gross = $result['total'] ?? 0;

    $stmt_wd = $pdo->prepare("SELECT COALESCE(SUM(jumlah_penarikan), 0) as total FROM withdraw WHERE id_alamat_dompet = ? AND status = 'completed'");
    $stmt_wd->execute([$selected_wallet_id]);
    $result_wd = $stmt_wd->fetch();
    $total_withdrawn_wallet = $result_wd['total'] ?? 0;

    $total_airdrop = $total_airdrop_gross - $total_withdrawn_wallet;
}
?>

<style>
    .datetime-input {
        font-family: 'Segoe UI', monospace;
        font-size: 14px;
    }
    .date-input {
        font-family: 'Segoe UI', monospace;
        font-size: 14px;
    }
    .form-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    @media (max-width: 768px) {
        .form-grid-3 {
            grid-template-columns: 1fr;
        }
    }

    /* Layout Container for Tip and Total Card */
    .info-total-container {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        align-items: stretch;
    }

    /* Tip Box - LEFT SIDE */
    .info-box {
        flex: 1;
        background: #e8f5e9;
        border-left: 4px solid #4caf50;
        padding: 15px 20px;
        border-radius: 8px;
        color: #2e7d32;
        font-size: 13px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* Total Airdrop Card - RIGHT SIDE */
    .total-airdrop-card {
        flex: 0 0 280px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 25px;
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
    }

    .total-airdrop-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
    }

    .total-airdrop-card.empty {
        background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);
        color: #666;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .total-airdrop-card.empty:hover {
        transform: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .total-airdrop-card .card-label {
        font-size: 12px;
        opacity: 0.9;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .total-airdrop-card .card-value {
        font-size: 28px;
        font-weight: bold;
        font-family: 'Segoe UI', monospace;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        line-height: 1.2;
    }

    .total-airdrop-card .card-unit {
        font-size: 14px;
        opacity: 0.8;
        margin-left: 3px;
    }

    .total-airdrop-card .wallet-name {
        font-size: 12px;
        margin-top: 8px;
        opacity: 0.85;
        font-style: italic;
    }

    /* Responsive: Stack on mobile */
    @media (max-width: 768px) {
        .info-total-container {
            flex-direction: column;
        }
        .total-airdrop-card {
            flex: 1;
        }
    }
</style>

<h2>🎁 Catat Bonus Air Drop Harian</h2>

<?php if($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
<?php endif; ?>

<!-- Container: Tip (Left) + Total Card (Right) -->
<div class="info-total-container">
    <!-- LEFT: Tip Box -->
    <div class="info-box">
        💡 <strong>Tip:</strong> 
        <br>• <strong>Tanggal Bonus</strong> = Hari Anda menerima bonus (untuk perhitungan akumulasi)
        <br>• <strong>Waktu Input</strong> = Kapan Anda mencatat data ini (boleh diubah untuk data historis)
    </div>

    <!-- RIGHT: Total Airdrop Card -->
    <div class="total-airdrop-card <?= empty($selected_wallet_id) ? 'empty' : '' ?>" id="totalAirdropCard">
        <div class="card-label">💰 Saldo Airdrop Bersih</div>
        <div class="card-value">
            <span id="totalAirdropValue">0,00</span>
            <span class="card-unit">XERA</span>
        </div>
        <div class="wallet-name" id="walletNameDisplay">
            <?= empty($selected_wallet_id) ? 'Pilih dompet' : htmlspecialchars($dompet_list[array_search($selected_wallet_id, array_column($dompet_list, 'id_alamat_dompet'))]['nama_dompet'] ?? 'Dompet Terpilih') ?>
        </div>
    </div>
</div>

<div class="card">
    <form method="POST" id="airdropForm">
        <div class="form-group" style="margin-bottom: 15px;">
            <label>Pilih Dompet</label>
            <select name="id_alamat_dompet" id="walletSelect" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                <option value="" data-total="0">-- Pilih Dompet --</option>
                <?php foreach($dompet_list as $d):
                    $stmt_total = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bonus), 0) as total FROM air_drop WHERE id_alamat_dompet = ?");
                    $stmt_total->execute([$d['id_alamat_dompet']]);
                    $wallet_total_gross = $stmt_total->fetch()['total'] ?? 0;

                    $stmt_wd2 = $pdo->prepare("SELECT COALESCE(SUM(jumlah_penarikan), 0) as total FROM withdraw WHERE id_alamat_dompet = ? AND status = 'completed'");
                    $stmt_wd2->execute([$d['id_alamat_dompet']]);
                    $wallet_withdrawn = $stmt_wd2->fetch()['total'] ?? 0;

                    $wallet_total = $wallet_total_gross - $wallet_withdrawn;
                ?>
                <option value="<?= $d['id_alamat_dompet'] ?>" 
                        data-total="<?= $wallet_total ?>"
                        data-name="<?= htmlspecialchars($d['nama_dompet']) ?>"
                        <?= $d['id_alamat_dompet'] == $wallet ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['nama_dompet']) ?> (<?= substr($d['id_alamat_dompet'], 0, 20) ?>...)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-grid-3" style="margin-top: 15px;">
            <div class="form-group">
                <label>Tanggal Bonus Diterima</label>
                <input type="date" 
                       name="tanggal_bonus" 
                       value="<?= date('Y-m-d') ?>" 
                       max="<?= date('Y-m-d') ?>"
                       required 
                       class="date-input"
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                <small style="color: #7f8c8d;">Hari Anda dapat bonus</small>
            </div>

            <div class="form-group">
                <label>Waktu Input (Catatan)</label>
                <input type="datetime-local" 
                       name="waktu_input" 
                       value="<?= date('Y-m-d\TH:i') ?>" 
                       required 
                       class="datetime-input"
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                <small style="color: #7f8c8d;">Kapan Anda mencatat</small>
            </div>

            <div class="form-group">
                <label>Jumlah Bonus (XERA)</label>
                <input type="number" 
                       step="0.00000001" 
                       name="jumlah_bonus" 
                       placeholder="0.00000000" 
                       required 
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                <small style="color: #27ae60; font-weight: 600;">+ Bonus masuk</small>
            </div>
        </div>

        <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
            <a href="index.php" class="btn btn-secondary" style="padding: 12px 25px;">← Kembali</a>
            <button type="submit" class="btn btn-success" style="padding: 12px 30px;">💾 Simpan Bonus</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const walletSelect = document.getElementById('walletSelect');
    const totalValue = document.getElementById('totalAirdropValue');
    const walletNameDisplay = document.getElementById('walletNameDisplay');
    const totalCard = document.getElementById('totalAirdropCard');

    // Format number with Indonesian locale (comma as decimal separator)
    function formatNumber(num) {
        return parseFloat(num).toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    walletSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const total = selectedOption.getAttribute('data-total') || '0';
        const name = selectedOption.getAttribute('data-name') || '';

        // Update total value with Indonesian format
        totalValue.textContent = formatNumber(total);

        // Update wallet name display and card style
        if (this.value === '') {
            walletNameDisplay.textContent = 'Pilih dompet';
            totalCard.classList.add('empty');
        } else {
            walletNameDisplay.textContent = name;
            totalCard.classList.remove('empty');
        }
    });

    // Trigger change event on page load if wallet is pre-selected
    if (walletSelect.value !== '') {
        walletSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>