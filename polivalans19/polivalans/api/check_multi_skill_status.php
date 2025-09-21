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
                pms.id,
                pms.person_id,
                pms.organization_id,
                pms.success_status,
                pms.teacher_id,
                pms.created_at,
                pms.updated_at,
                p.name AS person_name,
                o.name AS organization_name
            FROM planned_multi_skill pms
            LEFT JOIN persons p ON pms.person_id = p.id
            LEFT JOIN organizations o ON pms.organization_id = o.id
            WHERE pms.organization_id = ? AND pms.person_id = ?
            ORDER BY pms.created_at DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$organization_id, $person_id]);
        $multi_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Bu kişi için istatistikleri hesapla
        $totalSkills = count($multi_skills);
        $completedSkills = 0;
        $plannedSkills = 0;
        $requestedSkills = 0;
        
        foreach ($multi_skills as $skill) {
            switch ($skill['success_status']) {
                case 'tamamlandi':
                    $completedSkills++;
                    break;
                case 'planlandi':
                    $plannedSkills++;
                    break;
                case 'istek_gonderildi':
                    $requestedSkills++;
                    break;
            }
        }
        
        // Bu kişinin tüm çoklu becerileri tamamlandı mı kontrol et
        $allCompleted = ($totalSkills > 0 && $completedSkills === $totalSkills);
        
        echo json_encode([
            'success' => true,
            'organization_id' => $organization_id,
            'person_id' => $person_id,
            'total_skills' => $totalSkills,
            'completed_skills' => $completedSkills,
            'planned_skills' => $plannedSkills,
            'requested_skills' => $requestedSkills,
            'all_completed' => $allCompleted,
            'completion_percentage' => $totalSkills > 0 ? round(($completedSkills / $totalSkills) * 100, 2) : 0,
            'check_type' => 'person_specific_multi_skill'
        ]);
    } else {
        // Tüm organizasyon için kontrol et
        $query = "
            SELECT 
                pms.id,
                pms.person_id,
                pms.organization_id,
                pms.success_status,
                pms.teacher_id,
                pms.created_at,
                pms.updated_at,
                p.name AS person_name,
                o.name AS organization_name
            FROM planned_multi_skill pms
            LEFT JOIN persons p ON pms.person_id = p.id
            LEFT JOIN organizations o ON pms.organization_id = o.id
            WHERE pms.organization_id = ?
            ORDER BY pms.person_id, pms.created_at DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$organization_id]);
        $multi_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // İstatistikleri hesapla
        $totalSkills = count($multi_skills);
        $completedSkills = 0;
        $plannedSkills = 0;
        $requestedSkills = 0;
        
        foreach ($multi_skills as $skill) {
            switch ($skill['success_status']) {
                case 'tamamlandi':
                    $completedSkills++;
                    break;
                case 'planlandi':
                    $plannedSkills++;
                    break;
                case 'istek_gonderildi':
                    $requestedSkills++;
                    break;
            }
        }
        
        // Tüm çoklu beceriler tamamlandı mı kontrol et
        $allCompleted = ($totalSkills > 0 && $completedSkills === $totalSkills);
        
        echo json_encode([
            'success' => true,
            'organization_id' => $organization_id,
            'total_skills' => $totalSkills,
            'completed_skills' => $completedSkills,
            'planned_skills' => $plannedSkills,
            'requested_skills' => $requestedSkills,
            'all_completed' => $allCompleted,
            'completion_percentage' => $totalSkills > 0 ? round(($completedSkills / $totalSkills) * 100, 2) : 0,
            'check_type' => 'organization_wide_multi_skill'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
