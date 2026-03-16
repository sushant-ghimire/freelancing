<?php
// FILE: /complete_project.php (UPDATED AND FINAL)

require_once 'includes/auth_check.php';
require_once 'includes/db.php';

if ($_SESSION['role'] !== 'client') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_job'])) {
    $job_id = intval($_POST['job_id']);
    $client_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT j.budget, j.approved_freelancer_id, u.balance as client_balance 
                            FROM jobs j
                            JOIN users u ON j.client_id = u.id
                            WHERE j.id = ? AND j.client_id = ? AND j.status = 'in_progress'");
    $stmt->bind_param("ii", $job_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        $budget = $data['budget'];
        $client_balance = $data['client_balance'];
        $freelancer_id = $data['approved_freelancer_id'];

        if ($client_balance >= $budget) {
            $rate_res = $conn->query("SELECT setting_value FROM settings WHERE setting_name = 'commission_rate'");
            $commission_rate = $rate_res->fetch_assoc()['setting_value'];
            $commission_amount = $budget * ($commission_rate / 100);
            $freelancer_payout = $budget - $commission_amount;

            $conn->begin_transaction();
            try {
                // a. Deduct budget from client
                $stmt1 = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt1->bind_param("di", $budget, $client_id);
                $stmt1->execute();

                // b. Add payout to freelancer
                $stmt2 = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt2->bind_param("di", $freelancer_payout, $freelancer_id);
                $stmt2->execute();

                // c. Update job status
                $stmt3 = $conn->prepare("UPDATE jobs SET status = 'completed' WHERE id = ?");
                $stmt3->bind_param("i", $job_id);
                $stmt3->execute();

                // d. Log client's transaction
                $client_desc = "Payment for job #" . $job_id;
                $stmt4 = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'job_payment_debit', ?)");
                $stmt4->bind_param("ids", $client_id, $budget, $client_desc);
                $stmt4->execute();

                // e. Log freelancer's transaction
                $freelancer_desc = "Payout for job #" . $job_id;
                $stmt5 = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'job_payment_credit', ?)");
                $stmt5->bind_param("ids", $freelancer_id, $freelancer_payout, $freelancer_desc);
                $stmt5->execute();

                // f. Add commission to platform wallet
                $stmt6 = $conn->prepare("UPDATE settings SET setting_value = setting_value + ? WHERE setting_name = 'total_commission_earned'");
                $stmt6->bind_param("d", $commission_amount);
                $stmt6->execute();

                // NEW STEP g. Log the commission transaction for the platform's audit trail
                $platform_desc = "Commission earned from Job ID #" . $job_id;
                $stmt7 = $conn->prepare("INSERT INTO platform_transactions (amount, type, description) VALUES (?, 'commission', ?)");
                $stmt7->bind_param("ds", $commission_amount, $platform_desc);
                $stmt7->execute();

                $conn->commit();
                header("Location: client_dashboard.php?payment=success");
                exit();
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                header("Location: client_dashboard.php?payment=error");
                exit();
            }
        } else {
            header("Location: view_proposals.php?job_id=$job_id&error=insufficient_funds");
            exit();
        }
    }
}

header("Location: client_dashboard.php");
exit();
?>