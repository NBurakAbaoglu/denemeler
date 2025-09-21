<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0); // hata mesajlarını gizle
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// echo json_encode(['success' => true]);
// exit;

// Hataları kullanıcıya gösterme, log dosyasına yaz
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require_once __DIR__ . '/../config.php';

// PDO bağlantısının varlığını kontrol et
if (!isset($pdo)) {
    echo json_encode(['success' => false, 'message' => 'PDO bağlantısı bulunamadı.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Check if this is a skill update request (skill_id, skill_name, skill_description)
if (isset($data['skill_id'], $data['skill_name'], $data['skill_description'])) {
    $skillId = intval($data['skill_id']);
    $skillName = trim($data['skill_name']);
    $skillDescription = trim($data['skill_description']);

    if ($skillId <= 0 || empty($skillName) || empty($skillDescription)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz veri.']);
        exit;
    }

    try {
        // First get the skill_id from organization_skills table
        $stmt = $pdo->prepare("SELECT skill_id FROM organization_skills WHERE id = ?");
        $stmt->execute([$skillId]);
        $orgSkill = $stmt->fetch();
        
        if (!$orgSkill) {
            echo json_encode(['success' => false, 'message' => 'Organizasyon becerisi bulunamadı.']);
            exit;
        }
        
        // Update the skills table with the new name and description
        $stmt = $pdo->prepare("UPDATE skills SET skill_name = ?, skill_description = ? WHERE id = ?");
        $stmt->execute([$skillName, $skillDescription, $orgSkill['skill_id']]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
    exit;
}

// Check if this is a priority update request (organization_skill_id, priority)
if (!isset($data['organization_skill_id'], $data['priority'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametre.']);
    exit;
}

$organizationSkillId = intval($data['organization_skill_id']);
$priority = trim($data['priority']);

if ($organizationSkillId <= 0 || !in_array($priority, ['low', 'medium', 'high'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE organization_skills SET priority = ? WHERE id = ?");
    $stmt->execute([$priority, $organizationSkillId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
