<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Base query
$query = "
        SELECT e.*, c.name as college_name, e.registration_fee
        FROM events e
        JOIN colleges c ON e.college_id = c.college_id
        WHERE c.status = 'approved'
    ";

    $params = [];

    // Add filter conditions
    if ($filter !== 'all') {
        $query .= " AND e.status = ?";
        $params[] = $filter;
    }

    // Add search conditions
    if (!empty($search)) {
        $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.category LIKE ? OR c.name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Order by date
    $query .= " ORDER BY e.event_date ASC, e.event_time ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update event statuses based on current date/time
    $currentDateTime = new DateTime();
    foreach ($events as &$event) {
        $old_status = $event['status'];
        $eventDateTime = new DateTime($event['event_date'] . ' ' . $event['event_time']);

        if ($eventDateTime > $currentDateTime) {
            $new_status = 'upcoming';
        } elseif ((clone $eventDateTime) <= $currentDateTime && (clone $eventDateTime)->modify('+2 hours') > $currentDateTime) {
            $new_status = 'ongoing';
        } else {
            $new_status = 'expired';
        }

        $event['status'] = $new_status;

        // Update status in database if changed
        if ($old_status !== $new_status) {
            $updateStmt = $conn->prepare("UPDATE events SET status = ? WHERE event_id = ?");
            $updateStmt->execute([$new_status, $event['event_id']]);
        }
    }

    echo json_encode($events);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
