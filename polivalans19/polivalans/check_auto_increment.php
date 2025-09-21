<?php
require_once 'config.php';

try {
    echo "=== AUTO_INCREMENT Değerleri Kontrol Ediliyor ===\n\n";
    
    // Ana tabloları kontrol et
    $tables = ['persons', 'organizations', 'skills', 'organization_skills', 'planned_skills', 'planlandi', 'events', 'tep_teachers'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLE STATUS LIKE '$table'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "📋 Tablo: $table\n";
            echo "   AUTO_INCREMENT: " . ($result['Auto_increment'] ?? 'NULL') . "\n";
            echo "   Engine: " . $result['Engine'] . "\n";
            echo "   Rows: " . $result['Rows'] . "\n\n";
        } else {
            echo "❌ Tablo bulunamadı: $table\n\n";
        }
    }
    
    // Her tablodaki maksimum ID'yi kontrol et
    echo "=== Maksimum ID Değerleri ===\n\n";
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT MAX(id) as max_id FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "📊 $table: Maksimum ID = " . ($result['max_id'] ?? 'NULL') . "\n";
        } catch (Exception $e) {
            echo "❌ $table: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
