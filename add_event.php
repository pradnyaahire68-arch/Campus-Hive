<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'college') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location = trim($_POST['location']);

    $total_seats = (int)$_POST['total_seats'];
    $registration_fee = (float)($_POST['registration_fee'] ?? 0.00);

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            $upload_dir = '../assets/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = $file_name;
            }
        }
    }

    // Handle payment QR upload (for UPI)
    $payment_qr = null;
    if (isset($_FILES['payment_qr']) && $_FILES['payment_qr']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['payment_qr']['type'], $allowed_types)) {
            $upload_dir = '../assets/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_extension = pathinfo($_FILES['payment_qr']['name'], PATHINFO_EXTENSION);
            $file_name = 'qr_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['payment_qr']['tmp_name'], $target_path)) {
                $payment_qr = $file_name;
            }
        }
    }

    $db = new Database();
    $conn = $db->getConnection();

    $is_draft = isset($_POST['is_draft']) ? 1 : 0;
    $status = $is_draft ? 'draft' : 'upcoming';
    
    $stmt = $conn->prepare("INSERT INTO events (college_id, title, description, category, event_date, event_time, location, total_seats, available_seats, image, registration_fee, payment_qr, is_draft, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $title, $description, $category, $event_date, $event_time, $location, $total_seats, $total_seats, $image_path, $registration_fee, $payment_qr, $is_draft, $status])) {
        $action = $is_draft ? 'saved as draft' : 'created and published';
        $message = "Event {$action} successfully!";
    } else {
        $message = 'Failed to create event. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - CampusConnect+</title>
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
                        <a class="nav-link active" href="add_event.php">Add Event</a>
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
                        <h3>Add New Event</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Event Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Workshop">Workshop</option>
                                        <option value="Seminar">Seminar</option>
                                        <option value="Competition">Competition</option>
                                        <option value="Conference">Conference</option>
                                        <option value="Webinar">Webinar</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="total_seats" class="form-label">Total Seats *</label>
                                    <input type="number" class="form-control" id="total_seats" name="total_seats" min="1" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="registration_fee" class="form-label">Registration Fee (₹)</label>
                                    <input type="number" step="0.01" class="form-control" id="registration_fee" name="registration_fee" min="0" value="0.00" placeholder="0.00 for free">
                                    <small class="form-text text-muted">Set 0.00 for free events</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="event_date" class="form-label">Event Date *</label>
                                    <input type="date" class="form-control" id="event_date" name="event_date" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="event_time" class="form-label">Event Time *</label>
                                    <input type="time" class="form-control" id="event_time" name="event_time" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Location *</label>
                                <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Auditorium A, Main Building" required>
                            </div>



                            <div class="mb-3">
                                <label for="image" class="form-label">Event Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="form-text text-muted">Optional: JPG, PNG, or GIF (Max 5MB)</small>
                            </div>

                            <!-- NEW: UPI QR Payment Image Upload -->
                            <div class="mb-3">
                                <label for="payment_qr" class="form-label">UPI Payment QR Code (Optional)</label>
                                <input type="file" class="form-control" id="payment_qr" name="payment_qr" accept="image/*">
                                <small class="form-text text-muted">Upload UPI QR code for offline payments (PhonePe/GPay). Students will see 'Show Payment QR Code' button.</small>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" name="is_draft" value="1" class="btn btn-outline-secondary flex-fill" title="Save and edit later">
                                    <i class="fas fa-save me-1"></i>Save Draft
                                </button>
                                <button type="submit" name="is_draft" value="0" class="btn btn-primary flex-fill">
                                    <i class="fas fa-paper-plane me-1"></i>Publish Event
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today
        document.getElementById('event_date').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>
