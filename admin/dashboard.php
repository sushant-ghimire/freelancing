<?php
// FILE: /admin/dashboard.php (CORRECTED AND FINAL - ALL SECTIONS INCLUDED)

require_once 'header.php'; // Use the shared admin header

$message = '';

// Handle all possible messages from redirects
if(isset($_GET['status']) && $_GET['status'] === 'job_rejected') {
    $message = "Job has been successfully rejected and deleted.";
}
if(isset($_GET['withdraw']) && $_GET['withdraw'] === 'success') {
    $message = "Funds have been successfully withdrawn.";
}
if(isset($_GET['deposit_status'])) {
    if ($_GET['deposit_status'] === 'approved') $message = "Deposit approved and balance added to user.";
    if ($_GET['deposit_status'] === 'rejected') $message = "Deposit request has been rejected.";
    if ($_GET['deposit_status'] === 'error') $message = "An error occurred with the deposit request.";
}

// Fetch all necessary data for the dashboard
// 1. Pending Jobs
$pending_jobs = $conn->query("SELECT j.id, j.title, u.full_name, j.description FROM jobs j JOIN users u ON j.client_id = u.id WHERE status = 'pending_approval'");

// 2. Pending Deposits
$pending_deposits = $conn->query("SELECT d.id, d.amount, d.proof_screenshot, u.full_name 
                                  FROM deposits d 
                                  JOIN users u ON d.user_id = u.id 
                                  WHERE d.status = 'pending'");

// 3. Platform Transactions
$transactions = $conn->query("SELECT * FROM platform_transactions ORDER BY created_at DESC LIMIT 20");

// 4. Commission Rate Setting
$rate_res = $conn->query("SELECT setting_value FROM settings WHERE setting_name = 'commission_rate'");
$commission_rate = $rate_res->fetch_assoc()['setting_value'] ?? 10;
?>

<div class="container admin-dashboard">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
    </div>

    <?php if ($message): ?>
        <p class="success-message"><?php echo $message; ?></p>
    <?php endif; ?>

    <!-- PENDING DEPOSIT REQUESTS SECTION (This was missing) -->
    <div class="admin-section">
        <h2>Pending Deposit Requests</h2>
        <?php if ($pending_deposits && $pending_deposits->num_rows > 0): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Proof</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($deposit = $pending_deposits->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($deposit['full_name']); ?></td>
                    <td>NPR <?php echo number_format($deposit['amount'], 2); ?></td>
                    <td><a href="../uploads/proof/<?php echo htmlspecialchars($deposit['proof_screenshot']); ?>" target="_blank">View Proof</a></td>
                    <td class="admin-actions">
                        <form action="approve_deposit.php" method="POST" style="display:inline;">
                            <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                            <button type="submit" class="btn btn-approve">Approve</button>
                        </form>
                        <form action="reject_deposit.php" method="POST" style="display:inline;">
                            <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                    <button type="submit" class="btn btn-delete" style="background-color: var(--primary-color); color: #ffffff;">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No deposit requests are currently pending approval.</p>
        <?php endif; ?>
    </div>

    <!-- PENDING JOBS SECTION -->
    <div class="admin-section">
        <h2>Jobs Pending Approval</h2>
        <?php if ($pending_jobs && $pending_jobs->num_rows > 0): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Posted By</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($job = $pending_jobs->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($job['title']); ?></td>
                    <td><?php echo htmlspecialchars($job['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($job['description']); ?></td>
                    <td class="admin-actions">
                        <form action="approve_job.php" method="POST" style="display:inline;">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <button type="submit" class="btn btn-approve">Approve</button>
                        </form>
                        <form action="reject_job.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to reject and delete this job?');">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <button type="submit" class="btn btn-delete" style="background-color: var(--success-color); color: #ffffff;">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No jobs are currently pending approval.</p>
        <?php endif; ?>
    </div>
    
    <!-- PLATFORM TRANSACTION HISTORY SECTION -->
    <div class="admin-section">
        <h2>Platform Transaction History</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions && $transactions->num_rows > 0): ?>
                    <?php while ($t = $transactions->fetch_assoc()): ?>
                        <?php
                        $is_commission = $t['type'] === 'commission';
                        $amount_class = $is_commission ? 'transaction-in' : 'transaction-out';
                        $prefix = $is_commission ? '+' : '-';
                        ?>
                        <tr>
                            <td><?php echo date('M j, Y H:i', strtotime($t['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($t['description']); ?></td>
                            <td><?php echo ucfirst($t['type']); ?></td>
                            <td class='<?php echo $amount_class; ?>'><?php echo $prefix; ?> NPR <?php echo number_format($t['amount'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan='4'>No platform transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- SITE SETTINGS SECTION -->
    <div class="admin-section">
        <h2>Site Settings</h2>
        <form action="update_settings.php" method="POST">
            <div class="form-group">
                <label for="commission_rate">Commission Rate (%)</label>
                <input type="number" name="commission_rate" id="commission_rate" value="<?php echo $commission_rate; ?>" min="0" max="100" step="1">
            </div>
            <button type="submit" class="btn">Update Settings</button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>