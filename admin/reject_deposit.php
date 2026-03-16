<?php
// FILE: /admin/reject_deposit.php (NEW FILE - FINAL)

require_once '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit_id'])) {
    $deposit_id = intval($_POST['deposit_id']);

    // Update the status to 'rejected'
    $stmt = $conn->prepare("UPDATE deposits SET status = 'rejected' WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $deposit_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        header("Location: dashboard.php?deposit_status=rejected");
        exit();
    }
}

header("Location: dashboard.php?deposit_status=error");
exit();
?>