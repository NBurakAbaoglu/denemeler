<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $skillName = trim($input['skill_name'] ?? '');
    $skillDescription = trim($input['skill_description'] ?? '');
    $category = trim($input['category'] ?? '');
    $isActive = $input['is_active'] ?? 1;

    if (!$skillName || !$skillDescription) {
        throw new Exception("Skill adı ve açıklaması zorunludur");
    }

    // Aynı isimde skill var mı kontrol et
    $checkSQL = "SELECT id FROM skills WHERE LOWER(skill_name) = LOWER(?)";
    $stmt = $mysqli->prepare($checkSQL);
    if (!$stmt) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }
    $stmt->bind_param('s', $skillName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Bu isimde bir beceri zaten mevcut."
        ]);
        exit;
    }
    $stmt->close();

    // Yeni skill'i ekle
    $insertSQL = "INSERT INTO skills (skill_name, skill_description, category, is_active) VALUES (?, ?, ?, ?)";
    $stmtInsert = $mysqli->prepare($insertSQL);
    if (!$stmtInsert) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }
    $stmtInsert->bind_param('sssi', $skillName, $skillDescription, $category, $isActive);

    if ($stmtInsert->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Beceri başarıyla eklendi.",
            'skill_id' => $mysqli->insert_id
        ]);
    } else {
        throw new Exception("Beceri eklenemedi: " . $stmtInsert->error);
    }
    $stmtInsert->close();

    $mysqli->close();

} catch (Exception $e) {
    error_log("Beceri ekleme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "Hata: " . $e->getMessage()
    ]);
}
?>
