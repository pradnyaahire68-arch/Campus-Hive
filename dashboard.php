<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';

$db = new Database();
$conn = $db->getConnection();

// Get student's registered events
$stmt = $conn->prepare("
    SELECT e.*, c.name as college_name, r.registration_date, r.attendance_marked
    FROM registrations r
    JOIN events e ON r.event_id = e.event_id
    JOIN colleges c ON e.college_id = c.college_id
    WHERE r.student_id = ?
    ORDER BY e.event_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$registered_events = $stmt->fetchAll();

// Get upcoming events count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM events e
    JOIN colleges c ON e.college_id = c.college_id
    WHERE c.status = 'approved' AND e.status = 'upcoming'
    AND e.event_id NOT IN (
        SELECT event_id FROM registrations WHERE student_id = ?
    )
");
$stmt->execute([$_SESSION['user_id']]);
$upcoming_count = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CampusHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h5 class="mb-0">
                <i class="fas fa-user-graduate me-2"></i>Student Panel
            </h5>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="events.php" class="nav-link">
                <i class="fas fa-calendar-alt"></i>
                <span>Browse Events</span>
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-history"></i>
                <span>Event History</span>
            </a>
            <hr class="my-3">
            <a href="../auth/logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary me-3" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="navbar-brand fw-bold text-primary" href="../index.php">
                    <span class="text-primary">Campus</span><span class="text-accent">Hive</span>
                </a>
                <div class="ms-auto d-flex align-items-center">
                    <span class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
                </div>
            </div>
        </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                <p class="text-muted">Manage your event registrations and discover new opportunities.</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text-primary"><?php echo count($registered_events); ?></h3>
                        <p class="card-text">Registered Events</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text-success"><?php echo $upcoming_count; ?></h3>
                        <p class="card-text">Available Events</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text-info">
                            <?php
                            $attended_count = 0;
                            foreach ($registered_events as $event) {
                                if ($event['attendance_marked']) $attended_count++;
                            }
                            echo $attended_count;
                            ?>
                        </h3>
                        <p class="card-text">Events Attended</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registered Events -->
        <div class="row mt-5">
            <div class="col-12">
                <h3>My Registered Events</h3>
                <hr>
            </div>
        </div>

        <?php if (empty($registered_events)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h5>No Registered Events</h5>
                        <p>You haven't registered for any events yet. <a href="events.php">Browse available events</a> to get started!</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($registered_events as $event): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                    <span class="badge bg-<?php echo $event['status'] == 'upcoming' ? 'success' : ($event['status'] == 'ongoing' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($event['status']); ?>
                                    </span>
                                </div>

                                <p class="card-text"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <small class="text-muted">
                                            <strong>College:</strong> <?php echo htmlspecialchars($event['college_name']); ?><br>
                                            <strong>Date:</strong> <?php echo date('M j, Y', strtotime($event['event_date'])); ?><br>
                                            <strong>Time:</strong> <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                        </small>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted">
                                            <strong>Registered:</strong> <?php echo date('M j, Y', strtotime($event['registration_date'])); ?><br>
                                            <strong>Attendance:</strong>
                                            <span class="badge bg-<?php echo $event['attendance_marked'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $event['attendance_marked'] ? 'Marked' : 'Not Marked'; ?>
                                            </span>
                                        </small>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <a href="event_details.php?id=<?php echo $event['event_id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                    <?php if ($event['status'] == 'expired' && !$event['attendance_marked']): ?>
                                        <button class="btn btn-warning btn-sm" onclick="giveFeedback(<?php echo $event['event_id']; ?>)">Give Feedback</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="row mt-5">
            <div class="col-12">
                <h3>Quick Actions</h3>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                        <h5>Browse Events</h5>
                        <p>Discover new events from various colleges</p>
                        <a href="events.php" class="btn btn-primary">Browse Events</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-edit fa-3x text-primary mb-3"></i>
                        <h5>Update Profile</h5>
                        <p>Keep your profile information up to date</p>
                        <button class="btn btn-secondary" disabled>Coming Soon</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Give Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="feedbackForm">
                        <input type="hidden" id="feedbackEventId" name="event_id">
                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating (1-5)</label>
                            <select class="form-select" id="rating" name="rating" required>
                                <option value="">Select Rating</option>
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Very Good</option>
                                <option value="3">3 - Good</option>
                                <option value="2">2 - Fair</option>
                                <option value="1">1 - Poor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="feedback" class="form-label">Feedback</label>
                            <textarea class="form-control" id="feedback" name="feedback" rows="4" placeholder="Share your experience..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Feedback</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script>
        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');

        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('sidebar-open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                    mainContent.classList.remove('sidebar-open');
                }
            }
        });

        function giveFeedback(eventId) {
            document.getElementById('feedbackEventId').value = eventId;
            new bootstrap.Modal(document.getElementById('feedbackModal')).show();
        }

        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('../api/submit_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thank you for your feedback!');
                    bootstrap.Modal.getInstance(document.getElementById('feedbackModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting feedback.');
            });
        });

        // Add fade-in animation to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('fade-in');
                }, index * 100);
            });
        });
    </script>
</body>
</html>
