<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';

$db = new Database();
$conn = $db->getConnection();

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "
    SELECT * FROM colleges c
";
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(c.name LIKE ? OR c.email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$colleges = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Colleges - CampusHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">CampusHive</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_colleges.php">Colleges</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_events.php">Events</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../auth/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Manage Colleges</h2>
        <p>View all colleges: Approved | Pending | Blocked</p>

        <!-- Filters -->
        <div class="card mt-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Status</label>
                        <select class="form-select" name="status">
                            <option value="all" <?php echo $status_filter=='all'?'selected':'';?>>All Colleges</option>
                            <option value="pending" <?php echo $status_filter=='pending'?'selected':'';?>>Pending</option>
                            <option value="approved" <?php echo $status_filter=='approved'?'selected':'';?>>Approved</option>
                            <option value="blocked" <?php echo $status_filter=='blocked'?'selected':'';?>>Blocked</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="College name or email">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Colleges Table -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between">
                <h5>All Colleges (<?php echo count($colleges); ?>)</h5>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm">← Back to Dashboard</a>
            </div>
            <div class="card-body">
                <?php if (empty($colleges)): ?>
                    <div class="alert alert-info text-center">
                        <h5>No Colleges Found</h5>
                        <p>No colleges match your filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($colleges as $college): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($college['name']); ?></td>
                                        <td><?php echo htmlspecialchars($college['email']); ?></td>
                                        <td><?php echo htmlspecialchars($college['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $college['status']=='approved' ? 'success' : 
                                                    ($college['status']=='pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($college['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($college['created_at'])); ?></td>
                                        <td>
                                            <?php if ($college['status'] == 'pending'): ?>
                                                <button class="btn btn-success btn-sm" onclick="approveCollege(<?php echo $college['college_id']; ?>)">
                                                    <i class="fas fa-check me-1"></i>Approve
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="rejectCollege(<?php echo $college['college_id']; ?>)">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                            <?php elseif ($college['status'] == 'approved'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php elseif ($college['status'] == 'blocked'): ?>
                                                <button class="btn btn-warning btn-sm" onclick="unblockCollege(<?php echo $college['college_id']; ?>)">
                                                    Unblock
                                                </button>
                                            <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveCollege(id) {
            if (confirm('Approve this college?')) {
                fetch('../api/approve_college.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({college_id: id, action: 'approve'})
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function rejectCollege(id) {
            if (confirm('Reject this college?')) {
                fetch('../api/approve_college.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({college_id: id, action: 'reject'})
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function unblockCollege(id) {
            if (confirm('Unblock this college?')) {
                fetch('../api/approve_college.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({college_id: id, action: 'approve'})
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>
