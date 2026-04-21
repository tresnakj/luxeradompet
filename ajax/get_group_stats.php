<?php

// ajax/get_group_stats.php

header('Content-Type: application/json');

require_once '../config/database.php';



$group_code = $_GET['group_code'] ?? '';

$jenis_filter = $_GET['jenis_filter'] ?? 'Semua';

$wallet_address = $_GET['wallet_address'] ?? '';



if (empty($group_code)) {

    echo json_encode(['error' => 'Group code required']);

    exit;

}



try {

    // 1. Ambil semua dompet dalam jaringan (untuk stacking & investasi)

    $sql_recursive = "

        WITH RECURSIVE network AS (

            SELECT 

                id_alamat_dompet, 

                kode_referal, 

                jenis_dompet,

                nama_dompet,

                0 as level

            FROM dompet 

            WHERE kode_referal = ?

            UNION ALL

            SELECT 

                d.id_alamat_dompet, 

                d.kode_referal, 

                d.jenis_dompet,

                d.nama_dompet,

                n.level + 1

            FROM dompet d

            INNER JOIN network n ON d.jaringan_dari = n.kode_referal

        )

        SELECT * FROM network

    ";

    $stmt = $pdo->prepare($sql_recursive);

    $stmt->execute([$group_code]);

    $all_wallets = $stmt->fetchAll();



    // 2. Filter berdasarkan jenis

    $filtered_wallets = $all_wallets;

    if ($jenis_filter !== 'Semua') {

        $filtered_wallets = array_filter($all_wallets, function($w) use ($jenis_filter) {

            return $w['jenis_dompet'] === $jenis_filter;

        });

        $filtered_wallets = array_values($filtered_wallets);

    }



    // 3. Ambil data stacking untuk dompet yang lolos filter

    $wallet_addresses = array_column($filtered_wallets, 'id_alamat_dompet');



    if (empty($wallet_addresses)) {

        echo json_encode([

            'total_investasi' => 0,

            'total_koin' => 0,

            'jumlah_dompet_stacking' => 0,

            'jumlah_dompet_total' => count($all_wallets),

            'dompet_filtered' => 0,

            'detail' => [],

            'message' => 'Tidak ada dompet dengan jenis ' . $jenis_filter . ' dalam grup ini',

            'total_airdrop' => 0

        ]);

        exit;

    }



    $placeholders = implode(',', array_fill(0, count($wallet_addresses), '?'));

    $sql_stacking = "

        SELECT 

            COALESCE(SUM(jumlah_invest_rp), 0) as total_investasi,

            COALESCE(SUM(stacking_xera), 0) as total_koin,

            COUNT(DISTINCT id_alamat_dompet) as jumlah_dompet_stacking

        FROM xera_stacking 

        WHERE id_alamat_dompet IN ($placeholders)

    ";

    $stmt = $pdo->prepare($sql_stacking);

    $stmt->execute($wallet_addresses);

    $result = $stmt->fetch();



    // 4. HITUNG TOTAL AIRDROP untuk wallet yang dipilih (saldo bersih = bonus - penarikan completed)

    $total_airdrop_selected = 0;

    if (!empty($wallet_address)) {

        $stmt_airdrop = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bonus), 0) as total_airdrop FROM air_drop WHERE id_alamat_dompet = ?");

        $stmt_airdrop->execute([$wallet_address]);

        $total_airdrop_gross = (float) $stmt_airdrop->fetchColumn();

        $stmt_withdraw = $pdo->prepare("SELECT COALESCE(SUM(jumlah_penarikan), 0) as total_withdrawn FROM withdraw WHERE id_alamat_dompet = ? AND status = 'completed'");

        $stmt_withdraw->execute([$wallet_address]);

        $total_withdrawn_selected = (float) $stmt_withdraw->fetchColumn();

        $total_airdrop_selected = $total_airdrop_gross - $total_withdrawn_selected;

    }



    // 5. Detail per dompet (opsional)

    $sql_detail = "

        SELECT 

            d.id_alamat_dompet,

            d.nama_dompet,

            d.jenis_dompet,

            COALESCE(SUM(xs.jumlah_invest_rp), 0) as investasi,

            COALESCE(SUM(xs.stacking_xera), 0) as koin

        FROM dompet d

        LEFT JOIN xera_stacking xs ON d.id_alamat_dompet = xs.id_alamat_dompet

        WHERE d.id_alamat_dompet IN ($placeholders)

        GROUP BY d.id_alamat_dompet, d.nama_dompet, d.jenis_dompet

        ORDER BY d.nama_dompet

    ";

    $stmt = $pdo->prepare($sql_detail);

    $stmt->execute($wallet_addresses);

    $detail = $stmt->fetchAll();

    $formatted_detail = array_map(function($d) {

        return [

            'nama' => $d['nama_dompet'],

            'alamat' => $d['id_alamat_dompet'],

            'jenis' => $d['jenis_dompet'],

            'investasi' => (float)$d['investasi'],

            'koin' => (float)$d['koin']

        ];

    }, $detail);



    // 6. Kirim JSON

    echo json_encode([

        'total_investasi' => (float)$result['total_investasi'],

        'total_koin' => (float)$result['total_koin'],

        'jumlah_dompet_stacking' => (int)$result['jumlah_dompet_stacking'],

        'jumlah_dompet_total' => count($all_wallets),

        'dompet_filtered' => count($filtered_wallets),

        'detail' => $formatted_detail,

        'filters' => [

            'group' => $group_code,

            'jenis' => $jenis_filter

        ],

        'total_airdrop' => $total_airdrop_selected  // Data untuk kartu baru

    ]);



} catch (Exception $e) {

    echo json_encode([

        'error' => $e->getMessage(),

        'total_investasi' => 0,

        'total_koin' => 0,

        'total_airdrop' => 0

    ]);

}

?>
