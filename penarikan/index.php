<?php

$title = 'Data Air Drop - Luxera Dompet Manager';

require_once '../includes/header.php';

?>

<link rel="stylesheet" href="responsive.css">

<?php

cekLogin();



// Pagination

$limit = 15;

$page = $_GET['page'] ?? 1;

$offset = ($page - 1) * $limit;



// Filter

$filter_wallet = $_GET['wallet'] ?? '';

$filter_tanggal = $_GET['tanggal'] ?? '';

$where = [];

$params = [];



if ($filter_wallet) {

    $where[] = "ad.id_alamat_dompet = ?";

    $params[] = $filter_wallet;

}

if ($filter_tanggal) {

    $where[] = "ad.tanggal = ?";

    $params[] = $filter_tanggal;

}



$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";



// Total records

$count_sql = "SELECT COUNT(*) as total FROM air_drop ad $where_sql";

$stmt = $pdo->prepare($count_sql);

$stmt->execute($params);

$total = $stmt->fetch()['total'];

$total_pages = ceil($total / $limit);



// Data airdrop dengan info dompet

$sql = "SELECT ad.*, d.nama_dompet, d.kode_referal

    FROM air_drop ad

    LEFT JOIN dompet d ON ad.id_alamat_dompet = d.id_alamat_dompet

    $where_sql

    ORDER BY ad.tanggal DESC, ad.created_at DESC

    LIMIT $limit OFFSET $offset";



$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$airdrop_list = $stmt->fetchAll();



// Delete handler

if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    try {

        $pdo->prepare("DELETE FROM air_drop WHERE id = ?")->execute([$id]);

        header("Location: index.php?msg=deleted" . ($filter_wallet ? "&wallet=$filter_wallet" : "") . ($filter_tanggal ? "&tanggal=$filter_tanggal" : ""));

        exit;

    } catch(PDOException $e) {

        $error = "Gagal menghapus: " . $e->getMessage();

    }

}



// Hitung total keseluruhan

$total_stats = $pdo->query("SELECT 

    COUNT(DISTINCT id_alamat_dompet) as total_wallet,

    SUM(jumlah_bonus) as total_bonus,

    COUNT(*) as total_transaksi

    FROM air_drop")->fetch();



// Hitung hari ini

$today_stats = $pdo->prepare("SELECT SUM(jumlah_bonus) as total FROM air_drop WHERE tanggal = ?");

$today_stats->execute([date('Y-m-d')]);

$today_bonus = $today_stats->fetch()['total'] ?? 0;



$msg = $_GET['msg'] ?? '';



// Daftar dompet untuk filter

$dompet_filter = $pdo->query("SELECT id_alamat_dompet, nama_dompet FROM dompet ORDER BY nama_dompet")->fetchAll();



// Helper function untuk query params

function buildQueryParams($wallet, $tanggal) {

    $params = '';

    if ($wallet) $params .= '&wallet=' . urlencode($wallet);

    if ($tanggal) $params .= '&tanggal=' . urlencode($tanggal);

    return $params;

}

?>



<style>

    .stats-grid {

        display: grid;

        grid-template-columns: repeat(4, 1fr);

        gap: 20px;

        margin-bottom: 25px;

    }

    .stat-card {

        background: white;

        padding: 20px;

        border-radius: 10px;

        box-shadow: 0 2px 10px rgba(0,0,0,0.1);

        text-align: center;

    }

    .stat-card.primary {

        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

        color: white;

    }

    .stat-card.success {

        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);

        color: white;

    }

    .stat-card.warning {

        background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%);

        color: white;

    }

    .stat-card.info {

        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);

        color: white;

    }

    .stat-card h4 {

        font-size: 13px;

        opacity: 0.9;

        margin-bottom: 10px;

        text-transform: uppercase;

    }

    .stat-card .value {

        font-size: 22px;

        font-weight: bold;

    }

    .filter-box {

        background: #f8f9fa;

        padding: 15px;

        border-radius: 8px;

        margin-bottom: 20px;

        display: flex;

        gap: 10px;

        align-items: center;

        flex-wrap: wrap;

    }

    .filter-box select, .filter-box input {

        padding: 8px 12px;

        border: 1px solid #ddd;

        border-radius: 5px;

    }

    .bonus-amount {

        color: #27ae60;

        font-weight: bold;

    }

    .date-badge {

        background: #ecf0f1;

        padding: 4px 10px;

        border-radius: 5px;

        font-size: 13px;

        display: inline-block;

    }

    .date-badge.bonus {

        background: #d4edda;

        color: #155724;

        border: 1px solid #c3e6cb;

    }

    .date-badge.input {

        background: #e3f2fd;

        color: #1565c0;

        border: 1px solid #90caf9;

    }

    .time-small {

        font-size: 11px;

        color: #7f8c8d;

    }

    .date-header {

        background: #f8f9fa;

    }

    .date-header td {

        padding: 10px 15px;

        font-weight: bold;

        color: #2c3e50;

    }

    .btn-small {

        padding: 5px 10px;

        font-size: 12px;

    }

    

    @media (max-width: 768px) {

        .stats-grid {

            grid-template-columns: 1fr;

            gap: 15px;

        }

        .filter-box {

            flex-direction: column;

            align-items: stretch;

        }

        .filter-box select,

        .filter-box input {

            max-width: none;

        }

    }

    

    @media (max-width: 480px) {

        .container {

            padding: 0 10px;

        }

        .stat-card {

            padding: 18px 15px;

        }

        .stat-card .value {

            font-size: 20px;

        }

    }

