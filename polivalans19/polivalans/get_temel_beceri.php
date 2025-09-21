<?php
header('Content-Type: application/json');
require_once 'config.php'; // PDO bağlantısı

try {
    // planned_skills + persons + organization_skills (artık planlandi tablosu kullanılmıyor)
    $query = "
        SELECT 
            ps.id,
            ps.organization_id,
            ps.person_id,
            ps.skill_id,

            -- PERSON bilgileri persons tablosundan geliyor:
            p.name AS person_name,
            p.registration_no,
            p.company_name,
            p.title,

            -- SKILL bilgileri
            s.skill_name,
            s.skill_description,
            s.category,

            -- PLANNED_SKILLS bilgileri (artık planlandi tablosu yerine planned_skills kullanılıyor)
            ps.teacher_id AS selected_teacher,
            ps.event_id AS selected_event,
            ps.success_status

        FROM planned_skills ps
        LEFT JOIN persons p ON ps.person_id = p.id
        INNER JOIN organization_skills os ON ps.skill_id = os.id
        INNER JOIN skills s ON os.skill_id = s.id
        ORDER BY ps.id ASC
    ";

    $stmt = $pdo->query($query);
    $plannedSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Events tablosu - teachers tablosu ile join yaparak öğretmen adlarını al
    $eventsStmt = $pdo->query("
        SELECT 
            e.*,
            CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
        FROM events e
        LEFT JOIN teachers t ON e.teacher_id = t.id
    ");
    $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Teachers tablosu - sadece teachers tablosundan al
    $teachersStmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) AS person_name FROM teachers");
    $teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'plannedSkills' => $plannedSkills,
        'events' => $events,
        'teachers' => $teachers
    ]);

} catch(PDOException $e){
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
