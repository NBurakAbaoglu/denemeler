<?php
header('Content-Type: application/json');
require 'config.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE status = 'active'");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events = [];
    foreach ($rows as $row) {
        $events[] = [
            'id' => $row['id'],
            'name' => $row['event_name'],
            'teacher_id' => $row['teacher_id'],
            'lesson_id' => $row['lesson_id'],
            'startDate' => $row['event_date'],
            'endDate' => $row['end_date'],
            'capacity' => $row['capacity'],
            'description' => $row['description'],
        ];
    }

    echo json_encode($events);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
