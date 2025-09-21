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
    $organizationSkillId = $input['organization_skill_id'] ?? null;

    if (!$organizationSkillId) {
        throw new Exception("Organizasyon-Skill ID gerekli");
    }

    // 1. Önce silinecek organizasyon-skill ilişkisinin organization_id'sini bul
    $sql = "SELECT organization_id FROM organization_skills WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }
    $stmt->bind_param('i', $organizationSkillId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception("Organizasyon-Skill ilişkisi bulunamadı");
    }

    $organizationId = $row['organization_id'];

    // 2. Aynı organization_id'ye sahip toplam kaç skill ilişkisi var kontrol et
    $sql = "SELECT COUNT(*) as count FROM organization_skills WHERE organization_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }
    $stmt->bind_param('i', $organizationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $countRow = $result->fetch_assoc();
    $stmt->close();

    $count = $countRow['count'] ?? 0;

    if ($count <= 1) {
        // Eğer sadece 1 kayıt varsa silme yapma
        echo json_encode([
            'success' => false,
            'message' => 'Bu organizasyon için en az bir beceri olmalıdır, silme işlemi yapılmadı.'
        ]);
        exit;
    }

    // 3. Silme işlemini gerçekleştir (sadece organizasyon-skill ilişkisini sil, skill'i değil)
    $sql = "DELETE FROM organization_skills WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }
    $stmt->bind_param('i', $organizationSkillId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Beceri organizasyondan başarıyla kaldırıldı.'
        ]);
    } else {
        throw new Exception("Beceri organizasyondan kaldırılamadı veya bulunamadı");
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Beceri organizasyondan kaldırma hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
