<?php

if (!isset($pdo)) {

    require_once __DIR__ . '/../config/database.php';

}

ob_start();

?>



<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $title ?? 'Luxera Dompet Manager' ?></title>

    <link rel="icon" href="/luxeradompet/assets/img/coin.png" type="image/png">

    <link rel="stylesheet" href="assets/css/responsive.css">



    <style>

        * { margin: 0; padding: 0; box-sizing: border-box; }



        body {

            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

            background: #f0f2f5;

            line-height: 1.6;

        }

        

        /* ============================================

           NAVBAR STYLES - DESKTOP & MOBILE

           ============================================ */

        .navbar {

            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);

            color: white;

            padding: 15px 30px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            box-shadow: 0 2px 10px rgba(0,0,0,0.1);

            position: relative;

        }

        

        .navbar h1 { 

            font-size: 22px; 

            display: flex;

            align-items: center;

            gap: 10px;

        }

        

        /* Hamburger Menu Button - Hidden on Desktop */

        .menu-toggle {

            display: none;

            background: none;

            border: none;

            color: white;

            font-size: 24px;

            cursor: pointer;

            padding: 5px 10px;

            border-radius: 5px;

            transition: background 0.3s;

        }

        

        .menu-toggle:hover {

            background: rgba(255,255,255,0.2);

        }

        

        .nav-links {

            display: flex;

            gap: 10px;

            align-items: center;

        }

        

        .nav-links a {

            color: white;

            text-decoration: none;

            padding: 8px 16px;

            background: rgba(255,255,255,0.1);

            border-radius: 5px;

            transition: all 0.3s;

            font-size: 14px;

            white-space: nowrap;

        }

        

        .nav-links a:hover { 

            background: rgba(255,255,255,0.2);

            transform: translateY(-2px);

        }

        

        .nav-links a.active {

            background: #27ae60;

        }

        

        .nav-links a.logout {

            background: #e74c3c;

        }

        

        .nav-links a.logout:hover {

            background: #c0392b;

        }



        /* ============================================

           MOBILE RESPONSIVE - HAMBURGER MENU

           ============================================ */

        @media (max-width: 768px) {

            .navbar {

                padding: 15px 20px;

                flex-wrap: wrap;

            }

            

            .navbar h1 {

                font-size: 18px;

            }

            

            /* Show hamburger menu */

            .menu-toggle {

                display: block;

            }

            

            /* Hide nav links by default on mobile */

            .nav-links {

                display: none;

                width: 100%;

                flex-direction: column;

                gap: 8px;

                margin-top: 15px;

                padding-top: 15px;

                border-top: 1px solid rgba(255,255,255,0.2);

            }

            

            /* Show nav links when active */

            .nav-links.active {

                display: flex;

            }

            

            .nav-links a {

                width: 100%;

                text-align: center;

                padding: 12px 16px;

                font-size: 14px;

            }

        }



        @media (max-width: 480px) {

            .navbar {

                padding: 12px 15px;

            }

            

            .navbar h1 {

                font-size: 16px;

            }

            

            .menu-toggle {

                font-size: 22px;

                padding: 4px 8px;

            }

            

            .nav-links a {

                padding: 10px 14px;

                font-size: 13px;

            }

        }



