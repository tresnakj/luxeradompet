<?php
$title = 'Penarikan Airdrop - Luxera Dompet Manager';
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
$filter_wallet  = $_GET['wallet'] ?? '';
$filter_status  = $_GET['status'] ?? '';
$filter_tanggal = $_GET['tanggal'] ?? '';

$where  = [];
$params = [];

if ($filter_wallet) {
    $where[]  = "w.id_alamat_dompet = ?";
    $params[] = $filter_wallet;
}
if ($filter_status) {
    $where[]  = "w.status = ?";
    $params[] = $filter_status;
}
if ($filter_tanggal) {
    $where[]  = "w.tanggal_penarikan = ?";
    $params[] = $filter_tanggal;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Total records untuk pagination
$count_sql = "SELECT COUNT(*) as total FROM withdraw w $where_sql";
$stmt      = $pdo->prepare($count_sql);
$stmt->execute($params);
$total       = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// Data penarikan dengan info dompet
$sql = "SELECT w.*, d.nama_dompet, d.kode_referal
    FROM withdraw w
    LEFT JOIN dompet d ON w.id_alamat_dompet = d.id_alamat_dompet
    $where_sql
    ORDER BY w.tanggal_penarikan DESC, w.created_at DESC
    LIMIT $limit OFFSET $offset";

$stmt         = $pdo->prepare($sql);
$stmt->execute($params);
$penarikan_list = $stmt->fetchAll();

// Delete handler
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM withdraw WHERE id = ?")->execute([$id]);
        $redir = "index.php?msg=deleted";
        if ($filter_wallet)  $redir .= "&wallet=" . urlencode($filter_wallet);
        if ($filter_status)  $redir .= "&status=" . urlencode($filter_status);
        if ($filter_tanggal) $redir .= "&tanggal=" . urlencode($filter_tanggal);
        header("Location: $redir");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menghapus: " . $e->getMessage();
    }
}

// Update status handler
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id         = (int)$_GET['id'];
    $new_status = $_GET['update_status'];
    if (in_array($new_status, ['pending', 'completed', 'rejected'])) {
        try {
            $pdo->prepare("UPDATE withdraw SET status = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$new_status, $id]);
            header("Location: index.php?msg=updated" .
                ($filter_wallet  ? "&wallet=$filter_wallet"   : "") .
                ($filter_status  ? "&status=$filter_status"   : "") .
                ($filter_tanggal ? "&tanggal=$filter_tanggal" : ""));
            exit;
        } catch (PDOException $e) {
            $error = "Gagal update status: " . $e->getMessage();
        }
    }
}

