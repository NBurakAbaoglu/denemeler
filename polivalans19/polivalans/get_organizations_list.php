<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Organizasyonları getir
    $query = "SELECT id, name FROM organizations ORDER BY name";
    $result = $pdo->query($query);
    $organizations = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'organizations' => $organizations
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Organizasyonlar yüklenirken hata: ' . $e->getMessage()
    ]);
}
?>
