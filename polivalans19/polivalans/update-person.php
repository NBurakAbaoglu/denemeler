<?php
// Geliştirme ortamında hata raporlamayı açıyoruz
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once 'config.php';

// Bağlantı oluştur
$conn = getDBConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı']);
    exit;
}

// Gelen JSON verisini okuyoruz
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz JSON verisi']);
    exit;
}

$personId = isset($input['personId']) ? intval($input['personId']) : 0;
$fieldName = isset($input['fieldName']) ? trim($input['fieldName']) : '';
$newValue = isset($input['newValue']) ? trim($input['newValue']) : '';

// Geçersiz parametre kontrolü
if ($personId <= 0 || empty($fieldName)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler']);
    exit;
}

// Sadece izin verilen alanlar (SQL Injection'a karşı)
$allowedFields = ['company_name', 'title', 'registration_no'];

if (!in_array($fieldName, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz fieldName']);
    exit;
}

// SQL sorgusunu hazırlıyoruz
if ($newValue === '') {
    // Yeni değer boşsa alanı NULL yapıyoruz
    $sql = "UPDATE persons SET $fieldName = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Sorgu hazırlanamadı: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('i', $personId);
} else {
    // Yeni değer varsa onu güncelliyoruz
    $sql = "UPDATE persons SET $fieldName = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Sorgu hazırlanamadı: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('si', $newValue, $personId);
}

// Sorguyu çalıştırıyoruz
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Güncelleme başarılı']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Hiçbir kayıt güncellenmedi (aynı veri olabilir)']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Güncelleme sırasında hata: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
