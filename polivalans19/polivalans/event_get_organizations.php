<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, name FROM organizations ORDER BY name ASC");
    $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($organizations);
} catch (PDOException $e) {
    // JSON formatında hata döndür
    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
