<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz veri formatı');
    }

    $person_name = $input['person_name'] ?? null;
    $organization_id = $input['organization_id'] ?? null;
    $image_name = $input['image_name'] ?? null;
    $organization_name = $input['organization_name'] ?? null;

    if (!$person_name || !$image_name) {
        throw new Exception('Person name ve image name gerekli');
    }

    // Organization ID varsa onu kullan, yoksa organization name ile bul
    if ($organization_id) {
        $whereClause = "organization_id = ?";
        $params = [$image_name, $organization_id, $person_name];
    } elseif ($organization_name) {
        // Organization name ile organization_id'yi bul
        $orgQuery = "SELECT id FROM organizations WHERE name = ?";
        $orgStmt = $pdo->prepare($orgQuery);
        $orgStmt->execute([$organization_name]);
        $org = $orgStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$org) {
            throw new Exception("Organizasyon bulunamadı: $organization_name");
        }
        
        $organization_id = $org['id'];
        $whereClause = "organization_id = ?";
        $params = [$image_name, $organization_id, $person_name];
    } else {
        throw new Exception('Organization ID veya organization name gerekli');
    }

    // Önce mevcut kayıt var mı kontrol et
    $checkQuery = "SELECT id FROM organization_images WHERE $whereClause AND row_name = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$organization_id, $person_name]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Mevcut kaydı güncelle
        $updateQuery = "UPDATE organization_images SET image_name = ?, updated_at = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateQuery);
        $result = $updateStmt->execute([$image_name, $existing['id']]);
        
        if ($result) {
            error_log("✅ Organization image güncellendi: $person_name, $organization_id, $image_name");
        } else {
            error_log("❌ Organization image güncellenemedi: $person_name, $organization_id");
        }
    } else {
        // Yeni kayıt oluştur
        $insertQuery = "INSERT INTO organization_images (organization_id, row_name, image_name, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
        $insertStmt = $pdo->prepare($insertQuery);
        $result = $insertStmt->execute([$organization_id, $person_name, $image_name]);
        
        if ($result) {
            error_log("✅ Organization image oluşturuldu: $person_name, $organization_id, $image_name");
        } else {
            error_log("❌ Organization image oluşturulamadı: $person_name, $organization_id");
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Organization image güncellendi',
        'person_name' => $person_name,
        'organization_id' => $organization_id,
        'image_name' => $image_name
    ]);

} catch (Exception $e) {
    error_log("❌ Organization image güncelleme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
