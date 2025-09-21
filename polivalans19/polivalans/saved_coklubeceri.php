<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz veri formatı');
    }

    $person_id = $input['person_id'] ?? null;
    $organization_id = $input['organization_id'] ?? null;
    $teacher_id = $input['teacher_id'] ?? null;
    $success_status = $input['success_status'] ?? 'istek_gonderildi';
    $start_date = $input['start_date'] ?? date('Y-m-d');
    $end_date = $input['end_date'] ?? date('Y-m-d');


    if (!$person_id || !$organization_id) {
        throw new Exception('Person ID ve Organization ID gerekli');
    }

    // Mevcut kaydı kontrol et
    $checkQuery = "SELECT id FROM planned_multi_skill WHERE person_id = ? AND organization_id = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$person_id, $organization_id]);
    $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingRecord) {
        // Mevcut kaydı güncelle
        $updateQuery = "UPDATE planned_multi_skill SET 
            teacher_id = ?, 
            success_status = ?,
            start_date = ?,
            end_date = ?,
            updated_at = NOW()
            WHERE person_id = ? AND organization_id = ?";
        
        $updateStmt = $pdo->prepare($updateQuery);
        $result = $updateStmt->execute([
            $teacher_id,
            $success_status,
            $start_date,
            $end_date,
            $person_id,
            $organization_id
        ]);

        if ($result) {
            // Organization_images tablosunu güncelle
            updateOrganizationImage($person_id, $organization_id, $success_status);
            
            echo json_encode([
                'success' => true,
                'message' => 'Çoklu beceri kaydı güncellendi',
                'action' => 'updated'
            ]);
        } else {
            throw new Exception('Güncelleme başarısız');
        }
    } else {
        // Yeni kayıt oluştur
        $insertQuery = "INSERT INTO planned_multi_skill 
            (person_id, organization_id, teacher_id, success_status, start_date, end_date, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $insertStmt = $pdo->prepare($insertQuery);
        $result = $insertStmt->execute([
            $person_id,
            $organization_id,
            $teacher_id,
            $success_status,
            $start_date,
            $end_date
        ]);

        if ($result) {
            // Organization_images tablosunu güncelle
            updateOrganizationImage($person_id, $organization_id, $success_status);
            
            echo json_encode([
                'success' => true,
                'message' => 'Çoklu beceri kaydı oluşturuldu',
                'action' => 'created'
            ]);
        } else {
            throw new Exception('Kayıt oluşturma başarısız');
        }
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}

// Organization_images tablosunu güncelle
function updateOrganizationImage($person_id, $organization_id, $success_status) {
    global $pdo;
    
    try {
        // Kişi adını al
        $personQuery = "SELECT name FROM persons WHERE id = ?";
        $personStmt = $pdo->prepare($personQuery);
        $personStmt->execute([$person_id]);
        $person = $personStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$person) {
            error_log("❌ Kişi bulunamadı: $person_id");
            return;
        }
        
        $personName = $person['name'];
        
        // Success status'a göre pie chart belirle
        $imageName = 'pie (2).png'; // Varsayılan: Bilgisi Yok
        
        switch ($success_status) {
            case 'istek_gonderildi':
                $imageName = 'pie (3).png'; // İstek Gönderildi
                break;
            case 'planlandi':
                $imageName = 'pie (4).png'; // Planlandı
                break;
            case 'tamamlandi':
                $imageName = 'pie (5).png'; // Tamamlandı
                break;
        }
        
        // Organization_images tablosunu güncelle
        $updateQuery = "
            UPDATE organization_images 
            SET image_name = ?, updated_at = NOW()
            WHERE organization_id = ? AND row_name = ?
        ";
        
        $updateStmt = $pdo->prepare($updateQuery);
        $result = $updateStmt->execute([$imageName, $organization_id, $personName]);
        
        if ($result) {
            error_log("✅ Organization image güncellendi: $personName, $organization_id, $imageName");
        } else {
            error_log("❌ Organization image güncellenemedi: $personName, $organization_id");
        }
        
        // Test için basit bir log
        error_log("🧪 TEST: updateOrganizationImage çağrıldı - $personName, $organization_id, $success_status -> $imageName");
        
    } catch (Exception $e) {
        error_log("❌ Organization image güncelleme hatası: " . $e->getMessage());
    }
}
?>
