<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $query = "SELECT id, name, column_position FROM organizations ORDER BY name ASC";
    $result = $mysqli->query($query);
    
    if (!$result) throw new Exception("Sorgu hatası: " . $mysqli->error);

    $organizations = [];
    while ($row = $result->fetch_assoc()) {
        $organizations[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'column_position' => $row['column_position']
        ];
    }

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