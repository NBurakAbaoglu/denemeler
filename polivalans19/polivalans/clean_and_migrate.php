<?php
require_once 'config.php';

echo "Temizlik ve migrasyon başlatılıyor...\n\n";

try {
    // 1. Mevcut yedek tabloyu sil
    echo "1. Mevcut yedek tabloyu siliyorum...\n";
    try {
        $pdo->exec("DROP TABLE IF EXISTS organization_skills_backup");
        echo "✅ Eski yedek tablo silindi\n\n";
    } catch (Exception $e) {
        echo "⚠️ Yedek tablo zaten yok: " . $e->getMessage() . "\n\n";
    }
    
    // 2. Mevcut verileri yedekle
    echo "2. Mevcut verileri yedekliyorum...\n";
    $pdo->exec("CREATE TABLE organization_skills_backup AS SELECT * FROM organization_skills");
    echo "✅ Yedek oluşturuldu: organization_skills_backup\n\n";
    
    // 3. Skills tablosu oluştur
    echo "3. Skills tablosu oluşturuluyor...\n";
    $pdo->exec("
        CREATE TABLE `skills` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `skill_name` varchar(255) NOT NULL,
          `skill_description` text DEFAULT NULL,
          `category` varchar(100) DEFAULT NULL,
          `is_active` tinyint(1) DEFAULT 1,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_skill_name` (`skill_name`),
          KEY `idx_skill_name` (`skill_name`),
          KEY `idx_category` (`category`),
          KEY `idx_is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Skills tablosu oluşturuldu\n\n";
    
    // 4. Mevcut organization_skills verilerini skills tablosuna taşı
    echo "4. Mevcut becerileri skills tablosuna taşıyorum...\n";
    $pdo->exec("
        INSERT INTO `skills` (`skill_name`, `skill_description`, `category`)
        SELECT DISTINCT 
            `skill_name`, 
            `skill_description`,
            CASE 
                WHEN `organization_id` = 1 THEN 'İnsan Kaynakları'
                WHEN `organization_id` = 2 THEN 'Bilgi İşlem'
                WHEN `organization_id` = 3 THEN 'Muhasebe'
                WHEN `organization_id` = 4 THEN 'Satış'
                WHEN `organization_id` = 5 THEN 'Pazarlama'
                ELSE 'Genel'
            END as category
        FROM `organization_skills_backup`
    ");
    echo "✅ Beceriler skills tablosuna taşındı\n\n";
    
    // 5. Foreign key kısıtlamalarını kaldır
    echo "5. Foreign key kısıtlamalarını kaldırıyorum...\n";
    
    // Events tablosundaki foreign key'i kaldır
    try {
        $pdo->exec("ALTER TABLE `events` DROP FOREIGN KEY `events_ibfk_2`");
        echo "✅ Events foreign key kaldırıldı\n";
    } catch (Exception $e) {
        echo "⚠️ Events foreign key zaten yok: " . $e->getMessage() . "\n";
    }
    
    // Planned_skills tablosundaki foreign key'i kaldır
    try {
        $pdo->exec("ALTER TABLE `planned_skills` DROP FOREIGN KEY `planned_skills_ibfk_3`");
        echo "✅ Planned_skills foreign key kaldırıldı\n";
    } catch (Exception $e) {
        echo "⚠️ Planned_skills foreign key zaten yok: " . $e->getMessage() . "\n";
    }
    
    // Planlandi tablosundaki foreign key'i kaldır
    try {
        $pdo->exec("ALTER TABLE `planlandi` DROP FOREIGN KEY `planlandi_ibfk_3`");
        echo "✅ Planlandi foreign key kaldırıldı\n";
    } catch (Exception $e) {
        echo "⚠️ Planlandi foreign key zaten yok: " . $e->getMessage() . "\n";
    }
    
    // 6. Eski organization_skills tablosunu sil
    echo "\n6. Eski organization_skills tablosunu siliyorum...\n";
    $pdo->exec("DROP TABLE `organization_skills`");
    echo "✅ Eski tablo silindi\n\n";
    
    // 7. Yeni organization_skills junction table oluştur
    echo "7. Yeni organization_skills junction table oluşturuluyor...\n";
    $pdo->exec("
        CREATE TABLE `organization_skills` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `organization_id` int(11) NOT NULL,
          `skill_id` int(11) NOT NULL,
          `proficiency_level` int(11) DEFAULT 1 COMMENT '1-5 arası yeterlilik seviyesi',
          `is_required` tinyint(1) DEFAULT 0 COMMENT 'Bu organizasyon için zorunlu mu',
          `priority` enum('low','medium','high') DEFAULT 'medium',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_org_skill` (`organization_id`,`skill_id`),
          KEY `idx_organization_id` (`organization_id`),
          KEY `idx_skill_id` (`skill_id`),
          KEY `idx_proficiency_level` (`proficiency_level`),
          KEY `idx_is_required` (`is_required`),
          KEY `idx_priority` (`priority`),
          CONSTRAINT `organization_skills_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
          CONSTRAINT `organization_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Yeni junction table oluşturuldu\n\n";
    
    // 8. Yedek verilerden yeni junction table'a veri aktar
    echo "8. Verileri yeni yapıya aktarıyorum...\n";
    $pdo->exec("
        INSERT INTO `organization_skills` (`organization_id`, `skill_id`, `proficiency_level`, `is_required`, `priority`)
        SELECT 
            osb.organization_id,
            s.id as skill_id,
            3 as proficiency_level,
            1 as is_required,
            'medium' as priority
        FROM `organization_skills_backup` osb
        JOIN `skills` s ON s.skill_name = osb.skill_name
    ");
    echo "✅ Veriler yeni yapıya aktarıldı\n\n";
    
    // 9. Events tablosundaki lesson_id referansını güncelle
    echo "9. Events tablosunu güncelliyorum...\n";
    $pdo->exec("
        UPDATE `events` e
        JOIN `organization_skills_backup` osb ON e.lesson_id = osb.id
        JOIN `skills` s ON s.skill_name = osb.skill_name
        SET e.lesson_id = s.id
    ");
    
    $pdo->exec("
        ALTER TABLE `events` 
        ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `skills` (`id`) ON DELETE SET NULL
    ");
    echo "✅ Events tablosu güncellendi\n\n";
    
    // 10. Planned_skills tablosundaki skill_id referansını güncelle
    echo "10. Planned_skills tablosunu güncelliyorum...\n";
    $pdo->exec("
        UPDATE `planned_skills` ps
        JOIN `organization_skills_backup` osb ON ps.skill_id = osb.id
        JOIN `skills` s ON s.skill_name = osb.skill_name
        SET ps.skill_id = s.id
    ");
    
    $pdo->exec("
        ALTER TABLE `planned_skills` 
        ADD CONSTRAINT `planned_skills_ibfk_3` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
    ");
    echo "✅ Planned_skills tablosu güncellendi\n\n";
    
    // 11. Planlandi tablosundaki skill_id referansını güncelle
    echo "11. Planlandi tablosunu güncelliyorum...\n";
    $pdo->exec("
        UPDATE `planlandi` p
        JOIN `organization_skills_backup` osb ON p.skill_id = osb.id
        JOIN `skills` s ON s.skill_name = osb.skill_name
        SET p.skill_id = s.id
    ");
    
    $pdo->exec("
        ALTER TABLE `planlandi` 
        ADD CONSTRAINT `planlandi_ibfk_3` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
    ");
    echo "✅ Planlandi tablosu güncellendi\n\n";
    
    // 12. Sonuçları kontrol et
    echo "12. Sonuçları kontrol ediyorum...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM skills");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Skills tablosunda $result[count] kayıt var\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM organization_skills");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Organization_skills tablosunda $result[count] kayıt var\n";
    
    echo "\n🎉 MİGRASYON BAŞARIYLA TAMAMLANDI!\n";
    echo "Artık n:n ilişki yapısı kullanılıyor.\n";
    
} catch (Exception $e) {
    echo "❌ Migrasyon hatası: " . $e->getMessage() . "\n";
    echo "Yedek tablo: organization_skills_backup\n";
}
?>
