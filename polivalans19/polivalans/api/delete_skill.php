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
    $skillId = $input['skill_id'] ?? null;

    if (!$skillId) {
        throw new Exception("Skill ID gerekli");
    }

    // 1. Bu skill'in organizasyonlarda kullanılıp kullanılmadığını kontrol et
    $sql = "SELECT COUNT(*) as count FROM organization_skills WHERE skill_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }
    $stmt->bind_param('i', $skillId);
    $stmt->execute();
    $result = $stmt->get_result();
    $countRow = $result->fetch_assoc();
    $stmt->close();

    $organizationCount = $countRow['count'] ?? 0;

    if ($organizationCount > 0) {
        // Skill organizasyonlarda kullanılıyorsa, sadece pasif yap
        $updateSQL = "UPDATE skills SET is_active = 0 WHERE id = ?";
        $stmtUpdate = $mysqli->prepare($updateSQL);
        if (!$stmtUpdate) {
            throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
        }
        $stmtUpdate->bind_param('i', $skillId);
        $stmtUpdate->execute();

        if ($stmtUpdate->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Beceri pasif hale getirildi (organizasyonlarda kullanıldığı için silinemedi)."
            ]);
        } else {
            throw new Exception("Beceri pasif hale getirilemedi");
        }
        $stmtUpdate->close();
    } else {
        // Skill hiçbir organizasyonda kullanılmıyorsa, tamamen sil
        $deleteSQL = "DELETE FROM skills WHERE id = ?";
        $stmtDelete = $mysqli->prepare($deleteSQL);
        if (!$stmtDelete) {
            throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
        }
        $stmtDelete->bind_param('i', $skillId);
        $stmtDelete->execute();

        if ($stmtDelete->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Beceri başarıyla silindi."
            ]);
        } else {
            throw new Exception("Beceri silinemedi veya bulunamadı");
        }
        $stmtDelete->close();
    }

} catch (Exception $e) {
    error_log("Beceri silme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
