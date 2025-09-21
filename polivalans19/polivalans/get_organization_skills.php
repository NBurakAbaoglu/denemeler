<?php
require_once __DIR__ . '/config.php';


header('Content-Type: application/json; charset=utf-8');

try {
    $mysqli = getDBConnection();

    if (!$mysqli) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['organization_id']) && !empty($input['organization_id'])) {
        // Organizasyon bazlı becerileri getir
        $organizationId = $input['organization_id'];
        $sql = "
            SELECT 
                os.id,
                s.skill_name,
                s.skill_description,
                s.category,
                os.priority,
                os.created_at
            FROM organization_skills os
            JOIN skills s ON os.skill_id = s.id
            WHERE os.organization_id = ? AND s.is_active = 1
            ORDER BY s.skill_name ASC
        ";
        $skills = fetchAll($mysqli, $sql, [$organizationId]);

        echo json_encode([
            'success' => true,
            'skills' => $skills
        ]);
    } else {
        // organization_id yoksa, genel skill listesi ve organizasyon sayısı ile
        $query = "
            SELECT 
                s.skill_name,
                s.skill_description,
                s.category,
                COUNT(DISTINCT os.organization_id) AS org_count
            FROM skills s
            LEFT JOIN organization_skills os ON s.id = os.skill_id
            WHERE s.is_active = 1
            GROUP BY s.id, s.skill_name, s.skill_description, s.category
            ORDER BY s.skill_name
        ";

        $stmt = $mysqli->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        $skillsSummary = [];
        while ($row = $result->fetch_assoc()) {
            $skillsSummary[] = $row;
        }

        echo json_encode([
            'success' => true,
            'skills_summary' => $skillsSummary
        ]);
    }

} catch (Exception $e) {
    error_log("Beceri getirme hatası: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
