<?php
$title = 'Dashboard - Luxera Dompet Manager';
require_once 'config/database.php';
require_once 'includes/header.php';
cekLogin();

// ============================================
// AMBIL DATA UNTUK FILTER DROPDOWN
// ============================================

// Fungsi untuk mendapatkan level hierarki
function getWalletLevel($pdo, $kode_referal, $level = 0) {
    // Cari parent
    $stmt = $pdo->prepare("SELECT jaringan_dari FROM dompet WHERE kode_referal = ?");
    $stmt->execute([$kode_referal]);
    $parent = $stmt->fetchColumn();
    
    if (empty($parent)) {
        return $level;
    }
    
    return getWalletLevel($pdo, $parent, $level + 1);
}

// Ambil semua dompet untuk dropdown dengan level
$stmt = $pdo->query("
    SELECT 
        d.id,
        d.nama_dompet,
        d.id_alamat_dompet,
        d.kode_referal,
        d.jenis_dompet,
        d.jaringan_dari,
        CASE 
            WHEN d.jaringan_dari IS NULL THEN 0
            ELSE LENGTH(d.kode_referal) - LENGTH(REPLACE(d.kode_referal, '/', '')) 
        END as calc_level
    FROM dompet d
    ORDER BY 
        CASE WHEN d.jaringan_dari IS NULL THEN 0 ELSE 1 END,
        d.created_at ASC
");

$all_dompet = $stmt->fetchAll();

// Hitung level yang benar untuk setiap dompet
$dompet_levels = [];

foreach ($all_dompet as $dompet) {
    if ($dompet['jaringan_dari'] === null) {
        $dompet_levels[$dompet['kode_referal']] = 0;
    } else {
        // Cari level parent dan tambah 1
        $parent_level = $dompet_levels[$dompet['jaringan_dari']] ?? 0;
        $dompet_levels[$dompet['kode_referal']] = $parent_level + 1;
    }
}

// Group by level
$dompet_by_level = [];
foreach ($all_dompet as $dompet) {
    $level = $dompet_levels[$dompet['kode_referal']] ?? 0;
    $dompet['level'] = $level;
    $dompet_by_level[$level][] = $dompet;
}

// Default group (tresnakusumajaya)
$default_group = '';
foreach ($all_dompet as $d) {
    if (strtolower($d['nama_dompet']) === 'tresnakusumajaya') {
        $default_group = $d['kode_referal'];
        break;
    }
}
if (empty($default_group) && !empty($all_dompet)) {
    $default_group = $all_dompet[0]['kode_referal'];
}

// Helper function untuk format address
function formatAddress($address) {
    if (strlen($address) <= 12) return $address;
    return substr($address, 0, 6) . '...' . substr($address, -6);
}

// ============================================
// HITUNG DEFAULT STATS (TresnaKusumajaya - Semua)
// ============================================

// Ambil semua dompet dalam jaringan default
$sql_default = "
    WITH RECURSIVE network AS (
        SELECT id_alamat_dompet, kode_referal, jenis_dompet
        FROM dompet WHERE kode_referal = ?
        UNION ALL
        SELECT d.id_alamat_dompet, d.kode_referal, d.jenis_dompet
        FROM dompet d
        INNER JOIN network n ON d.jaringan_dari = n.kode_referal
    )
    SELECT id_alamat_dompet FROM network
";
$stmt = $pdo->prepare($sql_default);
$stmt->execute([$default_group]);
$default_wallets = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($default_wallets)) {
    $placeholders = implode(',', array_fill(0, count($default_wallets), '?'));
    $sql_stats = "
        SELECT 
            COALESCE(SUM(jumlah_invest_rp), 0) as total_investasi,
            COALESCE(SUM(stacking_xera), 0) as total_koin,
            COUNT(DISTINCT id_alamat_dompet) as stacking_count
        FROM xera_stacking 
        WHERE id_alamat_dompet IN ($placeholders)
    ";
    $stmt = $pdo->prepare($sql_stats);
    $stmt->execute($default_wallets);
    $default_stats = $stmt->fetch();
} else {
    $default_stats = ['total_investasi' => 0, 'total_koin' => 0, 'stacking_count' => 0];
}

