<?php
/**
 * verify_password.php
 * Handler AJAX untuk verifikasi password dompet
 */

require_once '../includes/encryption_fixed.php';
require_once '../config/database.php';

// GANTI DENGAN KEY ANDA!
define('ENCRYPTION_KEY', '@$%$=LuxeraDaoInternational=$%$@');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$walletId = isset($_POST['wallet_id']) ? intval($_POST['wallet_id']) : 0;
$inputPassword = $_POST['password'] ?? '';

if ($action !== 'decrypt' || $walletId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$crypto = new WalletEncryption(ENCRYPTION_KEY);

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

// Jika mode rekan dan tidak ada password, langsung sukses
if ($data['Jenis_Dompet'] === 'Rekan' && empty($data['kata_sandi_dompet'])) {
    unlockWallet($walletId);
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

    // Set session unlock
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
?>