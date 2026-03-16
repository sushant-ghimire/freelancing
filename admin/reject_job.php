<?php
// FILE: /admin/reject_job.php (CORRECTED AND FINAL)

// BUG FIX: The path must go up one directory to find the 'includes' folder.
require_once '../includes/db.php';

// Admin Authentication Check (Uses the correct admin session variable)
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Handle job rejection (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);

    // Prepare a statement to delete the job, ensuring it's still pending approval
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND status = 'pending_approval'");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the dashboard
header("Location: dashboard.php?status=job_rejected");
exit();