<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'college') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';

$message = '';
$selected_event = null;
$qr_code_url = '';

$db = new Database();
$conn = $db->getConnection();

// Get college's events that are ongoing or upcoming
$stmt = $conn->prepare("SELECT * FROM events WHERE college_id = ? AND status IN ('upcoming', 'ongoing') ORDER BY event_date, event_time");
$stmt->execute([$_SESSION['user_id']]);
$events = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['event_id'])) {
    $event_id = (int)$_POST['event_id'];

    // Verify the event belongs to this college
    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ? AND college_id = ?");
    $stmt->execute([$event_id, $_SESSION['user_id']]);
    $selected_event = $stmt->fetch();

    if ($selected_event) {
        // Generate QR code data: college_id-event_id-timestamp
        $qr_data = $_SESSION['user_id'] . '-' . $event_id . '-' . time();

        // For now, use Google Charts API for QR generation (can be replaced with local library later)
        $qr_code_url = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qr_data) . '&choe=UTF-8';

        $message = 'QR Code generated successfully for event: ' . htmlspecialchars($selected_event['title']);
    } else {
        $message = 'Invalid event selected.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate QR Code - CampusHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                        <a class="nav-link" href="add_event.php">Add Event</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="generate_qr.php">Generate QR</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_registrations.php">View Registrations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3>Generate QR Code for Student Attendance</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>How it works:</strong> Generate a QR code for your event that students can scan at the venue to mark their attendance. Only registered students will be able to successfully mark attendance.
                        </div>

                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <?php if (empty($events)): ?>
                            <div class="alert alert-warning">
                                <h5>No Active Events</h5>
                                <p>You don't have any upcoming or ongoing events. Create an event first to generate QR codes.</p>
                                <a href="add_event.php" class="btn btn-primary">Create Event</a>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="event_id" class="form-label">Select Event *</label>
                                    <select class="form-select" id="event_id" name="event_id" required>
                                        <option value="">Choose an event...</option>
                                        <?php foreach ($events as $event): ?>
                                            <option value="<?php echo $event['event_id']; ?>" <?php echo (isset($selected_event) && $selected_event['event_id'] == $event['event_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($event['title']); ?> - <?php echo date('M j, Y g:i A', strtotime($event['event_date'] . ' ' . $event['event_time'])); ?> (<?php echo ucfirst($event['status']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Generate QR Code</button>
                                </div>
                            </form>

                            <?php if ($qr_code_url && $selected_event): ?>
                                <hr>
                                <div class="text-center">
                                    <h5>QR Code for: <?php echo htmlspecialchars($selected_event['title']); ?></h5>
                                    <div class="mb-3">
                                        <img src="<?php echo $qr_code_url; ?>" alt="Event QR Code" class="img-fluid border">
                                    </div>
                                    <p class="text-muted">Students can scan this QR code at the event venue to mark their attendance.</p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <button onclick="printQR()" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-print me-2"></i>Print QR Code
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <button onclick="downloadQR()" class="btn btn-outline-success w-100">
                                                <i class="fas fa-download me-2"></i>Download QR Code
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printQR() {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Event QR Code - <?php echo htmlspecialchars($selected_event['title'] ?? ''); ?></title>
                    <style>
                        body { text-align: center; font-family: Arial, sans-serif; margin: 20px; }
                        img { max-width: 100%; height: auto; }
                        .event-info { margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <h2>Event Attendance QR Code</h2>
                    <div class="event-info">
                        <h3><?php echo htmlspecialchars($selected_event['title'] ?? ''); ?></h3>
                        <p>Date: <?php echo isset($selected_event) ? date('F j, Y g:i A', strtotime($selected_event['event_date'] . ' ' . $selected_event['event_time'])) : ''; ?></p>
                        <p>Location: <?php echo htmlspecialchars($selected_event['location'] ?? ''); ?></p>
                    </div>
                    <img src="<?php echo $qr_code_url; ?>" alt="Event QR Code">
                    <p>Scan this QR code at the event venue to mark your attendance</p>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }


            link.href = '<?php echo $qr_code_url; ?>';
            link.download = 'event-qr-<?php echo $selected_event['event_id'] ?? 'code'; ?>.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
