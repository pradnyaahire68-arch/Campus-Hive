<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';

$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$query = "
    SELECT e.*, c.name as college_name,
           (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id) as registration_count
    FROM events e
    JOIN colleges c ON e.college_id = c.college_id
";

$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "e.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(e.title LIKE ? OR c.name LIKE ? OR e.category LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY e.event_date DESC, e.event_time DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - CampusHive</title>
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
                        <a class="nav-link active" href="manage_events.php">Manage Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Manage Events</h2>

        <!-- Filters and Search -->
        <div class="card mt-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Events</option>
                            <option value="upcoming" <?php echo $status_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="ongoing" <?php echo $status_filter == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="expired" <?php echo $status_filter == 'expired' ? 'selected' : ''; ?>>Expired</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title, college, or category">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Events Table -->
        <div class="card mt-4">
            <div class="card-body">
                <?php if (empty($events)): ?>
                    <div class="alert alert-info">
                        <h5>No Events Found</h5>
                        <p>No events match your current filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>College</th>
                                    <th>Category</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Seats</th>
                                    <th>Registrations</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                                        <td><?php echo htmlspecialchars($event['college_name']); ?></td>
                                        <td><?php echo htmlspecialchars($event['category']); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($event['event_date'] . ' ' . $event['event_time'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $event['status'] == 'upcoming' ? 'success' : ($event['status'] == 'ongoing' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($event['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $event['available_seats']; ?>/<?php echo $event['total_seats']; ?></td>
                                        <td><?php echo $event['registration_count']; ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="viewEventDetails(<?php echo $event['event_id']; ?>)">View</button>
                                                <?php if ($event['registration_count'] == 0): ?>
                                                    <button class="btn btn-outline-danger" onclick="deleteEvent(<?php echo $event['event_id']; ?>)">Delete</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Event details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewEventDetails(eventId) {
            fetch(`../api/get_event_details.php?id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const event = data.event;
                        const modalBody = document.getElementById('eventModalBody');
                        modalBody.innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Event Information</h6>
                                    <p><strong>Title:</strong> ${event.title}</p>
                                    <p><strong>College:</strong> ${event.college_name}</p>
                                    <p><strong>Category:</strong> ${event.category}</p>
                                    <p><strong>Date:</strong> ${new Date(event.event_date).toLocaleDateString()}</p>
                                    <p><strong>Time:</strong> ${event.event_time}</p>
                                    <p><strong>Location:</strong> ${event.location}</p>
                                    <p><strong>Seats:</strong> ${event.available_seats}/${event.total_seats}</p>
                                    <p><strong>Status:</strong> <span class="badge bg-${event.status == 'upcoming' ? 'success' : (event.status == 'ongoing' ? 'warning' : 'danger')}">${event.status}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Description</h6>
                                    <p>${event.description}</p>
                                    ${event.image ? `<img src="../assets/images/${event.image}" class="img-fluid mt-3" alt="Event Image">` : ''}
                                </div>
                            </div>
                        `;
                        new bootstrap.Modal(document.getElementById('eventModal')).show();
                    } else {
                        alert('Error loading event details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading event details.');
                });
        }

        function deleteEvent(eventId) {
            if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
                fetch('../api/delete_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ event_id: eventId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Event deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting event.');
                });
            }
        }
    </script>
</body>
</html>
