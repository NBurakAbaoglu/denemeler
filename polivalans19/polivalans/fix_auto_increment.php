<?php
require_once 'config.php';

try {
    echo "=== AUTO_INCREMENT Değerleri Düzeltiliyor ===\n\n";
    
    // Her tablo için AUTO_INCREMENT değerini düzelt
    $tables = [
        'persons' => 'SELECT MAX(id) + 1 FROM persons',
        'organizations' => 'SELECT MAX(id) + 1 FROM organizations', 
        'skills' => 'SELECT MAX(id) + 1 FROM skills',
        'organization_skills' => 'SELECT MAX(id) + 1 FROM organization_skills',
        'planned_skills' => 'SELECT MAX(id) + 1 FROM planned_skills',
        'planlandi' => 'SELECT MAX(id) + 1 FROM planlandi',
        'events' => 'SELECT MAX(id) + 1 FROM events',
        'tep_teachers' => 'SELECT MAX(id) + 1 FROM tep_teachers'
    ];
    
    foreach ($tables as $table => $query) {
        try {
            // Maksimum ID'yi al
            $stmt = $pdo->query($query);
            $maxId = $stmt->fetchColumn();
            
            // Eğer tablo boşsa 1'den başla
            if (!$maxId || $maxId == 0) {
                $maxId = 1;
            }
            
            // AUTO_INCREMENT değerini güncelle
            $alterQuery = "ALTER TABLE $table AUTO_INCREMENT = $maxId";
            $pdo->exec($alterQuery);
            
            echo "✅ $table: AUTO_INCREMENT = $maxId\n";
            
        } catch (Exception $e) {
            echo "❌ $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Kontrol Ediliyor ===\n";
    
    // Kontrol et
    foreach ($tables as $table => $query) {
        try {
            $stmt = $pdo->query("SHOW TABLE STATUS LIKE '$table'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "📋 $table: AUTO_INCREMENT = " . ($result['Auto_increment'] ?? 'NULL') . "\n";
        } catch (Exception $e) {
            echo "❌ $table: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
