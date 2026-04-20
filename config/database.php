<?php

session_start();

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}



define('DB_HOST', '127.0.0.1');

define('DB_USER', 'root');

define('DB_PASS', 'root'); // Sesuaikan dengan password MySQL Anda

define('DB_NAME', 'crypto_wallet_backup');



// Koneksi Database

try {

    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch(PDOException $e) {

    die("Koneksi gagal: " . $e->getMessage());

}



// Fungsi helper

function rupiah($angka) {

    return "Rp " . number_format($angka, 2, ',', '.');

}



function formatKoin($angka) {

    return number_format($angka, 2, ',', '.');

}



// Cek login

function cekLogin() {

    if (!isset($_SESSION['user_id'])) {

        header("Location: /luxeradompet/login.php");

        exit;

    }

}

?>
