<?php
require_once '../includes/db.php'; // DB connection

// Admin Authentication Check
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Handle settings update (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commission_rate'])) {
    $new_rate = floatval($_POST['commission_rate']);

    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_name = 'commission_rate'");
    $stmt->bind_param("s", $new_rate);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the dashboard
header("Location: dashboard.php");
exit();
?>