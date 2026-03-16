<?php
// FILE: /admin/withdraw.php (NEW FILE - FINAL)

require_once 'header.php'; // Use the new admin header

$message = '';
$error = '';

// Handle the withdrawal form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_funds'])) {
    $amount = floatval($_POST['amount']);
    $reason = trim($_POST['reason']);

    if ($amount > 0 && !empty($reason)) {
        // Get current platform balance to ensure funds are sufficient
        $balance_res = $conn->query("SELECT setting_value FROM settings WHERE setting_name = 'total_commission_earned'");
        $current_balance = $balance_res->fetch_assoc()['setting_value'];

        if ($amount <= $current_balance) {
            // Use a transaction to ensure data integrity
            $conn->begin_transaction();
            try {
                // 1. Deduct amount from the settings wallet
                $stmt_deduct = $conn->prepare("UPDATE settings SET setting_value = setting_value - ? WHERE setting_name = 'total_commission_earned'");
                $stmt_deduct->bind_param("d", $amount);
                $stmt_deduct->execute();

                // 2. Log this action in the new platform_transactions table
                $stmt_log = $conn->prepare("INSERT INTO platform_transactions (amount, type, description) VALUES (?, 'withdrawal', ?)");
                $stmt_log->bind_param("ds", $amount, $reason);
                $stmt_log->execute();

                $conn->commit();
                header("Location: dashboard.php?withdraw=success");
                exit();
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $error = "Transaction failed. Please try again.";
            }
        } else {
            $error = "Withdrawal amount cannot be greater than the available balance.";
        }
    } else {
        $error = "Please provide a valid amount and a reason for the withdrawal.";
    }
}
?>

<div class="container admin-dashboard">
    <div class="dashboard-header">
        <h1>Withdraw Funds</h1>
        <p>Deduct funds from the platform's commission wallet.</p>
    </div>

    <?php if ($message): ?><p class="success-message"><?php echo $message; ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>

    <div class="admin-section">
        <h2>Withdrawal Form</h2>
        <form action="withdraw.php" method="POST">
            <div class="form-group">
                <label for="amount">Amount to Withdraw (NPR)</label>
                <input type="number" name="amount" id="amount" step="0.01" min="1" required>
            </div>
            <div class="form-group">
                <label for="reason">Reason / Description</label>
                <input type="text" name="reason" id="reason" placeholder="e.g., Monthly Payout, Business Expenses" required>
            </div>
            <button type="submit" name="withdraw_funds" class="btn">Confirm Withdrawal</button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; // Use the new admin footer ?>