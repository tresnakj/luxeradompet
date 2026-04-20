<?php
$title = 'Edit User - Luxera Dompet Manager';
require_once '../config/database.php';
require_once '../includes/header.php';
cekLogin();

$id = $_GET['id'] ?? 0;
if (!$id || $id != (int)$id) {
    header("Location: index.php?error=no_id");
    exit;
}

$user = null;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: index.php?error=user_not_found");
    exit;
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username)) {
        $error = 'Username wajib diisi!';
    } else {
        try {
            // Check username unique (except self)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                $error = 'Username sudah digunakan user lain!';
            } else {
                // Update username always, password only if provided
                if (!empty($password)) {
                    $password_confirm = $_POST['password_confirm'] ?? '';
                    if (strlen($password) < 6) {
                        $error = 'Password minimal 6 karakter!';
                    } elseif ($password !== $password_confirm) {
                        $error = 'Konfirmasi password tidak cocok!';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                        $stmt->execute([$username, $hash, $id]);
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                    $stmt->execute([$username, $id]);
                }
                if (empty($error)) {
                    header("Location: index.php?msg=updated");
                    exit;
                }
            }
        } catch(PDOException $e) {
            $error = 'Gagal update user: ' . $e->getMessage();
        }
    }
}
?>
<style>
/* Same as tambah.php */
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; }
.form-group input { width: 100%; padding: 12px 15px; border: 2px solid #e0e6ed; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; box-sizing: border-box; }
.form-group input:focus { outline: none; border-color: #3498db; box-shadow: 0 0 0 3px rgba(52,152,219,0.1); }
.btn-back { display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: #95a5a6; color: white; text-decoration: none; border-radius: 5px; }
.user-info { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
@media (max-width: 480px) { .form-group input { font-size: 16px; padding: 15px; } }
</style>

<div style="max-width: 500px; margin: 0 auto;">
    <div class="header-section" style="justify-content: flex-start; margin-bottom: 30px;">
        <h2>✏ Edit User: <?= htmlspecialchars($user['username']) ?></h2>
    </div>

    <div class="card">
        <div class="user-info">
            <strong>ID:</strong> #<?= $user['id'] ?> | 
            <strong>Username Saat Ini:</strong> <?= htmlspecialchars($user['username']) ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username Baru</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? $user['username']) ?>" required maxlength="50">
                <small style="color: #7f8c8d;">Kosongkan password jika tidak ingin ganti</small>
            </div>
            <div class="form-group">
                <label for="password">Password Baru (kosongkan jika tidak ganti)</label>
                <input type="password" id="password" name="password" minlength="6" placeholder="Password baru minimal 6 karakter">
            </div>
            <div class="form-group">
                <label for="password_confirm">Konfirmasi Password Baru</label>
                <input type="password" id="password_confirm" name="password_confirm" minlength="6" placeholder="Ulangi password baru">
            </div>
            <button type="submit" class="btn btn-warning" style="width: 100%; padding: 12px; font-size: 16px; background: #f39c12;">💾 Update User</button>
        </form>
        <a href="index.php" class="btn-back">← Kembali ke Daftar User</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
