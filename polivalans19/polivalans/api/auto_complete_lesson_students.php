<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['lesson_id'])) {
        throw new Exception("Ders ID gerekli");
    }

    $lessonId = intval($data['lesson_id']);

    // Bu dersi alan tüm kişileri bul
    $findStudentsQuery = "
        SELECT DISTINCT ps.person_id, ps.organization_id, ps.skill_id
        FROM planned_skills ps
        WHERE ps.skill_id = ?
    ";
    
    $stmt = $mysqli->prepare($findStudentsQuery);
    $stmt->bind_param('i', $lessonId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $affectedCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        // Her kişiyi tamamlandı yap
        $updateQuery = "
            UPDATE planned_skills 
            SET success_status = 'completed', 
                updated_at = NOW()
            WHERE person_id = ? AND organization_id = ? AND skill_id = ?
        ";
        
        $updateStmt = $mysqli->prepare($updateQuery);
        $updateStmt->bind_param('iii', $row['person_id'], $row['organization_id'], $row['skill_id']);
        
        if ($updateStmt->execute()) {
            $affectedCount++;
        }
        
        $updateStmt->close();
    }
    
    $stmt->close();
    closeDBConnection($mysqli);

    echo json_encode([
        'success' => true,
        'affected_count' => $affectedCount,
        'message' => "Ders için {$affectedCount} kişi tamamlandı yapıldı"
    ]);

} catch (Exception $e) {
    error_log("Ders öğrenci tamamlandı yapma hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
