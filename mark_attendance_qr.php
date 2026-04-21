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
    $student_id = isset($data['student_id']) ? (int)$data['student_id'] : 0;
    $event_id = isset($data['event_id']) ? (int)$data['event_id'] : 0;

    if ($student_id <= 0 || $event_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid student or event ID']);
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

    // Get student name for response
    $stmt = $conn->prepare("SELECT name FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }

    // Mark attendance
    $stmt = $conn->prepare("UPDATE registrations SET attendance_marked = TRUE WHERE student_id = ? AND event_id = ?");
    $stmt->execute([$student_id, $event_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'student_name' => $student['name']
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Student not registered for this event or attendance already marked']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
