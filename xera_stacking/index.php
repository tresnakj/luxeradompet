<?php
$title = 'Data Xera Stacking - Luxera Dompet Manager';
require_once '../includes/header.php';
cekLogin();

// Pagination
$limit = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;

// Filter
$filter_wallet = $_GET['wallet'] ?? '';
$where = '';
$params = [];

if ($filter_wallet) {
    $where = "WHERE xs.id_alamat_dompet = ?";
    $params = [$filter_wallet];
}

// Total records
$count_sql = "SELECT COUNT(*) as total FROM xera_stacking xs $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// Data stacking dengan info dompet
$sql = "SELECT xs.*, d.nama_dompet, d.kode_referal,
    (SELECT SUM(jumlah_bonus) FROM air_drop WHERE id_alamat_dompet = xs.id_alamat_dompet) as total_airdrop
    FROM xera_stacking xs
    LEFT JOIN dompet d ON xs.id_alamat_dompet = d.id_alamat_dompet
    $where
    ORDER BY xs.created_at DESC
    LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stacking_list = $stmt->fetchAll();

// Delete handler
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM xera_stacking WHERE id = ?")->execute([$id]);
        header("Location: index.php?msg=deleted" . ($filter_wallet ? "&wallet=$filter_wallet" : ""));
        exit;
    } catch(PDOException $e) {
        $error = "Gagal menghapus: " . $e->getMessage();
    }
}

// Hitung total keseluruhan
$total_stats = $pdo->query("SELECT 
    SUM(xera_koin) as total_koin,
    SUM(stacking_xera) as total_stacking,
    SUM(jumlah_invest_rp) as total_invest
    FROM xera_stacking")->fetch();

$msg = $_GET['msg'] ?? '';

// Daftar dompet untuk filter
$dompet_filter = $pdo->query("SELECT id_alamat_dompet, nama_dompet FROM dompet ORDER BY nama_dompet")->fetchAll();
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    .btn-small {
        padding: 5px 10px;
        font-size: 12px;
    }
    .btn-primary {
        background: #3498db;
        color: white;
    }
    .btn-primary:hover {
        background: #2980b9;
    }
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
    }
    @media (max-width: 480px) {
        .container {
            padding: 0 10px;
        }
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }
    .stat-card h4 {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 10px;
    }
    .stat-card .value {
        font-size: 24px;
        font-weight: bold;
    }
    .filter-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .filter-box select {
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        flex: 1;
        min-width: 220px;
    }
    .duration-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .duration-30 { background: #e3f2fd; color: #1976d2; }
    .duration-90 { background: #f3e5f5; color: #7b1fa2; }
    .duration-120 { background: #e8f5e9; color: #388e3c; }
    .duration-180 { background: #fff3e0; color: #f57c00; }
    .duration-360 { background: #ffebee; color: #d32f2f; }
    .aksi-flex {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
</style>

<h2>📊 Data Xera Stacking</h2>

<?php if($msg == 'deleted'): ?>
    <div class="alert alert-success">Data stacking berhasil dihapus!</div>
<?php endif; ?>
<?php if(isset($error)): ?>
    <div class="alert alert-error"><?= $error ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <h4>Total Xera Koin</h4>
        <div class="value"><?= formatKoin($total_stats['total_koin'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <h4>Total Stacking</h4>
        <div class="value"><?= formatKoin($total_stats['total_stacking'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <h4>Total Investasi</h4>
        <div class="value"><?= rupiah($total_stats['total_invest'] ?? 0) ?></div>
    </div>
</div>

<div class="card">
    <div class="d-flex justify-between align-center mb-3">
        <h3>Riwayat Stacking</h3>
        <a href="tambah.php" class="btn btn-success">+ Tambah Stacking</a>
    </div>

    <!-- Filter -->
    <div class="filter-box">
        <label><strong>Filter Dompet:</strong></label>
        <select onchange="location.href='?wallet='+this.value">
            <option value="">-- Semua Dompet --</option>
            <?php foreach($dompet_filter as $d): ?>
            <option value="<?= $d['id_alamat_dompet'] ?>" <?= $filter_wallet == $d['id_alamat_dompet'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['nama_dompet']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php if($filter_wallet): ?>
            <a href="index.php" class="btn btn-secondary">Reset Filter</a>
        <?php endif; ?>
    </div>

    <div class="table-container">
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Dompet</th>
                <th>Xera Koin</th>
                <th>Durasi</th>
                <th>Stacking</th>
                <th>Investasi (Rp)</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($stacking_list)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px; color: #7f8c8d;">
                        Tidak ada data stacking. <a href="tambah.php">Tambah data pertama</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($stacking_list as $i => $s): ?>
                <tr>
                    <td data-label="No"><?= $offset + $i + 1 ?></td>
                    <td data-label="Nama Dompet">
                        <strong><?= htmlspecialchars($s['nama_dompet'] ?? 'Unknown') ?></strong>
                        <br>
                        <small style="color: #7f8c8d; font-family: monospace;">
                            <?= substr($s['id_alamat_dompet'], 0, 15) ?>...
                        </small>
                    </td>
                    <td data-label="Xera Koin"><?= formatKoin($s['xera_koin']) ?></td>
                    <td data-label="Durasi">
                        <span class="duration-badge duration-<?= $s['stacking_duration'] ?>">
                            <?= $s['stacking_duration'] ?> Hari
                        </span>
                    </td>
                    <td data-label="Stacking"><?= formatKoin($s['stacking_xera']) ?></td>
                    <td data-label="Investasi"><?= rupiah($s['jumlah_invest_rp']) ?></td>
                    <td data-label="Tanggal"><?= date('d M Y H:i', strtotime($s['created_at'])) ?></td>
                    <td data-label="Aksi">
                        <div class="aksi-flex">
                            <a href="tambah.php?id=<?= $s['id'] ?>" 
                               class="btn btn-primary btn-small">
                                ✏️ Edit
                            </a>
                            <a href="?delete=<?= $s['id'] ?>" 
                               class="btn btn-danger btn-small" 
                               onclick="return confirm('Yakin ingin menghapus data stacking ini?')">
                                🗑️ Hapus
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
                    </tbody>
    </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div style="margin-top: 20px; display: flex; justify-content: center; gap: 5px;">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?><?= $filter_wallet ? '&wallet='.$filter_wallet : '' ?>" class="btn btn-secondary">← Prev</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="btn btn-primary"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?><?= $filter_wallet ? '&wallet='.$filter_wallet : '' ?>" class="btn btn-secondary"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?><?= $filter_wallet ? '&wallet='.$filter_wallet : '' ?>" class="btn btn-secondary">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
