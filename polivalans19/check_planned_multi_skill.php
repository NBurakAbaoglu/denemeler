bu dosya apinin dışında
<?php
require_once 'config.php';

$mysqli = getDBConnection();
if (!$mysqli) {
    echo "Veritabanı bağlantısı kurulamadı\n";
    exit;
}

echo "=== PLANNED_MULTI_SKILL TABLOSU ===\n";
$result = $mysqli->query('DESCRIBE planned_multi_skill');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Default'] . "\n";
}

echo "\n=== PLANNED_MULTI_SKILL VERİLERİ ===\n";
$result = $mysqli->query('SELECT * FROM planned_multi_skill LIMIT 5');
while ($row = $result->fetch_assoc()) {
    echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
}

$mysqli->close();
?>
