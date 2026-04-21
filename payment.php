<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$event = null;

if ($event_id > 0) {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - CampusHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">CampusHive</a>
            <!-- nav links -->
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Complete Registration Payment</h4>
                    </div>
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($event['title'] ?? 'Event'); ?></h5>
                        <p>Amount: <strong>₹<?php echo number_format($event['price'] ?? 50, 2); ?></strong></p>
                        <div class="mb-4">
                            <label>Choose Payment Method:</label>
                            <div class="mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="razorpayRadio" value="razorpay" checked onchange="togglePaymentSection('razorpay')">
                                    <label class="form-check-label" for="razorpayRadio">
                                        Razorpay (Credit Card / UPI / Net Banking)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="upiRadio" value="upi" onchange="togglePaymentSection('upi')">
                                    <label class="form-check-label" for="upiRadio">
                                        UPI
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="qrRadio" value="qr" onchange="togglePaymentSection('qr')">
                                    <label class="form-check-label" for="qrRadio">
                                        QR Code Scan
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Razorpay Section -->
                        <div id="razorpaySection">
                            <button onclick="payWithRazorpay(<?php echo $event_id ?? 0; ?>)" class="btn btn-primary w-100 mb-2">
                                Pay Now with Razorpay
                            </button>
                            <small class="text-muted">Secure payment gateway</small>
                        </div>

                        <!-- UPI Section -->
                        <div id="upiSection" style="display:none;">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="upiId" placeholder="pay to college@upi">
                            </div>
                            <button onclick="confirmManualPayment('upi')" class="btn btn-success w-100">
                                Confirm UPI Payment
                            </button>
                            <small class="text-muted mt-2">Pay via UPI app & confirm</small>
                        </div>

                        <!-- QR Section -->
                        <div id="qrSection" style="display:none;">
                            <?php if ($event['payment_qr']): ?>
                                <img src="../assets/images/payment_qrs/<?php echo htmlspecialchars($event['payment_qr']); ?>" class="img-fluid mb-3 border rounded" alt="Payment QR">
                            <?php endif; ?>
                            <button onclick="confirmManualPayment('qr')" class="btn btn-success w-100">
                                Confirm QR Payment
                            </button>
                            <small class="text-muted mt-2">Scan QR, pay, confirm</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentEventId = <?php echo $event_id ?? 0; ?>;

        function togglePaymentSection(method) {
            document.getElementById('razorpaySection').style.display = method === 'razorpay' ? 'block' : 'none';
            document.getElementById('upiSection').style.display = method === 'upi' ? 'block' : 'none';
            document.getElementById('qrSection').style.display = method === 'qr' ? 'block' : 'none';
        }

        function payWithRazorpay(eventId) {
            fetch('../api/initiate_payment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({event_id: eventId})
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    const options = {
                        "key": data.key,
                        "amount": data.amount,
                        "currency": "INR",
                        "name": "CampusHive",
                        "description": "Event Registration",
                        "order_id": data.order_id,
                        "handler": function (response){
                            verifyPayment(response);
                        },
                        "theme": {"color": "#3399cc"}
                    };
                    const rzp = new Razorpay(options);
                    rzp.open();
                }
            });
        }

        function verifyPayment(response) {
            fetch('../api/verify_payment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(response)
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    alert('Payment Successful!');
                    window.location.href = 'event_details.php?id=' + currentEventId;
                } else {
                    alert('Payment failed: ' + data.message);
                }
            });
        }

        function confirmManualPayment(method) {
            const upiId = document.getElementById('upiId').value;
            if (method === 'upi' && !upiId) {
                alert('Enter UPI ID');
                return;
            }
            if (confirm('Confirm payment completed via ' + method.toUpperCase() + '?')) {
                fetch('../api/register_event.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        event_id: currentEventId,
                        payment_method: method,
                        payment_status: 'paid'
                    })
                }).then(res => res.json()).then(data => {
                    if (data.success) {
                        alert('Registration Confirmed!');
                        window.location.href = 'event_details.php?id=' + currentEventId;
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>
