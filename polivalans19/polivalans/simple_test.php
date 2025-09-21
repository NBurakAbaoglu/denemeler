<?php
require_once 'config.php';

echo "Simple test...\n";

$stmt = $pdo->query("SELECT e.id, e.teacher_id, t.first_name, t.last_name FROM events e LEFT JOIN teachers t ON e.teacher_id = t.id WHERE e.teacher_id IS NOT NULL LIMIT 3");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Event: {$row['id']}, Teacher: {$row['teacher_id']}, Name: {$row['first_name']} {$row['last_name']}\n";
}
?>
