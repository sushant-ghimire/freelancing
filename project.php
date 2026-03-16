<?php
// FILE: /project.php (UPDATED AND SECURE)

require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Only freelancers can access this page
if ($_SESSION['role'] !== 'freelancer') {
    header("Location: index.php");
    exit();
}

$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$freelancer_id = $_SESSION['user_id'];
$message = '';
$error = '';

// SECURE - PREPARED STATEMENT ADDED
// Check freelancer's available bids
$stmt_bids = $conn->prepare("SELECT bids_available FROM users WHERE id = ?");
if ($stmt_bids) {
    $stmt_bids->bind_param("i", $freelancer_id);
    $stmt_bids->execute();
    $bids_res = $stmt_bids->get_result();
    $available_bids = $bids_res->fetch_assoc()['bids_available'];
    $stmt_bids->close();
} else {
    $available_bids = 0;
}

// Handle proposal submission (CREATE operation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_proposal'])) {
    
    if ($available_bids > 0) {
        // CLEANUP: No real_escape_string needed
        $proposal_text = sanitize_text($_POST['proposal_text']);

        if (!empty($proposal_text)) {
            // Check if proposal already exists
            $check_stmt = $conn->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
            if ($check_stmt === false) {
                $error = "System error: Could not verify if proposal already exists.";
            } else {
                $check_stmt->bind_param("ii", $job_id, $freelancer_id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error = "You have already submitted a proposal for this job.";
                } else {
                    // Use a transaction to ensure both actions complete
                    $conn->begin_transaction();
                    try {
                        // 1. Insert the proposal
                        $stmt_prop = $conn->prepare("INSERT INTO proposals (job_id, freelancer_id, proposal_text) VALUES (?, ?, ?)");
                        if ($stmt_prop === false) {
                            throw new Exception("Could not prepare proposal insert.");
                        }
                        $stmt_prop->bind_param("iis", $job_id, $freelancer_id, $proposal_text);
                        $stmt_prop->execute();

                        // 2. Deduct one bid from the freelancer
                        $stmt_bid = $conn->prepare("UPDATE users SET bids_available = bids_available - 1 WHERE id = ?");
                        if ($stmt_bid === false) {
                            throw new Exception("Could not prepare bid update.");
                        }
                        $stmt_bid->bind_param("i", $freelancer_id);
                        $stmt_bid->execute();

                        $conn->commit();
                        $message = "Proposal submitted successfully! 1 bid has been used.";
                        // Refresh available bids count
                        $available_bids--; 
                    } catch (mysqli_sql_exception $e) {
                        $conn->rollback();
                        $error = "An error occurred. Please try again.";
                    }
                }
                $check_stmt->close();
            }
        } else {
            $error = "Proposal text cannot be empty.";
        }
    } else {
        $error = "You have no bids left! Please purchase more from your wallet.";
    }
}

// Fetch job details (already secure)
$stmt = $conn->prepare("SELECT j.id, j.title, j.description, j.budget, u.full_name as client_name 
                        FROM jobs j 
                        JOIN users u ON j.client_id = u.id 
                        WHERE j.id = ? AND j.status = 'open'");
if ($stmt === false) {
    echo "System error: Could not load job details.";
    exit();
}
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();
$stmt->close();
if (!$job) { echo "Job not found or is no longer open."; exit(); }
?>

<div class="container">
    <div class="project-details">
        <h1><?php echo htmlspecialchars($job['title']); ?></h1>
        <div class="budget">Budget: NPR <?php echo number_format($job['budget']); ?></div>
        <p><strong>Posted by:</strong> <?php echo htmlspecialchars($job['client_name']); ?></p>
        <div class="description">
            <h3>Project Description</h3>
            <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
        </div>
    </div>

    <div class="proposal-form-container" style="margin-top: 2rem;">
        <h2>Submit Your Proposal</h2>
        <p style="margin-bottom: 1rem;">You have <strong style="color: var(--primary-color);"><?php echo $available_bids; ?></strong> bids remaining.</p>
        <?php if ($message): ?><p class="success-message"><?php echo $message; ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>
        
        <?php if ($available_bids > 0): ?>
        <form action="project.php?id=<?php echo $job_id; ?>" method="POST">
             <div class="form-group">
                <label for="proposal_text">Your Proposal (Costs 1 Bid)</label>
                <textarea name="proposal_text" id="proposal_text" rows="8" required placeholder="Explain why you are the best fit..."></textarea>
            </div>
            <button type="submit" name="submit_proposal" class="btn">Send Proposal</button>
        </form>
        <?php else: ?>
        <p>You need to purchase more bids to apply for this job. <a href="wallet.php" class="btn">Go to Wallet</a></p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>