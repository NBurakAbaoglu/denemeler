<?php
require_once 'config.php'; // PDO bağlantısı config.php'de var
header('Content-Type: application/json');

try {
    // Formdan gelen veriler
    $organization_id = intval($_POST['multiSkill'] ?? 0);
    $skill_name      = $_POST['basicSkill'] ?? '';
    $teacher_name    = $_POST['teacher'] ?? '';
    $start_date      = $_POST['startDate'] ?? '';
    $end_date        = $_POST['endDate'] ?? '';
    $capacity        = $_POST['quota'] ?? '';
    $description     = $_POST['description'] ?? '';

    if (
        $organization_id <= 0 || 
        $skill_name === '' || 
        $teacher_name === '' || 
        $start_date === '' || 
        $end_date === '' || 
        $capacity === ''
    ) {
        throw new Exception("Lütfen tüm zorunlu alanları doldurun.");
    }

    // skills tablosundan skill_name'e göre id çek
    $stmtLesson = $pdo->prepare("
        SELECT s.id 
        FROM skills s
        INNER JOIN organization_skills os ON s.id = os.skill_id
        WHERE s.skill_name = :skillName AND os.organization_id = :organizationId
        LIMIT 1
    ");
    $stmtLesson->execute(['skillName' => $skill_name, 'organizationId' => $organization_id]);
    $lesson = $stmtLesson->fetch();

    if (!$lesson) {
        throw new Exception("Seçilen beceri bu organizasyonda bulunamadı. Önce organizasyona beceriyi ekleyin.");
    }

    $lesson_id = $lesson['id'];

    // teacher_id'yi bul (teachers tablosundan)
    $stmtTeacher = $pdo->prepare("
        SELECT t.id 
        FROM teachers t
        INNER JOIN tep_teachers tt ON CONCAT(t.first_name, ' ', t.last_name) = tt.person_name
        WHERE tt.person_name = :teacherName 
          AND tt.skill_name = :skillName
        LIMIT 1
    ");
    $stmtTeacher->execute(['teacherName' => $teacher_name, 'skillName' => $skill_name]);
    $teacher = $stmtTeacher->fetch();

    if (!$teacher) throw new Exception("Seçilen öğretmen bulunamadı.");

    $teacher_id = $teacher['id'];

    // event_name olarak organizasyon adını al
    $stmtOrg = $pdo->prepare("SELECT name FROM organizations WHERE id = ?");
    $stmtOrg->execute([$organization_id]);
    $org = $stmtOrg->fetch();
    $event_name = $org ? $org['name'] : 'Bilinmeyen Organizasyon';

    // Veriyi events tablosuna ekle
    $stmtInsert = $pdo->prepare("
        INSERT INTO events 
            (event_name, event_date, end_date, teacher_id, lesson_id, course_title, capacity, enrolled_count, description, status, created_at)
        VALUES 
            (:event_name, :start_date, :end_date, :teacher_id, :lesson_id, :course_title, :capacity, 0, :description, 'active', NOW())
    ");

    $stmtInsert->execute([
        'event_name'   => $event_name,
        'start_date'   => $start_date,
        'end_date'     => $end_date,
        'teacher_id'   => $teacher_id,
        'lesson_id'    => $lesson_id,
        'course_title' => $skill_name,
        'capacity'     => $capacity,
        'description'  => $description
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
