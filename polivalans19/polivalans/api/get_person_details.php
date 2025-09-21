<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    // İsimler GET ile veya POST ile gönderilebilir, örnek GET ile:
    // ?names=Ali Veli,Ayşe Fatma
    if (!isset($_GET['names'])) {
        throw new Exception("Kişi isimleri alınamadı");
    }

    $namesParam = $_GET['names']; // "Ali Veli,Ayşe Fatma"
    $names = explode(',', $namesParam);

    // Güvenlik için prepared statement kullanacağız
    // Dinamik olarak soru işaretleri ekle
    $placeholders = implode(',', array_fill(0, count($names), '?'));

    $sql = "SELECT name, company_name, title, registration_no FROM persons WHERE name IN ($placeholders)";
    $stmt = $mysqli->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Sorgu hazırlanamadı: " . $mysqli->error);
    }

    // Parametreleri bağla (hepsi string olduğu için "s" ile)
    $types = str_repeat('s', count($names));
    $stmt->bind_param($types, ...$names);

    $stmt->execute();
    $result = $stmt->get_result();

    $persons = [];
    while ($row = $result->fetch_assoc()) {
        $persons[] = $row;
    }

    $stmt->close();
    closeDBConnection($mysqli);

    echo json_encode([
        'success' => true,
        'persons' => $persons
    ]);

} catch (Exception $e) {
    error_log("Kişi detayları getirme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
