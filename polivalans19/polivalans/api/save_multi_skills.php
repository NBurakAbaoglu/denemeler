<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['person_name']) || !isset($input['organization_name'])) {
        throw new Exception('Person Name ve Organization Name gerekli');
    }
    
    $person_name = $input['person_name'];
    $organization_name = $input['organization_name'];
    $status = $input['status'] ?? 'istek_gonderildi';
    
    // Person ID'yi al
    $person_query = "SELECT id FROM persons WHERE name = ?";
    $person_stmt = $pdo->prepare($person_query);
    $person_stmt->execute([$person_name]);
    $person = $person_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$person) {
        throw new Exception('Kişi bulunamadı: ' . $person_name);
    }
    $person_id = $person['id'];
    
    // Organization ID'yi al
    $org_query = "SELECT id FROM organizations WHERE name = ?";
    $org_stmt = $pdo->prepare($org_query);
    $org_stmt->execute([$organization_name]);
    $organization = $org_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$organization) {
        throw new Exception('Organizasyon bulunamadı: ' . $organization_name);
    }
    $organization_id = $organization['id'];
    
    // Mevcut tablo yapısını kullan - sadece gerekli sütunları kontrol et
    $check_columns_query = "SHOW COLUMNS FROM planned_multi_skill";
    $columns = $pdo->query($check_columns_query)->fetchAll(PDO::FETCH_COLUMN);
    
    // Eksik sütunları ekle
    if (!in_array('success_status', $columns)) {
        $pdo->exec("ALTER TABLE planned_multi_skill ADD COLUMN success_status enum('istek_gonderildi','planlandi','tamamlandi','iptal') DEFAULT 'istek_gonderildi' AFTER status");
    } else {
        // success_status sütunu varsa enum değerlerini güncelle
        $pdo->exec("ALTER TABLE planned_multi_skill MODIFY COLUMN success_status enum('istek_gonderildi','planlandi','tamamlandi','iptal') DEFAULT 'istek_gonderildi'");
    }
    
    // teacher_id sütununu NULL değer kabul edecek şekilde güncelle
    $pdo->exec("ALTER TABLE planned_multi_skill MODIFY COLUMN teacher_id int(11) DEFAULT NULL");
    
    // Mevcut 0 değerlerini istek_gonderildi olarak güncelle
    $pdo->exec("UPDATE planned_multi_skill SET success_status = 'istek_gonderildi' WHERE success_status = '0' OR success_status = 0");
    
    // UNIQUE constraint ekle (eğer yoksa)
    try {
        $pdo->exec("ALTER TABLE planned_multi_skill ADD UNIQUE KEY unique_person_org (person_id, organization_id)");
        error_log('✅ UNIQUE constraint eklendi');
    } catch (Exception $e) {
        // Constraint zaten varsa hata vermez
        error_log('ℹ️ UNIQUE constraint zaten mevcut veya eklenemedi: ' . $e->getMessage());
    }
    
    // Tekrarlanan kayıtları temizle (en son olanı hariç)
    $cleanup_query = "
        DELETE pms1 FROM planned_multi_skill pms1
        INNER JOIN planned_multi_skill pms2 
        WHERE pms1.person_id = pms2.person_id 
        AND pms1.organization_id = pms2.organization_id 
        AND pms1.id < pms2.id
    ";
    $cleanup_result = $pdo->exec($cleanup_query);
    error_log("🧹 Temizlenen kayıt sayısı: " . $cleanup_result);
    
    // Çoklu beceri isteğini kaydet
    $query = "
        INSERT INTO planned_multi_skill (
            person_id, 
            organization_id, 
            success_status,
            teacher_id
        ) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            success_status = VALUES(success_status),
            teacher_id = VALUES(teacher_id),
            updated_at = NOW()
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $person_id,
        $organization_id,
        'istek_gonderildi',
        null
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Çoklu beceri isteği başarıyla kaydedildi',
        'data' => [
            'person_name' => $person_name,
            'organization_name' => $organization_name,
            'status' => $status
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
