<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'college') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$event_id = (int)($data['event_id'] ?? 0);

if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("DELETE FROM events WHERE event_id = ? AND college_id = ? AND is_draft = 1");
if ($stmt->execute([$event_id, $_SESSION['user_id']]) && $stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Draft deleted!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Draft not found']);
}
?>

