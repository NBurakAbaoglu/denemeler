<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['organization_id']) || !isset($input['row_name']) || !isset($input['pie_chart_status'])) {
        throw new Exception('Gerekli parametreler eksik');
    }
    
    $organization_id = $input['organization_id'];
    $row_name = $input['row_name'];
    $pie_chart_status = $input['pie_chart_status']; // 'completed' veya 'pending'
    
    // Pie chart durumuna göre görsel adını belirle
    $image_name = ($pie_chart_status === 'completed') ? 'pie (5).png' : 'pie (4).png';
    
    // Önce mevcut kayıt var mı kontrol et
    $checkStmt = $pdo->prepare("
        SELECT id, image_name 
        FROM organization_images 
        WHERE organization_id = ? AND row_name = ?
    ");
    $checkStmt->execute([$organization_id, $row_name]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Mevcut kaydı güncelle
        $updateStmt = $pdo->prepare("
            UPDATE organization_images 
            SET image_name = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $updateStmt->execute([$image_name, $existing['id']]);
        
        echo json_encode([
            'success' => true,
            'action' => 'updated',
            'image_name' => $image_name,
            'message' => 'Pie chart durumu güncellendi'
        ]);
    } else {
        // Yeni kayıt oluştur
        $insertStmt = $pdo->prepare("
            INSERT INTO organization_images (organization_id, row_name, image_name, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $insertStmt->execute([$organization_id, $row_name, $image_name]);
        
        echo json_encode([
            'success' => true,
            'action' => 'created',
            'image_name' => $image_name,
            'message' => 'Pie chart durumu kaydedildi'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
