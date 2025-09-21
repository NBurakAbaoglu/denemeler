<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();

    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    // Gelen veriyi al
    $input = json_decode(file_get_contents('php://input'), true);
    $organizationId = $input['organization_id'] ?? null;

    if (!$organizationId) {
        throw new Exception("Organizasyon ID gerekli");
    }

    // Organizasyona bağlı organization_images kayıtlarını sil
    $deleteImagesSQL = "DELETE FROM organization_images WHERE organization_id = ?";
    executeQuery($mysqli, $deleteImagesSQL, [$organizationId]);

    // Organizasyona bağlı organization_skills kayıtlarını sil
    $deleteSkillsSQL = "DELETE FROM organization_skills WHERE organization_id = ?";
    executeQuery($mysqli, $deleteSkillsSQL, [$organizationId]);

    // Son olarak organizasyonu sil
    $deleteOrgSQL = "DELETE FROM organizations WHERE id = ?";
    $affected = executeQuery($mysqli, $deleteOrgSQL, [$organizationId]);

    if ($affected > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Organizasyon ve ilişkili kayıtlar başarıyla silindi'
        ]);
    } else {
        throw new Exception("Organizasyon silinemedi veya bulunamadı");
    }

} catch (Exception $e) {
    error_log("Organizasyon silme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
