<?php
require_once 'config.php';

try {
    echo "🔍 DERS3 İÇİN ÖĞRETMEN KONTROLÜ...\n\n";
    
    // 1. tep_teachers tablosunda DERS3 için öğretmen var mı?
    echo "📋 tep_teachers tablosunda DERS3 için öğretmenler:\n";
    $stmt = $pdo->query("SELECT id, person_name, skill_name FROM tep_teachers WHERE skill_name = 'DERS3'");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($teachers)) {
        echo "❌ DERS3 için hiç öğretmen bulunamadı!\n\n";
        
        // Tüm skill_name'leri göster
        echo "📋 Mevcut skill_name'ler:\n";
        $stmt = $pdo->query("SELECT DISTINCT skill_name FROM tep_teachers ORDER BY skill_name");
        $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($skills as $skill) {
            echo "   - $skill\n";
        }
        echo "\n";
        
    } else {
        echo "✅ DERS3 için öğretmenler bulundu:\n";
        foreach ($teachers as $teacher) {
            echo "   ID: {$teacher['id']}, Name: {$teacher['person_name']}\n";
        }
        echo "\n";
        
        // 2. Bu öğretmenlerin events tablosunda etkinliği var mı?
        echo "📅 Bu öğretmenlerin events tablosunda etkinlikleri:\n";
        foreach ($teachers as $teacher) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE teacher_id = {$teacher['id']} AND status = 'active'");
            $eventCount = $stmt->fetchColumn();
            echo "   {$teacher['person_name']} (ID: {$teacher['id']}): $eventCount etkinlik\n";
        }
        echo "\n";
    }
    
    // 3. events tablosunda DERS3 ile ilgili etkinlik var mı?
    echo "📅 events tablosunda DERS3 ile ilgili etkinlikler:\n";
    $stmt = $pdo->query("
        SELECT e.id, e.event_name, e.teacher_id, e.course_title, e.status
        FROM events e
        WHERE e.course_title = 'DERS3' OR e.event_name LIKE '%DERS3%'
    ");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($events)) {
        echo "❌ DERS3 ile ilgili hiç etkinlik bulunamadı!\n\n";
        
        // Mevcut course_title'ları göster
        echo "📋 Mevcut course_title'lar:\n";
        $stmt = $pdo->query("SELECT DISTINCT course_title FROM events ORDER BY course_title");
        $titles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($titles as $title) {
            echo "   - $title\n";
        }
    } else {
        echo "✅ DERS3 ile ilgili etkinlikler bulundu:\n";
        foreach ($events as $event) {
            echo "   ID: {$event['id']}, Title: {$event['course_title']}, Teacher ID: {$event['teacher_id']}, Status: {$event['status']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
