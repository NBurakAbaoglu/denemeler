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
    $personName = $input['person_name'] ?? null;
    $organizationName = $input['organization_name'] ?? null;
    $skillName = $input['skill_name'] ?? null;
    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;

    if (!$personName || !$organizationName || !$skillName) {
        throw new Exception("Gerekli veriler eksik");
    }

    // Önce kaydın var olup olmadığını kontrol et ve ID'lerini al
    $checkSQL = "SELECT ps.id 
                 FROM planned_skills ps
                 JOIN persons p ON ps.person_id = p.id
                 JOIN organizations o ON ps.organization_id = o.id
                 JOIN skills s ON ps.skill_id = s.id
                 WHERE p.name = ? AND o.name = ? AND s.skill_name = ? AND s.is_active = 1";
    $checkStmt = $mysqli->prepare($checkSQL);
    
    if (!$checkStmt) {
        throw new Exception("Kontrol sorgusu hazırlanamadı: " . $mysqli->error);
    }
    
    $checkStmt->bind_param("sss", $personName, $organizationName, $skillName);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $checkStmt->close();
    
    if ($result->num_rows > 0) {
        // Kayıt varsa güncelle
        $row = $result->fetch_assoc();
        $plannedSkillId = $row['id'];
        
        $updateSQL = "UPDATE planned_skills SET start_date = ?, end_date = ? WHERE id = ?";
        $stmt = $mysqli->prepare($updateSQL);
        
        if (!$stmt) {
            throw new Exception("Update sorgusu hazırlanamadı: " . $mysqli->error);
        }
        
        $stmt->bind_param("ssi", $startDate, $endDate, $plannedSkillId);
        
        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Tarihler başarıyla güncellendi',
                'affected_rows' => $affectedRows
            ]);
        } else {
            throw new Exception("Tarih güncelleme başarısız: " . $stmt->error);
        }
    } else {
        // Kayıt yoksa yeni kayıt oluştur
        // Önce gerekli ID'leri bul
        $personSQL = "SELECT id FROM persons WHERE name = ?";
        $personStmt = $mysqli->prepare($personSQL);
        $personStmt->bind_param("s", $personName);
        $personStmt->execute();
        $personResult = $personStmt->get_result();
        $personStmt->close();
        
        if ($personResult->num_rows === 0) {
            throw new Exception("Kişi bulunamadı: " . $personName);
        }
        $personId = $personResult->fetch_assoc()['id'];
        
        $orgSQL = "SELECT id FROM organizations WHERE name = ?";
        $orgStmt = $mysqli->prepare($orgSQL);
        $orgStmt->bind_param("s", $organizationName);
        $orgStmt->execute();
        $orgResult = $orgStmt->get_result();
        $orgStmt->close();
        
        if ($orgResult->num_rows === 0) {
            throw new Exception("Organizasyon bulunamadı: " . $organizationName);
        }
        $orgId = $orgResult->fetch_assoc()['id'];
        
        $skillSQL = "SELECT s.id FROM skills s JOIN organization_skills os ON s.id = os.skill_id WHERE s.skill_name = ? AND os.organization_id = ? AND s.is_active = 1";
        $skillStmt = $mysqli->prepare($skillSQL);
        $skillStmt->bind_param("si", $skillName, $orgId);
        $skillStmt->execute();
        $skillResult = $skillStmt->get_result();
        $skillStmt->close();
        
        if ($skillResult->num_rows === 0) {
            throw new Exception("Beceri bulunamadı: " . $skillName);
        }
        $skillId = $skillResult->fetch_assoc()['id'];
        
        // Yeni kayıt oluştur
        $insertSQL = "INSERT INTO planned_skills (person_id, organization_id, skill_id, target_level, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, 'planned')";
        $stmt = $mysqli->prepare($insertSQL);
        
        if (!$stmt) {
            throw new Exception("Insert sorgusu hazırlanamadı: " . $mysqli->error);
        }
        
        $defaultTargetLevel = 3; // Varsayılan hedef seviye
        $stmt->bind_param("iiiiss", $personId, $orgId, $skillId, $defaultTargetLevel, $startDate, $endDate);
        
        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Yeni kayıt oluşturuldu ve tarihler kaydedildi',
                'affected_rows' => $affectedRows
            ]);
        } else {
            throw new Exception("Yeni kayıt oluşturma başarısız: " . $stmt->error);
        }
    }

} catch (Exception $e) {
    error_log("Planlanan beceri tarih güncelleme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
