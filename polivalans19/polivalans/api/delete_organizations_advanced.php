<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    
    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    // POST verilerini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    $action = $input['action'] ?? 'delete_all';
    $organizationId = $input['organization_id'] ?? null;
    $organizationName = $input['organization_name'] ?? null;

    $deletedCount = 0;
    $message = '';

    switch ($action) {
        case 'delete_by_id':
            if (!$organizationId) {
                throw new Exception("Organizasyon ID'si belirtilmedi");
            }
            
            $deleteSQL = "DELETE FROM organizations WHERE id = ?";
            if (!executeQuery($mysqli, $deleteSQL, [$organizationId])) {
                throw new Exception("Organizasyon silinemedi: " . $mysqli->error);
            }
            $deletedCount = $mysqli->affected_rows;
            $message = "ID: $organizationId olan organizasyon silindi";
            break;

        case 'delete_by_name':
            if (!$organizationName) {
                throw new Exception("Organizasyon adı belirtilmedi");
            }
            
            $deleteSQL = "DELETE FROM organizations WHERE name = ?";
            if (!executeQuery($mysqli, $deleteSQL, [$organizationName])) {
                throw new Exception("Organizasyon silinemedi: " . $mysqli->error);
            }
            $deletedCount = $mysqli->affected_rows;
            $message = "Adı: '$organizationName' olan organizasyon silindi";
            break;

        case 'delete_all':
        default:
            // Önce mevcut organizasyonları say
            $countSQL = "SELECT COUNT(*) as total FROM organizations";
            $countResult = fetchRow($mysqli, $countSQL);
            $totalOrganizations = $countResult['total'];

            // Tüm organizasyonları sil
            $deleteSQL = "DELETE FROM organizations";
            if (!executeQuery($mysqli, $deleteSQL)) {
                throw new Exception("Organizasyonlar silinemedi: " . $mysqli->error);
            }
            $deletedCount = $mysqli->affected_rows;
            $message = "Tüm organizasyonlar silindi";
            break;
    }

    closeDBConnection($mysqli);

    echo json_encode([
        'success' => true,
        'message' => $message,
        'deleted_count' => $deletedCount,
        'action' => $action
    ]);

} catch (Exception $e) {
    error_log("Organizasyon silme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
