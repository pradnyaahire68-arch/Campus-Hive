<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/db.php';

try {
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';

    if ($event_id <= 0 || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid event ID or rating']);
        exit();
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Check if student is registered for this event
    $stmt = $conn->prepare("SELECT registration_id FROM registrations WHERE event_id = ? AND student_id = ?");
    $stmt->execute([$event_id, $_SESSION['user_id']]);

    if ($stmt->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You are not registered for this event']);
        exit();
    }

    // Update feedback
    $stmt = $conn->prepare("UPDATE registrations SET feedback = ?, rating = ? WHERE event_id = ? AND student_id = ?");
    $stmt->execute([$feedback, $rating, $event_id, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
