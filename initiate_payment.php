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
    $event_id = (int)$data['event_id'];

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT price FROM events WHERE event_id = ? AND available_seats > 0");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event || $event['price'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'No payment needed or event not available']);
        exit();
    }

    $amount = (int)($event['price'] * 100); // paise

    // Razorpay test keys (REPLACE WITH YOUR OWN)
    $key_id = 'rzp_test_1DP5mmOlF5G5ag'; // Test key
    $key_secret = 'dw6P2vY5MKLq0rCGR5TByO6V'; // Test secret - GET YOUR OWN FROM dashboard.razorpay.com

    $order_data = [
        'amount' => $amount,
        'currency' => 'INR',
        'receipt' => 'campus_hive_' . $_SESSION['user_id'] . '_' . $event_id,
        'notes' => ['event_id' => $event_id]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($key_id . ':' . $key_secret)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $order = json_decode($response, true);

    if ($order['status'] == 'created') {
        echo json_encode([
            'success' => true,
            'order_id' => $order['id'],
            'amount' => $amount,
            'key' => $key_id,
            'name' => 'CampusHive',
            'description' => 'Event Registration Fee'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order creation failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

