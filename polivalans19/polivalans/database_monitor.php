<?php
require_once 'config.php';

class DatabaseMonitor {
    private $pdo;
    private $logFile = 'database_monitor.log';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function startMonitoring() {
        echo "🔍 VERİTABANI İZLEME BAŞLATILIYOR...\n";
        echo "📝 Log dosyası: $this->logFile\n\n";
        
        $this->log("Database monitoring started");
        
        // Sürekli izleme döngüsü
        while (true) {
            $this->checkDatabaseHealth();
            $this->checkForNewIssues();
            $this->log("Health check completed");
            
            // 30 saniye bekle
            sleep(30);
        }
    }
    
    private function checkDatabaseHealth() {
        $issues = [];
        
        // AUTO_INCREMENT kontrolü
        $tables = ['persons', 'organizations', 'skills', 'organization_skills', 'planned_skills', 'planlandi', 'events', 'tep_teachers'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SHOW TABLE STATUS LIKE '$table'");
                $status = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($status) {
                    $autoIncrement = $status['Auto_increment'];
                    $stmt = $this->pdo->query("SELECT MAX(id) FROM $table");
                    $maxId = $stmt->fetchColumn();
                    
                    if ($maxId && $autoIncrement <= $maxId) {
                        $issues[] = "$table: AUTO_INCREMENT issue detected";
                    }
                }
            } catch (Exception $e) {
                $issues[] = "$table: " . $e->getMessage();
            }
        }
        
        if (!empty($issues)) {
            $this->log("Issues detected: " . implode(', ', $issues));
            $this->sendAlert($issues);
        }
    }
    
    private function checkForNewIssues() {
        // Yeni eklenen 0 ID'li kayıtları kontrol et
        $tables = ['persons', 'organizations', 'skills', 'organization_skills', 'planned_skills', 'planlandi', 'events', 'tep_teachers'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table WHERE id = 0");
                $zeroIdCount = $stmt->fetchColumn();
                
                if ($zeroIdCount > 0) {
                    $this->log("CRITICAL: $table has $zeroIdCount records with ID = 0");
                    $this->sendAlert(["$table has records with ID = 0"]);
                }
            } catch (Exception $e) {
                $this->log("Error checking $table: " . $e->getMessage());
            }
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        echo $logEntry;
    }
    
    private function sendAlert($issues) {
        // E-posta gönder (isteğe bağlı)
        $subject = "Database Health Alert";
        $message = "Database issues detected:\n" . implode("\n", $issues);
        
        // Bu kısmı kendi e-posta ayarlarınıza göre düzenleyin
        // mail('admin@yourdomain.com', $subject, $message);
        
        $this->log("ALERT SENT: " . $message);
    }
    
    public function getHealthReport() {
        echo "📊 VERİTABANI SAĞLIK RAPORU\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        $tables = ['persons', 'organizations', 'skills', 'organization_skills', 'planned_skills', 'planlandi', 'events', 'tep_teachers'];
        
        foreach ($tables as $table) {
            try {
                // Tablo bilgileri
                $stmt = $this->pdo->query("SHOW TABLE STATUS LIKE '$table'");
                $status = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($status) {
                    echo "📋 $table:\n";
                    echo "   Rows: " . $status['Rows'] . "\n";
                    echo "   AUTO_INCREMENT: " . $status['Auto_increment'] . "\n";
                    echo "   Engine: " . $status['Engine'] . "\n";
                    
                    // Maksimum ID
                    $stmt = $this->pdo->query("SELECT MAX(id) FROM $table");
                    $maxId = $stmt->fetchColumn();
                    echo "   MAX ID: " . ($maxId ?: 'NULL') . "\n";
                    
                    // 0 ID kontrolü
                    $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table WHERE id = 0");
                    $zeroCount = $stmt->fetchColumn();
                    if ($zeroCount > 0) {
                        echo "   ⚠️ 0 ID records: $zeroCount\n";
                    }
                    
                    echo "\n";
                }
            } catch (Exception $e) {
                echo "❌ $table: " . $e->getMessage() . "\n\n";
            }
        }
    }
}

// Komut satırı kullanımı
if (isset($argv[1])) {
    $monitor = new DatabaseMonitor($pdo);
    
    switch ($argv[1]) {
        case '--monitor':
            $monitor->startMonitoring();
            break;
        case '--report':
            $monitor->getHealthReport();
            break;
        default:
            echo "Kullanım:\n";
            echo "  php database_monitor.php --monitor  # Sürekli izleme\n";
            echo "  php database_monitor.php --report   # Sağlık raporu\n";
            break;
    }
} else {
    echo "🔍 VERİTABANI İZLEME SİSTEMİ\n\n";
    echo "Kullanım:\n";
    echo "  php database_monitor.php --monitor  # Sürekli izleme başlat\n";
    echo "  php database_monitor.php --report   # Sağlık raporu göster\n\n";
    
    echo "Diğer komutlar:\n";
    echo "  php database_health_check.php       # Tam sağlık kontrolü\n";
    echo "  php fix_database_issues.php         # Sorunları düzelt\n";
    echo "  php fix_database_issues.php --auto  # Otomatik düzeltme\n";
}
?>
