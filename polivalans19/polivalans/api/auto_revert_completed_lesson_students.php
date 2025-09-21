<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['lesson_id'])) {
        throw new Exception('Ders ID gerekli');
    }
    
    $lessonId = $input['lesson_id'];
    
    // Bu dersi alan ve tamamlandı olan kişileri bul
    $checkQuery = "
        SELECT ps.id, ps.person_id, ps.organization_id, ps.skill_id, ps.success_status
        FROM planned_skills ps
        WHERE ps.skill_id = ? AND ps.success_status = 'completed'
    ";
    
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$lessonId]);
    $completedStudents = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($completedStudents)) {
        echo json_encode([
            'success' => true,
            'affected_count' => 0,
            'message' => 'Bu ders için tamamlandı olan kişi bulunamadı'
        ]);
        return;
    }
    
    // Tamamlandı olanları tamamlanmadı yap
    $updateQuery = "
        UPDATE planned_skills 
        SET success_status = 'pending', updated_at = NOW()
        WHERE skill_id = ? AND success_status = 'completed'
    ";
    
    $updateStmt = $pdo->prepare($updateQuery);
    $result = $updateStmt->execute([$lessonId]);
    
    if ($result) {
        $affectedCount = $updateStmt->rowCount();
        
        // Organization_images tablosunu da güncelle
        foreach ($completedStudents as $student) {
            // Kişi adını al
            $personQuery = "SELECT name FROM persons WHERE id = ?";
            $personStmt = $pdo->prepare($personQuery);
            $personStmt->execute([$student['person_id']]);
            $person = $personStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($person) {
                // Pie chart'ı güncelle (tamamlanmadı = pie (3).png)
                $imageUpdateQuery = "
                    UPDATE organization_images 
                    SET image_name = 'pie (3).png', updated_at = NOW()
                    WHERE organization_id = ? AND row_name = ?
                ";
                $imageStmt = $pdo->prepare($imageUpdateQuery);
                $imageStmt->execute([$student['organization_id'], $person['name']]);
            }
        }
        
        echo json_encode([
            'success' => true,
            'affected_count' => $affectedCount,
            'message' => "Ders için {$affectedCount} kişi tamamlanmadı yapıldı"
        ]);
    } else {
        throw new Exception('Güncelleme başarısız');
    }
    
} catch (Exception $e) {
    error_log("Tamamlanmadı yapma hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
