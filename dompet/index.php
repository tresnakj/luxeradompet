<?php

$title = 'Daftar Dompet - Luxera Dompet Manager';

require_once '../includes/header.php';

?>

<link rel="stylesheet" href="responsive1.css">

<?php

cekLogin();



// Pagination - Limit selector (5, 10, 15, 20)

$allowed_limits = [5, 10, 15, 20];

$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowed_limits) ? (int)$_GET['limit'] : 10;

$page = $_GET['page'] ?? 1;

$offset = ($page - 1) * $limit;



// Search

$search = $_GET['search'] ?? '';

$where = '';

$params = [];



if ($search) {

    $where = "WHERE nama_dompet LIKE ? OR id_alamat_dompet LIKE ? OR kode_referal LIKE ?";

    $params = ["%$search%", "%$search%", "%$search%"];

}



// Total records

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM dompet $where");

$stmt->execute($params);

$total = $stmt->fetch()['total'];

$total_pages = ceil($total / $limit);



// Data dompet dengan statistik - URUTAN DARI YANG PERTAMA DITAMBAHKAN

$sql = "SELECT d.*, 

    (SELECT SUM(jumlah_invest_rp) FROM xera_stacking WHERE id_alamat_dompet = d.id_alamat_dompet) as total_investasi,

    (SELECT SUM(jumlah_bonus) FROM air_drop WHERE id_alamat_dompet = d.id_alamat_dompet) as total_airdrop,

    (SELECT COUNT(*) FROM dompet WHERE jaringan_dari = d.kode_referal) as jumlah_downline

    FROM dompet d 

    $where 

    ORDER BY d.id ASC

    LIMIT $limit OFFSET $offset";



$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$dompet_list = $stmt->fetchAll();



// Delete handler

if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    try {

        $pdo->prepare("DELETE FROM dompet WHERE id = ?")->execute([$id]);

        header("Location: index.php?msg=deleted");

        exit;

    } catch(PDOException $e) {

        $error = "Gagal menghapus: " . $e->getMessage();

    }

}



$msg = $_GET['msg'] ?? '';

?>

