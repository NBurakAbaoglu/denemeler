<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $oldName = $input['old_name'] ?? null;
    $newName = $input['new_name'] ?? null;
    
    if (!$oldName || !$newName || trim($oldName) === '' || trim($newName) === '') {
        throw new Exception("Eski ve yeni isim gerekli");
    }
    
    $oldName = trim($oldName);
    $newName = trim($newName);
    
    // Eski isimle kişiyi bul
    $personSql = "SELECT id FROM persons WHERE name = ?";
    $personResult = executeQuery($mysqli, $personSql, [$oldName]);
    
    if (!$personResult || $personResult->num_rows === 0) {
        throw new Exception("Kişi bulunamadı");
    }
    
    $person = $personResult->fetch_assoc();
    $personId = $person['id'];
    
    // Yeni isimle başka bir kişi var mı kontrol et
    $checkSql = "SELECT id FROM persons WHERE name = ? AND id != ?";
    $checkResult = executeQuery($mysqli, $checkSql, [$newName, $personId]);
    
    if ($checkResult && $checkResult->num_rows > 0) {
        throw new Exception("Bu isimde başka bir kişi zaten mevcut");
    }
    
    // Transaction başlat
    $mysqli->autocommit(false);
    
    try {
        // 1. Kişi ismini güncelle
        $updatePersonSql = "UPDATE persons SET name = ? WHERE id = ?";
        $personResult = executeQuery($mysqli, $updatePersonSql, [$newName, $personId]);
        
        if (!$personResult) {
            throw new Exception("Kişi ismi güncellenemedi");
        }
        
        // 2. İlgili tablolardaki isimleri de güncelle (eğer varsa)
        // planned_skills tablosunda person_name alanı yok, sadece person_id var
        // Bu yüzden planned_skills tablosunda güncelleme yapmıyoruz
        
        // İlgili tablolarda person_name sütunu varsa güncelle
        // Önce tablo varlığını ve sütun varlığını kontrol et
        
        // person_organization_images tablosu kontrolü
        $checkTableSql = "SHOW TABLES LIKE 'person_organization_images'";
        $tableResult = executeQuery($mysqli, $checkTableSql);
        if ($tableResult && $tableResult->num_rows > 0) {
            // Tablo var, sütun kontrolü yap
            $checkColumnSql = "SHOW COLUMNS FROM person_organization_images LIKE 'person_name'";
            $columnResult = executeQuery($mysqli, $checkColumnSql);
            if ($columnResult && $columnResult->num_rows > 0) {
                // Sütun var, güncelle
                $updateImagesSql = "UPDATE person_organization_images SET person_name = ? WHERE person_name = ?";
                executeQuery($mysqli, $updateImagesSql, [$newName, $oldName]);
            }
        }
        
        // person_completed_skills tablosu kontrolü
        $checkTableSql2 = "SHOW TABLES LIKE 'person_completed_skills'";
        $tableResult2 = executeQuery($mysqli, $checkTableSql2);
        if ($tableResult2 && $tableResult2->num_rows > 0) {
            // Tablo var, sütun kontrolü yap
            $checkColumnSql2 = "SHOW COLUMNS FROM person_completed_skills LIKE 'person_name'";
            $columnResult2 = executeQuery($mysqli, $checkColumnSql2);
            if ($columnResult2 && $columnResult2->num_rows > 0) {
                // Sütun var, güncelle
                $updateCompletedSkillsSql = "UPDATE person_completed_skills SET person_name = ? WHERE person_name = ?";
                executeQuery($mysqli, $updateCompletedSkillsSql, [$newName, $oldName]);
            }
        }
        
        // organization_images tablosu kontrolü
        $checkTableSql3 = "SHOW TABLES LIKE 'organization_images'";
        $tableResult3 = executeQuery($mysqli, $checkTableSql3);
        if ($tableResult3 && $tableResult3->num_rows > 0) {
            // Tablo var, sütun kontrolü yap
            $checkColumnSql3 = "SHOW COLUMNS FROM organization_images LIKE 'row_name'";
            $columnResult3 = executeQuery($mysqli, $checkColumnSql3);
            if ($columnResult3 && $columnResult3->num_rows > 0) {
                // Sütun var, güncelle
                $updateOrganizationImagesSql = "UPDATE organization_images SET row_name = ? WHERE row_name = ?";
                executeQuery($mysqli, $updateOrganizationImagesSql, [$newName, $oldName]);
            }
        }
        
        // Transaction'ı commit et
        $mysqli->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Kişi ismi başarıyla güncellendi',
            'old_name' => $oldName,
            'new_name' => $newName
        ]);
        
    } catch (Exception $e) {
        // Hata durumunda rollback
        $mysqli->rollback();
        throw $e;
    } finally {
        // Autocommit'i tekrar aç
        $mysqli->autocommit(true);
    }
    
} catch (Exception $e) {
    error_log("Kişi ismi güncelleme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
