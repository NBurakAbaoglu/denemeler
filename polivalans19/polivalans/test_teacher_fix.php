<?php
require_once 'config.php';

echo "🧪 TEACHER CONNECTION FIX TEST...\n\n";

try {
    // Yeni sorguyu test et
    echo "🔍 YENİ EVENTS SORGUSU TEST EDİLİYOR:\n\n";
    
    $eventsStmt = $pdo->query("
        SELECT 
            e.id,
            e.teacher_id,
            e.event_name,
            e.event_date,
            t.first_name,
            t.last_name,
            tt.id as tep_teacher_id,
            tt.person_name as tep_teacher_name,
            tt.skill_name as tep_teacher_skill
        FROM events e
        LEFT JOIN teachers t ON e.teacher_id = t.id
        LEFT JOIN tep_teachers tt ON CONCAT(t.first_name, ' ', t.last_name) = tt.person_name
        WHERE e.teacher_id IS NOT NULL AND e.teacher_id != ''
        LIMIT 10
    ");
    $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($events)) {
        echo "❌ Events tablosunda teacher_id'li kayıt yok!\n";
    } else {
        foreach ($events as $event) {
            echo "📅 Event ID: {$event['id']}\n";
            echo "   - Event Name: {$event['event_name']}\n";
            echo "   - Event Date: {$event['event_date']}\n";
            echo "   - Teacher ID: {$event['teacher_id']}\n";
            echo "   - Teachers Table: {$event['first_name']} {$event['last_name']}\n";
            
            if ($event['tep_teacher_id']) {
                echo "   ✅ TEP Teachers Match: ID={$event['tep_teacher_id']}, Name={$event['tep_teacher_name']}, Skill={$event['tep_teacher_skill']}\n";
            } else {
                echo "   ❌ TEP Teachers Match: BULUNAMADI\n";
            }
            echo "\n";
        }
    }
    
    echo "📊 BAĞLANTI İSTATİSTİKLERİ:\n";
    $totalEvents = count($events);
    $matchedEvents = 0;
    
    foreach ($events as $event) {
        if ($event['tep_teacher_id']) {
            $matchedEvents++;
        }
    }
    
    echo "   - Toplam Events: {$totalEvents}\n";
    echo "   - Eşleşen Events: {$matchedEvents}\n";
    echo "   - Eşleşmeyen Events: " . ($totalEvents - $matchedEvents) . "\n";
    
    if ($matchedEvents > 0) {
        echo "   ✅ Başarı Oranı: " . round(($matchedEvents / $totalEvents) * 100, 1) . "%\n";
    } else {
        echo "   ❌ Hiç eşleşme yok!\n";
    }
    
    echo "\n🎯 SONUÇ:\n";
    if ($matchedEvents > 0) {
        echo "✅ Çözüm çalışıyor! Events tablosundaki teacher_id'ler tep_teachers tablosuyla eşleşiyor.\n";
    } else {
        echo "❌ Çözüm çalışmıyor. Daha fazla analiz gerekli.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test hatası: " . $e->getMessage() . "\n";
}

echo "\n🏁 Test tamamlandı.\n";
?>
