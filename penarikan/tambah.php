<?php

$title = 'Catat Penarikan Airdrop - Luxera Dompet Manager';

require_once '../includes/header.php';

cekLogin();



$wallet  = $_GET['wallet'] ?? '';

$error   = '';

$success = '';



// ============================================================

// Proses POST

// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $alamat         = trim($_POST['id_alamat_dompet'] ?? '');

    $tanggal        = $_POST['tanggal_penarikan']   ?? date('Y-m-d');

    $jumlah         = (float)($_POST['jumlah_penarikan'] ?? 0);

    $status         = $_POST['status'] ?? 'pending';



    if (empty($alamat)) {

        $error = "Pilih dompet terlebih dahulu.";

    } elseif ($jumlah <= 0) {

        $error = "Jumlah penarikan harus lebih dari 0.";

    } elseif (!in_array($status, ['pending', 'completed', 'rejected'])) {

        $error = "Status tidak valid.";

    } else {

        // Cek saldo airdrop bersih dompet yang dipilih

        $stmt_saldo = $pdo->prepare("

            SELECT

                COALESCE(SUM(ad.jumlah_bonus), 0) -

                COALESCE((

                    SELECT SUM(w2.jumlah_penarikan)

                    FROM withdraw w2

                    WHERE w2.id_alamat_dompet = ?

                      AND w2.status = 'completed'

                ), 0) AS saldo_bersih

            FROM air_drop ad

            WHERE ad.id_alamat_dompet = ?

        ");

        $stmt_saldo->execute([$alamat, $alamat]);

        $saldo_bersih = (float)($stmt_saldo->fetch()['saldo_bersih'] ?? 0);
        if ($status === 'completed' && $jumlah > $saldo_bersih) {

            $error = "Jumlah penarikan (". formatKoin($jumlah) ." XERA) melebihi saldo bersih airdrop (".formatKoin($saldo_bersih)." XERA).";

        } else {

            try {

                $stmt = $pdo->prepare("

                    INSERT INTO withdraw

                        (id_alamat_dompet, jumlah_penarikan, tanggal_penarikan, status, created_at, updated_at)

                    VALUES (?, ?, ?, ?, NOW(), NOW())

                ");

                $stmt->execute([$alamat, $jumlah, $tanggal, $status]);

                $success = "Penarikan berhasil dicatat! <a href='index.php?msg=added'>Lihat riwayat</a>";

                // Reset wallet supaya form bersih

                $wallet = $alamat; // tampilkan saldo terbaru

            } catch (PDOException $e) {

                $error = "Error database: " . $e->getMessage();

            }

        }

    }

}



// ============================================================

// Dompet dengan saldo >= 0.1 (untuk dropdown)

// ============================================================

$dompet_list = $pdo->query("

    SELECT

        d.id_alamat_dompet,

        d.nama_dompet,

        COALESCE(SUM(ad.jumlah_bonus), 0) AS total_airdrop,

        COALESCE((

            SELECT SUM(w2.jumlah_penarikan)

            FROM withdraw w2

            WHERE w2.id_alamat_dompet = d.id_alamat_dompet

              AND w2.status = 'completed'

        ), 0) AS total_withdrawn,

        COALESCE(SUM(ad.jumlah_bonus), 0) - COALESCE((

            SELECT SUM(w2.jumlah_penarikan)

            FROM withdraw w2

            WHERE w2.id_alamat_dompet = d.id_alamat_dompet

              AND w2.status = 'completed'

        ), 0) AS saldo_airdrop

    FROM dompet d

    LEFT JOIN air_drop ad ON d.id_alamat_dompet = ad.id_alamat_dompet

    GROUP BY d.id_alamat_dompet, d.nama_dompet

    HAVING saldo_airdrop >= 0.1

    ORDER BY d.nama_dompet ASC

")->fetchAll();

// Saldo dompet yang dipilih (untuk card tampilan)

$selected_saldo = 0;

$selected_total_airdrop    = 0;

$selected_total_withdrawn  = 0;

if (!empty($wallet)) {

    foreach ($dompet_list as $d) {

        if ($d['id_alamat_dompet'] === $wallet) {

            $selected_saldo          = (float)$d['saldo_airdrop'];

            $selected_total_airdrop  = (float)$d['total_airdrop'];

            $selected_total_withdrawn = (float)$d['total_withdrawn'];

            break;

        }

    }

}

?>



<style>

    .form-grid-2 {

        display: grid;

        grid-template-columns: repeat(2, 1fr);

        gap: 20px;

    }

    @media (max-width: 640px) {

        .form-grid-2 { grid-template-columns: 1fr; }

    }



    /* ============================================================

       INFO + SALDO CARD

       ============================================================ */

    .info-saldo-container {

        display: flex;

        gap: 20px;

        margin-bottom: 20px;

        align-items: stretch;

    }

    .info-box {

        flex: 1;

        background: #fff8e1;

        border-left: 4px solid #f39c12;

        padding: 15px 20px;

        border-radius: 8px;

        color: #7d5a00;

        font-size: 13px;

        display: flex;

        flex-direction: column;

        justify-content: center;

        gap: 5px;

    }
    
    /* Saldo card */

    .saldo-card {

        flex: 0 0 300px;

        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);

        color: white;

        padding: 20px 25px;

        border-radius: 10px;

        box-shadow: 0 8px 25px rgba(17, 153, 142, 0.3);

        display: flex;

        flex-direction: column;

        justify-content: center;

        text-align: center;

        transition: all 0.3s;

    }

    .saldo-card.empty {

        background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);

        color: #555;

        box-shadow: 0 4px 12px rgba(0,0,0,0.1);

    }

    .saldo-card .card-label {

        font-size: 11px;

        opacity: 0.9;

        text-transform: uppercase;

        letter-spacing: 1px;

        margin-bottom: 4px;

    }

    .saldo-card .card-value {

        font-size: 28px;

        font-weight: bold;

        font-family: monospace;

        text-shadow: 0 2px 4px rgba(0,0,0,0.15);

        line-height: 1.2;

    }

    .saldo-card .card-unit {

        font-size: 14px;

        opacity: 0.8;

        margin-left: 3px;

    }

    .saldo-card .detail-row {

        margin-top: 10px;

        padding-top: 10px;

        border-top: 1px solid rgba(255,255,255,0.3);

        display: flex;

        justify-content: space-between;

        font-size: 12px;

        opacity: 0.9;

    }
    .saldo-card .wallet-name {

        margin-top: 8px;

        font-size: 12px;

        opacity: 0.85;

        font-style: italic;

    }



    @media (max-width: 768px) {

        .info-saldo-container { flex-direction: column; }

        .saldo-card { flex: 1; }

    }

