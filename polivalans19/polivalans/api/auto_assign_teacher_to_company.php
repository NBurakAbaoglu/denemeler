<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['company_id']) || !isset($data['teacher_id'])) {
        throw new Exception("Şirket ID ve Eğitmen ID gerekli");
    }

    $companyId = intval($data['company_id']);
    $teacherId = intval($data['teacher_id']);

    // Bu şirketteki tüm kişileri bul
    $findPersonsQuery = "
        SELECT DISTINCT ps.person_id, ps.organization_id, ps.skill_id
        FROM planned_skills ps
        JOIN persons p ON ps.person_id = p.id
        WHERE p.company_name = (SELECT name FROM organizations WHERE id = ?)
    ";
    
    $stmt = $mysqli->prepare($findPersonsQuery);
    $stmt->bind_param('i', $companyId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $affectedCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        // Her kişi için eğitmen ata
        $updateQuery = "
            UPDATE planned_skills 
            SET teacher_id = ?, 
                updated_at = NOW()
            WHERE person_id = ? AND organization_id = ? AND skill_id = ?
        ";
        
        $updateStmt = $mysqli->prepare($updateQuery);
        $updateStmt->bind_param('iiii', $teacherId, $row['person_id'], $row['organization_id'], $row['skill_id']);
        
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
        'message' => "Şirket için {$affectedCount} kişiye eğitmen atandı"
    ]);

} catch (Exception $e) {
    error_log("Şirket eğitmen atama hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
