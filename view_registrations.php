<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'college') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = new Database();
$conn = $db->getConnection();

if ($event_id > 0) {
    // Single event registrations
    $stmt = $conn->prepare("SELECT e.*, c.name as college_name FROM events e JOIN colleges c ON e.college_id = c.college_id WHERE e.event_id = ? AND e.college_id = ?");
    $stmt->execute([$event_id, $_SESSION['user_id']]);
    $event = $stmt->fetch();
    
    if (!$event) {
        header('Location: dashboard.php');
        exit();
    }
    
    $stmt = $conn->prepare("
        SELECT r.registration_id, r.student_id, r.registration_date, r.payment_id,
               COALESCE(p.status, CASE WHEN e.registration_fee = 0 THEN 'not_required' ELSE 'pending' END) as payment_status,
               s.name as student_name, s.email, s.college, e.title as event_title
        FROM registrations r 
        JOIN students s ON r.student_id = s.student_id 
        JOIN events e ON r.event_id = e.event_id
        LEFT JOIN payments p ON r.payment_id = p.payment_id
        WHERE r.event_id = ? AND e.college_id = ?
        ORDER BY r.registration_date DESC
    ");
    $stmt->execute([$event_id, $_SESSION['user_id']]);
    $registrations = $stmt->fetchAll();
} else {
    // All events registrations
    $stmt = $conn->prepare("
        SELECT r.registration_id, r.student_id, r.registration_date, r.payment_id,
               COALESCE(p.status, CASE WHEN e.registration_fee = 0 THEN 'not_required' ELSE 'pending' END) as payment_status,
               s.name as student_name, s.email, s.college, e.title as event_title
        FROM registrations r 
        JOIN students s ON r.student_id = s.student_id 
        JOIN events e ON r.event_id = e.event_id
        LEFT JOIN payments p ON r.payment_id = p.payment_id
        WHERE e.college_id = ? 
        ORDER BY r.registration_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $registrations = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrations - CampusHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">CampusHive</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="#">Registrations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../auth/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?php echo $event_id > 0 ? 'Event Registrations: ' . htmlspecialchars($event['title']) : 'All Registrations'; ?></h2>
            <a href="dashboard.php" class="btn btn-secondary">← Dashboard</a>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (empty($registrations)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No registrations yet</h4>
                        <p class="text-muted">Students will appear here once they register for your events.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Student</th>
                                    <th>College</th>
                                    <th>Email</th>
                                    <th>Event</th>
                                    <th>Payment</th>
                                    <th>Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $reg): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($reg['student_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($reg['college'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['event_title']); ?></td>
                                        <td>
                                            <?php 
                                            $status = $reg['payment_status'] ?? 'pending';
                                            $badgeClass = match($status) {
                                                'succeeded', 'paid' => 'success',
                                                'pending' => 'warning',
                                                'not_required' => 'info',
                                                'failed' => 'danger',
                                                default => 'secondary'
                                            };
                                            $statusText = match($status) {
                                                'succeeded', 'paid' => 'Paid',
                                                'pending' => 'Pending',
                                                'not_required' => 'Free',
                                                'failed' => 'Failed',
                                                default => ucfirst($status)
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($reg['registration_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

