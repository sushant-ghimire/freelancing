<?php
// FILE: /client_dashboard.php
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

if ($_SESSION['role'] !== 'client') {
    header("Location: freelancer_dashboard.php");
    exit();
}

$client_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch client balance
$bal_stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
if ($bal_stmt) {
    $bal_stmt->bind_param("i", $client_id);
    $bal_stmt->execute();
    $bal_res = $bal_stmt->get_result();
    $client_balance = $bal_res->fetch_assoc()['balance'];
    $bal_stmt->close();
} else {
    $client_balance = 0;
}

// Handle messages from redirects
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'job_posted')
        $message = "Job posted successfully! It is now pending admin approval.";

    if ($_GET['status'] === 'job_updated')
        $message = "Job updated successfully!";

    if ($_GET['status'] === 'job_deleted')
        $message = "Job deleted successfully.";

    if ($_GET['status'] === 'job_rejected')
        $error = "Your job has been rejected by admin. You can edit and resubmit it.";
}

if (isset($_GET['error']) && $_GET['error'] === 'delete_failed') {
    $error = "Could not delete job. It may have already been assigned to a freelancer.";
}

// Handle form submission for posting a new job
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_job'])) {

    $title = $_POST['title'];
    $description = $_POST['description'];
    $budget = floatval($_POST['budget']);
    $catagory = $_POST['catagory'];

    if (!empty($title) && !empty($description) && $budget > 0 && !empty($catagory)) {

        if ($client_balance < 100) {
            $error = "Insufficient money! You need at least NPR 100 in your wallet to post a job.";
        } else {

            $stmt = $conn->prepare("INSERT INTO jobs (client_id, title, description, budget, catagory) VALUES (?, ?, ?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param("issds", $client_id, $title, $description, $budget, $catagory);

                if ($stmt->execute()) {
                    header("Location: client_dashboard.php?status=job_posted");
                    exit();
                } else {
                    $error = "Error posting job.";
                }

                $stmt->close();
            } else {
                $error = "System error: Could not prepare job posting.";
            }
        }
    } else {
        $error = "Please fill in all fields correctly.";
    }
}
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        <p>Manage your projects and hire talented freelancers.</p>
    </div>

    <?php if ($message): ?>
        <p class="success-message"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error-message"><?php echo $error; ?></p>
    <?php endif; ?>

    <div class="client-dashboard-grid">

        <!-- Post Job Form -->
        <div class="post-job-form">
            <h2>Post a New Job</h2>
            <form action="client_dashboard.php" method="POST">
                <div class="form-group">
                    <label>Project Title</label>
                    <input type="text" name="title" required>
                </div>

                <div class="form-group">
                    <label>Project Description</label>
                    <textarea name="description" rows="6" required></textarea>
                </div>

                <div class="form-group">
                    <label>Budget (NPR)</label>
                    <input type="number" name="budget" required>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="catagory" required>
                </div>

                <button type="submit" name="post_job" class="btn">Post Job</button>
            </form>
        </div>

        <!-- My Jobs -->
        <div class="my-jobs-list">
            <h2>My Posted Jobs</h2>

            <?php
            $stmt = $conn->prepare("SELECT id, title, status FROM jobs WHERE client_id = ? ORDER BY created_at DESC");

            if ($stmt) {
                $stmt->bind_param("i", $client_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {

                    while ($job = $result->fetch_assoc()) {
            ?>

                        <div class="job-item-container">
                            <div class="job-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($job['title']); ?></strong><br>

                                    <span class="status status-<?php echo $job['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?>
                                    </span>
                                </div>

                                <a href="view_proposals.php?job_id=<?php echo $job['id']; ?>" class="btn">
                                    View Details
                                </a>
                            </div>

                            <!-- Allow Edit/Delete if Pending, Open OR Rejected -->
                            <?php if (
                                $job['status'] === 'pending_approval' ||
                                $job['status'] === 'open' ||
                                $job['status'] === 'rejected'
                            ): ?>
                                <div class="job-item-actions">
                                    <a href="edit_job.php?job_id=<?php echo $job['id']; ?>" class="btn-edit">
                                        Edit
                                    </a>

                                    <form action="delete_job.php" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this job?');">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <button type="submit" name="delete_job" class="btn-delete">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>

            <?php
                    }

                } else {
                    echo "<p>You haven't posted any jobs yet.</p>";
                }

                $stmt->close();
            } else {
                echo "<p>Error loading jobs.</p>";
            }
            ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>