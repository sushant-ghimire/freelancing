<?php
// FILE: /wallet.php (COMPLETELY OVERHAULED AND FINAL)

require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle messages from redirects
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'request_submitted')
        $message = "Deposit request submitted successfully! Please wait for admin approval.";
    if ($_GET['status'] === 'bids_purchased')
        $message = "Bids purchased successfully!";
}

// Handle ALL form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_deposit'])) {
        // ... (Deposit request logic remains the same) ...
        $amount = floatval($_POST['amount']);
        if (isset($_FILES['proof_screenshot']) && $_FILES['proof_screenshot']['error'] == 0) {
            $upload_dir = 'uploads/proof/';
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $max_size = 5 * 1024 * 1024;
            if ($amount <= 0) {
                $error = "Please enter a valid amount.";
            } elseif (!in_array($_FILES['proof_screenshot']['type'], $allowed_types)) {
                $error = "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
            } elseif ($_FILES['proof_screenshot']['size'] > $max_size) {
                $error = "File is too large. Maximum size is 5 MB.";
            } else {
                $file_extension = pathinfo($_FILES['proof_screenshot']['name'], PATHINFO_EXTENSION);
                $unique_filename = $user_id . '_' . time() . '.' . $file_extension;
                $destination = $upload_dir . $unique_filename;
                if (move_uploaded_file($_FILES['proof_screenshot']['tmp_name'], $destination)) {
                    $stmt = $conn->prepare("INSERT INTO deposits (user_id, amount, proof_screenshot) VALUES (?, ?, ?)");
                    if ($stmt === false) {
                        $error = "System error: Could not prepare deposit request. Please contact administrator. (Error: " . $conn->error . ")";
                    } else {
                        $stmt->bind_param("ids", $user_id, $amount, $unique_filename);
                        if ($stmt->execute()) {
                            header("Location: wallet.php?status=request_submitted");
                            exit();
                        } else {
                            $error = "Database error: Could not submit request. (Error: " . $stmt->error . ")";
                        }
                        $stmt->close();
                    }
                } else {
                    $error = "Failed to upload screenshot.";
                }
            }
        } else {
            $error = "Please upload a payment proof screenshot.";
        }
    } elseif (isset($_POST['buy_bids'])) {
        // ... (Bid purchase logic remains the same) ...
        $bids_to_buy = intval($_POST['bids']);
        $cost_per_bid = 10;
        $total_cost = $bids_to_buy * $cost_per_bid;
        $stmt_check_bal = $conn->prepare("SELECT balance FROM users WHERE id = ?");
        if ($stmt_check_bal === false) {
            $error = "System error: Could not fetch balance info.";
        } else {
            $stmt_check_bal->bind_param("i", $user_id);
            $stmt_check_bal->execute();
            $bal_result = $stmt_check_bal->get_result();
            $user_balance = $bal_result ? $bal_result->fetch_assoc()['balance'] : 0;
            $stmt_check_bal->close();
        }
        if ($bids_to_buy > 0 && $user_balance >= $total_cost) {
            $conn->begin_transaction();
            try {
                $stmt_bal = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt_bal->bind_param("di", $total_cost, $user_id);
                $stmt_bal->execute();
                $stmt_bids = $conn->prepare("UPDATE users SET bids_available = bids_available + ? WHERE id = ?");
                $stmt_bids->bind_param("ii", $bids_to_buy, $user_id);
                $stmt_bids->execute();
                $desc = "Purchased $bids_to_buy bids";
                $log_stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'bid_purchase', ?)");
                $log_stmt->bind_param("ids", $user_id, $total_cost, $desc);
                $log_stmt->execute();
                $conn->commit();
                header("Location: wallet.php?status=bids_purchased");
                exit();
            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $error = "An error occurred during the purchase.";
            }
        } else {
            $error = "Insufficient balance or invalid bid amount.";
        }
    }
}

