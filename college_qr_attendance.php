<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['qr_data']) || !isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Invalid request or not logged in as student']);
    exit();
}

$qr_data = $input['qr_data'];
$db = new Database();
$conn = $db->getConnection();

// Parse QR data: college_id-event_id-timestamp
$parts = explode('-', $qr_data);
if (count($parts) !== 3) {
    echo json_encode(['success' => false, 'message' => 'Invalid QR code format']);
    exit();
}

$college_id = (int)$parts[0];
$event_id = (int)$parts[1];
$timestamp = (int)$parts[2];

// Verify the QR code is not too old (valid for 24 hours)
$current_time = time();
if (($current_time - $timestamp) > 86400) { // 24 hours in seconds
    echo json_encode(['success' => false, 'message' => 'QR code has expired. Please get a new one from the college.']);
    exit();
}

// Verify the event exists and belongs to the college
$stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ? AND college_id = ?");
$stmt->execute([$event_id, $college_id]);
$event = $stmt->fetch();

if (!$event) {
    echo json_encode(['success' => false, 'message' => 'Invalid event or college information']);
    exit();
}

// Check if the event is currently active (upcoming or ongoing)
$currentDateTime = new DateTime();
$eventDateTime = new DateTime($event['event_date'] . ' ' . $event['event_time']);

if ($eventDateTime > $currentDateTime) {
    echo json_encode(['success' => false, 'message' => 'This event has not started yet']);
    exit();
}

// Check if student is registered for this event
$stmt = $conn->prepare("SELECT * FROM registrations WHERE event_id = ? AND student_id = ?");
$stmt->execute([$event_id, $_SESSION['user_id']]);
$registration = $stmt->fetch();

if (!$registration) {
    echo json_encode(['success' => false, 'message' => 'You are not registered for this event']);
    exit();
}

// Check if attendance is already marked
if ($registration['attendance_marked']) {
    echo json_encode(['success' => false, 'message' => 'Your attendance has already been marked for this event']);
    exit();
}

// Mark attendance
$stmt = $conn->prepare("UPDATE registrations SET attendance_marked = 1, attendance_time = NOW() WHERE registration_id = ?");
if ($stmt->execute([$registration['registration_id']])) {
    // Get student name for confirmation
    $stmt = $conn->prepare("SELECT name FROM students WHERE student_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Attendance marked successfully!',
        'student_name' => $student['name'],
        'event_title' => $event['title']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark attendance. Please try again.']);
}
?>