</style>



<h2>🎁 Data Air Drop (Bonus Harian)</h2>



<?php if($msg == 'deleted'): ?>

    <div class="alert alert-success">Data air drop berhasil dihapus!</div>

<?php endif; ?>

<?php if(isset($error)): ?>

    <div class="alert alert-error"><?= $error ?></div>

<?php endif; ?>



<!-- Stats -->

<div class="stats-grid">

    <div class="stat-card primary">

        <h4>Total Bonus Keseluruhan</h4>

        <div class="value"><?= formatKoin($total_stats['total_bonus'] ?? 0) ?> XERA</div>

    </div>

    <div class="stat-card success">

        <h4>Bonus Hari Ini</h4>

        <div class="value"><?= formatKoin($today_bonus) ?> XERA</div>

    </div>

    <div class="stat-card warning">

        <h4>Total Wallet</h4>

        <div class="value"><?= $total_stats['total_wallet'] ?? 0 ?> Wallet</div>

    </div>

    <div class="stat-card info">

        <h4>Total Transaksi</h4>

        <div class="value"><?= $total_stats['total_transaksi'] ?? 0 ?>x</div>

    </div>

</div>



<div class="card">

    <div class="d-flex justify-between align-center mb-3">

        <h3>Riwayat Air Drop</h3>

        <div class="d-flex justify-between align-center mb-3">

          

        <a href="/luxeradompet/penarikan/index.php" class="btn btn-danger">Withdraw</a>

        

        

        <a href="tambah.php" class="btn btn-success">+ Catat Bonus</a>

      

        </div>

    </div>



    <!-- Filter -->

    <div class="filter-box">

        <label><strong>Filter:</strong></label>

        <select onchange="updateFilter('wallet', this.value)">

            <option value="">-- Semua Dompet --</option>

            <?php foreach($dompet_filter as $d): ?>

            <option value="<?= $d['id_alamat_dompet'] ?>" <?= $filter_wallet == $d['id_alamat_dompet'] ? 'selected' : '' ?>>

                <?= htmlspecialchars($d['nama_dompet']) ?>

            </option>

            <?php endforeach; ?>

        </select>

        

        <input type="date" 

               value="<?= $filter_tanggal ?>" 

               onchange="updateFilter('tanggal', this.value)"

               max="<?= date('Y-m-d') ?>"

               placeholder="Filter tanggal bonus">

        

        <?php if($filter_wallet || $filter_tanggal): ?>

            <a href="index.php" class="btn btn-secondary">Reset</a>

        <?php endif; ?>

    </div>



    <div class="table-container">

    <table>

        <thead>

            <tr>

                <th>No</th>

                <th>Nama Dompet</th>

                <th>Tanggal Bonus</th>

                <th>Waktu Input</th>

                <th>Jumlah Bonus</th>

                <th>Aksi</th>

            </tr>

        </thead>

        <tbody>

            <?php if (empty($airdrop_list)): ?>



                <tr>

                    <td colspan="6" style="text-align: center; padding: 30px; color: #7f8c8d;">

                        Tidak ada data air drop. <a href="tambah.php">Catat bonus pertama</a>

                    </td>

                </tr>

            <?php else: ?>

                <?php 

                $current_date = '';

                foreach ($airdrop_list as $i => $a): 

                    $row_date = $a['tanggal'];

                    $show_date_header = ($row_date != $current_date && !$filter_tanggal);

                    $current_date = $row_date;

                ?>

                <?php if($show_date_header): ?>

                <tr class="date-header">

                    <td colspan="6">

                        📅 <?= date('l, d F Y', strtotime($row_date)) ?>

                    </td>

                </tr>

                <?php endif; ?>

                <tr>

                    <td data-label="No"><?= $offset + $i + 1 ?></td>

                    <td data-label="Nama Dompet">

                        <strong><?= htmlspecialchars($a['nama_dompet'] ?? 'Unknown') ?></strong>

                        <br>

                        <small style="color: #7f8c8d; font-family: monospace;">

                            <?= substr($a['id_alamat_dompet'], 0, 15) ?>...

                        </small>

                    </td>

                    <td data-label="Tanggal Bonus">

                        <span class="date-badge bonus">

                            <?= date('d/m/Y', strtotime($a['tanggal'])) ?>

                        </span>

                        <br>

                        <small class="time-small">

                            <?= date('l', strtotime($a['tanggal'])) ?>

                        </small>

                    </td>

                    <td data-label="Waktu Input">

                        <?php if ($a['created_at']): ?>

                            <span class="date-badge input">

                                <?= date('d/m/Y', strtotime($a['created_at'])) ?>

                            </span>

                            <br>

                            <small class="time-small">

                                <?= date('H:i', strtotime($a['created_at'])) ?> WIB

                            </small>

                        <?php else: ?>

                            <span style="color: #95a5a6; font-size: 12px;">-</span>

                        <?php endif; ?>

                    </td>

                    <td data-label="Jumlah Bonus" class="bonus-amount">

                        +<?= formatKoin($a['jumlah_bonus']) ?> XERA

                    </td>

                    <td data-label="Aksi">

                        <a href="?delete=<?= $a['id'] ?>" 

                           class="btn btn-danger btn-small" 

                           onclick="return confirm('Yakin ingin menghapus data air drop ini?')">

                            Hapus

                        </a>

                    </td>

                </tr>



                <?php endforeach; ?>

            <?php endif; ?>

        </tbody>

    </table>



    <!-- Pagination -->

    <?php if ($total_pages > 1): ?>

    <div style="margin-top: 20px; display: flex; justify-content: center; gap: 5px;">

        <?php if ($page > 1): ?>

            <a href="?page=<?= $page-1 ?><?= buildQueryParams($filter_wallet, $filter_tanggal) ?>" class="btn btn-secondary">← Prev</a>

        <?php endif; ?>

        

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>

            <?php if ($i == $page): ?>

                <span class="btn btn-primary"><?= $i ?></span>

            <?php else: ?>

                <a href="?page=<?= $i ?><?= buildQueryParams($filter_wallet, $filter_tanggal) ?>" class="btn btn-secondary"><?= $i ?></a>

            <?php endif; ?>

        <?php endfor; ?>

        

        <?php if ($page < $total_pages): ?>

            <a href="?page=<?= $page+1 ?><?= buildQueryParams($filter_wallet, $filter_tanggal) ?>" class="btn btn-secondary">Next →</a>

        <?php endif; ?>

    </div>

    <?php endif; ?>

</div>



<script>

function updateFilter(type, value) {

    const url = new URL(window.location.href);

    if (value) {

        url.searchParams.set(type, value);

    } else {

        url.searchParams.delete(type);

    }

    url.searchParams.delete('page');

    window.location.href = url.toString();

}

</script>



<?php require_once '../includes/footer.php'; ?>
