<?php
require_once 'config.php';

class DatabaseFixer {
    private $pdo;
    private $fixedIssues = [];
    private $failedFixes = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function runFixes() {
        echo "🔧 VERİTABANI SORUNLARI DÜZELTİLİYOR...\n\n";
        
        // JSON dosyasından sorunları oku
        if (file_exists('database_issues.json')) {
            $data = json_decode(file_get_contents('database_issues.json'), true);
            $fixes = $data['fixes'] ?? [];
            
            if (empty($fixes)) {
                echo "✅ Düzeltilecek sorun bulunamadı.\n";
                return;
            }
            
            echo "📋 " . count($fixes) . " adet düzeltme uygulanacak...\n\n";
            
            foreach ($fixes as $fix) {
                $this->applyFix($fix);
            }
            
            $this->displayResults();
            
        } else {
            echo "❌ database_issues.json dosyası bulunamadı.\n";
            echo "💡 Önce 'database_health_check.php' scriptini çalıştırın.\n";
        }
    }
    
    private function applyFix($fix) {
        try {
            echo "🔨 Uygulanıyor: $fix\n";
            
            $this->pdo->exec($fix);
            $this->fixedIssues[] = $fix;
            
            echo "✅ Başarılı\n\n";
            
        } catch (Exception $e) {
            echo "❌ Hata: " . $e->getMessage() . "\n\n";
            $this->failedFixes[] = [
                'fix' => $fix,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function displayResults() {
        echo "📊 DÜZELTME SONUÇLARI:\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        if (!empty($this->fixedIssues)) {
            echo "✅ BAŞARILI DÜZELTMELER (" . count($this->fixedIssues) . " adet):\n";
            foreach ($this->fixedIssues as $fix) {
                echo "   ✅ $fix\n";
            }
            echo "\n";
        }
        
        if (!empty($this->failedFixes)) {
            echo "❌ BAŞARISIZ DÜZELTMELER (" . count($this->failedFixes) . " adet):\n";
            foreach ($this->failedFixes as $failed) {
                echo "   ❌ " . $failed['fix'] . "\n";
                echo "      Hata: " . $failed['error'] . "\n";
            }
            echo "\n";
        }
        
        if (empty($this->failedFixes)) {
            echo "🎉 TÜM SORUNLAR BAŞARIYLA DÜZELTİLDİ!\n";
        }
    }
    
    public function autoFixCommonIssues() {
        echo "🚀 OTOMATİK DÜZELTME BAŞLATILIYOR...\n\n";
        
        $this->fixAutoIncrement();
        $this->fixOrphanedRecords();
        $this->fixNullIds();
        
        $this->displayResults();
    }
    
    private function fixAutoIncrement() {
        echo "📊 AUTO_INCREMENT düzeltiliyor...\n";
        
        $tables = ['persons', 'organizations', 'skills', 'organization_skills', 'planned_skills', 'planlandi', 'events', 'tep_teachers'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SELECT MAX(id) + 1 FROM $table");
                $maxId = $stmt->fetchColumn();
                
                if (!$maxId || $maxId == 0) {
                    $maxId = 1;
                }
                
                $this->pdo->exec("ALTER TABLE $table AUTO_INCREMENT = $maxId");
                echo "✅ $table: AUTO_INCREMENT = $maxId\n";
                
            } catch (Exception $e) {
                echo "❌ $table: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }
    
    private function fixOrphanedRecords() {
        echo "🧹 Yetim kayıtlar temizleniyor...\n";
        
        try {
            // planlandi'de olan ama planned_skills'te olmayan kayıtları sil
            $stmt = $this->pdo->query("
                DELETE pl FROM planlandi pl 
                LEFT JOIN planned_skills ps ON pl.person_id = ps.person_id AND pl.organization_id = ps.organization_id AND pl.skill_id = ps.skill_id
                WHERE ps.id IS NULL
            ");
            $deletedCount = $stmt->rowCount();
            
            if ($deletedCount > 0) {
                echo "✅ $deletedCount adet yetim kayıt temizlendi\n";
            } else {
                echo "✅ Yetim kayıt bulunamadı\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Yetim kayıt temizleme hatası: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    private function fixNullIds() {
        echo "🔍 NULL ID'ler düzeltiliyor...\n";
        
        $tables = ['persons', 'organizations', 'skills', 'organization_skills', 'planned_skills', 'planlandi', 'events', 'tep_teachers'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table WHERE id IS NULL OR id = 0");
                $nullCount = $stmt->fetchColumn();
                
                if ($nullCount > 0) {
                    echo "⚠️ $table: $nullCount adet NULL/0 ID'li kayıt bulundu (manuel müdahale gerekli)\n";
                } else {
                    echo "✅ $table: NULL ID yok\n";
                }
                
            } catch (Exception $e) {
                echo "❌ $table NULL ID kontrol hatası: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }
}

// Ana işlem
try {
    $fixer = new DatabaseFixer($pdo);
    
    // Komut satırı argümanlarını kontrol et
    if (isset($argv[1]) && $argv[1] === '--auto') {
        $fixer->autoFixCommonIssues();
    } else {
        $fixer->runFixes();
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
?>
