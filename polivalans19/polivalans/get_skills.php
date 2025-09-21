<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $query = "SELECT id, skill_name FROM skills WHERE is_active = 1 ORDER BY skill_name ASC";
    $result = $mysqli->query($query);
    
    if (!$result) throw new Exception("Sorgu hatası: " . $mysqli->error);

    $skills = [];
    while ($row = $result->fetch_assoc()) {
        $skills[] = [
            'id' => $row['id'],
            'skill_name' => $row['skill_name']
        ];
    }

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