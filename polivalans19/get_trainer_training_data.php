<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

try {
    $mysqli = getDBConnection();
    if (!$mysqli) throw new Exception("Veritabanı bağlantısı kurulamadı");

    $query = "
        SELECT 
            pms.id,
            pms.person_id,
            pms.organization_id,
            pms.success_status,
            pms.teacher_id,
            pms.created_at,
            pms.updated_at,
            
            -- PERSON bilgileri
            p.name AS person_name,
            p.registration_no,
            p.company_name,
            p.title,
            
            -- ORGANIZATION bilgileri
            o.name AS organization_name,
            
            -- TEACHER bilgileri
            tt.person_name AS teacher_name,
            tt.skill_name AS teacher_skill_name,
            
            -- SKILL bilgileri (organization_skills üzerinden)
            s.skill_name,
            
            -- Eğiticinin eğitimi durumu
            tts.training_status,
            tts.completion_date,
            tts.notes
            
        FROM planned_multi_skill pms
        LEFT JOIN persons p ON pms.person_id = p.id
        LEFT JOIN organizations o ON pms.organization_id = o.id
        LEFT JOIN tep_teachers tt ON pms.teacher_id = tt.id
        LEFT JOIN organization_skills os ON pms.organization_id = os.organization_id
        LEFT JOIN skills s ON os.skill_id = s.id
        LEFT JOIN trainer_training_status tts ON pms.id = tts.planned_multi_skill_id
        ORDER BY pms.created_at DESC
    ";
    
    $result = $mysqli->query($query);
    if (!$result) throw new Exception("Sorgu hatası: " . $mysqli->error);

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => $row['id'],
            'person_id' => $row['person_id'],
            'organization_id' => $row['organization_id'],
            'success_status' => $row['success_status'],
            'teacher_id' => $row['teacher_id'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'person_name' => $row['person_name'],
            'registration_no' => $row['registration_no'],
            'company_name' => $row['company_name'],
            'title' => $row['title'],
            'organization_name' => $row['organization_name'],
            'teacher_name' => $row['teacher_name'],
            'teacher_skill_name' => $row['teacher_skill_name'],
            'skill_name' => $row['skill_name'],
            'multi_skill_name' => $row['teacher_skill_name'], // Çoklu beceri adı
            'training_status' => $row['training_status'] ?: 'tamamlanmadı',
            'completion_date' => $row['completion_date'],
            'notes' => $row['notes']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ]);

} catch (Exception $e) {
    error_log("Eğiticinin eğitimi veri çekme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
