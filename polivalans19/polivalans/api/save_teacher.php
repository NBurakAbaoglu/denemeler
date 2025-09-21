<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    // JSON verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception("Geçersiz JSON verisi");
    }

    // Zorunlu alanları kontrol et
    $requiredFields = ['first_name', 'last_name', 'organization_id', 'skills'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("$field alanı zorunludur");
        }
    }

    // Transaction başlat
    $mysqli->autocommit(false);

    try {
        // Öğretmeni teachers tablosuna ekle
        $stmt = $mysqli->prepare("
            INSERT INTO teachers (
                first_name, 
                last_name, 
                specialization, 
                created_at,
                updated_at
            ) VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        if (!$stmt) throw new Exception("Öğretmen sorgusu hazırlama hatası: " . $mysqli->error);
        
        $specialization = $input['specialization'] ?? null;
        $stmt->bind_param(
            "sss", 
            $input['first_name'],
            $input['last_name'],
            $specialization
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Öğretmen kaydetme hatası: " . $stmt->error);
        }
        
        $teacherId = $mysqli->insert_id;
        $stmt->close();

        // Seçili becerileri tep_teachers tablosuna ekle
        $stmt = $mysqli->prepare("
            INSERT INTO tep_teachers (
                person_name,
                organization_name,
                skill_name,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        if (!$stmt) throw new Exception("TEP öğretmen sorgusu hazırlama hatası: " . $mysqli->error);

        $personName = $input['first_name'] . ' ' . $input['last_name'];
        
        // Organizasyon adını al
        $orgStmt = $mysqli->prepare("SELECT name FROM organizations WHERE id = ?");
        $orgStmt->bind_param("i", $input['organization_id']);
        $orgStmt->execute();
        $orgResult = $orgStmt->get_result();
        $organizationName = $orgResult->fetch_assoc()['name'];
        $orgStmt->close();
        
        $successCount = 0;

        foreach ($input['skills'] as $skill) {
            $stmt->bind_param(
                "sss",
                $personName,
                $organizationName,
                $skill['name']
            );
            
            if ($stmt->execute()) {
                $successCount++;
            } else {
                error_log("TEP öğretmen kaydetme hatası: " . $stmt->error);
            }
        }
        
        $stmt->close();

        // Transaction'ı commit et
        $mysqli->commit();
        $mysqli->autocommit(true);

        echo json_encode([
            'success' => true,
            'message' => "Öğretmen başarıyla kaydedildi. $successCount beceri eklendi.",
            'teacher_id' => $teacherId,
            'skills_added' => $successCount
        ]);

    } catch (Exception $e) {
        // Transaction'ı rollback et
        $mysqli->rollback();
        $mysqli->autocommit(true);
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
