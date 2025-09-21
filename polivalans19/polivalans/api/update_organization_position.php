<?php
require_once '../config.php';
//sutün sıralama için 
header('Content-Type: application/json');

try {
    // Veritabanı bağlantısını al
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Veritabanı bağlantısı kurulamadı');
    }
    
    // JSON verisini al
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    // Debug bilgilerini response'a ekle
    $debugInfo = [
        'raw_input' => $rawInput,
        'decoded_input' => $input,
        'has_organization_name' => isset($input['organization_name']),
        'has_new_position' => isset($input['new_position']),
        'organization_name_value' => isset($input['organization_name']) ? $input['organization_name'] : 'NOT_SET',
        'new_position_value' => isset($input['new_position']) ? $input['new_position'] : 'NOT_SET',
        'input_keys' => $input ? array_keys($input) : 'NULL',
        'input_type' => gettype($input)
    ];
    
    if (!$input || !isset($input['organization_name']) || !isset($input['new_position'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Gerekli parametreler eksik',
            'debug' => $debugInfo
        ]);
        exit;
    }
    
    $organizationName = trim($input['organization_name']);
    $newPosition = (int)$input['new_position'];
    
    if (empty($organizationName)) {
        throw new Exception('Organizasyon adı boş olamaz');
    }
    
    if ($newPosition < 3) {
        throw new Exception('Pozisyon 3\'ten küçük olamaz');
    }
    
    // Organizasyonun mevcut pozisyonunu al
    $currentPositionQuery = "SELECT column_position FROM organizations WHERE name = ?";
    $stmt = $conn->prepare($currentPositionQuery);
    $stmt->bind_param("s", $organizationName);
    $stmt->execute();
    $currentPositionResult = $stmt->get_result();
    
    if (!$currentPositionResult || $currentPositionResult->num_rows === 0) {
        throw new Exception('Organizasyon bulunamadı');
    }
    
    $currentPosition = $currentPositionResult->fetch_assoc()['column_position'];
    
    // Eğer pozisyon aynıysa güncelleme yapma
    if ($currentPosition == $newPosition) {
        echo json_encode([
            'success' => true,
            'message' => 'Pozisyon zaten aynı'
        ]);
        exit;
    }
    
    // Yeni pozisyonu kullanan başka organizasyon var mı kontrol et
    $checkPositionQuery = "SELECT name FROM organizations WHERE column_position = ? AND name != ?";
    $stmt = $conn->prepare($checkPositionQuery);
    $stmt->bind_param("is", $newPosition, $organizationName);
    $stmt->execute();
    $checkPositionResult = $stmt->get_result();
    
    if ($checkPositionResult && $checkPositionResult->num_rows > 0) {
        // Pozisyon çakışması varsa, mevcut organizasyonu geçici bir pozisyona taşı
        $tempPosition = 999; // Geçici pozisyon
        $updateTempQuery = "UPDATE organizations SET column_position = ? WHERE column_position = ? AND name != ?";
        $stmt = $conn->prepare($updateTempQuery);
        $stmt->bind_param("iii", $tempPosition, $newPosition, $organizationName);
        $stmt->execute();
    }
    
    // Organizasyon pozisyonunu güncelle
    $updateQuery = "UPDATE organizations SET column_position = ? WHERE name = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("is", $newPosition, $organizationName);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Organizasyon pozisyonu başarıyla güncellendi',
            'old_position' => $currentPosition,
            'new_position' => $newPosition
        ]);
    } else {
        throw new Exception('Pozisyon güncellenirken hata oluştu');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Veritabanı bağlantısını kapat
    if (isset($conn)) {
        closeDBConnection($conn);
    }
}
?>
