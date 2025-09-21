<?php
// api/get_person_id.php

header('Content-Type: application/json');

// JSON'dan gelen veriyi al
$data = json_decode(file_get_contents('php://input'), true);

// Gelen veride 'name' var mı kontrol et
if (!isset($data['name']) || empty(trim($data['name']))) {
    echo json_encode(['success' => false, 'message' => 'İsim gönderilmedi']);
    exit;
}

$name = trim($data['name']);

// config.php dosyasını dahil et
require_once '../config.php'; // doğru yol olduğundan emin ol

try {
    // persons tablosunda adı arıyoruz (büyük/küçük harf duyarsız)
    $stmt = $pdo->prepare('SELECT id FROM persons WHERE LOWER(name) = LOWER(:name) LIMIT 1');
    $stmt->execute(['name' => $name]);

    $person = $stmt->fetch();

    if ($person) {
        echo json_encode(['success' => true, 'personId' => $person['id']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kişi bulunamadı']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sunucu hatası oluştu.',
        'error' => $e->getMessage() // prod ortamda bunu kapatabilirsin
    ]);
}
?>
