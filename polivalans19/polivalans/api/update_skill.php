<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $skillId = $input['skill_id'] ?? null;
    $skillName = trim($input['skill_name'] ?? '');
    $skillDescription = trim($input['skill_description'] ?? '');
    $category = trim($input['category'] ?? '');
    $isActive = $input['is_active'] ?? 1;

    if (!$skillId || !$skillName || !$skillDescription) {
        throw new Exception("Skill ID, adı ve açıklaması zorunludur");
    }

    // Aynı isimde başka skill var mı kontrol et (kendisi hariç)
    $checkSQL = "SELECT id FROM skills WHERE LOWER(skill_name) = LOWER(?) AND id != ?";
    $stmt = $mysqli->prepare($checkSQL);
    if (!$stmt) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }
    $stmt->bind_param('si', $skillName, $skillId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Bu isimde başka bir beceri zaten mevcut."
        ]);
        exit;
    }
    $stmt->close();

    // Skill'i güncelle
    $updateSQL = "UPDATE skills SET skill_name = ?, skill_description = ?, category = ?, is_active = ? WHERE id = ?";
    $stmtUpdate = $mysqli->prepare($updateSQL);
    if (!$stmtUpdate) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }
    $stmtUpdate->bind_param('sssii', $skillName, $skillDescription, $category, $isActive, $skillId);

    if ($stmtUpdate->execute()) {
        if ($stmtUpdate->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Beceri başarıyla güncellendi."
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Beceri bulunamadı veya değişiklik yapılmadı."
            ]);
        }
    } else {
        throw new Exception("Beceri güncellenemedi: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    $mysqli->close();

} catch (Exception $e) {
    error_log("Beceri güncelleme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "Hata: " . $e->getMessage()
    ]);
}
?>
