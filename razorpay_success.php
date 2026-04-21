
<?php
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
$razorpay_order_id = $input['razorpay_order_id'] ?? '';
$razorpay_signature = $input['razorpay_signature'] ?? '';
$event_id = (int)($input['event_id'] ?? 0);

if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature) || !$event_id) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

require_once '../config/db.php';
require_once '../config/razorpay.php';


use Razorpay\Api\Api;

$api = new Api(RazorpayConfig::getKeyId(), RazorpayConfig::getKeySecret());

try {
    $attributes = [
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'razorpay_signature' => $razorpay_signature
    ];

    $api->utility->verifyPaymentSignature($attributes);

    $payment_id = $razorpay_payment_id;
    $user_id = $_SESSION['user_id'];

    $db = new Database();
    $conn = $db->getConnection();

    // Register after payment
    $stmt = $conn->prepare("
        INSERT INTO registrations (student_id, event_id, payment_id, registration_date) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $event_id, $payment_id]);

    // Update event seats
    $stmt = $conn->prepare("UPDATE events SET available_seats = available_seats - 1 WHERE event_id = ?");
    $stmt->execute([$event_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Payment verified! Event registered successfully!',
        'payment_id' => $payment_id
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Payment verification failed: ' . $e->getMessage()]);
}
?>

