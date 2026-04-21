<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

try {
    $event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($event_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit();
    }

    $db = new Database();
    $conn = $db->getConnection();

$stmt = $conn->prepare("
SELECT e.*, c.name as college_name, c.address as college_address, e.payment_qr
        FROM events e
        JOIN colleges c ON e.college_id = c.college_id
        WHERE e.event_id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit;
    }



    echo json_encode(['success' => true, 'event' => $event]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