@media (max-width: 320px) {

            .navbar h1 {

                font-size: 14px;

            }

            

            .menu-toggle {

                font-size: 20px;

            }

        }



        /* ============================================

           TABLET PORTRAIT 12-INCH (769px - 1024px)

           ============================================ */

        @media (min-width: 769px) and (max-width: 1024px) {

            .navbar {

                padding: 12px 25px;

            }

            

            .navbar h1 {

                font-size: 20px;

            }

            

            .navbar h1 img:first-child {

                width: 28px;

                height: 28px;

            }

            

            .navbar h1 img:last-child {

                width: 70px;

                height: auto;

            }

            

            .nav-links {

                gap: 8px;

            }

            

            .nav-links a {

                padding: 6px 12px;

                font-size: 13px;

            }

            

            .container {

                padding: 0 18px;

                margin: 25px auto;

            }

        }



        /* ============================================

           CONTAINER & GENERAL STYLES

           ============================================ */

        .container {

            max-width: 1200px;

            margin: 30px auto;

            padding: 0 20px;

            min-height: calc(100vh - 200px);

        }

        

        @media (max-width: 768px) {

            .container {

                margin: 20px auto;

                padding: 0 15px;

            }

        }

        

        @media (max-width: 480px) {

            .container {

                margin: 15px auto;

                padding: 0 10px;

            }

        }

        

        .card {

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow: 0 2px 10px rgba(0,0,0,0.1);

            margin-bottom: 20px;

            max-width: 100%;

        }

        

        @media (max-width: 480px) {

            .card {

                padding: 15px;

            }

        }

        

        .btn {

            display: inline-block;

            padding: 10px 20px;

            border-radius: 5px;

            text-decoration: none;

            border: none;

            cursor: pointer;

            font-size: 14px;

            transition: all 0.3s;

        }

        

        @media (max-width: 480px) {

            .btn {

                padding: 8px 16px;

                font-size: 13px;

            }

        }

        

        .btn-primary { background: #3498db; color: white; }

        .btn-primary:hover { background: #2980b9; }

        .btn-success { background: #27ae60; color: white; }

        .btn-success:hover { background: #229954; }

        .btn-warning { background: #f39c12; color: white; }

        .btn-warning:hover { background: #e67e22; }

        .btn-danger { background: #e74c3c; color: white; }

        .btn-danger:hover { background: #c0392b; }

        .btn-secondary { background: #95a5a6; color: white; }

        .btn-secondary:hover { background: #7f8c8d; }

        

        .alert {

            padding: 15px;

            border-radius: 5px;

            margin-bottom: 20px;

        }

        

        @media (max-width: 480px) {

            .alert {

                padding: 12px;

                font-size: 13px;

            }

        }

        

        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }

        

        table {

            width: 100%;

            border-collapse: collapse;

            margin-top: 15px;

        }

        th, td {

            padding: 12px;

            text-align: left;

            border-bottom: 1px solid #ddd;

        }

        th {

            background: #34495e;

            color: white;

            font-weight: 600;

        }

        tr:hover { background: #f8f9fa; }

        .badge {

            padding: 4px 10px;

            border-radius: 12px;

            font-size: 12px;

            font-weight: 600;

        }

        .badge-primary { background: #3498db; color: white; }

        .badge-success { background: #27ae60; color: white; }

        .badge-warning { background: #f39c12; color: white; }

        .text-right { text-align: right; }

        .mb-3 { margin-bottom: 15px; }

        .d-flex { display: flex; }

        .justify-between { justify-content: space-between; }

        .align-center { align-items: center; }

        .gap-2 { gap: 10px; }

    </style>

</head>

<body>

    <div class="navbar">

        <h1><img src="/luxeradompet/assets/img/coin.png" alt="Coin" style="width: 32px; height: 32px; margin-right: 8px;"><img src="/luxeradompet/assets/img/xeralogo.png" alt="Xera" style="width: 80px; height: auto; margin-left: 8px;">LUXERA DOMPET MANAGER</h1>

        <button class="menu-toggle" onclick="toggleMenu()" aria-label="Toggle Menu">☰</button>

        <div class="nav-links" id="navLinks">

            <a href="/luxeradompet/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>

            <a href="/luxeradompet/dompet/index.php" class="<?= strpos($_SERVER['PHP_SELF'], '/dompet/') !== false ? 'active' : '' ?>">Dompet</a>

            <a href="/luxeradompet/xera_stacking/index.php" class="<?= strpos($_SERVER['PHP_SELF'], '/xera_stacking/') !== false ? 'active' : '' ?>">Stacking</a>

            <a href="/luxeradompet/airdrop/index.php" class="<?= strpos($_SERVER['PHP_SELF'], '/airdrop/') !== false ? 'active' : '' ?>">Air Drop</a>

            <a href="/luxeradompet/penarikan/index.php" class="<?= strpos($_SERVER['PHP_SELF'], '/penarikan/') !== false ? 'active' : '' ?>">Penarikan</a>

            <a href="/luxeradompet/user/index.php" class="<?= strpos($_SERVER['PHP_SELF'], '/user/') !== false ? 'active' : '' ?>">User</a>

            <a href="/luxeradompet/logout.php" class="logout">Logout (<?= $_SESSION['username'] ?? 'Guest' ?>)</a>

        </div>

    </div>

    

    <script>

        function toggleMenu() {

            const navLinks = document.getElementById('navLinks');

            navLinks.classList.toggle('active');

        }

        

        // Close menu when clicking on a link

        document.querySelectorAll('.nav-links a').forEach(link => {

            link.addEventListener('click', () => {

                document.getElementById('navLinks').classList.remove('active');

            });

        });

        

        // Close menu when clicking outside

        document.addEventListener('click', (e) => {

            const navbar = document.querySelector('.navbar');

            const navLinks = document.getElementById('navLinks');

            if (!navbar.contains(e.target)) {

                navLinks.classList.remove('active');

            }

        });

    </script>

    

    <div class="container">
