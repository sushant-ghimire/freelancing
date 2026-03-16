<?php
// FILE: /admin/approve_deposit.php (NEW FILE - FINAL)

require_once '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit_id'])) {
    $deposit_id = intval($_POST['deposit_id']);

    // First, get the deposit details to prevent double-approvals
    $get_stmt = $conn->prepare("SELECT user_id, amount, status FROM deposits WHERE id = ?");
    $get_stmt->bind_param("i", $deposit_id);
    $get_stmt->execute();
    $deposit = $get_stmt->get_result()->fetch_assoc();

    if ($deposit && $deposit['status'] === 'pending') {
        $user_id = $deposit['user_id'];
        $amount = $deposit['amount'];

        // Use a transaction to ensure both actions complete successfully
        $conn->begin_transaction();
        try {
            // 1. Add the balance to the user's account
            $user_stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $user_stmt->bind_param("di", $amount, $user_id);
            $user_stmt->execute();

            // 2. Update the deposit request status to 'approved'
            $deposit_stmt = $conn->prepare("UPDATE deposits SET status = 'approved' WHERE id = ?");
            $deposit_stmt->bind_param("i", $deposit_id);
            $deposit_stmt->execute();

            $conn->commit();
            header("Location: dashboard.php?deposit_status=approved");
            exit();
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
        }
    }
}

header("Location: dashboard.php?deposit_status=error");
exit();
?>