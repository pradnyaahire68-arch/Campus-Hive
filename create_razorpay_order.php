
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
$amount = (float)($input['amount'] ?? 0);

if ($event_id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

require_once '../config/db.php';
require_once '../config/razorpay.php';


$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT registration_fee FROM events WHERE event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event || $amount != $event['registration_fee']) {
    echo json_encode(['success' => false, 'message' => 'Event not found or amount mismatch']);
    exit;
}

use Razorpay\Api\Api;

$api = new Api(RazorpayConfig::getKeyId(), RazorpayConfig::getKeySecret());

try {
    $orderData = [
        'receipt' => 'event_' . $event_id . '_' . $_SESSION['user_id'],
        'amount' => $amount * 100,  // paise
        'currency' => 'INR'
    ];

    $razorpayOrder = $api->order->create($orderData);

    echo json_encode([
        'success' => true,
        'order_id' => $razorpayOrder['id'],
        'amount' => $amount * 100,
        'currency' => 'INR',
        'key' => RazorpayConfig::getKeyId()
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Order creation failed: ' . $e->getMessage()]);
}
?>