</style>



<h2>💸 Catat Penarikan Airdrop</h2>



<?php if ($success): ?>

    <div class="alert alert-success"><?= $success ?></div>

<?php endif; ?>

<?php if ($error): ?>

    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>

<?php endif; ?>



<!-- ============================================================

     INFO + SALDO

     ============================================================ -->

<div class="info-saldo-container">

    <div class="info-box">

        ⚠️ <strong>Catatan Penting:</strong><br>

        • Hanya dompet dengan saldo airdrop ≥ 0.1 XERA yang ditampilkan.<br>

        • Status <strong>Completed</strong> akan langsung mengurangi saldo bersih airdrop.<br>

        • Status <strong>Pending</strong> belum mempengaruhi saldo bersih (hanya tercatat).<br>

        • Status <strong>Rejected</strong> tidak mengurangi saldo airdrop sama sekali.

    </div>

    <div class="saldo-card <?= empty($wallet) ? 'empty' : '' ?>" id="saldoCard">

        <div class="card-label">💰 Saldo Airdrop Bersih</div>

        <div class="card-value">

            <span id="saldoBersihValue"><?= empty($wallet) ? '0,00' : formatKoin($selected_saldo) ?></span>

            <span class="card-unit">XERA</span>

        </div>

        <div class="detail-row" id="saldoDetailRow" style="<?= empty($wallet) ? 'display:none' : '' ?>">

            <span>Total Airdrop: <strong><span id="totalAirdropVal"><?= formatKoin($selected_total_airdrop) ?></span></strong></span>

            <span>Ditarik: <strong><span id="totalWithdrawnVal"><?= formatKoin($selected_total_withdrawn) ?></span></strong></span>

        </div>

        <div class="wallet-name" id="walletNameDisplay">
          <?php
