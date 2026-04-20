<?php

$title = 'Daftar User - :uxera Dompet Manager';

require_once '../config/database.php';

require_once '../includes/header.php';

cekLogin();



// Pagination

$limit = 10;

$page = $_GET['page'] ?? 1;

$offset = ($page - 1) * $limit;



// Search

$search = $_GET['search'] ?? '';

$where = '';

$params = [];



if ($search) {

    $where = "WHERE username LIKE ? OR id LIKE ?";

    $params = ["%$search%", "%$search%"];

}



// Total records

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $where");

$stmt->execute($params);

$total = $stmt->fetch()['total'];

$total_pages = ceil($total / $limit);



// Data users ORDER BY id ASC

$sql = "SELECT * FROM users $where ORDER BY id ASC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$users_list = $stmt->fetchAll();



// Delete handler

if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    try {

        // Check if user is current logged in user

        if ($id == $_SESSION['user_id']) {

            $error = "Tidak bisa hapus user yang sedang login!";

        } else {

            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);

            header("Location: index.php?msg=deleted");

            exit;

        }

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

       TABLE CONTAINER - HORIZONTAL SCROLL ONLY

       FORCE TABLE LAYOUT - NEVER CARD

       ============================================ */

    

    .table-container {

        overflow-x: auto !important;

        -webkit-overflow-scrolling: touch !important;

        margin-top: 15px;

        scrollbar-width: thin;

        scrollbar-color: #95a5a6 #f1f1f1;

        border-radius: 10px;

        box-shadow: 0 2px 8px rgba(0,0,0,0.1);

    }



    /* Custom scrollbar styling */

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

    

    .wallet-name {

        font-weight: 600;

        color: #2c3e50;

        font-size: 14px;

        margin-bottom: 4px;

    }

    .status-badge {

        display: inline-block;

        padding: 3px 8px;

        border-radius: 4px;

        font-size: 11px;

        font-weight: 600;

        text-transform: uppercase;

    }

    .status-active {

        background: #d4edda;

        color: #155724;

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

    }

    .btn-small:hover {

        transform: translateY(-1px);

        box-shadow: 0 2px 4px rgba(0,0,0,0.2);

    }

    .btn-edit { background: #f39c12; }

    .btn-delete { background: #e74c3c; }

    

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

    

    /* RESPONSIVE - FORCE TABLE LAYOUT - NO CARD */

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

        .table-container {

            overflow-x: auto !important;

        }

        .data-table {

            min-width: 900px;

        }

        .data-table thead {

            display: table-header-group !important;

            visibility: visible !important;

            opacity: 1 !important;

            position: static !important;

        }

        .data-table th {

            padding: 10px 8px !important;

        }

        .data-table td {

            padding: 10px 8px !important;

        }

    }

    @media (max-width: 480px) {

        .data-table {

            min-width: 850px;

        }

        .data-table th {

            padding: 8px 6px !important;

            font-size: 10px;

        }

        .data-table td {

            padding: 8px 6px !important;

        }

        .btn-small {

            padding: 4px 8px;

            font-size: 10px;

        }

    }

    @media (max-width: 360px) {

        .data-table {

            min-width: 800px;

        }

        .data-table th {

            padding: 6px 4px !important;

            font-size: 9px;

        }

        .data-table td {

            padding: 6px 4px !important;

        }

    }

</style>



<div class="header-section">

    <h2>👤 Daftar User</h2>

    <a href="tambah.php" class="btn btn-success">+ Tambah User Baru</a>

</div>



<?php if($msg == 'deleted'): ?>

    <div class="alert alert-success">User berhasil dihapus!</div>

<?php endif; ?>

<?php if(isset($error)): ?>

    <div class="alert alert-error"><?= $error ?></div>

<?php endif; ?>



<div class="card">

    <div class="search-box">

        <form method="GET">

            <input type="text" name="search" placeholder="🔍 Cari username atau ID..." value="<?= htmlspecialchars($search) ?>">

            <button type="submit" class="btn btn-primary">Cari</button>

            <?php if($search): ?>

                <a href="index.php" class="btn btn-secondary">Reset</a>

            <?php endif; ?>

        </form>

    </div>



    <div class="table-container">

        <table class="data-table">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Username</th>

                    <th>Status</th>

                    <th>Dibuat</th>

                    <th>Aksi</th>

                </tr>

            </thead>

            <tbody>

                <?php if (empty($users_list)): ?>

                    <tr>

                        <td colspan="5" style="text-align: center; padding: 50px;">

                            <div style="font-size: 48px; margin-bottom: 15px;">👥</div>

                            <h3>Belum Ada User</h3>

                            <p>Silakan tambah user pertama</p>

                            <a href="tambah.php" class="btn btn-success" style="margin-top: 15px;">+ Tambah User</a>

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($users_list as $u): ?>

                    <tr>

                        <td><strong>#<?= $u['id'] ?></strong></td>

                        <td>

                            <div class="wallet-name"><?= htmlspecialchars($u['username']) ?></div>

                            <?php if ($u['id'] == $_SESSION['user_id']): ?>

                                <span class="status-badge status-active">⭐ Current User</span>

                            <?php endif; ?>

                        </td>

                        <td><span class="status-badge status-active">Aktif</span></td>

                        <td><?= date('d/m/Y H:i', strtotime($u['created_at'] ?? $u['id']*1000)) ?></td>

                        <td>

                            <div style="display: flex; gap: 5px;">

                                <a href="edit.php?id=<?= $u['id'] ?>" class="btn-small btn-edit" title="Edit">✏ Edit</a>

                                <a href="?delete=<?= $u['id'] ?>" class="btn-small btn-delete" 

                                   onclick="return confirm('Yakin hapus user <?= htmlspecialchars($u['username']) ?>?')" 

                                   title="Hapus">🗑 Hapus</a>

                            </div>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>



    <?php if ($total_pages > 1): ?>

    <div class="pagination">

        <?php if ($page > 1): ?>

            <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">← Prev</a>

        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>

            <?php if ($i == $page): ?>

                <span class="current"><?= $i ?></span>

            <?php else: ?>

                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>

            <?php endif; ?>

        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>

            <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Next →</a>

        <?php endif; ?>

    </div>

    <?php endif; ?>

</div>



<?php require_once '../includes/footer.php'; ?>

  
