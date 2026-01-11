<?php
/**
 * Debug Vietnamese matching
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

echo "<meta charset='UTF-8'>";
echo "<style>body{font-family:sans-serif;padding:20px;}</style>";

$q = isset($_GET['q']) ? $_GET['q'] : 'rau';

echo "<h1>Debug Vietnamese Search</h1>";
echo "<form><input name='q' value='".htmlspecialchars($q)."'><button>Test</button></form><hr>";

echo "<h2>Input Analysis</h2>";
echo "<p><b>Query:</b> \"$q\"</p>";
echo "<p><b>Query lowercase:</b> \"" . mb_strtolower($q, 'UTF-8') . "\"</p>";
echo "<p><b>Query hex:</b> " . bin2hex($q) . "</p>";

echo "<h2>Test Products</h2>";

// Test data
$testProducts = [
    "Rau má",
    "Rau muống", 
    "Râu bạch tuộc đông lạnh 300g",
    "Cá hồi",
    "Cải thảo",
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Tên</th><th>Lowercase</th><th>mb_strpos result</th><th>Match?</th></tr>";

$qLower = mb_strtolower(trim($q), 'UTF-8');

foreach ($testProducts as $ten) {
    $tenLower = mb_strtolower($ten, 'UTF-8');
    $pos = mb_strpos($tenLower, $qLower);
    $match = ($pos !== false) ? 'YES ✅' : 'NO ❌';
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($ten) . "</td>";
    echo "<td>" . $tenLower . " (hex: " . bin2hex($tenLower) . ")</td>";
    echo "<td>" . var_export($pos, true) . "</td>";
    echo "<td>" . $match . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>From Database</h2>";
$db = Database::getInstance();
$results = $db->query("SELECT ID_sp, Ten FROM san_pham WHERE Ten LIKE ? LIMIT 10", ['%'.$q.'%'])->fetchAll();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Tên</th><th>mb_strpos</th><th>Should Include?</th></tr>";

foreach ($results as $row) {
    $tenLower = mb_strtolower($row['Ten'], 'UTF-8');
    $pos = mb_strpos($tenLower, $qLower);
    $include = ($pos !== false) ? 'YES ✅' : 'NO ❌';
    
    echo "<tr>";
    echo "<td>{$row['ID_sp']}</td>";
    echo "<td>" . htmlspecialchars($row['Ten']) . "</td>";
    echo "<td>" . var_export($pos, true) . "</td>";
    echo "<td>$include</td>";
    echo "</tr>";
}
echo "</table>";

?>
