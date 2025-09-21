<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['organization_id'])) {
        throw new Exception('Organization ID gerekli');
    }
    
    $organization_id = $input['organization_id'];
    $person_id = isset($input['person_id']) ? $input['person_id'] : null;
    
    // Eğer person_id verilmişse, sadece o kişi için kontrol et
    if ($person_id) {
        $query = "
            SELECT 
                ps.id,
                ps.person_id,
                ps.skill_id,
                ps.status as planned_status,
                ps.success_status,
                s.skill_name,
                s.skill_description,
                s.category
            FROM planned_skills ps
            INNER JOIN organization_skills os ON ps.skill_id = os.id
            INNER JOIN skills s ON os.skill_id = s.id
            WHERE ps.organization_id = ? AND ps.person_id = ? AND s.is_active = 1
            ORDER BY s.skill_name
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$organization_id, $person_id]);
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Bu kişi için istatistikleri hesapla
        $totalSkills = count($skills);
        $completedSkills = 0;
        $pendingSkills = 0;
        $notStartedSkills = 0;
        
        foreach ($skills as $skill) {
            // Yeni durum sistemi: planned_skills.status alanını kontrol et
            if ($skill['planned_status'] === 'tamamlandi') {
                $completedSkills++;
            } elseif ($skill['planned_status'] === 'planlandi' || $skill['planned_status'] === 'istek_gonderildi') {
                $pendingSkills++;
            } else {
                $notStartedSkills++;
            }
        }
        
        // Bu kişinin tüm becerileri tamamlandı mı kontrol et
        $allCompleted = ($totalSkills > 0 && $completedSkills === $totalSkills);
        
        echo json_encode([
            'success' => true,
            'organization_id' => $organization_id,
            'person_id' => $person_id,
            'total_skills' => $totalSkills,
            'completed_skills' => $completedSkills,
            'pending_skills' => $pendingSkills,
            'not_started_skills' => $notStartedSkills,
            'all_completed' => $allCompleted,
            'completion_percentage' => $totalSkills > 0 ? round(($completedSkills / $totalSkills) * 100, 2) : 0,
            'check_type' => 'person_specific'
        ]);
    } else {
        // Tüm organizasyon için kontrol et (eski davranış)
        $query = "
            SELECT 
                ps.id,
                ps.person_id,
                ps.skill_id,
                ps.status as planned_status,
                ps.success_status,
                s.skill_name,
                s.skill_description,
                s.category
            FROM planned_skills ps
            INNER JOIN organization_skills os ON ps.skill_id = os.id
            INNER JOIN skills s ON os.skill_id = s.id
            WHERE ps.organization_id = ? AND s.is_active = 1
            ORDER BY ps.person_id, s.skill_name
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$organization_id]);
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // İstatistikleri hesapla
        $totalSkills = count($skills);
        $completedSkills = 0;
        $pendingSkills = 0;
        $notStartedSkills = 0;
        
        foreach ($skills as $skill) {
            // Yeni durum sistemi: planned_skills.status alanını kontrol et
            if ($skill['planned_status'] === 'tamamlandi') {
                $completedSkills++;
            } elseif ($skill['planned_status'] === 'planlandi' || $skill['planned_status'] === 'istek_gonderildi') {
                $pendingSkills++;
            } else {
                $notStartedSkills++;
            }
        }
        
        // Tüm beceriler tamamlandı mı kontrol et
        $allCompleted = ($totalSkills > 0 && $completedSkills === $totalSkills);
        
        echo json_encode([
            'success' => true,
            'organization_id' => $organization_id,
            'total_skills' => $totalSkills,
            'completed_skills' => $completedSkills,
            'pending_skills' => $pendingSkills,
            'not_started_skills' => $notStartedSkills,
            'all_completed' => $allCompleted,
            'completion_percentage' => $totalSkills > 0 ? round(($completedSkills / $totalSkills) * 100, 2) : 0,
            'check_type' => 'organization_wide'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
