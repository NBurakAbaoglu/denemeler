<?php
require_once 'config.php';

echo "🔍 TEACHER ID MISMATCH ANALİZİ...\n\n";

try {
    echo "📊 SORUN TESPİTİ:\n";
    echo "1. Events tablosunda teacher_id'ler var ama bazıları tep_teachers'te yok\n";
    echo "2. İki farklı teachers tablosu var: 'teachers' ve 'tep_teachers'\n";
    echo "3. Events tablosu 'teachers' tablosundaki ID'leri kullanıyor\n";
    echo "4. Ama get_temel_beceri.php 'tep_teachers' tablosunu kullanıyor\n\n";
    
    echo "🔍 EVENTS'TEKİ TEACHER_ID'LERİN HANGİ TABLODA OLDUĞUNU KONTROL EDELİM:\n\n";
    
    // Events'teki teacher_id'leri al
    $stmt = $pdo->query("SELECT DISTINCT teacher_id FROM events WHERE teacher_id IS NOT NULL AND teacher_id != ''");
    $eventsTeacherIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($eventsTeacherIds as $teacherId) {
        echo "🔍 Teacher ID {$teacherId} kontrol ediliyor...\n";
        
        // teachers tablosunda var mı?
        $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM teachers WHERE id = ?");
        $stmt->execute([$teacherId]);
        $teacherInTeachers = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // tep_teachers tablosunda var mı?
        $stmt = $pdo->prepare("SELECT id, person_name, skill_name FROM tep_teachers WHERE id = ?");
        $stmt->execute([$teacherId]);
        $teacherInTepTeachers = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacherInTeachers) {
            echo "   ✅ teachers tablosunda: {$teacherInTeachers['first_name']} {$teacherInTeachers['last_name']}\n";
        } else {
            echo "   ❌ teachers tablosunda YOK\n";
        }
        
        if ($teacherInTepTeachers) {
            echo "   ✅ tep_teachers tablosunda: {$teacherInTepTeachers['person_name']} ({$teacherInTepTeachers['skill_name']})\n";
        } else {
            echo "   ❌ tep_teachers tablosunda YOK\n";
        }
        
        echo "\n";
    }
    
    echo "🎯 ÇÖZÜM ÖNERİLERİ:\n\n";
    echo "1. get_temel_beceri.php'de events tablosundan teacher bilgilerini çekerken:\n";
    echo "   - Events'teki teacher_id'yi teachers tablosundan çek\n";
    echo "   - Sonra o teacher'ın tep_teachers'teki karşılığını bul\n\n";
    
    echo "2. VEYA events tablosundaki teacher_id'leri tep_teachers'teki ID'lerle eşleştir\n\n";
    
    echo "3. VEYA iki tabloyu birleştir/senkronize et\n\n";
    
    echo "🔧 ÖNERİLEN ÇÖZÜM KODU:\n";
    echo "```sql\n";
    echo "-- get_temel_beceri.php'deki events sorgusunu şöyle değiştir:\n";
    echo "SELECT \n";
    echo "    e.*,\n";
    echo "    t.first_name,\n";
    echo "    t.last_name,\n";
    echo "    tt.person_name as tep_teacher_name,\n";
    echo "    tt.skill_name\n";
    echo "FROM events e\n";
    echo "LEFT JOIN teachers t ON e.teacher_id = t.id\n";
    echo "LEFT JOIN tep_teachers tt ON CONCAT(t.first_name, ' ', t.last_name) = tt.person_name\n";
    echo "```\n\n";
    
    echo "🔍 MEVCUT DURUMDA HANGİ TEACHER'LARIN EŞLEŞTİĞİNİ KONTROL EDELİM:\n\n";
    
    $stmt = $pdo->query("
        SELECT 
            t.id as teachers_id,
            CONCAT(t.first_name, ' ', t.last_name) as teachers_name,
            tt.id as tep_teachers_id,
            tt.person_name as tep_teachers_name,
            tt.skill_name
        FROM teachers t
        LEFT JOIN tep_teachers tt ON CONCAT(t.first_name, ' ', t.last_name) = tt.person_name
        ORDER BY t.id
    ");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($matches as $match) {
        if ($match['tep_teachers_id']) {
            echo "✅ EŞLEŞEN: teachers.id={$match['teachers_id']} ({$match['teachers_name']}) ↔ tep_teachers.id={$match['tep_teachers_id']} ({$match['tep_teachers_name']}) - {$match['skill_name']}\n";
        } else {
            echo "❌ EŞLEŞMEYEN: teachers.id={$match['teachers_id']} ({$match['teachers_name']}) - tep_teachers'te yok\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}

echo "\n🏁 Analiz tamamlandı.\n";
?>
