<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    $lessonId = $_GET['lesson_id'] ?? null;
    
    if (!$lessonId) {
        throw new Exception('Ders ID gerekli');
    }
    
    // Bu dersi verebilecek öğretmenleri bul
    // Öğretmenlerin hangi dersleri verebileceğini belirlemek için
    // teacher_skills tablosu varsa onu kullan, yoksa tüm öğretmenleri döndür
    
    // Önce teacher_skills tablosunun var olup olmadığını kontrol et
    $checkTableQuery = "SHOW TABLES LIKE 'teacher_skills'";
    $checkStmt = $pdo->query($checkTableQuery);
    $tableExists = $checkStmt->fetch();
    
    if ($tableExists) {
        // teacher_skills tablosu varsa, bu dersi verebilecek öğretmenleri bul
        $query = "
            SELECT DISTINCT t.id, p.name as person_name
            FROM teachers t
            JOIN persons p ON t.person_id = p.id
            JOIN teacher_skills ts ON t.id = ts.teacher_id
            WHERE ts.skill_id = ?
            ORDER BY p.name
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$lessonId]);
    } else {
        // teacher_skills tablosu yoksa, tüm öğretmenleri döndür
        $query = "
            SELECT t.id, p.name as person_name
            FROM teachers t
            JOIN persons p ON t.person_id = p.id
            ORDER BY p.name
        ";
        $stmt = $pdo->query($query);
    }
    
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'teachers' => $teachers,
        'message' => 'Öğretmenler başarıyla yüklendi'
    ]);
    
} catch (Exception $e) {
    error_log("Öğretmen yükleme hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
