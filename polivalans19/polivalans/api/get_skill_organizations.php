<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $skillId = $_GET['skill_id'] ?? null;
    
    if (!$skillId) {
        throw new Exception("Skill ID gerekli");
    }

    $query = "
        SELECT 
            os.id,
            o.name as organization_name,
            os.priority,
            COUNT(ps.id) as planned_count,
            COUNT(e.id) as events_count
        FROM organization_skills os
        JOIN organizations o ON os.organization_id = o.id
        LEFT JOIN planned_skills ps ON os.skill_id = ps.skill_id AND os.organization_id = ps.organization_id
        LEFT JOIN events e ON os.skill_id = e.lesson_id
        WHERE os.skill_id = ?
        GROUP BY os.id, o.name, os.priority
        ORDER BY o.name ASC
    ";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) throw new Exception("Sorgu hazırlama hatası: " . $mysqli->error);
    
    $stmt->bind_param("i", $skillId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) throw new Exception("Sorgu hatası: " . $mysqli->error);

    $organizations = [];
    while ($row = $result->fetch_assoc()) {
        $organizations[] = [
            'id' => $row['id'],
            'organization_name' => $row['organization_name'],
            'priority' => $row['priority'],
            'planned_count' => $row['planned_count'],
            'events_count' => $row['events_count']
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'organizations' => $organizations
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
