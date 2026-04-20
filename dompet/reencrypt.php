<?php
/**
 * Script Re-Encrypt Data Dompet
 * FIXED VERSION
 */

require_once '../includes/encryption.php';
require_once '../config/database.php';

// Security: Hanya bisa diakses via CLI atau dengan key khusus
$adminKey = $_GET['key'] ?? '';
$expectedKey = 'RECRYPT_' . date('Ymd');

if (php_sapi_name() !== 'cli' && $adminKey !== $expectedKey) {
    die('<h2>❌ Akses Ditolak</h2><p>Gunakan: reencrypt.php?key=' . $expectedKey . '</p>');
}

define('ENCRYPTION_KEY', '@$%$=LuxeraDaoInternational=$%$@');
$crypto = new WalletEncryption(ENCRYPTION_KEY);

echo '<!DOCTYPE html>
<html>
<head>
    <title>Re-Encrypt Data Dompet</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .warning { background: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 5px solid #28a745; padding: 15px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 5px solid #dc3545; padding: 15px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        tr:hover { background: #f5f5f5; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin: 5px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #3498db; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .stat-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: bold; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="container">';

echo "<h1>🔐 Re-Encrypt Data Dompet</h1>";

// Mode 1: Analisis Data
if (!isset($_POST['action']) && !isset($_GET['edit'])) {
    $stmt = $pdo->query("SELECT id, nama_dompet, nama_pemilik, Jenis_Dompet, 
                         LENGTH(kata_sandi_dompet) as pass_len,
                         LENGTH(frasa_pemulihan) as frasa_len
                         FROM dompet ORDER BY id");
    $wallets = $stmt->fetchAll();

    $total = count($wallets);
    $corrupt = 0;
    $rekan = 0;

    echo '<div class="stats">';
    echo '<div class="stat-box"><div class="stat-number">' . $total . '</div><div>Total Dompet</div></div>';

    foreach ($wallets as $w) {
        if ($w['Jenis_Dompet'] === 'Rekan') $rekan++;

        $stmt2 = $pdo->prepare("SELECT * FROM dompet WHERE id = ?");
        $stmt2->execute([$w['id']]);
        $data = $stmt2->fetch();

        $testDecrypt = $crypto->decrypt(
            $data['kata_sandi_dompet'],
            $data['sandi_iv'],
            $data['sandi_auth_tag']
        );

        if ($testDecrypt === false && $w['Jenis_Dompet'] !== 'Rekan') {
            $corrupt++;
        }
    }

    echo '<div class="stat-box" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);"><div class="stat-number">' . $corrupt . '</div><div>Data Bermasalah</div></div>';
    echo '<div class="stat-box" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);"><div class="stat-number">' . $rekan . '</div><div>Dompet Rekan</div></div>';
    echo '<div class="stat-box" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);"><div class="stat-number">' . ($total - $corrupt - $rekan) . '</div><div>Data Normal</div></div>';
    echo '</div>';

    if ($corrupt > 0) {
        echo '<div class="warning">
            <strong>⚠️ PERINGATAN!</strong><br>
            Ditemukan <b>' . $corrupt . ' dompet</b> dengan data enkripsi bermasalah.<br>
            Data ini perlu diperbaiki agar bisa dibuka dengan sistem baru.
        </div>';

        echo '<h3>Opsi Perbaikan:</h3>';

        // Form 1: Re-encrypt dengan Password Baru
        echo '<form method="POST" style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 10px;">';
        echo '<input type="hidden" name="action" value="reset_password">';
        echo '<h4>🔑 Opsi 1: Reset Password & Frasa</h4>';
        echo '<p>Set ulang password dan frasa pemulihan untuk dompet yang bermasalah.</p>';

        echo '<div class="form-group">';
        echo '<label>Password Baru Default:</label>';
        echo '<input type="text" name="default_pass" placeholder="Kosongkan jika ingin input manual">';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label>Frasa Pemulihan Baru Default (12 kata):</label>';
        echo '<input type="text" name="default_frasa" placeholder="Contoh: word1 word2 word3 ... word12">';
        echo '</div>';

        // FIXED: Gunakan double quote untuk onclick
        echo '<button type="submit" class="btn btn-danger" onclick="return confirm(&quot;Yakin reset password dompet? Data lama akan hilang!&quot;)">🔄 Reset & Re-Encrypt</button>';
        echo '</form>';

    } else {
        echo '<div class="success">
            <strong>✅ Semua data normal!</strong><br>
            Tidak ditemukan data yang perlu diperbaiki.
        </div>';
    }

    // Tabel detail
    echo '<h3>Detail Data:</h3>';
    echo '<table>';
    echo '<tr><th>ID</th><th>Nama</th><th>Pemilik</th><th>Jenis</th><th>Status</th><th>Aksi</th></tr>';

    foreach ($wallets as $w) {
        $status = '<span style="color: green;">✓ Normal</span>';
        if ($w['Jenis_Dompet'] === 'Rekan') {
            $status = '<span style="color: orange;">👥 Rekan</span>';
        } else {
            $stmt2 = $pdo->prepare("SELECT * FROM dompet WHERE id = ?");
            $stmt2->execute([$w['id']]);
            $data = $stmt2->fetch();

            $test = $crypto->decrypt($data['kata_sandi_dompet'], $data['sandi_iv'], $data['sandi_auth_tag']);
            if ($test === false) {
                $status = '<span style="color: red;">✗ Corrupt</span>';
            }
        }

        echo '<tr>';
        echo '<td>#' . $w['id'] . '</td>';
        echo '<td>' . htmlspecialchars($w['nama_dompet']) . '</td>';
        echo '<td>' . htmlspecialchars($w['nama_pemilik']) . '</td>';
        echo '<td>' . $w['Jenis_Dompet'] . '</td>';
        echo '<td>' . $status . '</td>';
        echo '<td><a href="?key=' . $expectedKey . '&edit=' . $w['id'] . '" class="btn btn-primary">Edit</a></td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Mode 2: Reset Password Massal
if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $defaultPass = $_POST['default_pass'] ?? '';
    $defaultFrasa = $_POST['default_frasa'] ?? '';

    $stmt = $pdo->query("SELECT id, nama_dompet, Jenis_Dompet FROM dompet WHERE Jenis_Dompet = 'Pribadi'");
    $wallets = $stmt->fetchAll();

    $success = 0;
    $failed = 0;

    echo '<h3>Hasil Re-Encrypt:</h3>';

    foreach ($wallets as $wallet) {
        $newPass = $defaultPass ?: 'TempPass_' . substr(md5(rand()), 0, 8);
        $newFrasa = $defaultFrasa ?: implode(' ', array_map(function() { return chr(rand(97, 122)) . chr(rand(97, 122)) . chr(rand(97, 122)) . chr(rand(97, 122)); }, range(1, 12)));

        try {
            $encPass = $crypto->encrypt($newPass);
            $encFrasa = $crypto->encrypt($newFrasa);

            $update = $pdo->prepare("UPDATE dompet SET 
                kata_sandi_dompet = ?,
                sandi_iv = ?,
                sandi_auth_tag = ?,
                frasa_pemulihan = ?,
                frasa_iv = ?,
                frasa_auth_tag = ?
                WHERE id = ?");

            $update->execute([
                base64_decode($encPass['ciphertext']),
                base64_decode($encPass['iv']),
                base64_decode($encPass['tag']),
                base64_decode($encFrasa['ciphertext']),
                base64_decode($encFrasa['iv']),
                base64_decode($encFrasa['tag']),
                $wallet['id']
            ]);

            echo '<div class="success">
                <strong>✅ #' . $wallet['id'] . ' - ' . htmlspecialchars($wallet['nama_dompet']) . '</strong><br>
                Password: <code>' . $newPass . '</code><br>
                Frasa: <code>' . $newFrasa . '</code>
            </div>';
            $success++;

        } catch (Exception $e) {
            echo '<div class="error">❌ #' . $wallet['id'] . ' Gagal: ' . $e->getMessage() . '</div>';
            $failed++;
        }
    }

    echo '<div style="margin-top: 20px; padding: 20px; background: #ecf0f1; border-radius: 10px;">';
    echo '<h4>Ringkasan:</h4>';
    echo '<p>Berhasil: ' . $success . ' | Gagal: ' . $failed . '</p>';
    echo '<a href="?key=' . $expectedKey . '" class="btn btn-primary">← Kembali</a>';
    echo '</div>';
}

// Mode 3: Edit Individual
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM dompet WHERE id = ?");
    $stmt->execute([$id]);
    $wallet = $stmt->fetch();

    if ($wallet) {
        echo '<h3>Edit Dompet: ' . htmlspecialchars($wallet['nama_dompet']) . '</h3>';

        $currentPass = '';
        $currentFrasa = '';

        if ($wallet['Jenis_Dompet'] === 'Pribadi') {
            $testPass = $crypto->decrypt($wallet['kata_sandi_dompet'], $wallet['sandi_iv'], $wallet['sandi_auth_tag']);
            $currentPass = ($testPass === false) ? '[TIDAK BISA DIDEKRIPSI - DATA CORRUPT]' : $testPass;

            $testFrasa = $crypto->decrypt($wallet['frasa_pemulihan'], $wallet['frasa_iv'], $wallet['frasa_auth_tag']);
            $currentFrasa = ($testFrasa === false) ? '[TIDAK BISA DIDEKRIPSI - DATA CORRUPT]' : $testFrasa;
        }

        echo '<form method="POST">';
        echo '<input type="hidden" name="action" value="update_single">';
        echo '<input type="hidden" name="wallet_id" value="' . $id . '">';

        echo '<div class="form-group">';
        echo '<label>Password Saat Ini:</label>';
        echo '<input type="text" value="' . htmlspecialchars($currentPass) . '" readonly style="background: #ecf0f1;">';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label>Password Baru:</label>';
        echo '<input type="text" name="new_password" required placeholder="Masukkan password baru">';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label>Frasa Pemulihan Saat Ini:</label>';
        echo '<textarea readonly style="background: #ecf0f1; width: 100%; padding: 10px; height: 60px;">' . htmlspecialchars($currentFrasa) . '</textarea>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label>Frasa Pemulihan Baru (12 kata dipisah spasi):</label>';
        echo '<input type="text" name="new_frasa" required placeholder="word1 word2 word3 word4 word5 word6 word7 word8 word9 word10 word11 word12">';
        echo '</div>';

        echo '<button type="submit" class="btn btn-success">💾 Simpan Perubahan</button>';
        echo '<a href="?key=' . $expectedKey . '" class="btn btn-secondary">Batal</a>';
        echo '</form>';
    }
}

// Mode 4: Update Single
if (isset($_POST['action']) && $_POST['action'] === 'update_single') {
    $id = intval($_POST['wallet_id']);
    $newPass = $_POST['new_password'];
    $newFrasa = $_POST['new_frasa'];

    try {
        $encPass = $crypto->encrypt($newPass);
        $encFrasa = $crypto->encrypt($newFrasa);

        $update = $pdo->prepare("UPDATE dompet SET 
            kata_sandi_dompet = ?,
            sandi_iv = ?,
            sandi_auth_tag = ?,
            frasa_pemulihan = ?,
            frasa_iv = ?,
            frasa_auth_tag = ?
            WHERE id = ?");

        $update->execute([
            base64_decode($encPass['ciphertext']),
            base64_decode($encPass['iv']),
            base64_decode($encPass['tag']),
            base64_decode($encFrasa['ciphertext']),
            base64_decode($encFrasa['iv']),
            base64_decode($encFrasa['tag']),
            $id
        ]);

        echo '<div class="success">✅ Data berhasil diupdate!</div>';
        echo '<p>Password baru: <strong>' . htmlspecialchars($newPass) . '</strong></p>';
        echo '<p>Frasa baru: <strong>' . htmlspecialchars($newFrasa) . '</strong></p>';
        echo '<a href="?key=' . $expectedKey . '" class="btn btn-primary">← Kembali ke Daftar</a>';

    } catch (Exception $e) {
        echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
        echo '<a href="?key=' . $expectedKey . '&edit=' . $id . '" class="btn btn-primary">← Coba Lagi</a>';
    }
}

echo '</div></body></html>';
?>