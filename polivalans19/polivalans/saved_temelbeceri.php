<?php
header('Content-Type: application/json');
require_once 'config.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri']);
    exit;
}

try {
    // Mevcut kayıt var mı kontrol et (artık planned_skills tablosunda)
    $checkStmt = $pdo->prepare("
        SELECT id FROM planned_skills 
        WHERE person_id = :person_id 
        AND organization_id = :organization_id 
        AND skill_id = :skill_id
        LIMIT 1
    ");
    $checkStmt->execute([
        ':person_id' => $data['person_id'],
        ':organization_id' => $data['organization_id'],
        ':skill_id' => $data['skill_id']
    ]);

    $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

    // Eğer teacher_id boş, null ya da gelmediyse → planned_skills'te teacher_id'yi null yap
    if (!isset($data['teacher_id']) || is_null($data['teacher_id']) || trim($data['teacher_id']) === '') {
        if ($existingRecord) {
            // planned_skills kaydını güncelle (teacher_id'yi null yap)
            $updateStmt = $pdo->prepare("
                UPDATE planned_skills 
                SET teacher_id = NULL, event_id = NULL, status = 'planlandi', updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([':id' => $existingRecord['id']]);
        }

        echo json_encode(['success' => true, 'message' => 'Eğitmen ataması kaldırıldı.']);
        exit;
    }

    // Buraya geldiğimizde teacher_id geçerli → Kayıt güncelle veya ekle

    if ($existingRecord) {
        // Güncelle (artık planned_skills tablosunda)
        $stmt = $pdo->prepare("
            UPDATE planned_skills SET
                teacher_id = :teacher_id,
                event_id = :event_id,
                target_level = :target_level,
                start_date = :start_date,
                end_date = :end_date,
                status = :status,
                priority = :priority,
                notes = :notes,
                success_status = :success_status,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':teacher_id' => $data['teacher_id'],
            ':event_id' => $data['event_id'],
            ':target_level' => $data['target_level'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':status' => $data['status'],
            ':priority' => $data['priority'],
            ':notes' => $data['notes'],
            ':success_status' => $data['success_status'],
            ':id' => $existingRecord['id']
        ]);
    } else {
        // Yeni kayıt oluştur (artık planned_skills tablosunda)
        $stmt = $pdo->prepare("
            INSERT INTO planned_skills
            (person_id, organization_id, skill_id, teacher_id, event_id, target_level, start_date, end_date, status, priority, notes, success_status, created_by, created_at, updated_at)
            VALUES
            (:person_id, :organization_id, :skill_id, :teacher_id, :event_id, :target_level, :start_date, :end_date, :status, :priority, :notes, :success_status, :created_by, NOW(), NOW())
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
            ':success_status' => $data['success_status'],
            ':created_by' => $data['created_by']
        ]);
    }

    // Artık planned_skills tablosunda çalıştığımız için ek güncelleme gerekmiyor
    // Diğer organizasyonlardaki aynı becerileri senkronize et
    syncSkillStatusAcrossOrganizations($data['person_id'], $data['skill_id'], $data['success_status'], $data['status'], $pdo);

    echo json_encode(['success' => true, 'message' => $existingRecord ? 'Kayıt güncellendi ve senkronize edildi' : 'Yeni kayıt eklendi ve senkronize edildi']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Diğer organizasyonlardaki aynı becerileri senkronize eder
 */
function syncSkillStatusAcrossOrganizations($personId, $skillId, $successStatus, $status, $pdo) {
    try {
        $skillNameStmt = $pdo->prepare("
            SELECT s.skill_name 
            FROM skills s
            INNER JOIN organization_skills os ON s.id = os.skill_id
            WHERE os.id = ?
            LIMIT 1
        ");
        $skillNameStmt->execute([$skillId]);
        $skillName = $skillNameStmt->fetch(PDO::FETCH_ASSOC)['skill_name'];

        if (!$skillName) return;

        $allSkillsStmt = $pdo->prepare("
            SELECT os.id as organization_skill_id, o.name as organization_name
            FROM skills s
            INNER JOIN organization_skills os ON s.id = os.skill_id
            INNER JOIN organizations o ON os.organization_id = o.id
            WHERE s.skill_name = ? AND s.is_active = 1
        ");
        $allSkillsStmt->execute([$skillName]);
        $allSkills = $allSkillsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allSkills as $skill) {
            $orgSkillId = $skill['organization_skill_id'];

            $checkStmt = $pdo->prepare("
                SELECT id FROM planned_skills 
                WHERE person_id = ? AND skill_id = ?
                LIMIT 1
            ");
            $checkStmt->execute([$personId, $orgSkillId]);
            $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRecord) {
                $updateStmt = $pdo->prepare("
                    UPDATE planned_skills SET
                    success_status = ?,
                    status = ?,
                    updated_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$successStatus, $status, $existingRecord['id']]);
            } else {
                $insertStmt = $pdo->prepare("
                    INSERT INTO planned_skills
                    (person_id, organization_id, skill_id, success_status, status, created_at, updated_at)
                    VALUES
                    (?, (SELECT organization_id FROM organization_skills WHERE id = ?), ?, ?, ?, NOW(), NOW())
                ");
                $insertStmt->execute([$personId, $orgSkillId, $orgSkillId, $successStatus, $status]);
            }
        }

    } catch (Exception $e) {
        error_log("❌ Beceri senkronizasyon hatası: " . $e->getMessage());
    }
    
}
