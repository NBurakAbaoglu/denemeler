<?php
require_once 'config.php';

echo "🔍 TEACHER CONNECTION DEBUG BAŞLATIYOR...\n\n";

try {
    // 1. Events tablosundaki veriler
    echo "📅 EVENTS TABLOSU:\n";
    $stmt = $pdo->query("SELECT id, teacher_id, event_name, event_date, end_date FROM events LIMIT 10");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($events)) {
        echo "   ❌ Events tablosunda veri yok!\n";
    } else {
        foreach ($events as $event) {
            echo "   ID: {$event['id']}, Teacher ID: {$event['teacher_id']}, Event: {$event['event_name']}, Date: {$event['event_date']}\n";
        }
    }
    
    echo "\n👨‍🏫 TEP_TEACHERS TABLOSU:\n";
    $stmt = $pdo->query("SELECT id, person_name, skill_name FROM tep_teachers LIMIT 10");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($teachers)) {
        echo "   ❌ tep_teachers tablosunda veri yok!\n";
    } else {
        foreach ($teachers as $teacher) {
            echo "   ID: {$teacher['id']}, Name: {$teacher['person_name']}, Skill: {$teacher['skill_name']}\n";
        }
    }
    
    echo "\n👨‍🏫 TEACHERS TABLOSU (eğer varsa):\n";
    try {
        $stmt = $pdo->query("SELECT id, first_name, last_name FROM teachers LIMIT 10");
        $teachers2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($teachers2)) {
            echo "   ❌ teachers tablosunda veri yok!\n";
        } else {
            foreach ($teachers2 as $teacher) {
                echo "   ID: {$teacher['id']}, Name: {$teacher['first_name']} {$teacher['last_name']}\n";
            }
        }
    } catch (Exception $e) {
        echo "   ❌ teachers tablosu bulunamadı: " . $e->getMessage() . "\n";
    }
    
    echo "\n🔗 BAĞLANTI KONTROLÜ (events.teacher_id → tep_teachers.id):\n";
    // Events'teki teacher_id'lerin tep_teachers'te olup olmadığını kontrol et
    $stmt = $pdo->query("
        SELECT e.id as event_id, e.teacher_id, e.event_name, t.id as teacher_table_id, t.person_name
        FROM events e
        LEFT JOIN tep_teachers t ON e.teacher_id = t.id
        LIMIT 10
    ");
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($connections)) {
        echo "   ❌ Events tablosunda veri yok!\n";
    } else {
        $foundCount = 0;
        $notFoundCount = 0;
        
        foreach ($connections as $conn) {
            if ($conn['teacher_table_id']) {
                echo "   ✅ Event {$conn['event_id']} → Teacher {$conn['teacher_table_id']} ({$conn['person_name']})\n";
                $foundCount++;
            } else {
                echo "   ❌ Event {$conn['event_id']} → Teacher ID {$conn['teacher_id']} (BULUNAMADI)\n";
                $notFoundCount++;
            }
        }
        
        echo "\n📊 BAĞLANTI İSTATİSTİKLERİ:\n";
        echo "   ✅ Bulunan bağlantılar: {$foundCount}\n";
        echo "   ❌ Bulunamayan bağlantılar: {$notFoundCount}\n";
    }
    
    echo "\n🔍 EVENTS TABLOSUNDAKİ UNIQUE TEACHER_ID'LER:\n";
    $stmt = $pdo->query("SELECT DISTINCT teacher_id FROM events WHERE teacher_id IS NOT NULL");
    $uniqueTeacherIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($uniqueTeacherIds)) {
        echo "   ❌ Events tablosunda teacher_id yok!\n";
    } else {
        foreach ($uniqueTeacherIds as $teacherId) {
            echo "   - Teacher ID: {$teacherId}\n";
        }
    }
    
    echo "\n🔍 TEP_TEACHERS TABLOSUNDAKİ UNIQUE ID'LER:\n";
    $stmt = $pdo->query("SELECT DISTINCT id FROM tep_teachers");
    $uniqueTeacherTableIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($uniqueTeacherTableIds)) {
        echo "   ❌ tep_teachers tablosunda id yok!\n";
    } else {
        foreach ($uniqueTeacherTableIds as $teacherId) {
            echo "   - Teacher ID: {$teacherId}\n";
        }
    }
    
    echo "\n🔍 TUTARSIZLIK ANALİZİ:\n";
    if (!empty($uniqueTeacherIds) && !empty($uniqueTeacherTableIds)) {
        $eventsTeacherIds = array_map('intval', $uniqueTeacherIds);
        $tepTeacherIds = array_map('intval', $uniqueTeacherTableIds);
        
        $missingInTep = array_diff($eventsTeacherIds, $tepTeacherIds);
        $missingInEvents = array_diff($tepTeacherIds, $eventsTeacherIds);
        
        if (!empty($missingInTep)) {
            echo "   ❌ Events'te var ama tep_teachers'te yok: " . implode(', ', $missingInTep) . "\n";
        }
        
        if (!empty($missingInEvents)) {
            echo "   ⚠️  tep_teachers'te var ama events'te kullanılmıyor: " . implode(', ', $missingInEvents) . "\n";
        }
        
        if (empty($missingInTep) && empty($missingInEvents)) {
            echo "   ✅ Tüm ID'ler eşleşiyor!\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    echo "📍 Hata detayı: " . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Debug tamamlandı.\n";
?>
