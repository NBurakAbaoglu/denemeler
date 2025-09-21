<?php
header('Content-Type: application/json');
require_once 'config.php'; // PDO bağlantısı

// Gelen JSON veriyi al
$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("
        INSERT INTO planlandi 
        (person_id, organization_id, skill_id, teacher_id, event_id, target_level, start_date, end_date, status, priority, notes, created_by, created_at, updated_at)
        VALUES 
        (:person_id, :organization_id, :skill_id, :teacher_id, :event_id, :target_level, :start_date, :end_date, :status, :priority, :notes, :created_by, NOW(), NOW())
    ");

    $stmt->execute([
        ':person_id' => $data['person_id'],
        ':organization_id' => $data['organization_id'],
        ':skill_id' => $data['skill_id'],
        ':teacher_id' => $data['teacher_id'],
        ':event_id' => $data['event_id'],
        ':target_level' => $data['target_level'],
        ':start_date' => $data['start_date'],
        ':end_date' => $data['end_date'],
        ':status' => $data['status'],
        ':priority' => $data['priority'],
        ':notes' => $data['notes'],
        ':created_by' => $data['created_by']
    ]);

    echo json_encode(['success' => true]);

} catch(PDOException $e){
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
