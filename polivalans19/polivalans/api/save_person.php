<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $personName = $input['person_name'] ?? null;
    
    if (!$personName || trim($personName) === '') {
        throw new Exception("Kişi adı gerekli");
    }
    
    // Kişi adını veritabanına kaydet
    $sql = "INSERT INTO persons (name) VALUES (?)";
    $result = executeQuery($mysqli, $sql, [trim($personName)]);
    
    if ($result) {
        $personId = $mysqli->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'Kişi başarıyla kaydedildi',
            'person_id' => $personId,
            'person_name' => trim($personName)
        ]);
    } else {
        throw new Exception("Kişi kaydedilemedi");
    }
    
} catch (Exception $e) {
    error_log("Kişi kaydetme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
