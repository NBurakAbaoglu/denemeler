<?php
require_once 'config.php';

header('Content-Type: application/json');

// Veritabanı bağlantısını al
$mysqli = getDBConnection();

if (!$mysqli) {
    die(json_encode(["error" => "Veritabanı bağlantısı kurulamadı."]));
}

// Sorgu: Etkinlik tarihine göre ve kurs adına göre sıralı şekilde course_title dahil çekiyoruz
$sql = "SELECT id, course_title, event_date, end_date FROM events ORDER BY event_date ASC, course_title";
$result = executeQuery($mysqli, $sql);

$events = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
} else {
    // Hata durumunda veya veri yoksa log kaydı yap
    error_log("Etkinlik verisi bulunamadı veya sorgu hatası oluştu.");
}

// JSON formatında sonucu döndür
echo json_encode($events);

// Bağlantıyı kapat
closeDBConnection($mysqli);
?>
