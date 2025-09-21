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
    $skillName = mb_strtoupper(trim($input['skill_name'] ?? ''), 'UTF-8');
    $skillDescription = trim($input['skill_description'] ?? '');
    $priority = $input['priority'] ?? 'medium';

    if (!$organizationId || !$skillName) {
        throw new Exception("Organizasyon ID ve Skill Name zorunludur");
    }

    // Önce skill_name ile skill_id'yi bul
    $skillSQL = "SELECT id FROM skills WHERE skill_name = ? AND is_active = 1";
    $stmt = $mysqli->prepare($skillSQL);
    if (!$stmt) {
        throw new Exception("Skill sorgusu hazırlanamadı: " . $mysqli->error);
    }
    $stmt->bind_param('s', $skillName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Skill yoksa oluştur
        $insertSkillSQL = "INSERT INTO skills (skill_name, skill_description, category, is_active) VALUES (?, ?, 'Temel Beceri', 1)";
        $stmtInsert = $mysqli->prepare($insertSkillSQL);
        if (!$stmtInsert) {
            throw new Exception("Skill ekleme sorgusu hazırlanamadı: " . $mysqli->error);
        }
        $stmtInsert->bind_param('ss', $skillName, $skillDescription);
        if (!$stmtInsert->execute()) {
            throw new Exception("Skill eklenemedi: " . $stmtInsert->error);
        }
        $skillId = $mysqli->insert_id;
        $stmtInsert->close();
    } else {
        $skillRow = $result->fetch_assoc();
        $skillId = $skillRow['id'];
        
        // Bu skill başka organizasyonlarda var mı kontrol et
        $existingOrgsSQL = "
            SELECT DISTINCT o.name as organization_name 
            FROM organization_skills os 
            JOIN organizations o ON os.organization_id = o.id 
            WHERE os.skill_id = ? AND os.organization_id != ?
        ";
        $stmtOrgs = $mysqli->prepare($existingOrgsSQL);
        if ($stmtOrgs) {
            $stmtOrgs->bind_param('ii', $skillId, $organizationId);
            $stmtOrgs->execute();
            $orgsResult = $stmtOrgs->get_result();
            
            if ($orgsResult->num_rows > 0) {
                $existingOrgs = [];
                while ($orgRow = $orgsResult->fetch_assoc()) {
                    $existingOrgs[] = $orgRow['organization_name'];
                }
                
                // Başka organizasyonlarda varsa özel response döndür
                echo json_encode([
                    'success' => false,
                    'exists_in_other_orgs' => true,
                    'skill_name' => $skillName,
                    'existing_organizations' => $existingOrgs,
                    'message' => "Bu beceri şu organizasyonlarda zaten tanımlanmış: " . implode(', ', $existingOrgs)
                ]);
                exit;
            }
            $stmtOrgs->close();
        }
    }
    $stmt->close();

    // Aynı organizasyon ve skill kombinasyonu var mı kontrol et
    $checkSQL = "SELECT id FROM organization_skills WHERE organization_id = ? AND skill_id = ?";
    $stmt = $mysqli->prepare($checkSQL);
    if (!$stmt) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }
    $stmt->bind_param('ii', $organizationId, $skillId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Aynı skill zaten bu organizasyona eklenmiş - direkt uyarı ver
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
            'message' => "Beceri başarıyla eklendi."
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