// Fetch user data
$stmt_user = $conn->prepare("SELECT balance, bids_available FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();
?>

<div class="container">
    <div class="dashboard-header">
        <h1>My Wallet</h1>
        <p>Manage your funds, request deposits, and purchase bids.</p>
    </div>

    <?php if ($message): ?>
        <p class="success-message"><?php echo $message; ?></p><?php endif; ?>
    <?php if ($error): ?>
        <p class="error-message"><?php echo $error; ?></p><?php endif; ?>

    <div class="wallet-summary">
        <div class="summary-card">
            <h3>Current Balance</h3>
            <p class="balance-amount">NPR <?php echo number_format($user_data['balance'], 2); ?></p>
        </div>
        <div class="summary-card">
            <h3>Available Bids</h3>
            <p class="bids-amount"><?php echo $user_data['bids_available']; ?></p>
        </div>
    </div>

    <!-- NEW TABBED INTERFACE -->
    <div class="wallet-interface">
        <div class="wallet-tabs">
            <button class="tab-btn active" data-tab="deposit">Request Deposit</button>
            <?php if ($_SESSION['role'] === 'freelancer'): ?>
                <button class="tab-btn" data-tab="bids">Buy Bids</button>
            <?php endif; ?>
        </div>
        <div class="wallet-tab-content">
            <!-- Deposit Tab Panel -->
            <div class="tab-content active" id="deposit">
                <h2>Request Wallet Deposit</h2>
                <p class="muted-text">Submit your payment proof for admin approval. Please send payments to eSewa ID:
                    98xxxxxxxx.</p>
                <form action="wallet.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="amount">Amount Deposited (NPR)</label>
                        <input type="number" name="amount" id="amount" min="100" step="100" required>
                    </div>
                    <div class="form-group">
                        <label for="proof_screenshot">Payment Proof Screenshot</label>
                        <input type="file" name="proof_screenshot" id="proof_screenshot"
                            accept="image/png, image/jpeg, image/jpg" required>
                    </div>
                    <button type="submit" name="request_deposit" class="btn">Submit Request</button>
                </form>
            </div>
            <!-- Buy Bids Tab Panel -->
            <?php if ($_SESSION['role'] === 'freelancer'): ?>
                <div class="tab-content" id="bids">
                    <h2>Buy Bids</h2>
                    <p class="muted-text">Cost: NPR 10 per bid. Funds will be deducted from your wallet balance.</p>
                    <form action="wallet.php" method="POST">
                        <div class="form-group">
                            <label for="bids">Number of Bids</label>
                            <select name="bids" id="bids" required>
                                <option value="10">10 Bids (NPR 100)</option>
                                <option value="25">25 Bids (NPR 250)</option>
                                <option value="50">50 Bids (NPR 500)</option>
                            </select>
                        </div>
                        <button type="submit" name="buy_bids" class="btn">Buy Bids</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- HISTORY TABLES WRAPPER -->
    <div class="wallet-history-grid">
        <!-- Deposit History -->
        <div class="transaction-history">
            <h2>Deposit Request History</h2>
            <table class="wallet-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Proof</th>
                    </tr>
                </thead>
                <tbody>
                    <?php /* Deposit history query remains the same */
                    $dep_stmt = $conn->prepare("SELECT amount, status, proof_screenshot, created_at FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
                    $dep_stmt->bind_param("i", $user_id);
                    $dep_stmt->execute();
                    $deposits = $dep_stmt->get_result();
                    if ($deposits->num_rows > 0) {
                        while ($d = $deposits->fetch_assoc()) {
                            echo "<tr><td>" . date('M j, Y H:i', strtotime($d['created_at'])) . "</td><td>NPR " . number_format($d['amount'], 2) . "</td><td><span class='status status-" . $d['status'] . "'>" . ucfirst($d['status']) . "</span></td><td><a href='uploads/proof/" . htmlspecialchars($d['proof_screenshot']) . "' target='_blank'>View</a></td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No deposit requests found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- NEW: Bid Purchase History -->
        <?php if ($_SESSION['role'] === 'freelancer'): ?>
            <div class="transaction-history">
                <h2>Bid Purchase History</h2>
                <table class="wallet-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $bid_stmt = $conn->prepare("SELECT amount, description, created_at FROM transactions WHERE user_id = ? AND type = 'bid_purchase' ORDER BY created_at DESC LIMIT 10");
                        $bid_stmt->bind_param("i", $user_id);
                        $bid_stmt->execute();
                        $bid_history = $bid_stmt->get_result();
                        if ($bid_history->num_rows > 0) {
                            while ($b = $bid_history->fetch_assoc()) {
                                echo "<tr><td>" . date('M j, Y H:i', strtotime($b['created_at'])) . "</td><td>" . htmlspecialchars($b['description']) . "</td><td class='transaction-out'>- NPR " . number_format($b['amount'], 2) . "</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>No bid purchases found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JAVASCRIPT FOR TABS -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabsContainer = document.querySelector('.wallet-tabs');
        const tabContents = document.querySelectorAll('.tab-content');
        const tabButtons = document.querySelectorAll('.tab-btn');

        tabsContainer.addEventListener('click', function (e) {
            if (e.target.tagName === 'BUTTON') {
                const tabId = e.target.getAttribute('data-tab');

                // Update button styles
                tabButtons.forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');

                // Update content visibility
                tabContents.forEach(content => {
                    if (content.id === tabId) {
                        content.classList.add('active');
                    } else {
                        content.classList.remove('active');
                    }
                });
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>