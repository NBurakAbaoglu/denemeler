<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0); // Üretim ortamında kapalı tut
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);

require_once 'config.php';

// mysqli bağlantısı alıyoruz
$mysqli = getDBConnection();

if (!$mysqli) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı bağlantısı sağlanamadı.'
    ]);
    exit;
}

$sql = "SELECT * FROM planned_skills WHERE status = 'active'";
$result = $mysqli->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sorgu başarısız: ' . $mysqli->error
    ]);
    closeDBConnection($mysqli);
    exit;
}

$plannedSkills = [];
while ($row = $result->fetch_assoc()) {
    $plannedSkills[] = $row;
}

echo json_encode([
    'success' => true,
    'planned_skills' => $plannedSkills
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

closeDBConnection($mysqli);
exit;
