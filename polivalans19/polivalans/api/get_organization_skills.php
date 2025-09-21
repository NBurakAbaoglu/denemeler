<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $organizationId = $_GET['organization_id'] ?? null;
    
    if (!$organizationId) {
        throw new Exception("Organizasyon ID gerekli");
    }

    $query = "
        SELECT 
            os.id,
            s.skill_name,
            s.skill_description,
            s.category,
            os.priority
        FROM organization_skills os
        JOIN skills s ON os.skill_id = s.id
        WHERE os.organization_id = ? AND s.is_active = 1
        ORDER BY s.skill_name ASC
    ";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) throw new Exception("Sorgu hazırlama hatası: " . $mysqli->error);
    
    $stmt->bind_param("i", $organizationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) throw new Exception("Sorgu hatası: " . $mysqli->error);

    $skills = [];
    while ($row = $result->fetch_assoc()) {
        $skills[] = [
            'id' => $row['id'],
            'skill_name' => $row['skill_name'],
            'skill_description' => $row['skill_description'],
            'category' => $row['category'],
            'priority' => $row['priority']
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'skills' => $skills
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
