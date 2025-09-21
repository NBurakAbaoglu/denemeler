<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

try {
    // Tüm organizasyon becerilerini al
    $query = "
        SELECT 
            os.id,
            os.organization_id,
            os.skill_id,
            s.skill_name,
            s.skill_description,
            s.category,
            os.priority,
            o.name as organization_name
        FROM organization_skills os
        JOIN skills s ON os.skill_id = s.id
        LEFT JOIN organizations o ON os.organization_id = o.id
        WHERE s.is_active = 1
        ORDER BY o.name, s.skill_name
    ";
    
    $stmt = $pdo->query($query);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
