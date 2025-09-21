<?php
header('Content-Type: application/json');
include 'config.php'; // DB bağlantısı

$mysqli = getDBConnection();
if (!$mysqli) {
  echo json_encode(['success' => false, 'message' => 'DB bağlantı hatası']);
  exit;
}

$sql = "SELECT id, name, column_position FROM organizations ORDER BY column_position ASC";
$result = $mysqli->query($sql);

$organizations = [];
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $organizations[] = $row;
  }
  echo json_encode(['success' => true, 'organizations' => $organizations]);
} else {
  echo json_encode(['success' => false, 'message' => 'Sorgu hatası: ' . $mysqli->error]);
}

closeDBConnection($mysqli);
?>
