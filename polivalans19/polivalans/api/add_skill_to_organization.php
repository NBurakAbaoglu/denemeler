<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $organizationId = $input['organization_id'] ?? null;
    $skillId = $input['skill_id'] ?? null;
    $priority = $input['priority'] ?? 'medium';

    if (!$organizationId || !$skillId) {
        throw new Exception("Organizasyon ID ve Skill ID zorunludur");
    }

    // Aynı organizasyon-skill kombinasyonu var mı kontrol et
    $checkSQL = "SELECT id FROM organization_skills WHERE organization_id = ? AND skill_id = ?";
    $stmt = $mysqli->prepare($checkSQL);
    if (!$stmt) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }
    $stmt->bind_param('ii', $organizationId, $skillId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Bu beceri zaten bu organizasyona eklenmiş."
        ]);
        exit;
    }
    $stmt->close();

    // Yeni organizasyon-skill ilişkisini ekle
    $insertSQL = "INSERT INTO organization_skills (organization_id, skill_id, priority) VALUES (?, ?, ?)";
    $stmtInsert = $mysqli->prepare($insertSQL);
    if (!$stmtInsert) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }
    $stmtInsert->bind_param('iis', $organizationId, $skillId, $priority);

    if ($stmtInsert->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Beceri organizasyona başarıyla eklendi.",
            'organization_skill_id' => $mysqli->insert_id
        ]);
    } else {
        throw new Exception("Beceri organizasyona eklenemedi: " . $stmtInsert->error);
    }
    $stmtInsert->close();

    $mysqli->close();

} catch (Exception $e) {
    error_log("Beceri organizasyona ekleme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "Hata: " . $e->getMessage()
    ]);
}
?>
