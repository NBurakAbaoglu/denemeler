<?php
require 'config.php';  // DB bağlantını buradan al

$mysqli = getDBConnection();

if (!isset($_GET['event_name'])) {
    echo json_encode([]);
    exit;
}

$event_name = $_GET['event_name'];

// Burada events tablosundaki event_name ile gelen event_name eşleşiyor
// sonra bu event_name'e karşılık gelen ders adını teachers tablosundaki course ile karşılaştırıyoruz
// ama event_name ile course aynı olmalı mı? Genelde hayır, o yüzden events.course_title ile teachers.course karşılaştırması yapılabilir

// Ama senin verdiğin örneklerde events tablosunda course_title var, teachers tablosunda course var
// Öyleyse şöyle yapalım:

$sql = "
    SELECT DISTINCT t.id, CONCAT(t.first_name, ' ', t.last_name) AS name
    FROM teachers t
    JOIN events e ON t.course = e.course_title
    WHERE e.event_name = ?
    ORDER BY t.first_name
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $event_name);
$stmt->execute();
$result = $stmt->get_result();

$teachers = [];
while ($row = $result->fetch_assoc()) {
    $teachers[] = [
        'id' => $row['id'],
        'name' => $row['name']
    ];
}

header('Content-Type: application/json');
echo json_encode($teachers);
