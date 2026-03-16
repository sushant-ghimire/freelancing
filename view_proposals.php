<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Only clients can access this page
if ($_SESSION['role'] !== 'client') {
    header("Location: index.php");
    exit();
}

$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$client_id = $_SESSION['user_id'];

// Verify that the logged-in client owns this job
$job_stmt = $conn->prepare("SELECT title, status, approved_freelancer_id FROM jobs WHERE id = ? AND client_id = ?");
if ($job_stmt === false) {
    echo "System error: Could not verify job ownership. (Error: " . $conn->error . ")";
    exit();
}
$job_stmt->bind_param("ii", $job_id, $client_id);
$job_stmt->execute();
$job_result = $job_stmt->get_result();

if ($job_result->num_rows == 0) {
    echo "Job not found or you do not have permission to view it.";
    exit();
}

$job = $job_result->fetch_assoc();
$job_stmt->close();


// Handle proposal acceptance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_proposal'])) {

    $proposal_id = intval($_POST['proposal_id']);
    $freelancer_id = intval($_POST['freelancer_id']);

    $conn->begin_transaction();

    try {

        // Update job
        $update_job_stmt = $conn->prepare("UPDATE jobs SET status = 'in_progress', approved_freelancer_id = ? WHERE id = ? AND client_id = ?");
        if ($update_job_stmt === false) { throw new Exception("Could not prepare job update."); }
        $update_job_stmt->bind_param("iii", $freelancer_id, $job_id, $client_id);
        $update_job_stmt->execute();

        // Update proposal
        $update_proposal_stmt = $conn->prepare("UPDATE proposals SET status = 'accepted' WHERE id = ?");
        if ($update_proposal_stmt === false) { throw new Exception("Could not prepare proposal update."); }
        $update_proposal_stmt->bind_param("i", $proposal_id);
        $update_proposal_stmt->execute();

        $conn->commit();

        header("Location: view_proposals.php?job_id=$job_id&accepted=true");
        exit();

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        echo "Error accepting proposal.";
    }
}
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Proposals for "<?php echo htmlspecialchars($job['title']); ?>"</h1>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'insufficient_funds'): ?>
            <p class="error-message">
                Payment failed. You do not have enough funds in your wallet.
                Please <a href="wallet.php">load your wallet</a>.
            </p>
        <?php endif; ?>

        <?php if ($job['status'] === 'in_progress'): ?>
            <p class="success-message">
                You have hired a freelancer! 
                <a href="messages.php?job_id=<?php echo $job_id; ?>">Start a conversation</a>.
            </p>

            <div style="background-color: var(--card-bg); padding: 1.5rem; margin-top: 2rem; border-radius: 8px; text-align: center;">
                <h3>Project In Progress</h3>
                <p>Once satisfied, mark project as complete to release payment.</p>

                <form action="complete_project.php" method="POST">
                    <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                    <button type="submit" name="complete_job" class="btn"
                        onclick="return confirm('Are you sure you want to complete this project?');">
                        Mark as Complete & Pay
                    </button>
                </form>
            </div>

        <?php elseif ($job['status'] === 'completed'): ?>
            <p class="success-message">
                This project has been completed and payment has been sent.
            </p>
        <?php endif; ?>
    </div>

    <div class="proposals-list">
        <?php
        $proposal_stmt = $conn->prepare("
            SELECT p.id, p.proposal_text, p.created_at, p.status,
                   u.full_name, u.id as freelancer_id
            FROM proposals p
            JOIN users u ON p.freelancer_id = u.id
            WHERE p.job_id = ?
            ORDER BY p.created_at DESC
        ");

        $proposal_stmt->bind_param("i", $job_id);
        if ($proposal_stmt === false) {
            echo "<p>Error: Could not load proposals.</p>";
        } else {
            $proposal_stmt->execute();
            $proposals = $proposal_stmt->get_result();

        if ($proposals->num_rows > 0):

            while ($proposal = $proposals->fetch_assoc()):
        ?>
                <div class="proposal-card">
                    <div class="proposal-card-header">
                        <strong>From: <?php echo htmlspecialchars($proposal['full_name']); ?></strong>
                        <span><?php echo date('M j, Y', strtotime($proposal['created_at'])); ?></span>
                    </div>

                    <p>
                        <?php echo nl2br(htmlspecialchars($proposal['proposal_text'])); ?>
                    </p>

                    <div style="margin-top:1rem; text-align:right;">
                        <?php if ($job['status'] === 'open'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                <input type="hidden" name="freelancer_id" value="<?php echo $proposal['freelancer_id']; ?>">
                                <button type="submit" name="accept_proposal" class="btn">
                                    Accept & Hire
                                </button>
                            </form>

                        <?php elseif ($proposal['status'] === 'accepted'): ?>
                            <span style="color:green;"><strong>Hired</strong></span>
                        <?php endif; ?>
                    </div>
                </div>
        <?php
            endwhile;

        else:
            echo "<p>No proposals have been submitted yet.</p>";
        endif;

        $proposal_stmt->close();
        }
        ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>