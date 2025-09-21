<?php
require_once __DIR__ . '/../config.php';
//organizasyon adını güncellemek için 
header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    
    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    // Gelen veriyi al
    $input = json_decode(file_get_contents('php://input'), true);
    $organizationId = $input['organization_id'] ?? null;
    $organizationName = $input['organization_name'] ?? null;

    if (!$organizationId || !$organizationName) {
        throw new Exception("Organizasyon ID ve adı gerekli");
    }

    // Organizasyonun var olup olmadığını kontrol et
    $checkOrgSQL = "SELECT id FROM organizations WHERE id = ?";
    $organization = fetchRow($mysqli, $checkOrgSQL, [$organizationId]);
    
    if (!$organization) {
        throw new Exception("Organizasyon bulunamadı");
    }

    // Organizasyon adını güncelle
    $sql = "UPDATE organizations SET name = ? WHERE id = ?";
    $result = executeQuery($mysqli, $sql, [$organizationName, $organizationId]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Organizasyon adı başarıyla güncellendi'
        ]);
    } else {
        throw new Exception("Organizasyon adı güncellenemedi");
    }

} catch (Exception $e) {
    error_log("Organizasyon güncelleme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
