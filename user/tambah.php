<?php
$title = 'Tambah User Baru - Luxera Dompet Manager';
require_once '../config/database.php';
require_once '../includes/header.php';
cekLogin();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username sudah terdaftar!';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $hash]);
                header("Location: index.php?msg=added");
                exit;
            }
        } catch(PDOException $e) {
            $error = 'Gagal menambah user: ' . $e->getMessage();
        }
    }
}
?>
<style>
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; }
.form-group input { width: 100%; padding: 12px 15px; border: 2px solid #e0e6ed; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; box-sizing: border-box; }
.form-group input:focus { outline: none; border-color: #3498db; box-shadow: 0 0 0 3px rgba(52,152,219,0.1); }
.btn-back { display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: #95a5a6; color: white; text-decoration: none; border-radius: 5px; }
@media (max-width: 480px) { .form-group input { font-size: 16px; padding: 15px; } }
</style>

<div style="max-width: 500px; margin: 0 auto;">
    <div class="header-section" style="justify-content: flex-start; margin-bottom: 30px;">
        <h2>➕ Tambah User Baru</h2>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required maxlength="50" placeholder="Masukkan username unik">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6" placeholder="Password minimal 6 karakter">
            </div>
            <div class="form-group">
                <label for="password_confirm">Konfirmasi Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6" placeholder="Ulangi password">
            </div>
            <button type="submit" class="btn btn-success" style="width: 100%; padding: 12px; font-size: 16px;">💾 Simpan User Baru</button>
        </form>
        <a href="index.php" class="btn-back">← Kembali ke Daftar User</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
