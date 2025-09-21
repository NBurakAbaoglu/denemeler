<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

try {
    // Tekrarlanan kayıtları bul ve en son olanı hariç diğerlerini sil
    $query = "
        DELETE pms1 FROM planned_multi_skill pms1
        INNER JOIN planned_multi_skill pms2 
        WHERE pms1.person_id = pms2.person_id 
        AND pms1.organization_id = pms2.organization_id 
        AND pms1.id < pms2.id
    ";
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute();
    
    $deleted_count = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => "Tekrarlanan kayıtlar temizlendi",
        'deleted_count' => $deleted_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
