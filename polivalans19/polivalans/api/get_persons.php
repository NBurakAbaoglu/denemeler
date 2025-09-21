<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    
    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    // Kişileri getir
    $getPersonsSQL = "SELECT id, name, created_at FROM persons ORDER BY created_at ASC";
    $persons = fetchAll($mysqli, $getPersonsSQL);
    
    closeDBConnection($mysqli);

    echo json_encode([
        'success' => true,
        'persons' => $persons
    ]);

} catch (Exception $e) {
    error_log("Kişi getirme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
