<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz veri formatı');
    }

    $person_id = $input['person_id'] ?? null;
    $organization_id = $input['organization_id'] ?? null;
    $teacher_id = $input['teacher_id'] ?? null;
    $success_status = $input['success_status'] ?? 'pending';

    if (!$person_id || !$organization_id) {
        throw new Exception('Person ID ve Organization ID gerekli');
    }

    // Mevcut kaydı kontrol et
    $checkQuery = "SELECT id FROM planned_multi_skill WHERE person_id = ? AND organization_id = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$person_id, $organization_id]);
    $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingRecord) {
        // Mevcut kaydı güncelle
        $updateQuery = "UPDATE planned_multi_skill SET 
            teacher_id = ?, 
            success_status = ?,
            status = ?,
            updated_at = NOW()
            WHERE person_id = ? AND organization_id = ?";
        
        $status = ($success_status === 'completed') ? 'tamamlandi' : 'planlandi';
        
        $updateStmt = $pdo->prepare($updateQuery);
        $result = $updateStmt->execute([
            $teacher_id,
            $success_status,
            $status,
            $person_id,
            $organization_id
        ]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Çoklu beceri durumu güncellendi'
            ]);
        } else {
            throw new Exception('Güncelleme başarısız');
        }
    } else {
        // Yeni kayıt oluştur
        $insertQuery = "INSERT INTO planned_multi_skill 
            (person_id, organization_id, teacher_id, status, success_status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $status = ($success_status === 'completed') ? 'tamamlandi' : 'planlandi';
        
        $insertStmt = $pdo->prepare($insertQuery);
        $result = $insertStmt->execute([
            $person_id,
            $organization_id,
            $teacher_id,
            $status,
            $success_status
        ]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Çoklu beceri kaydı oluşturuldu'
            ]);
        } else {
            throw new Exception('Kayıt oluşturma başarısız');
        }
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>