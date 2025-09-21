<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

try {
    // planned_multi_skill + persons + organizations + tep_teachers
    $query = "
        SELECT 
            pms.id,
            pms.person_id,
            pms.organization_id,
            pms.success_status,
            pms.teacher_id,
            pms.created_at,
            pms.updated_at,
            
            -- PERSON bilgileri persons tablosundan geliyor:
            p.name AS person_name,
            p.registration_no,
            p.company_name,
            p.title,
            
            -- ORGANIZATION bilgileri
            o.name AS organization_name,
            
            -- TEACHER bilgileri tep_teachers tablosundan geliyor:
            tt.person_name AS teacher_name,
            tt.skill_name AS teacher_skill_name
            
        FROM planned_multi_skill pms
        LEFT JOIN persons p ON pms.person_id = p.id
        LEFT JOIN organizations o ON pms.organization_id = o.id
        LEFT JOIN tep_teachers tt ON pms.teacher_id = tt.id
        ORDER BY pms.created_at DESC
    ";
    
    $stmt = $pdo->query($query);
    $multi_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tekrarlanan kayıtları temizle - aynı person_id ve organization_id için sadece en son kaydı al
    $unique_skills = [];
    $seen_combinations = [];
    
    foreach ($multi_skills as $skill) {
        $key = $skill['person_id'] . '_' . $skill['organization_id'];
        
        // Eğer bu kombinasyon daha önce görülmemişse veya bu kayıt daha yeni ise
        if (!isset($seen_combinations[$key]) || $skill['id'] > $seen_combinations[$key]['id']) {
            $seen_combinations[$key] = $skill;
        }
    }
    
    // Benzersiz kayıtları al
    $multi_skills = array_values($seen_combinations);
    
    // Debug: Kaç kayıt var kontrol et
    error_log('🔍 Unique skills count: ' . count($multi_skills));
    foreach ($multi_skills as $skill) {
        error_log('🔍 Skill: person_id=' . $skill['person_id'] . ', organization_id=' . $skill['organization_id'] . ', id=' . $skill['id']);
    }
    
    // Her multi_skill kaydı için o organizasyona ait becerileri çek ve her beceri için ayrı satır oluştur
    $expanded_skills = [];
    
    foreach ($multi_skills as $skill) {
        $org_skills_query = "
            SELECT s.skill_name 
            FROM organization_skills os 
            LEFT JOIN skills s ON os.skill_id = s.id 
            WHERE os.organization_id = ?
        ";
        $org_skills_stmt = $pdo->prepare($org_skills_query);
        $org_skills_stmt->execute([$skill['organization_id']]);
        $org_skills = $org_skills_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Her organizasyon için tek satır oluştur (tüm eğitimleri birleştir)
        if (empty($org_skills)) {
            // Beceri yoksa tek satır oluştur
            $skill['skill_names'] = 'Çoklu Beceri';
            $expanded_skills[] = $skill;
        } else {
            // Tüm eğitimleri birleştir ve tek satır oluştur
            $skill['skill_names'] = implode(', ', $org_skills);
            $expanded_skills[] = $skill;
        }
    }
    
    $multi_skills = $expanded_skills;
    
    // Events tablosu (teacher_id ile tep_teachers join)
    $eventsQuery = "
        SELECT 
            e.*,
            tt.person_name AS teacher_name
        FROM events e
        LEFT JOIN tep_teachers tt ON e.teacher_id = tt.id
        ORDER BY e.event_date DESC
    ";
    $eventsStmt = $pdo->query($eventsQuery);
    $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Teachers tablosu
    $teachersStmt = $pdo->query("SELECT id, person_name, skill_name, organization_name FROM tep_teachers");
    $teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'multi_skills' => $multi_skills,
        'events' => $events,
        'teachers' => $teachers,
        'count' => count($multi_skills)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