// Gunakan default stats untuk initial load
$total_investasi = $default_stats['total_investasi'];
$total_stacking_xera = $default_stats['total_koin'];
$stacking_dompet_count = $default_stats['stacking_count'];

// Ambil data dompet utama (tanpa referal/jaringan)
$stmt = $pdo->query("SELECT * FROM dompet WHERE jaringan_dari IS NULL ORDER BY created_at ASC");
$dompet_utama = $stmt->fetchAll();

// Hitung total aset
$total_investasi = $pdo->query("SELECT SUM(jumlah_invest_rp) as total FROM xera_stacking")->fetch()['total'] ?? 0;
$total_airdrop_raw = $pdo->query("SELECT COALESCE(SUM(jumlah_bonus),0) as total FROM air_drop")->fetch()['total'] ?? 0;
$total_withdrawn   = $pdo->query("SELECT COALESCE(SUM(jumlah_penarikan),0) as total FROM withdraw WHERE status='completed'")->fetch()['total'] ?? 0;
$total_airdrop     = $total_airdrop_raw - $total_withdrawn; // saldo bersih airdrop

// Jumlah Dompet yang sudah stacking (distinct wallets dari tabel xera_stacking)
$stacking_dompet_count = $pdo->query("SELECT COUNT(DISTINCT id_alamat_dompet) as total FROM xera_stacking WHERE stacking_xera > 0")->fetchColumn() ?? 0;

// Total Coin Stacking (sum of all XERA coins dari tabel xera_stacking)
$total_stacking_xera = $pdo->query("SELECT COALESCE(SUM(stacking_xera), 0) as total FROM xera_stacking")->fetchColumn() ?? 0;

