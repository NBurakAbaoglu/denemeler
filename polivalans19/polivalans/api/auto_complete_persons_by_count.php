<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['person_count']) || $data['person_count'] < 1) {
        throw new Exception("Geçerli bir kişi adedi gerekli");
    }

    $personCount = intval($data['person_count']);

    // Tamamlanmamış kişileri bul ve sınırla
    $findPersonsQuery = "
        SELECT DISTINCT ps.person_id, ps.organization_id, ps.skill_id
        FROM planned_skills ps
        WHERE ps.success_status != 'completed'
        ORDER BY ps.created_at ASC
        LIMIT ?
    ";
    
    $stmt = $mysqli->prepare($findPersonsQuery);
    $stmt->bind_param('i', $personCount);
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
        'message' => "{$affectedCount} kişi tamamlandı yapıldı"
    ]);

} catch (Exception $e) {
    error_log("Kişi tamamlandı yapma hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
