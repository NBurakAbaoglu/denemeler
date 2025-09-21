<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $query = "SELECT DISTINCT id, person_name FROM tep_teachers ORDER BY person_name ASC";
    $result = $mysqli->query($query);
    
    if (!$result) throw new Exception("Sorgu hatası: " . $mysqli->error);

    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = [
            'id' => $row['id'],
            'person_name' => $row['person_name']
        ];
    }

    echo json_encode([
        'success' => true,
        'teachers' => $teachers
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>