<style>

    .header-section {

        display: flex;

        justify-content: space-between;

        align-items: center;

        margin-bottom: 25px;

        padding-bottom: 15px;

        border-bottom: 2px solid #e0e6ed;

    }

    .header-section h2 {

        margin: 0;

        color: #2c3e50;

        font-size: 24px;

    }

    .search-box {

        display: flex;

        gap: 10px;

        margin-bottom: 20px;

    }

    .search-box input {

        flex: 1;

        max-width: 400px;

        padding: 10px 15px;

        border: 2px solid #e0e6ed;

        border-radius: 8px;

        font-size: 14px;

    }

    .search-box input:focus {

        outline: none;

        border-color: #3498db;

    }



    /* ============================================

       DUAL SCROLLBAR CONTAINER

       Scrollbar atas dan bawah

       ============================================ */



    .dual-scroll-wrapper {

        position: relative;

        margin-top: 15px;

    }



    /* Scrollbar Atas */

    .scroll-top-container {

        overflow-x: auto;

        overflow-y: hidden;

        height: 20px;

        margin-bottom: 5px;

        scrollbar-width: thin;

        scrollbar-color: #95a5a6 #f1f1f1;

    }



    .scroll-top-container::-webkit-scrollbar {

        height: 12px;

    }



    .scroll-top-container::-webkit-scrollbar-track {

        background: #f1f1f1;

        border-radius: 6px;

    }



    .scroll-top-container::-webkit-scrollbar-thumb {

        background: #95a5a6;

        border-radius: 6px;

    }



    .scroll-top-container::-webkit-scrollbar-thumb:hover {

        background: #7f8c8d;

    }



    .scroll-top-content {

        height: 1px;

    }



    /* Table Container dengan scrollbar bawah */

    .table-container {

        overflow-x: auto !important;

        -webkit-overflow-scrolling: touch !important;

        scrollbar-width: thin;

        scrollbar-color: #95a5a6 #f1f1f1;

        border-radius: 10px;

        box-shadow: 0 2px 8px rgba(0,0,0,0.1);

    }



    .table-container::-webkit-scrollbar {

        height: 12px;

    }



    .table-container::-webkit-scrollbar-track {

        background: #f1f1f1;

        border-radius: 6px;

    }



    .table-container::-webkit-scrollbar-thumb {

        background: #95a5a6;

        border-radius: 6px;

    }



    .table-container::-webkit-scrollbar-thumb:hover {

        background: #7f8c8d;

    }



    /* FORCE TABLE STRUCTURE - ANTI CARD */

    .table-container table,

    .table-container .data-table {

        display: table !important;

        min-width: 1000px;

        width: 100%;

        border-collapse: separate !important;

        border-spacing: 0;

    }



    /* FORCE THEAD ALWAYS VISIBLE */

    .data-table thead {

        display: table-header-group !important;

        visibility: visible !important;

        opacity: 1 !important;

        position: static !important;

        background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%) !important;

        color: white !important;

    }



    .data-table thead tr {

        display: table-row !important;

        visibility: visible !important;

        opacity: 1 !important;

    }



    .data-table thead th {

        display: table-cell !important;

        visibility: visible !important;

        opacity: 1 !important;

        position: static !important;

        left: auto !important;

        top: auto !important;

    }



    .data-table tbody,

    .data-table tfoot {

        display: table-row-group !important;

    }



    .data-table tr {

        display: table-row !important;

    }



    .data-table th,

    .data-table td {

        display: table-cell !important;

    }



    /* Hapus data-label yang membuat card layout */

    .data-table td[data-label]:before {

        content: none !important;

        display: none !important;

    }



    .data-table {

        width: 100%;

        font-size: 13px;

    }

    .data-table th {

        padding: 14px 12px;

        text-align: left;

        font-weight: 600;

        text-transform: uppercase;

        font-size: 11px;

        letter-spacing: 0.5px;

        border-bottom: 2px solid #2c3e50;

        white-space: nowrap;

    }

    .data-table th:first-child {

        border-radius: 10px 0 0 0;

        text-align: center;

        width: 60px;

    }

    .data-table th:last-child {

        border-radius: 0 10px 0 0;

        text-align: center;

        width: 180px;

    }

    .data-table td {

        padding: 12px;

        border-bottom: 1px solid #ecf0f1;

        vertical-align: middle;

        white-space: nowrap;

    }

    .data-table tbody tr:hover {

        background: #f8f9fa;

    }

    .data-table tbody tr:last-child td:first-child {

        border-radius: 0 0 0 10px;

    }

    .data-table tbody tr:last-child td:last-child {

        border-radius: 0 0 10px 0;

    }



    /* Cell Content Styling */

    .urutan-badge {

        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

        color: white;

        width: 36px;

        height: 36px;

        border-radius: 50%;

        display: inline-flex;

        align-items: center;

        justify-content: center;

        font-weight: bold;

        font-size: 14px;

        box-shadow: 0 2px 4px rgba(0,0,0,0.2);

    }

    .urutan-badge.old {

        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);

    }

    .urutan-badge.new {

        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);

    }



    .wallet-name {

        font-weight: 600;

        color: #2c3e50;

        font-size: 14px;

        margin-bottom: 4px;

    }

    .wallet-badge {

        display: inline-block;

        padding: 3px 8px;

        border-radius: 4px;

        font-size: 10px;

        font-weight: 600;

        text-transform: uppercase;

    }

    .wallet-badge.utama {

        background: #d4edda;

        color: #155724;

    }

    .wallet-badge.child {

        background: #fff3cd;

        color: #856404;

    }

    .wallet-date {

        font-size: 11px;

        color: #7f8c8d;

        margin-top: 4px;

    }



    .wallet-address {

        font-family: 'Courier New', monospace;

        font-size: 12px;

        color: #555;

        background: #f8f9fa;

        padding: 6px 10px;

        border-radius: 5px;

        display: inline-block;

        max-width: 150px;

        overflow: hidden;

        text-overflow: ellipsis;

        white-space: nowrap;

    }



    .ref-code {

        background: #e3f2fd;

        color: #1565c0;

        padding: 5px 10px;

        border-radius: 15px;

        font-size: 12px;

        font-weight: 600;

        font-family: monospace;

        display: inline-block;

    }

    .ref-parent {

        background: #fff3e0;

        color: #e65100;

        padding: 4px 8px;

        border-radius: 4px;

        font-size: 11px;

        margin-top: 4px;

        display: inline-block;

    }

    .ref-parent:empty {

        display: none;

    }



    .amount-cell {

        font-weight: 600;

        font-size: 13px;

        white-space: nowrap;

    }

    .amount-invest {

        color: #27ae60;

    }

    .amount-airdrop {

        color: #3498db;

    }



    .downline-count {

        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

        color: white;

        width: 28px;

        height: 28px;

        border-radius: 50%;

        display: inline-flex;

        align-items: center;

        justify-content: center;

        font-weight: bold;

        font-size: 12px;

    }

    .downline-zero {

        color: #95a5a6;

        font-size: 12px;

    }



    .action-buttons {

        display: flex;

        gap: 5px;

        justify-content: center;

    }

    .btn-small {

        padding: 6px 12px;

        font-size: 11px;

        border-radius: 5px;

        text-decoration: none;

        color: white;

        font-weight: 500;

        transition: all 0.2s;

        white-space: nowrap;

        border: none;

        cursor: pointer;

        display: inline-block;

    }

    .btn-small:hover {

        transform: translateY(-1px);

        box-shadow: 0 2px 4px rgba(0,0,0,0.2);

    }

    .btn-detail { background: #3498db; }

    .btn-edit { background: #f39c12; }

    .btn-delete { background: #e74c3c; }



    .empty-state {

        text-align: center;

        padding: 50px 20px;

        color: #7f8c8d;

    }

    .empty-state-icon {

        font-size: 48px;

        margin-bottom: 15px;

    }



    /* ============================================

       LIMIT SELECTOR & TABLE INFO

       ============================================ */

    .table-controls {

        display: flex;

        justify-content: space-between;

        align-items: center;

        margin-bottom: 15px;

        flex-wrap: wrap;

        gap: 15px;

    }



    .limit-selector {

        display: flex;

        align-items: center;

        gap: 10px;

    }



    .limit-selector label {

        font-size: 14px;

        color: #555;

        font-weight: 500;

    }



    .limit-selector select {

        padding: 8px 15px;

        border: 2px solid #e0e6ed;

        border-radius: 8px;

        font-size: 14px;

        background: white;

        cursor: pointer;

        transition: all 0.2s;

        min-width: 80px;

    }



    .limit-selector select:hover,

    .limit-selector select:focus {

        border-color: #3498db;

        outline: none;

    }



    .table-info {

        font-size: 14px;

        color: #7f8c8d;

    }



    .table-info strong {

        color: #2c3e50;

    }



    .pagination {

        display: flex;

        justify-content: center;

        gap: 5px;

        margin-top: 25px;

        flex-wrap: wrap;

    }

    .pagination a, .pagination span {

        padding: 8px 14px;

        border-radius: 6px;

        text-decoration: none;

        font-size: 13px;

    }

    .pagination .current {

        background: #3498db;

        color: white;

        font-weight: 600;

    }

    .pagination a {

        background: #ecf0f1;

        color: #555;

    }

    .pagination a:hover {

        background: #3498db;

        color: white;

    }



    /* ============================================

       MODAL PASSWORD - SAMA SEPERTI DETAIL.PHP

       ============================================ */

    .modal-password {

        display: none;

        position: fixed;

        z-index: 10000;

        left: 0;

        top: 0;

        width: 100%;

        height: 100%;

        background-color: rgba(0,0,0,0.6);

        backdrop-filter: blur(4px);

    }



    .modal-content-password {

        background: white;

        margin: 10% auto;

        padding: 0;

        border-radius: 16px;

        width: 90%;

        max-width: 450px;

        box-shadow: 0 20px 60px rgba(0,0,0,0.3);

        animation: modalSlideIn 0.3s ease;

        overflow: hidden;

    }



    @keyframes modalSlideIn {

        from { opacity: 0; transform: translateY(-50px); }

        to { opacity: 1; transform: translateY(0); }

    }



    .modal-header-password {

        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

        color: white;

        padding: 30px;

        text-align: center;

    }



    .modal-icon {

        font-size: 48px;

        margin-bottom: 15px;

    }



    .modal-header-password h3 {

        margin: 0 0 10px 0;

        font-size: 22px;

    }



    .modal-header-password p {

        margin: 0;

        opacity: 0.9;

        font-size: 14px;

    }



    .modal-body-password {

        padding: 30px;

    }



    .modal-body-password input {

        width: 100%;

        padding: 15px;

        border: 2px solid #e0e6ed;

        border-radius: 10px;

        font-size: 16px;

        text-align: center;

        margin-bottom: 20px;

        transition: all 0.3s;

    }



    .modal-body-password input:focus {

        outline: none;

        border-color: #667eea;

        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);

    }



    .modal-error {

        display: none;

        background: #fee;

        color: #c0392b;

        padding: 12px;

        border-radius: 8px;

        margin-bottom: 20px;

        text-align: center;

        font-size: 14px;

    }



    .modal-buttons {

        display: flex;

        gap: 10px;

    }



    .modal-buttons button {

        flex: 1;

        padding: 14px 20px;

        border: none;

        border-radius: 10px;

        font-size: 14px;

        font-weight: 600;

        cursor: pointer;

        transition: all 0.3s;

    }



    .btn-modal-cancel {

        background: #ecf0f1;

        color: #555;

    }



    .btn-modal-cancel:hover {

        background: #bdc3c7;

    }



    .btn-modal-unlock {

        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

        color: white;

    }



    .btn-modal-unlock:hover {

        transform: translateY(-2px);

        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);

    }



    .btn-modal-unlock:disabled {

        opacity: 0.7;

        cursor: not-allowed;

        transform: none;

    }



    /* Toast Notification */

    #toast-notification {

        position: fixed;

        bottom: 20px;

        left: 50%;

        transform: translateX(-50%);

        background: #27ae60;

        color: white;

        padding: 15px 30px;

        border-radius: 8px;

        box-shadow: 0 4px 20px rgba(0,0,0,0.2);

        z-index: 10001;

        animation: slideUp 0.3s ease;

    }



    @keyframes slideUp {

        from { transform: translate(-50%, 100px); opacity: 0; }

        to { transform: translate(-50%, 0); opacity: 1; }

    }



    @keyframes slideDown {

        from { transform: translate(-50%, 0); opacity: 1; }

        to { transform: translate(-50%, 100px); opacity: 0; }

    }



    /* ============================================

       RESPONSIVE - FORCE TABLE LAYOUT

       NEVER CHANGE TO CARD - ALWAYS HORIZONTAL SCROLL

       THEAD ALWAYS VISIBLE

       ============================================ */



    @media (max-width: 768px) {

        .header-section {

            flex-direction: column;

            gap: 15px;

            align-items: flex-start;

        }



        .search-box {

            flex-direction: column;

            width: 100%;

        }



        .search-box input {

            max-width: none;

            width: 100%;

        }



        .table-controls {

            flex-direction: column;

            align-items: flex-start;

        }



        /* Modal responsive */

        .modal-content-password {

            margin: 20% auto;

            width: 95%;

        }



        /* FORCE TABLE - ANTI CARD */

        .table-container {

            overflow-x: auto !important;

            -webkit-overflow-scrolling: touch !important;

        }



        .table-container table,

        .table-container .data-table {

            display: table !important;

            min-width: 900px;

        }



        /* FORCE THEAD VISIBLE */

        .data-table thead {

            display: table-header-group !important;

            visibility: visible !important;

            opacity: 1 !important;

            position: static !important;

        }



        .data-table thead tr {

            display: table-row !important;

            visibility: visible !important;

            opacity: 1 !important;

        }



        .data-table thead th {

            display: table-cell !important;

            visibility: visible !important;

            opacity: 1 !important;

            position: static !important;

            left: auto !important;

            top: auto !important;

            clip: auto !important;

            height: auto !important;

            width: auto !important;

            overflow: visible !important;

            padding: 10px 8px !important;

        }



        .data-table tbody {

            display: table-row-group !important;

        }



        .data-table tr {

            display: table-row !important;

            margin-bottom: 0 !important;

            border: none !important;

            border-radius: 0 !important;

            box-shadow: none !important;

        }



        .data-table td {

            display: table-cell !important;

            text-align: left !important;

            padding: 10px 8px !important;

            border-bottom: 1px solid #ecf0f1 !important;

        }



        .data-table td:before {

            content: none !important;

            display: none !important;

        }



        .data-table td[data-label]:before {

            display: none !important;

        }

    }



    /* ============================================

       480px DAN LEBIH KECIL - FORCE TABLE

       THEAD ALWAYS VISIBLE

       ============================================ */

    @media (max-width: 480px) {

        .container {

            padding: 0 10px;

        }



        .header-section h2 {

            font-size: 18px;

        }



        /* FORCE TABLE - ANTI CARD */

        .table-container {

            margin-top: 10px;

            overflow-x: auto !important;

            -webkit-overflow-scrolling: touch !important;

        }



        .table-container table,

        .table-container .data-table {

            display: table !important;

            min-width: 850px;

        }



        /* FORCE THEAD VISIBLE */

        .data-table thead {

            display: table-header-group !important;

            visibility: visible !important;

            opacity: 1 !important;

            position: static !important;

        }



        .data-table thead tr {

            display: table-row !important;

            visibility: visible !important;

            opacity: 1 !important;

        }



        .data-table thead th {

            display: table-cell !im
