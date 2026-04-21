<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$event = null;
$is_registered = false;

if ($event_id > 0) {
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

    if ($event) {
        $stmt = $conn->prepare("SELECT registration_id FROM registrations WHERE event_id = ? AND student_id = ?");
        $stmt->execute([$event_id, $_SESSION['user_id']]);
        $is_registered = $stmt->rowCount() > 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - CampusHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">CampusHive</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($event): ?>
            <div class="card shadow-lg">
                <div class="card-header bg-gradient-primary text-white p-4">
                    <h2 class="mb-0"><?php echo htmlspecialchars($event['title']); ?></h2>
                </div>
                <div class="card-body p-5">
                    <?php if ($event['image']): ?>
                        <div class="text-center mb-4">
    <img src="../assets/images/<?php echo htmlspecialchars($event['image']); ?>" class="img-fluid rounded shadow-lg" alt="Event Poster" style="height: 600px; width: 100%; object-fit: contain;">
                        </div>
                    <?php endif; ?>

                    <div class="event-description">
                        <h5><i class="fas fa-align-left text-primary me-2"></i>Description</h5>
                        <p class="lead"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-calendar-alt fa-2x text-warning mb-2"></i>
                                <h6>Date</h6>
                                <p class="fw-bold"><?php echo date('M j, Y', strtotime($event['event_date'])); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-clock fa-2x text-info mb-2"></i>
                                <h6>Time</h6>
                                <p class="fw-bold"><?php echo date('g:i A', strtotime($event['event_time'])); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-university fa-2x text-success mb-2"></i>
                                <h6>College</h6>
                                <p class="fw-bold"><?php echo htmlspecialchars($event['college_name']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <i class="fas fa-map-marker-alt fa-2x text-danger mb-2"></i>
                                <h6>Location</h6>
                                <p><?php echo htmlspecialchars($event['location']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-tag me-2 text-primary"></i>Category</h6>
                                    <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($event['category']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-users me-2 text-success"></i>Availability</h6>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo (100 - (($event['total_seats'] - $event['available_seats']) / $event['total_seats'] * 100)); ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $event['available_seats']; ?> / <?php echo $event['total_seats']; ?> seats</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($is_registered): ?>
                        <div class="alert alert-success border-0 shadow-sm">
                            <i class="fas fa-check-circle fa-2x float-start me-3 text-success"></i>
                            <h5 class="mb-2">Congratulations! 🎉</h5>
                            <p class="mb-0">You are successfully registered for this event.</p>
                        </div>
                    <?php elseif ($event['available_seats'] > 0): ?>
                        <?php if ($event['registration_fee'] > 0): ?>
                            <button class="btn btn-primary btn-lg w-100 mt-4 shadow-lg" onclick="payAndRegister(<?php echo $event['event_id']; ?>, <?php echo $event['registration_fee']; ?>)">
                                <i class="fab fa-google-pay me-2"></i>Pay ₹<?php echo number_format($event['registration_fee'], 0); ?> & Register
                            </button>
                            <?php if ($event['payment_qr']): ?>
                            <button class="btn btn-success btn-lg w-100 mt-3 shadow-lg" onclick="showPaymentQR(<?php echo $event['event_id']; ?>)">
                                <i class="fas fa-qrcode me-2"></i>Show UPI QR Code
                            </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-success btn-lg w-100 mt-4 shadow-lg" onclick="registerForEvent(<?php echo $event['event_id']; ?>)">
                                <i class="fas fa-plus-circle me-2"></i>Register for FREE Event
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning shadow-sm">
                            <i class="fas fa-chair fa-2x float-start me-3 text-warning"></i>
                            <h5 class="mb-2">Fully Booked! 😔</h5>
                            <p class="mb-0">All seats for this event have been taken.</p>
                        </div>
                    <?php endif; ?>

                    <div class="mt-5">
                        <a href="events.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Events
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger shadow-lg">
                <h4 class="alert-heading">Event Not Found</h4>
                <p>The event you're looking for doesn't exist or has been removed.</p>
                <hr>
                <div class="d-flex justify-content-between">
                    <a href="events.php" class="btn btn-primary">← Browse Events</a>
                    <a href="dashboard.php" class="btn btn-outline-primary">Dashboard</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="../assets/js/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        function registerForEvent(eventId) {
            if (confirm('Register for this FREE event?')) {
                fetch('../api/register_event.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({event_id: eventId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Registration successful!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => alert('Network error: ' + error));
            }
        }

        function payAndRegister(eventId, amount) {
            if (!confirm(`Pay Rs ${amount} & Register?`)) return;
            
            fetch('../api/create_razorpay_order.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({event_id: eventId, amount: amount})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    var options = {
                        key: data.key,
                        amount: data.amount,
                        currency: data.currency,
                        name: 'CampusHive Event Registration',
                        description: 'Pay for event registration',
                        order_id: data.order_id,
                        handler: function (response) {
                            fetch('../api/razorpay_success.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_signature: response.razorpay_signature,
                                    event_id: eventId
                                })
                            })
                            .then(r => r.json())
                            .then(result => {
                                if (result.success) {
                                    alert('Payment successful! Registered!');
                                    location.reload();
                                } else {
                                    alert('Registration failed: ' + (result.message || 'Unknown error'));
                                }
                            });
                        },
                        prefill: {
                            name: 'Student',
                            email: '<?php echo $_SESSION["email"] ?? ""; ?>'
                        },
                        theme: {
                            color: '#007bff'
                        }
                    };
                    var rzp = new Razorpay(options);
                    rzp.open();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function showPaymentQR(eventId) {
            fetch('../api/get_event_details.php?id=' + eventId)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.event && data.event.payment_qr && data.event.payment_qr.trim() !== '') {
                    const fee = data.event.registration_fee;
                    const qrUrl = '../assets/images/' + data.event.payment_qr;
                    // Show QR image in modal
                    const modalHTML = `
                        <div class="modal fade" id="qrModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5>UPI Payment QR</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <h6>Registration Fee: ₹${fee.toLocaleString()}</h6>
                                        <img src="${qrUrl}" class="img-fluid border rounded mb-3" style="max-width: 300px; max-height: 300px;" alt="UPI QR Code">
                                        <p class="small text-muted">Scan with PhonePe/GPay/Paytm and pay exact amount</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-success" onclick="confirmQRPayment(${eventId})">Payment Done - Register</button>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    document.body.insertAdjacentHTML('beforeend', modalHTML);
                    const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
                    qrModal.show();
                } else {
                    alert('No UPI QR code available for this event.');
                }
            })
            .catch(err => alert('Error loading QR: ' + err));
        }

        function confirmQRPayment(eventId) {
            $('#qrModal').modal('hide');
            if (confirm('Confirm payment completed?')) {
                const paymentRef = 'UPI-QR-' + Date.now();
                fetch('../api/register_event.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({event_id: eventId, payment_ref: paymentRef})
                })
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        alert('Registered successfully!');
                        location.reload();
                    } else {
                        alert('Registration failed: ' + result.message);
                    }
                })
                .catch(err => alert('Network error'));
            }
        }
    </script>
</body>
</html>
