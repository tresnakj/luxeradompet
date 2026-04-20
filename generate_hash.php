<?php
require_once 'config/database.php';

$username = 'tresnakj';
$password = '@Aptx4869';

// Generate hash bcrypt
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "<h2>Generate User Login</h2>";
echo "<pre>";
echo "Username: " . $username . "\n";
echo "Password: " . $password . "\n";
echo "Hash Bcrypt: " . $hash . "\n";
echo "</pre>";

// Hapus user lama jika ada
$stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
$stmt->execute([$username]);

// Insert user baru
$stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->execute([$username, $hash]);

echo "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-top:20px;'>";
echo "✅ User berhasil dibuat/reset!<br>";
echo "Username: <b>" . $username . "</b><br>";
echo "Password: <b>" . $password . "</b><br>";
echo "Hash: <code>" . $hash . "</code>";
echo "</div>";

echo "<br><a href='login.php' style='display:inline-block; padding:10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:5px;'>Ke Halaman Login</a>";

// Test verify
echo "<hr><h3>Test Verify:</h3>";
if (password_verify($password, $hash)) {
    echo "✅ Password verify: <b>SUKSES</b>";
} else {
    echo "❌ Password verify: <b>GAGAL</b>";
}
?>