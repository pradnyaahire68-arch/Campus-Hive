<?php
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$event_id = (int)($input['event_id'] ?? 0);
$payment_ref = $input['payment_ref'] ?? null;
$user_id = $_SESSION['user_id'] ?? 0;

if (!$event_id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

require_once '../config/db.php';
$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // Check already registered
    $stmt = $conn->prepare("SELECT 1 FROM registrations WHERE event_id = ? AND student_id = ?");
    $stmt->execute([$event_id, $user_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Already registered']);
        exit;
    }

    // Check seats
    $stmt = $conn->prepare("SELECT available_seats FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    if (!$event || $event['available_seats'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'No seats available']);
        exit;
    }

    $payment_id = $payment_ref ?: 'FREE-' . time();
    
    // Insert registration
    $stmt = $conn->prepare("
        INSERT INTO registrations (student_id, event_id, payment_id, registration_date) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $event_id, $payment_id]);
    $registration_id = $conn->lastInsertId();

    // Update seats
    $stmt = $conn->prepare("UPDATE events SET available_seats = available_seats - 1 WHERE event_id = ?");
    $stmt->execute([$event_id]);

    // Update payment status if paid
    if ($payment_ref) {
        $stmt = $conn->prepare("UPDATE payments SET status = 'succeeded' WHERE payment_id = ?");
        $stmt->execute([$payment_ref]);
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $payment_ref ? 'Payment confirmed! Event registered.' : 'Registration successful!',
        'registration_id' => $registration_id,
        'payment_id' => $payment_id
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}
?>

