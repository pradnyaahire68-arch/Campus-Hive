<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'college') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $registration_id = isset($data['registration_id']) ? (int)$data['registration_id'] : 0;
    $event_id = isset($data['event_id']) ? (int)$data['event_id'] : 0;

    if ($registration_id <= 0 || $event_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid registration or event ID']);
        exit();
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Verify that the event belongs to this college
    $stmt = $conn->prepare("SELECT event_id FROM events WHERE event_id = ? AND college_id = ?");
    $stmt->execute([$event_id, $_SESSION['user_id']]);

    if ($stmt->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    // Mark attendance
    $stmt = $conn->prepare("UPDATE registrations SET attendance_marked = TRUE WHERE registration_id = ? AND event_id = ?");
    $stmt->execute([$registration_id, $event_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Attendance marked successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Failed to mark attendance']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
