<?php
echo "<h2>Cek Struktur Folder</h2>";
echo "<pre>";

function listFolder($path, $level = 0) {
    $items = scandir($path);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $fullPath = $path . '/' . $item;
        $indent = str_repeat("  ", $level);
        
        if (is_dir($fullPath)) {
            echo $indent . "📁 " . $item . "/\n";
            if ($level < 2) { // Batasi kedalaman
                listFolder($fullPath, $level + 1);
            }
        } else {
            echo $indent . "📄 " . $item . " (" . filesize($fullPath) . " bytes)\n";
        }
    }
}

$root = __DIR__;
echo "Root: " . $root . "\n\n";

listFolder($root);

echo "</pre>";

// Cek file spesifik
echo "<h3>Cek File Spesifik:</h3>";
$files = [
    'xera_stacking/index.php',
    'xera_stacking/tambah.php',
    'airdrop/index.php',
    'airdrop/tambah.php'
];

foreach ($files as $file) {
    $path = $root . '/' . $file;
    echo $file . ": " . (file_exists($path) ? "✅ ADA" : "❌ TIDAK ADA") . "<br>";
}
?>