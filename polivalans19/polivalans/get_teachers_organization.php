<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $lesson_id = $_GET['lesson_id'] ?? null;
    $course_title = $_GET['course_title'] ?? null;

    if ($lesson_id || $course_title) {
        if ($lesson_id) {
            $whereSql = "e.lesson_id = ?";
            $params = [$lesson_id];
        } else {
            // Ders adına göre filtreleme course_title üzerinden yapıyoruz
            $whereSql = "e.course_title = ?";
            $params = [$course_title];
        }

        $query = "
            SELECT 
                e.teacher_id,
                CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                e.capacity,
                e.enrolled_count,
                (e.capacity - e.enrolled_count) AS available_slots,
                CASE 
                    WHEN (e.capacity - e.enrolled_count) > 0 THEN 'available'
                    WHEN (e.capacity - e.enrolled_count) = 0 THEN 'full'
                    ELSE 'overbooked'
                END AS capacity_status,
                ROUND((e.enrolled_count / e.capacity) * 100, 1) AS occupancy_percentage,
                e.course_title,
                e.event_date,
                e.end_date
            FROM events e
            JOIN teachers t ON e.teacher_id = t.id
            WHERE $whereSql AND e.status = 'active'
            ORDER BY available_slots DESC, teacher_name ASC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

    } else {
        $query = "
            SELECT 
                e.teacher_id,
                CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                e.capacity,
                e.enrolled_count,
                (e.capacity - e.enrolled_count) AS available_slots,
                CASE 
                    WHEN (e.capacity - e.enrolled_count) > 0 THEN 'available'
                    WHEN (e.capacity - e.enrolled_count) = 0 THEN 'full'
                    ELSE 'overbooked'
                END AS capacity_status,
                ROUND((e.enrolled_count / e.capacity) * 100, 1) AS occupancy_percentage,
                e.course_title,
                e.event_date,
                e.end_date
            FROM events e
            JOIN teachers t ON e.teacher_id = t.id
            WHERE e.status = 'active'
            ORDER BY e.course_title, available_slots DESC, teacher_name ASC
        ";

        $stmt = $pdo->query($query);
    }

    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($teachers) > 0) {
        $response = [
            'success' => true,
            'message' => 'Eğitimciler başarıyla getirildi',
            'count' => count($teachers),
            'teachers' => $teachers,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        error_log("✅ Eğitimciler getirildi: " . count($teachers) . " kayıt");
    } else {
        $response = [
            'success' => false,
            'message' => 'Hiç eğitimci bulunamadı',
            'count' => 0,
            'teachers' => [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        error_log("⚠️ Eğitimci bulunamadı");
    }
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    error_log("❌ Veritabanı hatası: " . $e->getMessage());
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Sistem hatası: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    error_log("❌ Genel hata: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
