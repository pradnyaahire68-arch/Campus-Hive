<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'college') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';

$db = new Database();
$conn = $db->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$event_id = (int)($data['event_id'] ?? 0);

if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    exit;
}

$stmt = $conn->prepare("UPDATE events SET is_draft = 0, status = 'upcoming' WHERE event_id = ? AND college_id = ?");
if ($stmt->execute([$event_id, $_SESSION['user_id']]) && $stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Event published!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Event not found or already published']);
}
?>

