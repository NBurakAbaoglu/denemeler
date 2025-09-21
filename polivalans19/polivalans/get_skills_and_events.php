<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    $data = [];

    // Planned skills verilerini çek
    $skillsSQL = "SELECT * FROM planned_skills";
    $skillsResult = $mysqli->query($skillsSQL);

    if (!$skillsResult) {
        throw new Exception("Planned skills sorgusu başarısız: " . $mysqli->error);
    }

    $skills = [];
    while ($row = $skillsResult->fetch_assoc()) {
        $skills[] = $row;
    }

    $data['planned_skills'] = $skills;

    // Events verilerini çek
    $eventsSQL = "SELECT * FROM events";
    $eventsResult = $mysqli->query($eventsSQL);

    if (!$eventsResult) {
        throw new Exception("Events sorgusu başarısız: " . $mysqli->error);
    }

    $events = [];
    while ($row = $eventsResult->fetch_assoc()) {
        $events[] = $row;
    }

    $data['events'] = $events;

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log("Veri çekme hatası: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
