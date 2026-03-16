<?php
require_once '../includes/db.php'; // DB connection

// Admin Authentication Check
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Handle job approval (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);

    $stmt = $conn->prepare("UPDATE jobs SET status = 'open' WHERE id = ? AND status = 'pending_approval'");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the dashboard
header("Location: dashboard.php");
exit();
?>