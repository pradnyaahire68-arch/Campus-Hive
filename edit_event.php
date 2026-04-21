<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'college') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';

$message = '';
$event = null;

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Fetch event details and verify ownership
$stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ? AND college_id = ?");
$stmt->execute([$event_id, $_SESSION['user_id']]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location = trim($_POST['location']);
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
$total_seats = (int)$_POST['total_seats'];
    $registration_fee = (float)($_POST['registration_fee'] ?? $event['registration_fee']); 

    // Handle image upload
    $image_path = $event['image']; // Keep existing image by default
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
                // Delete old image if it exists
                if ($event['image'] && file_exists($upload_dir . $event['image'])) {
                    unlink($upload_dir . $event['image']);
                }
                $image_path = $file_name;
            }
        }
    }

    // Calculate available seats (can't be less than current registrations)
    $stmt = $conn->prepare("SELECT COUNT(*) as registered FROM registrations WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $registered_count = $stmt->fetch()['registered'];
    $available_seats = max($total_seats - $registered_count, 0);

$stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, category = ?, event_date = ?, event_time = ?, location = ?, latitude = ?, longitude = ?, total_seats = ?, available_seats = ?, image = ?, registration_fee = ? WHERE event_id = ? AND college_id = ?");
    if ($stmt->execute([$title, $description, $category, $event_date, $event_time, $location, $latitude, $longitude, $total_seats, $available_seats, $image_path, $registration_fee, $event_id, $_SESSION['user_id']])) {
        $message = 'Event updated successfully!';
        // Refresh event data
        $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
    } else {
        $message = 'Failed to update event. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - CampusHive</title>
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
                        <a class="nav-link active" href="edit_event.php?id=<?php echo $event_id; ?>">Edit Event</a>
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
                        <h3>Edit Event</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Event Title *</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Workshop" <?php echo $event['category'] == 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                                        <option value="Seminar" <?php echo $event['category'] == 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                                        <option value="Competition" <?php echo $event['category'] == 'Competition' ? 'selected' : ''; ?>>Competition</option>
                                        <option value="Conference" <?php echo $event['category'] == 'Conference' ? 'selected' : ''; ?>>Conference</option>
                                        <option value="Webinar" <?php echo $event['category'] == 'Webinar' ? 'selected' : ''; ?>>Webinar</option>
                                        <option value="Other" <?php echo $event['category'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="total_seats" class="form-label">Total Seats *</label>
                                    <input type="number" class="form-control" id="total_seats" name="total_seats" min="1" value="<?php echo $event['total_seats']; ?>" required>
                                    <small class="form-text text-muted">Current registrations: <?php
                                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE event_id = ?");
                                        $stmt->execute([$event_id]);
                                        echo $stmt->fetch()['count'];
                                    ?></small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="registration_fee" class="form-label">Registration Fee (₹)</label>
                                    <input type="number" step="0.01" class="form-control" id="registration_fee" name="registration_fee" min="0" value="<?php echo $event['registration_fee']; ?>" placeholder="0.00">
                                    <small class="form-text text-muted">0.00 for free</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Available Seats</label>
                                    <input type="number" class="form-control" value="<?php echo $event['available_seats']; ?>" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="event_date" class="form-label">Event Date *</label>
                                    <input type="date" class="form-control" id="event_date" name="event_date" value="<?php echo $event['event_date']; ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="event_time" class="form-label">Event Time *</label>
                                    <input type="time" class="form-control" id="event_time" name="event_time" value="<?php echo $event['event_time']; ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Location *</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" placeholder="e.g., Auditorium A, Main Building" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo htmlspecialchars($event['latitude'] ?? ''); ?>" placeholder="e.g., 40.7128">
                                    <small class="form-text text-muted">Optional: For map navigation</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo htmlspecialchars($event['longitude'] ?? ''); ?>" placeholder="e.g., -74.0060">
                                    <small class="form-text text-muted">Optional: For map navigation</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Event Image</label>
                                <?php if ($event['image']): ?>
                                    <div class="mb-2">
                                        <img src="../assets/images/<?php echo htmlspecialchars($event['image']); ?>" class="img-thumbnail" style="max-width: 200px;" alt="Current Image">
                                        <p class="text-muted small mt-1">Current image. Upload a new one to replace it.</p>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="form-text text-muted">Optional: JPG, PNG, or GIF (Max 5MB). Leave empty to keep current image.</small>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Event</button>
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
