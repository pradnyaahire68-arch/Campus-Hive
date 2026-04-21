<?php
require_once '../config/db.php';

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS is_draft TINYINT(1) DEFAULT 0");
    $conn->exec("ALTER TABLE events MODIFY COLUMN status ENUM('upcoming', 'ongoing', 'expired', 'draft') DEFAULT 'upcoming'");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_events_draft ON events(is_draft)");
    
    echo "✅ is_draft column added successfully!<br>";
    $result = $conn->query("SHOW COLUMNS FROM events LIKE 'is_draft'");
    if ($result->rowCount() > 0) {
        echo "Column confirmed: " . print_r($result->fetch(), true);
    } else {
        echo "Column not found - check manually.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

