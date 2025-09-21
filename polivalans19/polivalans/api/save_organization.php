<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Debug bilgileri
error_log("=== Organizasyon Kaydetme Başladı ===");
error_log("POST verisi: " . file_get_contents('php://input'));

try {
    $mysqli = getDBConnection();
    
    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    // POST verilerini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    error_log("JSON decode sonucu: " . print_r($input, true));
    
    if (!$input) {
        throw new Exception("Geçersiz veri formatı");
    }

    $organizationName = trim($input['organization_name'] ?? '');
    $columnPosition = intval($input['column_position'] ?? 0);
    
    error_log("Organizasyon adı: '$organizationName'");
    error_log("Sütun pozisyonu: $columnPosition");
    
    if (empty($organizationName)) {
        throw new Exception("Organizasyon adı boş olamaz");
    }

    // Organizasyon tablosunu oluştur (eğer yoksa) - sütun pozisyonu ile
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS organizations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            column_position INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_column_position (column_position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if (!executeQuery($mysqli, $createTableSQL)) {
        throw new Exception("Organizasyon tablosu oluşturulamadı");
    }

    // Organizasyonu veritabanına ekle
    $insertSQL = "INSERT INTO organizations (name, column_position) VALUES (?, ?) ON DUPLICATE KEY UPDATE column_position = ?, updated_at = CURRENT_TIMESTAMP";
    
    error_log("SQL sorgusu: $insertSQL");
    error_log("Parametreler: $organizationName, $columnPosition");
    
    if (!executeQuery($mysqli, $insertSQL, [$organizationName, $columnPosition, $columnPosition])) {
        error_log("SQL hatası: " . $mysqli->error);
        throw new Exception("Organizasyon kaydedilemedi: " . $mysqli->error);
    }

    $organizationId = getLastInsertId($mysqli);
    error_log("Eklenen organizasyon ID: $organizationId");
    
    // Eğer yeni bir kayıt eklendiyse ID döner, yoksa mevcut kaydın ID'sini bul
    if (!$organizationId) {
        $selectSQL = "SELECT id FROM organizations WHERE name = ?";
        $result = fetchRow($mysqli, $selectSQL, [$organizationName]);
        $organizationId = $result['id'];
        error_log("Mevcut organizasyon ID bulundu: $organizationId");
    }

    closeDBConnection($mysqli);

    $response = [
        'success' => true,
        'message' => 'Organizasyon başarıyla kaydedildi',
        'organization_id' => $organizationId,
        'organization_name' => $organizationName,
        'column_position' => $columnPosition
    ];
    
    error_log("Başarılı response: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    error_log("❌ Organizasyon kaydetme hatası: " . $e->getMessage());
    
    $errorResponse = [
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ];
    
    error_log("Hata response: " . json_encode($errorResponse));
    echo json_encode($errorResponse);
}

error_log("=== Organizasyon Kaydetme Bitti ===");
?>
