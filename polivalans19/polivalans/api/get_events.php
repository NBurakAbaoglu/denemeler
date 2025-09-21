<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    // Events tablosundan etkinlikleri çek
    $query = "
        SELECT 
            e.id,
            e.event_name,
            e.event_date as start_date,
            e.end_date,
            e.capacity,
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            s.skill_name
        FROM events e
        LEFT JOIN teachers t ON e.teacher_id = t.id
        LEFT JOIN skills s ON e.lesson_id = s.id
        ORDER BY e.event_date ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'events' => $events,
        'count' => count($events)
    ]);
    
} catch (Exception $e) {
    error_log("Etkinlik yükleme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Etkinlikler yüklenirken hata oluştu: ' . $e->getMessage()
    ]);
}
?>
