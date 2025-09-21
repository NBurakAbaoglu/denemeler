<?php
require_once 'config.php';

class DatabaseHealthCheck {
    private $pdo;
    private $issues = [];
    private $warnings = [];
    private $fixes = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function runFullCheck() {
        echo "🔍 VERİTABANI SAĞLIK KONTROLÜ BAŞLATILIYOR...\n\n";
        
        $this->checkAutoIncrement();
        $this->checkForeignKeys();
        $this->checkDataIntegrity();
        $this->checkTableStructure();
        $this->checkOrphanedRecords();
        $this->checkIndexes();
        
        $this->displayResults();
        $this->suggestFixes();
    }
    
    private function checkAutoIncrement() {
        echo "📊 AUTO_INCREMENT Kontrolü...\n";
        
        $tables = ['persons', 'organizations', 'skills', 'organization_skills', 'planned_skills', 'planlandi', 'events', 'tep_teachers'];
        
        foreach ($tables as $table) {
            try {
                // Tablo durumunu kontrol et
                $stmt = $this->pdo->query("SHOW TABLE STATUS LIKE '$table'");
                $status = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$status) {
                    $this->issues[] = "❌ Tablo bulunamadı: $table";
                    continue;
                }
                
                $autoIncrement = $status['Auto_increment'];
                $rows = $status['Rows'];
                
                // Maksimum ID'yi kontrol et
                $stmt = $this->pdo->query("SELECT MAX(id) as max_id FROM $table");
                $maxId = $stmt->fetchColumn();
                
                if ($rows > 0 && $maxId && $autoIncrement <= $maxId) {
                    $this->issues[] = "⚠️ $table: AUTO_INCREMENT ($autoIncrement) <= MAX_ID ($maxId)";
                    $this->fixes[] = "ALTER TABLE $table AUTO_INCREMENT = " . ($maxId + 1);
                } elseif ($rows == 0 && $autoIncrement != 1) {
                    $this->warnings[] = "ℹ️ $table: Boş tablo ama AUTO_INCREMENT = $autoIncrement";
                } else {
                    echo "✅ $table: AUTO_INCREMENT = $autoIncrement, MAX_ID = " . ($maxId ?: 'NULL') . "\n";
                }
                
            } catch (Exception $e) {
                $this->issues[] = "❌ $table kontrol hatası: " . $e->getMessage();
            }
        }
        echo "\n";
    }
    
    private function checkForeignKeys() {
        echo "🔗 Foreign Key Kontrolü...\n";
        
        $foreignKeyChecks = [
            'planned_skills' => [
                'person_id' => 'persons',
                'organization_id' => 'organizations',
                'skill_id' => 'organization_skills'
            ],
            'planlandi' => [
                'person_id' => 'persons',
                'organization_id' => 'organizations',
                'skill_id' => 'organization_skills',
                'teacher_id' => 'tep_teachers',
                'event_id' => 'events'
            ],
            'organization_skills' => [
                'organization_id' => 'organizations',
                'skill_id' => 'skills'
            ],
            'events' => [
                'lesson_id' => 'organization_skills',
                'teacher_id' => 'tep_teachers'
            ]
        ];
        
        foreach ($foreignKeyChecks as $table => $relations) {
            foreach ($relations as $column => $refTable) {
                try {
                    $stmt = $this->pdo->query("
                        SELECT COUNT(*) as count 
                        FROM $table t 
                        LEFT JOIN $refTable r ON t.$column = r.id 
                        WHERE t.$column IS NOT NULL AND r.id IS NULL
                    ");
                    $orphanedCount = $stmt->fetchColumn();
                    
                    if ($orphanedCount > 0) {
                        $this->issues[] = "❌ $table.$column: $orphanedCount adet geçersiz referans ($refTable tablosunda yok)";
                    } else {
                        echo "✅ $table.$column → $refTable: Geçerli\n";
                    }
                } catch (Exception $e) {
                    $this->issues[] = "❌ $table.$column kontrol hatası: " . $e->getMessage();
                }
            }
        }
        echo "\n";
    }
    
    private function checkDataIntegrity() {
        echo "🔍 Veri Bütünlüğü Kontrolü...\n";
        
        // NULL ID kontrolü
        $tables = ['persons', 'organizations', 'skills', 'organization_skills', 'planned_skills', 'planlandi', 'events', 'tep_teachers'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table WHERE id IS NULL OR id = 0");
                $nullCount = $stmt->fetchColumn();
                
                if ($nullCount > 0) {
                    $this->issues[] = "❌ $table: $nullCount adet NULL veya 0 ID'li kayıt";
                } else {
                    echo "✅ $table: ID alanları geçerli\n";
                }
            } catch (Exception $e) {
                $this->issues[] = "❌ $table ID kontrol hatası: " . $e->getMessage();
            }
        }
        echo "\n";
    }
    
    private function checkTableStructure() {
        echo "🏗️ Tablo Yapısı Kontrolü...\n";
        
        $expectedTables = ['persons', 'organizations', 'skills', 'organization_skills', 'planned_skills', 'planlandi', 'events', 'tep_teachers'];
        
        foreach ($expectedTables as $table) {
            try {
                $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->fetch()) {
                    echo "✅ Tablo mevcut: $table\n";
                } else {
                    $this->issues[] = "❌ Eksik tablo: $table";
                }
            } catch (Exception $e) {
                $this->issues[] = "❌ $table yapı kontrol hatası: " . $e->getMessage();
            }
        }
        echo "\n";
    }
    
    private function checkOrphanedRecords() {
        echo "🧹 Yetim Kayıt Kontrolü...\n";
        
        // planned_skills'te olmayan ama planlandi'de olan kayıtlar
        try {
            $stmt = $this->pdo->query("
                SELECT COUNT(*) FROM planlandi pl 
                LEFT JOIN planned_skills ps ON pl.person_id = ps.person_id AND pl.organization_id = ps.organization_id AND pl.skill_id = ps.skill_id
                WHERE ps.id IS NULL
            ");
            $orphanedCount = $stmt->fetchColumn();
            
            if ($orphanedCount > 0) {
                $this->issues[] = "❌ planlandi tablosunda $orphanedCount adet yetim kayıt (planned_skills'te yok)";
            } else {
                echo "✅ planlandi → planned_skills: Bağlantılar geçerli\n";
            }
        } catch (Exception $e) {
            $this->issues[] = "❌ Yetim kayıt kontrol hatası: " . $e->getMessage();
        }
        echo "\n";
    }
    
    private function checkIndexes() {
        echo "📇 İndeks Kontrolü...\n";
        
        $tables = ['persons', 'organizations', 'skills', 'organization_skills', 'planned_skills', 'planlandi', 'events', 'tep_teachers'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SHOW INDEX FROM $table");
                $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $hasPrimaryKey = false;
                foreach ($indexes as $index) {
                    if ($index['Key_name'] === 'PRIMARY') {
                        $hasPrimaryKey = true;
                        break;
                    }
                }
                
                if (!$hasPrimaryKey) {
                    $this->issues[] = "❌ $table: Primary key eksik";
                } else {
                    echo "✅ $table: Primary key mevcut\n";
                }
            } catch (Exception $e) {
                $this->issues[] = "❌ $table indeks kontrol hatası: " . $e->getMessage();
            }
        }
        echo "\n";
    }
    
    private function displayResults() {
        echo "📋 SONUÇLAR:\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        if (empty($this->issues) && empty($this->warnings)) {
            echo "🎉 TEBRİKLER! Veritabanı sağlıklı durumda.\n\n";
            return;
        }
        
        if (!empty($this->issues)) {
            echo "🚨 SORUNLAR (" . count($this->issues) . " adet):\n";
            foreach ($this->issues as $issue) {
                echo "   $issue\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            echo "⚠️ UYARILAR (" . count($this->warnings) . " adet):\n";
            foreach ($this->warnings as $warning) {
                echo "   $warning\n";
            }
            echo "\n";
        }
    }
    
    private function suggestFixes() {
        if (!empty($this->fixes)) {
            echo "🔧 ÖNERİLEN DÜZELTMELER:\n";
            echo "=" . str_repeat("=", 50) . "\n\n";
            
            foreach ($this->fixes as $fix) {
                echo "   $fix;\n";
            }
            
            echo "\n💡 Bu komutları çalıştırmak için 'fix_database_issues.php' scriptini kullanın.\n";
        }
    }
    
    public function getIssues() {
        return $this->issues;
    }
    
    public function getFixes() {
        return $this->fixes;
    }
}

// Ana kontrol
try {
    $healthCheck = new DatabaseHealthCheck($pdo);
    $healthCheck->runFullCheck();
    
    // Sorunları dosyaya kaydet
    $issues = $healthCheck->getIssues();
    $fixes = $healthCheck->getFixes();
    
    if (!empty($issues) || !empty($fixes)) {
        file_put_contents('database_issues.json', json_encode([
            'issues' => $issues,
            'fixes' => $fixes,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT));
        
        echo "\n📄 Sorunlar 'database_issues.json' dosyasına kaydedildi.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
?>
