<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Hataları ekrana basma, istersen logla

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=kurumsal;charset=utf8", "root", "");

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = "
        SELECT 
            o.name AS organization_name,
            COUNT(DISTINCT ps.person_id) AS person_count
        FROM person_skills ps
        JOIN organizations o ON ps.organization_id = o.id
        GROUP BY o.name
        ORDER BY o.name
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($results as $row) {
        $data[$row['organization_name']] = (int)$row['person_count'];
    }

    echo json_encode($data);

} catch (PDOException $e) {
    // Hata varsa JSON olarak dön
    http_response_code(500);
    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
