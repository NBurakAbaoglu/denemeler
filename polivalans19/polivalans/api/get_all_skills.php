<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $category = $_GET['category'] ?? null;
    $isActive = $_GET['is_active'] ?? 1;

    $query = "
        SELECT 
            s.id,
            s.skill_name,
            s.skill_description,
            s.category,
            s.is_active,
            COUNT(os.id) as organization_count
        FROM skills s
        LEFT JOIN organization_skills os ON s.id = os.skill_id
        WHERE s.is_active = ?
    ";
    
    $params = [$isActive];
    $paramTypes = "i";
    
    if ($category) {
        $query .= " AND s.category = ?";
        $params[] = $category;
        $paramTypes .= "s";
    }
    
    $query .= " GROUP BY s.id, s.skill_name, s.skill_description, s.category, s.is_active";
    $query .= " ORDER BY s.skill_name ASC";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) throw new Exception("Sorgu hazırlama hatası: " . $mysqli->error);
    
    $stmt->bind_param($paramTypes, ...$params);
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
            'is_active' => $row['is_active'],
            'organization_count' => $row['organization_count']
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