if (empty($wallet)) {
    echo 'Pilih dompet';
} else {
    $selected = array_values(array_filter($dompet_list, function($d) use ($wallet) {
        return $d['id_alamat_dompet'] === $wallet;
    }));
    echo htmlspecialchars($selected[0]['nama_dompet'] ?? 'Dompet Terpilih');
}
?>

        </div>

    </div>

</div>



<!-- ============================================================

     FORM

     ============================================================ -->

<div class="card">

    <form method="POST" id="penarikanForm">



        <!-- Pilih Dompet -->

        <div class="form-group" style="margin-bottom: 20px;">

            <label>Pilih Dompet</label>

            <?php if (empty($dompet_list)): ?>

                <div class="alert alert-warning">

                    Tidak ada dompet dengan saldo airdrop ≥ 0.1 XERA.

                    <a href="../airdrop/index.php">Lihat data airdrop →</a>

                </div>

            <?php else: ?>

            <select name="id_alamat_dompet" id="walletSelect" required

                    style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px;">

                <option value="" data-saldo="0" data-total="0" data-withdrawn="0">-- Pilih Dompet --</option>

                <?php foreach ($dompet_list as $d): ?>

                <option value="<?= htmlspecialchars($d['id_alamat_dompet']) ?>"

                        data-saldo="<?= $d['saldo_airdrop'] ?>"

                        data-total="<?= $d['total_airdrop'] ?>"

                        data-withdrawn="<?= $d['total_withdrawn'] ?>"

                        data-name="<?= htmlspecialchars($d['nama_dompet']) ?>"

                        <?= $d['id_alamat_dompet'] === $wallet ? 'selected' : '' ?>>

                    <?= htmlspecialchars($d['nama_dompet']) ?>

                    — Saldo: <?= formatKoin($d['saldo_airdrop']) ?> XERA

                    (<?= substr($d['id_alamat_dompet'], 0, 18) ?>...)

                </option>

                <?php endforeach; ?>

            </select>

            <?php endif; ?>

        </div>
        
        <!-- Grid: Tanggal + Jumlah + Status -->

        <div class="form-grid-2" style="margin-top: 15px;">

            <div class="form-group">

                <label>Tanggal Penarikan</label>

                <input type="date"

                       name="tanggal_penarikan"

                       value="<?= date('Y-m-d') ?>"

                       max="<?= date('Y-m-d') ?>"

                       required

                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px;">

                <small style="color: #7f8c8d;">Tanggal penarikan dilakukan</small>

            </div>



            <div class="form-group">

                <label>Jumlah Penarikan (XERA)</label>

                <input type="number"

                       step="0.00000001"

                       name="jumlah_penarikan"

                       id="jumlahInput"

                       placeholder="0.00000000"

                       min="0.00000001"

                       required

                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px;">

                <small style="color: #e74c3c; font-weight: 600;" id="jumlahHint">Masukkan jumlah yang ditarik</small>

            </div>

        </div>



        <div class="form-group" style="margin-top: 20px;">

            <label>Status Penarikan</label>

            <select name="status" id="statusSelect"

                    style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px;">

                <option value="pending">⏳ Pending — Belum dikonfirmasi (tidak mempengaruhi saldo)</option>

                <option value="completed">✅ Completed — Penarikan berhasil (saldo airdrop berkurang)</option>

                <option value="rejected">❌ Rejected — Ditolak (tidak mempengaruhi saldo)</option>

            </select>

            <small style="color: #7f8c8d;">Hanya status <strong>Completed</strong> yang mengurangi saldo airdrop</small>

        </div>



        <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;">

            <a href="index.php" class="btn btn-secondary" style="padding: 12px 25px;">← Kembali</a>

            <?php if (!empty($dompet_list)): ?>

            <button type="submit" class="btn btn-danger" style="padding: 12px 30px;">💸 Simpan Penarikan</button>

            <?php endif; ?>

        </div>

    </form>