// Fungsi rekursif untuk tree dengan foto/icon
function getTreeVisual($pdo, $parent_code, $level = 0) {
    $stmt = $pdo->prepare("SELECT * FROM dompet WHERE jaringan_dari = ? ORDER BY created_at ASC");
    $stmt->execute([$parent_code]);
    $children = $stmt->fetchAll();
    
    if (empty($children)) return '';
    
    $html = '<div class="tree-children">';
    foreach ($children as $child) {
        // Generate avatar/icon berdasarkan nama
        $initial = strtoupper(substr($child['nama_dompet'], 0, 1));
        $color = generateColor($child['nama_dompet']);
        
        // Hitung downline
        $downline = $pdo->prepare("SELECT COUNT(*) FROM dompet WHERE jaringan_dari = ?");
        $downline->execute([$child['kode_referal']]);
        $downline_count = $downline->fetchColumn();
        
        // Hitung investasi dan staking per dompet
        $invest_stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_invest_rp), 0) as total_rp FROM xera_stacking WHERE id_alamat_dompet = ?");
        $invest_stmt->execute([$child['id_alamat_dompet']]);
        $invest_rp = $invest_stmt->fetchColumn();
        
        $staking_stmt = $pdo->prepare("SELECT COALESCE(SUM(stacking_xera), 0) as total_xera FROM xera_stacking WHERE id_alamat_dompet = ?");
        $staking_stmt->execute([$child['id_alamat_dompet']]);
        $staking_xera = $staking_stmt->fetchColumn();
        
        // Hitung panjang nama untuk menyesuaikan ukuran font
        $nama_length = strlen($child['nama_dompet']);
        $font_size_class = '';
        if ($nama_length > 20) {
            $font_size_class = 'font-small';
        } elseif ($nama_length > 15) {
            $font_size_class = 'font-medium';
        }
        
        $html .= '<div class="tree-node">';
        $html .= '<div class="tree-connector"></div>';
        $html .= '<a href="dompet/detail.php?id='.$child['id'].'" class="tree-card" style="--avatar-color: '.$color.'">';
        $html .= '<div class="tree-avatar">'.$initial.'</div>';
        $html .= '<div class="tree-info">';
        // Nama dompet lengkap tanpa singkat
        $html .= '<div class="tree-name '.$font_size_class.'">'.htmlspecialchars($child['nama_dompet']).'</div>';
        $html .= '<div class="tree-meta">';
        if ($downline_count > 0) {
            $html .= '<span class="tree-badge">'.$downline_count.' downline</span>';
        }
        // Tampilkan ID dompet (alamat wallet) alih-alih kode referal
        $html .= '<span class="tree-id" title="'.$child['id_alamat_dompet'].'">'.substr($child['id_alamat_dompet'], 0, 12).'...</span>';
        $html .= '<div class="tree-invest">Rp. ' . number_format($invest_rp, 0) . ' | ' . number_format($staking_xera, 0) . ' Xera</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</a>';
        
        // Recursive untuk anak-anak
        $grandchildren = getTreeVisual($pdo, $child['kode_referal'], $level + 1);
        if ($grandchildren) {
            $html .= $grandchildren;
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

// Generate warna konsisten berdasarkan string
function generateColor($str) {
    $colors = ['#e74c3c', '#e67e22', '#f39c12', '#27ae60', '#2980b9', '#8e44ad', '#c0392b', '#16a085', '#d35400', '#7f8c8d'];
    $hash = crc32($str);
    return $colors[abs($hash) % count($colors)];
}
?>

<!-- PDF Export Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- CSS Eksternal -->
<link rel="stylesheet" href="assets/css/dashboard.css">

<!-- Filter Section -->
<div class="filter-section">
    <div class="filter-title">
        <span>🔍</span>
        Filter Omset & Stacking
    </div>
    <div class="filter-grid">
        <div class="filter-group">
            <label for="filter-grup">Grup Jaringan</label>
            <select id="filter-grup" class="filter-select">
                <?php foreach ($dompet_by_level as $level => $dompets): ?>
                    <optgroup label="Level <?= $level ?>">
                        <?php foreach ($dompets as $dompet): 
                            $selected = ($dompet['kode_referal'] === $default_group) ? 'selected' : '';
                            $formatted_addr = formatAddress($dompet['id_alamat_dompet']);
                        ?>
                            <option value="<?= htmlspecialchars($dompet['kode_referal']) ?>" 
                                    data-nama="<?= htmlspecialchars($dompet['nama_dompet']) ?>"
                                    data-alamat="<?= htmlspecialchars($dompet['id_alamat_dompet']) ?>"
                                    <?= $selected ?>>
                                <?= htmlspecialchars($dompet['nama_dompet']) ?> (<?= $formatted_addr ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="filter-jenis">Jenis Dompet</label>
            <select id="filter-jenis" class="filter-select">
                <option value="Semua" selected>📋 Semua</option>
                <option value="Pribadi">👤 Pribadi</option>
                <option value="Rekan">🤝 Rekan</option>
            </select>
        </div>
        
        <div class="filter-info">
            <span class="filter-info-icon">ℹ️</span>
            <span id="filter-status">Memuat data...</span>
        </div>
    </div>
</div>

<!-- Stats Cards - 4 Card Layout Responsive -->
<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">

    <!-- CARD 1: Total Investasi -->
    <div class="stat-card" id="card-investasi" onclick="location.href='xera_stacking/index.php'" style="cursor: pointer;">
        <h3>Total Investasi (IDR)</h3>
        <div class="stat-value" id="stat-investasi"><?= rupiah($total_investasi) ?></div>
        <div class="stat-usd" id="usdt-value">
            <span class="price-loading"></span>
        </div>
        <div class="rate-info" id="usdt-rate">1 USDT = Loading...</div>
        
        <!-- Selisih Profit -->
        <div class="profit-section" style="background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%); border-radius: 8px; padding: 12px; margin-top: 12px;">
            <div style="font-size: 11px; color: #7f8c8d; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; font-weight: 600;">Selisih Profit</div>
            <div class="profit-value" id="profit-selisih" style="font-size: 20px; font-weight: bold; font-family: monospace;">
                <span class="price-loading"></span>
            </div>
            <div class="profit-percentage" id="profit-percent" style="font-size: 12px; margin-top: 4px; font-weight: 600;">
                <span class="price-loading"></span>
            </div>
        </div>
        
        <div class="live-indicator">
            <span class="live-dot"></span>
            Live Update
        </div>
        
        <div id="filter-info-investasi" style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ddd; font-size: 11px; color: #7f8c8d;">
            Grup: <strong id="info-grup-investasi">-</strong> | Jenis: <strong id="info-jenis-investasi">-</strong>
        </div>
    </div>

    <!-- CARD 2: Air Drop Gabungan (Saldo Bersih + Wallet Terpilih) -->
    <div class="stat-card" style="padding: 0; overflow: hidden;">
        <!-- Bagian 1: Saldo Bersih Airdrop (Total - Penarikan Completed) -->
        <div onclick="location.href='airdrop/index.php'" style="cursor: pointer; padding: 20px; border-bottom: 1px solid #eee;">
            <h3>Saldo Airdrop Bersih<br>
                <span style="font-size: 11px; font-weight: normal; color: #95a5a6;">
                    Total: <?= formatKoin($total_airdrop_raw) ?> &minus; Tarik: <?= formatKoin($total_withdrawn) ?>
                </span>
            </h3>
            <div class="stat-value"><?= formatKoin($total_airdrop) ?> XERA</div>
            <div class="stat-rupiah" id="airdrop-rp-value">
                <span class="price-loading"></span>
            </div>
            <div class="stat-usd" id="airdrop-usd-value" style="font-size: 14px; color: #667eea;">
                <span class="price-loading"></span>
            </div>
            <div class="live-indicator" style="margin-top: 8px;">
                <span class="live-dot"></span>
                Live Update
            </div>
        </div>

        <!-- Bagian 2: Link ke Penarikan + Air Drop Wallet Terpilih -->
        <div style="padding: 12px 20px; background: #fff8f0; border-bottom: 1px solid #eee;">
            <a href="penarikan/index.php"
               style="display: block; text-align: center; padding: 8px 12px;
                      background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
                      color: white; border-radius: 6px; text-decoration: none;
                      font-size: 13px; font-weight: 600;">
                💸 Kelola Penarikan
            </a>
        </div>

        <!-- Bagian 3: Air Drop Wallet Terpilih -->
        <div id="card-airdrop-selected" style="cursor: pointer; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
            <h3 style="font-size: 14px; color: #667eea;">🎯 Wallet Terpilih</h3>
            <div class="stat-value" id="selected-airdrop-xera" style="font-size: 18px;">0 XERA</div>
            <div class="stat-rupiah" id="selected-airdrop-rp" style="font-size: 14px;">Rp 0</div>
            <div class="stat-usd" id="selected-airdrop-usd" style="font-size: 12px;">$0.00 USDT</div>
            <div class="wallet-name" id="selected-airdrop-wallet" style="font-size: 11px; color: #7f8c8d; margin-top: 5px;">-</div>
            <div class="live-indicator" style="margin-top: 5px;">
                <span class="live-dot"></span>
                Live
            </div>
        </div>
    </div>

    <!-- CARD 3: Harga XERA & Smart Contract (Container dengan 2 Div) -->
    <div class="stat-card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
        <!-- Bagian 1: Harga XERA (Clickable ke CMC) -->
        <div class="xera-card" onclick="window.open('https://coinmarketcap.com/currencies/luxera/', '_blank')" style="cursor: pointer; padding: 20px; border-bottom: 1px solid #eee; flex: 1;">
            <h3>
                <img src="assets/img/coin.png" alt="XERA" class="xera-logo" onerror="this.style.display='none'">
                Harga 1 Koin Xera
            </h3>
            <div class="stat-value" id="xera-price">
                <span class="price-loading"></span>
            </div>
            <div class="stat-usd" id="xera-change" style="font-size: 14px;">
                Loading...
            </div>
            
            <!-- Konversi Real-time 1 XERA -->
            <div class="xera-conversion-box" style="background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%); border-radius: 8px; padding: 10px; margin-top: 10px; margin-bottom: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <span style="font-size: 10px; color: #7f8c8d; text-transform: uppercase; font-weight: 600;">1 XERA =</span>
                    <span class="stacking-conversion-value usd" id="xera-usd-convert" style="font-size: 12px; font-weight: 700; font-family: monospace; color: #27ae60;">
                        <span class="price-loading"></span>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 10px; color: #7f8c8d; text-transform: uppercase; font-weight: 600;">1 XERA =</span>
                    <span class="stacking-conversion-value idr" id="xera-idr-convert" style="font-size: 12px; font-weight: 700; font-family: monospace; color: #e74c3c;">
                        <span class="price-loading"></span>
                    </span>
                </div>
            </div>
            
            <div class="live-indicator" style="margin-top: 5px;">
                <span class="live-dot"></span>
                Real-time CMC (5s)
            </div>
            
            <div class="xera-countdown-container" style="margin-top: 8px;">
                <div class="xera-countdown-bar-wrapper">
                    <div class="xera-countdown-bar" id="xeraCountdownBar" style="width: 100%;"></div>
                    <div class="update-ring" id="updateRing"></div>
                </div>
                <div class="xera-countdown-info">
                    <span class="xera-countdown-label">
                        <span class="countdown-dot" id="countdownDot"></span>
                        <span id="countdownStatus" style="font-size: 10px;">Next update</span>
                    </span>
                    <span class="xera-countdown-time" id="xeraCountdownTime" style="font-size: 10px;">15s</span>
                </div>
            </div>
        </div>

        <!-- Bagian 2: Smart Contract (Tampilan sama, tapi dengan modal) -->
        <div style="padding: 15px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
            <h3 style="font-size: 13px; color: #667eea; margin-bottom: 8px;">🔗 Smart Contract</h3>
            
            <div style="font-size: 13px; font-family: monospace; word-break: break-all; margin-bottom: 10px; color: #2c3e50; line-height: 1.3;">
                0xcA7dF3c62AEe9....1fD95a0b2d6688
            </div>
            
            <!-- Tombol Copy - Buka Modal -->
            <button 
                type="button"
                onclick="openContractModal(); event.stopPropagation();"
                style="
                    width: 100%;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    border-radius: 6px;
                    padding: 12px 15px;
                    cursor: pointer;
                    text-align: center;
                    font-weight: 600;
                    font-size: 13px;
                    transition: all 0.3s ease;
                    -webkit-tap-highlight-color: rgba(255,255,255,0.3);
                    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
                "
            >
                📋 Click to Copy
            </button>
            
            <div style="font-size: 10px; color: #7f8c8d; text-align: center; margin-top: 8px;">
                Tap to copy full address
            </div>
        </div>
    </div>

    <!-- CARD 4: Stacking Overview -->
    <div class="stat-card" id="card-stacking" onclick="location.href='xera_stacking/index.php'" style="cursor: pointer;">
        <h3>📊 Stacking Overview</h3>
        
        <div class="stacking-section" style="margin-bottom: 15px;">
            <div class="stacking-label" style="font-size: 12px; color: #7f8c8d; margin-bottom: 5px;">Jumlah Dompet Stacking</div>
            <div class="stacking-value-large" id="stat-dompet-count" style="font-size: 24px; font-weight: bold; color: #2c3e50;">
                <?= number_format($stacking_dompet_count) ?> <span style="font-size: 14px; font-weight: normal; color: #7f8c8d;">wallet</span>
            </div>
        </div>
        
        <div class="stacking-section" style="margin-bottom: 15px;">
            <div class="stacking-label" style="font-size: 12px; color: #7f8c8d; margin-bottom: 5px;">Total Coin Stacking</div>
            <div class="stacking-value-medium" id="stat-total-koin" style="font-size: 20px; font-weight: bold; color: #27ae60;">
                <?= formatKoin($total_stacking_xera) ?> <span style="font-size: 14px; font-weight: normal; color: #7f8c8d;">XERA</span>
            </div>
        </div>
        
        <!-- Konversi Real-time -->
        <div class="stacking-conversion-box" style="background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%); border-radius: 8px; padding: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 11px; color: #7f8c8d; text-transform: uppercase; font-weight: 600;">Konversi USDT</span>
                <span class="stacking-conversion-value usd" id="stacking-usd-value" style="font-size: 14px; font-weight: 700; font-family: monospace; color: #27ae60;">
                    <span class="price-loading"></span>
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; color: #7f8c8d; text-transform: uppercase; font-weight: 600;">Konversi Rupiah</span>
                <span class="stacking-conversion-value idr" id="stacking-rp-value" style="font-size: 14px; font-weight: 700; font-family: monospace; color: #e74c3c;">
                    <span class="price-loading"></span>
                </span>
            </div>
        </div>
        
        <div class="live-indicator" style="margin-top: 10px;">
            <span class="live-dot"></span>
            Live Conversion
        </div>
        
        <div id="filter-info-stacking" style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ddd; font-size: 11px; color: #7f8c8d;">
            Grup: <strong id="info-grup-stacking">-</strong> | Jenis: <strong id="info-jenis-stacking">-</strong>
        </div>
    </div>

</div>

<!-- Visual Tree Jaringan dengan Dual Scrollbar -->
<div class="tree-section">
    <div class="tree-header">
        <h2>🌳 Visual Tree Jaringan</h2>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
            <a href="dompet/tambah.php" class="btn btn-success" style="padding: 10px 20px; text-decoration: none; color: white; background: #27ae60; border-radius: 5px;">+ Tambah Dompet</a>
            <div class="zoom-controls">
                <button id="zoom-out" class="zoom-btn" title="Zoom Out">−</button>
                <span id="zoom-level" class="zoom-level">100%</span>
                <button id="zoom-in" class="zoom-btn" title="Zoom In">+</button>
                <span class="zoom-reset" id="zoom-reset" title="Reset Zoom">Reset</span>
            </div>
            <button id="export-pdf" class="btn btn-primary">📄 Export PDF</button>
        </div>
    </div>
    
    <!-- Dual Scrollbar Wrapper -->
    <div class="tree-scroll-wrapper">
        <!-- Scrollbar Atas -->
        <div class="tree-scroll-top" id="treeScrollTop">
            <div class="tree-scroll-dummy" id="treeScrollDummy"></div>
        </div>
        
        <!-- Konten Tree dengan Scrollbar Bawah -->
        <div class="tree-scroll-content" id="treeScrollContent">
            <div class="zoom-wrapper" id="zoomWrapper">

                <div class="tree-visual" id="treeVisual">

                <?php if (empty($dompet_utama)): ?>
                    <div style="text-align: center; padding: 50px; color: #7f8c8d;">
                        <div style="font-size: 64px; margin-bottom: 20px;">🌱</div>
                        <h3>Belum Ada Dompet</h3>
                        <p>Mulai dengan menambah dompet utama</p>
                        <a href="dompet/tambah.php" style="display: inline-block; margin-top: 15px; padding: 12px 25px; background: #27ae60; color: white; text-decoration: none; border-radius: 8px;">+ Tambah Dompet Pertama</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($dompet_utama as $root): 
                        $initial = strtoupper(substr($root['nama_dompet'], 0, 1));
                        $color = generateColor($root['nama_dompet']);
                        
                        // Hitung panjang nama root
                        $root_nama_length = strlen($root['nama_dompet']);
                        $root_font_size_class = '';
                        if ($root_nama_length > 20) {
                            $root_font_size_class = 'font-small';
                        } elseif ($root_nama_length > 15) {
                            $root_font_size_class = 'font-medium';
                        }
                        
                        // Hitung total downline untuk root
                        $total_downline = $pdo->prepare("
                            WITH RECURSIVE downline AS (
                                SELECT kode_referal FROM dompet WHERE kode_referal = ?
                                UNION ALL
                                SELECT d.kode_referal FROM dompet d
                                INNER JOIN downline dl ON d.jaringan_dari = dl.kode_referal
                            )
                            SELECT COUNT(*) - 1 as total FROM downline
                        ");
                        $total_downline->execute([$root['kode_referal']]);
                        $downline_count = $total_downline->fetch()['total'] ?? 0;
                        
                        // Investasi root
                        $root_invest = $pdo->prepare("SELECT COALESCE(SUM(jumlah_invest_rp), 0) as total_rp FROM xera_stacking WHERE id_alamat_dompet = ?");
                        $root_invest->execute([$root['id_alamat_dompet']]);
                        $root_invest_rp = $root_invest->fetchColumn();
                        
                        $root_staking = $pdo->prepare("SELECT COALESCE(SUM(stacking_xera), 0) as total_xera FROM xera_stacking WHERE id_alamat_dompet = ?");
                        $root_staking->execute([$root['id_alamat_dompet']]);
                        $root_staking_xera = $root_staking->fetchColumn();
                    ?>
                    <div class="tree-root">
                        <a href="dompet/detail.php?id=<?= $root['id'] ?>" class="tree-card" style="--avatar-color: <?= $color ?>">
                            <span class="tree-root-badge">ROOT</span>
                            <div class="tree-avatar"><?= $initial ?></div>
                            <div class="tree-info">
                                <div class="tree-name <?= $root_font_size_class ?>"><?= htmlspecialchars($root['nama_dompet']) ?></div>
                                <div class="tree-meta">
                                    <?php if ($downline_count > 0): ?>
                                        <span class="tree-badge"><?= $downline_count ?> total downline</span>
                                    <?php endif; ?>
                                    <span class="tree-id" title="<?= $root['id_alamat_dompet'] ?>"><?= substr($root['id_alamat_dompet'], 0, 12) ?>...</span>
                                    <div class="tree-invest">Rp. <?= number_format($root_invest_rp, 0) ?> | <?= number_format($root_staking_xera, 0) ?> Xera</div>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Wrapper untuk children dengan connector -->
                        <div class="tree-children-wrapper">
                            <?= getTreeVisual($pdo, $root['kode_referal']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Zoom Indicator -->
<div class="zoom-indicator" id="zoomIndicator">Zoom: 100%</div>

<!-- Daftar Dompet Utama -->
<div class="section-card">
    <div class="section-header">
        <h3 class="section-title">📋 Daftar Dompet Utama</h3>
        <a href="dompet/index.php" style="color: #3498db; text-decoration: none;">Lihat Semua →</a>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Dompet</th>
                    <th>Alamat</th>
                    <th>Kode Referal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dompet_utama as $i => $d): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($d['nama_dompet']) ?></strong></td>
                    <td style="font-family: monospace; font-size: 12px;"><?= substr($d['id_alamat_dompet'], 0, 20) ?>...</td>
                    <td><span class="badge badge-primary"><?= substr($d['kode_referal'], 0, 15) ?>...</span></td>
                    <td>
                        <div class="action-buttons">
                            <a href="dompet/detail.php?id=<?= $d['id'] ?>">Detail</a>
                            <a href="xera_stacking/tambah.php?wallet=<?= $d['id_alamat_dompet'] ?>">+Stacking</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Variabel Global dari PHP -->
<script>
    window.currentTotalKoin = <?= $total_stacking_xera ?>;
    window.currentTotalInvestasi = <?= $total_investasi ?>;
    window.currentDompetCount = <?= $stacking_dompet_count ?>;
    window.totalAirdrop    = <?= $total_airdrop ?>;       // saldo bersih (total - penarikan completed)
    window.totalAirdropRaw = <?= $total_airdrop_raw ?>;   // total airdrop sebelum dikurangi penarikan
    window.totalWithdrawn  = <?= $total_withdrawn ?>;     // total penarikan completed
    window.defaultGroup = '<?= htmlspecialchars($default_group) ?>';
</script>

<!-- JavaScript Eksternal -->
<script src="assets/javascript/dashboard.js"></script>
<!-- MODAL SMART CONTRACT - 100% WORK DI MOBILE -->
<div id="contract-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 99999; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    <div style="background: white; padding: 25px; border-radius: 15px; max-width: 90%; width: 400px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <div style="font-size: 40px; margin-bottom: 15px;">📋</div>
        <h3 style="margin: 0 0 10px 0; color: #333; font-size: 18px;">Smart Contract</h3>
        <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
            Tap the address below, Select All, then Copy:
        </p>
        
        <div style="position: relative; margin-bottom: 15px;">
            <textarea 
                id="modal-contract-text"
                readonly
                style="
                    width: 100%;
                    height: 80px;
                    padding: 12px;
                    border: 2px solid #667eea;
                    border-radius: 8px;
                    font-family: monospace;
                    font-size: 13px;
                    resize: none;
                    text-align: center;
                    background: #f8f9fa;
                    color: #2c3e50;
                "
            >0xcA7dF3c62AEe95E44D2B6eE51D1fD95a0b2d6688</textarea>
            <div style="position: absolute; top: 5px; right: 5px; background: #667eea; color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px;">FULL</div>
        </div>
        
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <button 
                onclick="tryAutoCopy()"
                style="
                    flex: 1;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 12px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 14px;
                "
            >
                📋 Try Auto Copy
            </button>
            <button 
                onclick="closeContractModal()"
                style="
                    flex: 1;
                    background: #95a5a6;
                    color: white;
                    border: none;
                    padding: 12px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 14px;
                "
            >
                Close
            </button>
        </div>
        
        <div id="copy-instruction" style="font-size: 11px; color: #7f8c8d; margin-top: 10px; padding: 8px; background: #f8f9fa; border-radius: 6px; display: none;">
            <!-- Dynamic instruction -->
        </div>
    </div>
</div>

<script>
// Fungsi Modal Smart Contract
function openContractModal() {
    var modal = document.getElementById('contract-modal');
    modal.style.display = 'flex';
    
    // Auto select text setelah modal muncul
    setTimeout(function() {
        var ta = document.getElementById('modal-contract-text');
        ta.focus();
        ta.select();
        ta.setSelectionRange(0, 999999);
    }, 100);
    
    // Detect device dan show instruction
    var instruction = document.getElementById('copy-instruction');
    var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    
    if (isIOS) {
        instruction.innerHTML = '👆 <strong>iOS:</strong> Tap text box → Select All → Copy';
    } else {
        instruction.innerHTML = '👆 <strong>Android:</strong> Tap and hold text → Select All → Copy';
    }
    instruction.style.display = 'block';
}

function closeContractModal() {
    document.getElementById('contract-modal').style.display = 'none';
}

function tryAutoCopy() {
    var ta = document.getElementById('modal-contract-text');
    ta.focus();
    ta.select();
    ta.setSelectionRange(0, 999999);
    
    var success = false;
    try {
        success = document.execCommand('copy');
    } catch(e) {}
    
    if (success) {
        // Show success feedback
        var btn = event.target;
        var originalText = btn.innerHTML;
        btn.innerHTML = '✅ Copied!';
        btn.style.background = '#27ae60';
        
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.style.background = '';
            closeContractModal();
        }, 1500);
    } else {
        // Keep modal open, user will copy manually
        ta.focus();
        alert('Please copy manually using the steps shown above.');
    }
}

// Close modal when clicking outside
document.getElementById('contract-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeContractModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeContractModal();
    }
});
</script>
<?php require_once 'includes/footer.php'; ?>