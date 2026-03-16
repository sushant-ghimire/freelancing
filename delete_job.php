<?php
// FILE: /delete_job.php (NEW FILE - FINAL)

require_once 'includes/auth_check.php';
require_once 'includes/db.php';
// Only clients can perform this action
if ($_SESSION['role'] !== 'client') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job'])) {
    $job_id = intval($_POST['job_id']);
    $client_id = $_SESSION['user_id'];

    if ($job_id > 0) {
        // Prepare the delete statement. Critically, it checks for ownership (client_id)
        // and ensures the job is in a deletable state.
        $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND client_id = ? AND status IN ('pending_approval', 'open')");
        $stmt->bind_param("ii", $job_id, $client_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Success
                header("Location: client_dashboard.php?status=job_deleted");
                exit();
            } else {
                // Job was not deletable (e.g., already in progress) or didn't belong to the user
                header("Location: client_dashboard.php?error=delete_failed");
                exit();
            }
        }
        $stmt->close();
    }
}

// Redirect if accessed improperly
header("Location: client_dashboard.php");
exit();
?>