</div>



<script>
  (function() {

    const walletSelect  = document.getElementById('walletSelect');

    const saldoCard     = document.getElementById('saldoCard');

    const saldoVal      = document.getElementById('saldoBersihValue');

    const totalAirdropV = document.getElementById('totalAirdropVal');

    const totalWithdV   = document.getElementById('totalWithdrawnVal');

    const detailRow     = document.getElementById('saldoDetailRow');

    const walletName    = document.getElementById('walletNameDisplay');

    const jumlahInput   = document.getElementById('jumlahInput');

    const jumlahHint    = document.getElementById('jumlahHint');



    if (!walletSelect) return; // no dompet



    function fmt(num) {

        return parseFloat(num).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 8 });

    }



    function updateCard() {

        const opt = walletSelect.options[walletSelect.selectedIndex];

        const saldo    = parseFloat(opt.getAttribute('data-saldo')    || '0');

        const total    = parseFloat(opt.getAttribute('data-total')    || '0');

        const withdwn  = parseFloat(opt.getAttribute('data-withdrawn')|| '0');

        const name     = opt.getAttribute('data-name') || '';



        if (walletSelect.value === '') {

            saldoCard.classList.add('empty');

            saldoVal.textContent = '0,00';

            walletName.textContent = 'Pilih dompet';

            detailRow.style.display = 'none';

        } else {

            saldoCard.classList.remove('empty');

            saldoVal.textContent = fmt(saldo);

            totalAirdropV.textContent = fmt(total);

            totalWithdV.textContent   = fmt(withdwn);

            walletName.textContent    = name;

            detailRow.style.display   = '';



            // Update max di input jumlah

            if (jumlahInput) {

                jumlahInput.max = saldo;

                jumlahHint.textContent = 'Maks: ' + fmt(saldo) + ' XERA';

            }

        }

    }



    walletSelect.addEventListener('change', updateCard);



    // Trigger on load jika sudah ada pre-selected

    if (walletSelect.value !== '') updateCard();
    
    // Validasi jumlah tidak melebihi saldo (hanya warn, server tetap validasi)

    if (jumlahInput) {

        jumlahInput.addEventListener('input', function() {

            const opt    = walletSelect.options[walletSelect.selectedIndex];

            const saldo  = parseFloat(opt.getAttribute('data-saldo') || '0');

            const val    = parseFloat(this.value || '0');

            const statusSel = document.getElementById('statusSelect');

            const isCompleted = statusSel && statusSel.value === 'completed';



            if (isCompleted && val > saldo && saldo > 0) {

                jumlahHint.style.color = '#e74c3c';

                jumlahHint.textContent = '⚠️ Melebihi saldo bersih! Maks: ' + fmt(saldo) + ' XERA';

                this.style.borderColor = '#e74c3c';

            } else {

                jumlahHint.style.color = '#27ae60';

                jumlahHint.textContent = 'Maks: ' + fmt(saldo) + ' XERA';

                this.style.borderColor = '#ddd';

            }

        });

    }

})();

</script>



<?php require_once '../includes/footer.php'; ?>