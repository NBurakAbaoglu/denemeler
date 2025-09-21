<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $searchTerm = $_GET['q'] ?? '';
    $limit = $_GET['limit'] ?? 10;
    
    if (empty($searchTerm)) {
        echo json_encode([
            'success' => true,
            'skills' => []
        ]);
        exit;
    }

    $query = "
        SELECT 
            id,
            skill_name,
            skill_description,
            category
        FROM skills 
        WHERE is_active = 1 
        AND (skill_name LIKE ? OR skill_description LIKE ?)
        ORDER BY 
            CASE 
                WHEN skill_name LIKE ? THEN 1
                WHEN skill_name LIKE ? THEN 2
                ELSE 3
            END,
            skill_name ASC
        LIMIT ?
    ";
    
    $searchPattern = '%' . $searchTerm . '%';
    $exactPattern = $searchTerm . '%';
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) throw new Exception("Sorgu hazırlama hatası: " . $mysqli->error);
    
    $stmt->bind_param("ssssi", 
        $searchPattern,  // skill_name LIKE %term%
        $searchPattern,  // skill_description LIKE %term%
        $exactPattern,   // skill_name LIKE term% (exact start)
        $exactPattern,   // skill_name LIKE term% (exact start)
        $limit
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) throw new Exception("Sorgu hatası: " . $mysqli->error);

    $skills = [];
    while ($row = $result->fetch_assoc()) {
        $skills[] = [
            'id' => $row['id'],
            'skill_name' => $row['skill_name'],
            'skill_description' => $row['skill_description'],
            'category' => $row['category']
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
