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
    $data = json_decode(file_get_contents('php://input'), true);
    $razorpay_order_id = $data['razorpay_order_id'];
    $razorpay_payment_id = $data['razorpay_payment_id'];
    $razorpay_signature = $data['razorpay_signature'];

    // Razorpay test keys (MATCH initiate_payment)
    $key_secret = 'dw6P2vY5MKLq0rCGR5TByO6V';

    // Verify signature
    $expected_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $key_secret);
    if ($expected_signature != $razorpay_signature) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment signature']);
        exit();
    }

    // Fetch payment details
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/payments/' . $razorpay_payment_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, 'rzp_test_1DP5mmOlF5G5ag:' . $key_secret);
    $response = curl_exec($ch);
    curl_close($ch);

    $payment = json_decode($response, true);
    if ($payment['status'] != 'captured') {
        echo json_encode(['success' => false, 'message' => 'Payment not captured']);
        exit();
    }

    // Extract event_id from receipt or notes
    $receipt_parts = explode('_', $payment['receipt']);
    $event_id = end($receipt_parts);

    // Now register (reuse register logic)
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event || $event['available_seats'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Event not available']);
        exit();
    }

    $stmt = $conn->prepare("SELECT registration_id FROM registrations WHERE event_id = ? AND student_id = ?");
    $stmt->execute([$event_id, $_SESSION['user_id']]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Already registered']);
        exit();
    }

    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO registrations (student_id, event_id, payment_method, payment_status) VALUES (?, ?, 'razorpay', 'paid')");
    $stmt->execute([$_SESSION['user_id'], $event_id]);

    $stmt = $conn->prepare("UPDATE events SET available_seats = available_seats - 1 WHERE event_id = ?");
    $stmt->execute([$event_id]);

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Payment verified and registration successful']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

