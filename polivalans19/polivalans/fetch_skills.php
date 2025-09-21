<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Veritabanı bağlantısı (config.php dosyanda $pdo tanımlı olmalı)
require_once 'config.php';

try {
    $query = "
        SELECT 
            s.skill_name,
            s.skill_description,
            s.category,
            COUNT(os.id) AS skill_count
        FROM skills s
        LEFT JOIN organization_skills os ON s.id = os.skill_id
        WHERE s.is_active = 1
        GROUP BY s.id, s.skill_name, s.skill_description, s.category
        ORDER BY s.skill_name
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($results as $row) {
        $data[] = [
            'skill_name' => $row['skill_name'],
            'count' => (int)$row['skill_count']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