// ============================================================
// Statistik global
// ============================================================
$global_stats = $pdo->query("
    SELECT
        COUNT(DISTINCT id_alamat_dompet)                                   AS total_wallet,
        COALESCE(SUM(jumlah_penarikan), 0)                                 AS total_penarikan,
        COALESCE(SUM(CASE WHEN status='completed' THEN jumlah_penarikan END), 0) AS total_completed,
        COALESCE(SUM(CASE WHEN status='pending'   THEN jumlah_penarikan END), 0) AS total_pending,
        COUNT(*)                                                            AS total_transaksi
    FROM withdraw
")->fetch();

// Total airdrop keseluruhan
$total_airdrop_global = $pdo->query("SELECT COALESCE(SUM(jumlah_bonus),0) AS total FROM air_drop")->fetch()['total'] ?? 0;
// Net airdrop = total airdrop - total penarikan completed
$net_airdrop_global   = $total_airdrop_global - ($global_stats['total_completed'] ?? 0);

// ============================================================
// Daftar dompet yang memiliki airdrop >= 0.1 XERA (setelah dikurangi penarikan completed)
// ============================================================
$dompet_filter_list = $pdo->query("
    SELECT
        d.id_alamat_dompet,
        d.nama_dompet,
        COALESCE(SUM(ad.jumlah_bonus), 0)                                                  AS total_airdrop,
        COALESCE((
            SELECT SUM(w2.jumlah_penarikan)
            FROM withdraw w2
            WHERE w2.id_alamat_dompet = d.id_alamat_dompet
              AND w2.status = 'completed'
        ), 0)                                                                               AS total_withdrawn,
        COALESCE(SUM(ad.jumlah_bonus), 0) - COALESCE((
            SELECT SUM(w2.jumlah_penarikan)
            FROM withdraw w2
            WHERE w2.id_alamat_dompet = d.id_alamat_dompet
              AND w2.status = 'completed'
        ), 0)                                                                               AS saldo_airdrop
    FROM dompet d
    LEFT JOIN air_drop ad ON d.id_alamat_dompet = ad.id_alamat_dompet
    GROUP BY d.id_alamat_dompet, d.nama_dompet
    HAVING saldo_airdrop >= 0.1
    ORDER BY d.nama_dompet ASC
")->fetchAll();

// ============================================================
// Tabel ringkasan per dompet (saldo airdrop bersih), hanya >= 0.1
// ============================================================
$ringkasan_dompet = $pdo->query("
    SELECT
        d.id_alamat_dompet,
        d.nama_dompet,
        COALESCE(SUM(ad.jumlah_bonus), 0)                                                  AS total_airdrop,
        COALESCE((
            SELECT SUM(w2.jumlah_penarikan)
            FROM withdraw w2
            WHERE w2.id_alamat_dompet = d.id_alamat_dompet
              AND w2.status = 'completed'
        ), 0)                                                                               AS total_withdrawn,
        COALESCE(SUM(ad.jumlah_bonus), 0) - COALESCE((
            SELECT SUM(w2.jumlah_penarikan)
            FROM withdraw w2
            WHERE w2.id_alamat_dompet = d.id_alamat_dompet
              AND w2.status = 'completed'
        ), 0)                                                                               AS saldo_airdrop,
        COALESCE((
            SELECT SUM(w3.jumlah_penarikan)
            FROM withdraw w3
            WHERE w3.id_alamat_dompet = d.id_alamat_dompet
              AND w3.status = 'pending'
        ), 0)                                                                               AS pending_penarikan
    FROM dompet d
    LEFT JOIN air_drop ad ON d.id_alamat_dompet = ad.id_alamat_dompet
    GROUP BY d.id_alamat_dompet, d.nama_dompet
    HAVING saldo_airdrop >= 0.1
    ORDER BY saldo_airdrop DESC
")->fetchAll();

$msg = $_GET['msg'] ?? '';

// Helper
function buildQueryParams2($wallet, $status, $tanggal) {
    $p = '';
    if ($wallet)  $p .= '&wallet='  . urlencode($wallet);
    if ($status)  $p .= '&status='  . urlencode($status);
    if ($tanggal) $p .= '&tanggal=' . urlencode($tanggal);
    return $p;
}
?>

<style>
    /* ============================================================
       STAT CARDS
       ============================================================ */
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
    .stat-card.primary  { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .stat-card.success  { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
    .stat-card.warning  { background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%); color: white; }
    .stat-card.info     { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; }
    .stat-card.danger   { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; }
    .stat-card h4 {
        font-size: 12px;
        opacity: 0.9;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-card .value { font-size: 20px; font-weight: bold; }

    /* ============================================================
       FILTER BOX
       ============================================================ */
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

    /* ============================================================
       RINGKASAN DOMPET
       ============================================================ */
    .ringkasan-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }
    .dompet-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        padding: 18px;
        border-left: 4px solid #667eea;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .dompet-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    }
    .dompet-card .dompet-name {
        font-weight: 700;
        font-size: 15px;
        color: #2c3e50;
        margin-bottom: 4px;
    }
    .dompet-card .dompet-addr {
        font-size: 11px;
        color: #7f8c8d;
        font-family: monospace;
        margin-bottom: 12px;
    }
    .dompet-card .balance-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        padding: 4px 0;
    }
    .dompet-card .balance-row .label { color: #7f8c8d; }
    .dompet-card .balance-row .val   { font-weight: 600; font-family: monospace; }
    .dompet-card .saldo-net {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dompet-card .saldo-net .label { font-size: 12px; color: #7f8c8d; font-weight: 600; text-transform: uppercase; }
    .dompet-card .saldo-net .val   { font-size: 18px; font-weight: 800; color: #27ae60; font-family: monospace; }
    .dompet-card .pending-warn {
        margin-top: 6px;
        font-size: 11px;
        color: #e67e22;
        font-style: italic;
    }
    .dompet-card .btn-tarik {
        display: block;
        width: 100%;
        margin-top: 12px;
        padding: 8px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 6px;
        text-align: center;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .dompet-card .btn-tarik:hover { opacity: 0.9; }

    /* ============================================================
       TABLE ELEMENTS
       ============================================================ */
    .table-container { overflow-x: auto; }
    .amount-debit  { color: #e74c3c; font-weight: bold; }
    .amount-credit { color: #27ae60; font-weight: bold; }
    .date-badge {
        background: #ecf0f1;
        padding: 4px 10px;
        border-radius: 5px;
        font-size: 12px;
        display: inline-block;
    }
    .date-badge.penarikan { background: #fde8e8; color: #c0392b; border: 1px solid #f5c6cb; }
    .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
    .badge-pending   { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
    .badge-completed { background: #d4edda; color: #155724; border: 1px solid #28a745; }
    .badge-rejected  { background: #f8d7da; color: #721c24; border: 1px solid #dc3545; }
    .btn-small { padding: 4px 9px; font-size: 11px; }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 900px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .stats-grid { grid-template-columns: 1fr; gap: 12px; }
        .filter-box { flex-direction: column; align-items: stretch; }
        .filter-box select, .filter-box input { max-width: none; }
        .ringkasan-grid { grid-template-columns: 1fr; }
    }
</style>

<h2>💸 Penarikan Saldo Airdrop</h2>

<?php if ($msg == 'added'):   ?><div class="alert alert-success">✅ Data penarikan berhasil dicatat!</div><?php endif; ?>
<?php if ($msg == 'deleted'): ?><div class="alert alert-success">🗑️ Data penarikan berhasil dihapus!</div><?php endif; ?>
<?php if ($msg == 'updated'): ?><div class="alert alert-success">🔄 Status penarikan berhasil diupdate!</div><?php endif; ?>
<?php if (isset($error)):     ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- ============================================================
     STATS CARDS
     ============================================================ -->
<div class="stats-grid">
    <div class="stat-card primary">
        <h4>Total Airdrop Tersedia</h4>
        <div class="value"><?= formatKoin($total_airdrop_global) ?> XERA</div>
    </div>
    <div class="stat-card danger">
        <h4>Total Ditarik (Selesai)</h4>
        <div class="value"><?= formatKoin($global_stats['total_completed'] ?? 0) ?> XERA</div>
    </div>
    <div class="stat-card success">
        <h4>Saldo Airdrop Bersih</h4>
        <div class="value"><?= formatKoin($net_airdrop_global) ?> XERA</div>
    </div>
    <div class="stat-card warning">
        <h4>Menunggu Proses</h4>
        <div class="value"><?= formatKoin($global_stats['total_pending'] ?? 0) ?> XERA</div>
    </div>
</div>

<!-- ============================================================
     RINGKASAN SALDO PER DOMPET
     ============================================================ -->
<?php if (!empty($ringkasan_dompet)): ?>
<div class="card">
    <div class="d-flex justify-between align-center mb-3">
        <h3>💰 Saldo Airdrop Per Dompet <small style="font-size: 13px; color: #7f8c8d;">(≥ 0.1 XERA)</small></h3>
        <a href="tambah.php" class="btn btn-success">+ Catat Penarikan</a>
    </div>
    <div class="ringkasan-grid">
        <?php foreach ($ringkasan_dompet as $rd): ?>
        <div class="dompet-card">
            <div class="dompet-name"><?= htmlspecialchars($rd['nama_dompet']) ?></div>
            <div class="dompet-addr"><?= substr($rd['id_alamat_dompet'], 0, 20) ?>...</div>

            <div class="balance-row">
                <span class="label">Total Airdrop</span>
                <span class="val amount-credit">+<?= formatKoin($rd['total_airdrop']) ?> XERA</span>
            </div>
            <div class="balance-row">
                <span class="label">Total Ditarik</span>
                <span class="val amount-debit">-<?= formatKoin($rd['total_withdrawn']) ?> XERA</span>
            </div>

            <div class="saldo-net">
                <span class="label">Saldo Bersih</span>
                <span class="val"><?= formatKoin($rd['saldo_airdrop']) ?> XERA</span>
            </div>

            <?php if ($rd['pending_penarikan'] > 0): ?>
            <div class="pending-warn">
                ⏳ Pending: <?= formatKoin($rd['pending_penarikan']) ?> XERA (belum dikonfirmasi)
            </div>
            <?php endif; ?>

            <a href="tambah.php?wallet=<?= urlencode($rd['id_alamat_dompet']) ?>" class="btn-tarik">
                💸 Tarik Saldo
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="card" style="text-align: center; padding: 40px; color: #7f8c8d;">
    <div style="font-size: 48px; margin-bottom: 15px;">💰</div>
    <h3>Tidak Ada Saldo Yang Dapat Ditarik</h3>
    <p style="margin-top: 8px;">Belum ada dompet dengan saldo airdrop ≥ 0.1 XERA.</p>
    <a href="../airdrop/index.php" class="btn btn-primary" style="margin-top: 15px;">Lihat Data Airdrop</a>
</div>
<?php endif; ?>

<!-- ============================================================
     RIWAYAT PENARIKAN
     ============================================================ -->
<div class="card">
    <div class="d-flex justify-between align-center mb-3">
        <h3>📋 Riwayat Penarikan</h3>
        <a href="tambah.php" class="btn btn-success">+ Catat Penarikan</a>
    </div>

    <!-- Filter -->
    <div class="filter-box">
        <label><strong>Filter:</strong></label>

        <select onchange="updateFilter('wallet', this.value)">
            <option value="">-- Semua Dompet --</option>
            <?php foreach ($dompet_filter_list as $df): ?>
            <option value="<?= htmlspecialchars($df['id_alamat_dompet']) ?>"
                    <?= $filter_wallet == $df['id_alamat_dompet'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($df['nama_dompet']) ?>
                (<?= formatKoin($df['saldo_airdrop']) ?> XERA)
            </option>
            <?php endforeach; ?>
        </select>

        <select onchange="updateFilter('status', this.value)">
            <option value="">-- Semua Status --</option>
            <option value="pending"   <?= $filter_status == 'pending'   ? 'selected' : '' ?>>⏳ Pending</option>
            <option value="completed" <?= $filter_status == 'completed' ? 'selected' : '' ?>>✅ Completed</option>
            <option value="rejected"  <?= $filter_status == 'rejected'  ? 'selected' : '' ?>>❌ Rejected</option>
        </select>

        <input type="date"
               value="<?= htmlspecialchars($filter_tanggal) ?>"
               onchange="updateFilter('tanggal', this.value)"
               max="<?= date('Y-m-d') ?>"
               placeholder="Filter tanggal">

        <?php if ($filter_wallet || $filter_status || $filter_tanggal): ?>
            <a href="index.php" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
    </div>

    <div class="table-container">
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Dompet</th>
                <th>Tanggal Penarikan</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Dicatat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($penarikan_list)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 30px; color: #7f8c8d;">
                    Belum ada riwayat penarikan.
                    <a href="tambah.php">Catat penarikan pertama</a>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($penarikan_list as $i => $p): ?>
            <tr>
                <td data-label="No"><?= $offset + $i + 1 ?></td>
                <td data-label="Dompet">
                    <strong><?= htmlspecialchars($p['nama_dompet'] ?? 'Unknown') ?></strong><br>
                    <small style="color: #7f8c8d; font-family: monospace;">
                        <?= substr($p['id_alamat_dompet'], 0, 16) ?>...
                    </small>
                </td>
                <td data-label="Tanggal">
                    <span class="date-badge penarikan">
                        <?= date('d/m/Y', strtotime($p['tanggal_penarikan'])) ?>
                    </span><br>
                    <small style="color: #7f8c8d; font-size: 11px;">
                        <?= date('l', strtotime($p['tanggal_penarikan'])) ?>
                    </small>
                </td>
                <td data-label="Jumlah" class="amount-debit">
                    -<?= formatKoin($p['jumlah_penarikan']) ?> XERA
                </td>
                <td data-label="Status">
                    <?php
                    $badge_class = match($p['status']) {
                        'completed' => 'badge-completed',
                        'rejected'  => 'badge-rejected',
                        default     => 'badge-pending',
                    };
                    $badge_label = match($p['status']) {
                        'completed' => '✅ Selesai',
                        'rejected'  => '❌ Ditolak',
                        default     => '⏳ Pending',
                    };
                    ?>
                    <span class="badge-status <?= $badge_class ?>"><?= $badge_label ?></span>
                </td>
                <td data-label="Dicatat">
                    <?php if ($p['created_at']): ?>
                        <span class="date-badge">
                            <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                        </span><br>
                        <small style="color: #7f8c8d; font-size: 11px;">
                            <?= date('H:i', strtotime($p['created_at'])) ?> WIB
                        </small>
                    <?php else: ?>
                        <span style="color: #95a5a6; font-size: 12px;">-</span>
                    <?php endif; ?>
                </td>
                <td data-label="Aksi">
                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                        <?php if ($p['status'] === 'pending'): ?>
                            <a href="?id=<?= $p['id'] ?>&update_status=completed<?= buildQueryParams2($filter_wallet, $filter_status, $filter_tanggal) ?>"
                               class="btn btn-success btn-small"
                               onclick="return confirm('Tandai penarikan ini sebagai SELESAI?')">
                                ✅
                            </a>
                            <a href="?id=<?= $p['id'] ?>&update_status=rejected<?= buildQueryParams2($filter_wallet, $filter_status, $filter_tanggal) ?>"
                               class="btn btn-warning btn-small"
                               onclick="return confirm('Tandai penarikan ini sebagai DITOLAK?')">
                                ❌
                            </a>
                        <?php elseif ($p['status'] === 'rejected'): ?>
                            <a href="?id=<?= $p['id'] ?>&update_status=pending<?= buildQueryParams2($filter_wallet, $filter_status, $filter_tanggal) ?>"
                               class="btn btn-secondary btn-small"
                               onclick="return confirm('Reset status ke PENDING?')">
                                🔄
                            </a>
                        <?php endif; ?>
                        <a href="?delete=<?= $p['id'] ?><?= buildQueryParams2($filter_wallet, $filter_status, $filter_tanggal) ?>"
                           class="btn btn-danger btn-small"
                           onclick="return confirm('Yakin ingin menghapus data penarikan ini?')">
                            Hapus
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
    <div style="margin-top: 20px; display: flex; justify-content: center; gap: 5px; flex-wrap: wrap;">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?><?= buildQueryParams2($filter_wallet, $filter_status, $filter_tanggal) ?>"
               class="btn btn-secondary">← Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="btn btn-primary"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?><?= buildQueryParams2($filter_wallet, $filter_status, $filter_tanggal) ?>"
                   class="btn btn-secondary"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?><?= buildQueryParams2($filter_wallet, $filter_status, $filter_tanggal) ?>"
               class="btn btn-secondary">Next →</a>
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
