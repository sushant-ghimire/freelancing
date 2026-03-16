<?php
// FILE: /admin/header.php (FINAL VERSION)

require_once '../includes/db.php'; // Go up one level to include DB connection

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch the platform's total earned commission (wallet balance)
$wallet_res = $conn->query("SELECT setting_value FROM settings WHERE setting_name = 'total_commission_earned'");
$platform_balance = $wallet_res->fetch_assoc()['setting_value'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Local Freelancing Platform</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header>
    <div class="container admin-header">
        <a href="dashboard.php" class="logo">Local Freelancing Platform Admin</a>
        <nav class="admin-nav">
            <div class="new-div">
                Wallet:
                <strong  style="color: var(--success-color); margin-right: 1.5rem;">NPR <?php echo number_format($platform_balance, 2); ?></strong>
            
            <a href="dashboard.php">Dashboard</a>
            <a href="withdraw.php">Withdraw Funds</a>
            <a href="../logout.php" class="btn btn-logout">Logout</a>
            </div>
        </nav>
    </div>
</header>
<main>