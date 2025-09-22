<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $input = json_decode(file_get_contents('php://input'), true);
    $plannedMultiSkillId = $input['planned_multi_skill_id'] ?? null;
    $trainingStatus = $input['training_status'] ?? null;

    if (!$plannedMultiSkillId || !$trainingStatus) {
        throw new Exception("Gerekli parametreler eksik");
    }

    // Geçerli durum kontrolü
    if (!in_array($trainingStatus, ['tamamlandı', 'tamamlanmadı'])) {
        throw new Exception("Geçersiz eğitim durumu");
    }

    // planned_multi_skill kaydının var olup olmadığını kontrol et
    $checkStmt = $mysqli->prepare("SELECT id, person_id, organization_id FROM planned_multi_skill WHERE id = ?");
    $checkStmt->bind_param("i", $plannedMultiSkillId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        throw new Exception("Planned multi skill kaydı bulunamadı");
    }
    
    $plannedSkill = $checkResult->fetch_assoc();
    $checkStmt->close();

    // Eğitim durumu kaydı var mı kontrol et
    $existingStmt = $mysqli->prepare("SELECT id FROM trainer_training_status WHERE planned_multi_skill_id = ?");
    $existingStmt->bind_param("i", $plannedMultiSkillId);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();
    $existing = $existingResult->fetch_assoc();
    $existingStmt->close();

    $completionDate = ($trainingStatus === 'tamamlandı') ? date('Y-m-d') : null;

    if ($existing) {
        // Mevcut kaydı güncelle
        $updateStmt = $mysqli->prepare("
            UPDATE trainer_training_status 
            SET training_status = ?, completion_date = ?, updated_at = NOW()
            WHERE planned_multi_skill_id = ?
        ");
        $updateStmt->bind_param("ssi", $trainingStatus, $completionDate, $plannedMultiSkillId);
        
        if ($updateStmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Eğitim durumu başarıyla güncellendi',
                'training_status' => $trainingStatus,
                'completion_date' => $completionDate
            ]);
        } else {
            throw new Exception("Eğitim durumu güncellenemedi: " . $updateStmt->error);
        }
        $updateStmt->close();
    } else {
        // Yeni kayıt oluştur
        $insertStmt = $mysqli->prepare("
            INSERT INTO trainer_training_status 
            (planned_multi_skill_id, person_id, organization_id, training_status, completion_date, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $insertStmt->bind_param("iiiss", 
            $plannedMultiSkillId, 
            $plannedSkill['person_id'], 
            $plannedSkill['organization_id'], 
            $trainingStatus, 
            $completionDate
        );
        
        if ($insertStmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Eğitim durumu başarıyla kaydedildi',
                'training_status' => $trainingStatus,
                'completion_date' => $completionDate
            ]);
        } else {
            throw new Exception("Eğitim durumu kaydedilemedi: " . $insertStmt->error);
        }
        $insertStmt->close();
    }

} catch (Exception $e) {
    error_log("Eğitim durumu güncelleme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
