<?php
// FILE: /edit_job.php (NEW FILE - FINAL)

require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Only clients can access this page
if ($_SESSION['role'] !== 'client') {
    header("Location: index.php");
    exit();
}

$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$client_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle the form submission for updating the job
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $budget = floatval($_POST['budget']);

    if (!empty($title) && !empty($description) && $budget > 0) {
        // Prepare the update statement. Also checks ownership and editable status.
        $stmt = $conn->prepare("UPDATE jobs SET title = ?, description = ?, budget = ? 
                                WHERE id = ? AND client_id = ? AND status IN ('pending_approval', 'open')");
        $stmt->bind_param("ssdii", $title, $description, $budget, $job_id, $client_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Redirect on success to prevent form resubmission
                header("Location: client_dashboard.php?status=job_updated");
                exit();
            } else {
                $error = "Could not update job. It might have already been assigned or does not exist.";
            }
        } else {
            $error = "An error occurred while updating the job.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all fields correctly.";
    }
}


// Fetch the job details to pre-fill the form
$stmt = $conn->prepare("SELECT title, description, budget FROM jobs WHERE id = ? AND client_id = ? AND status IN ('pending_approval', 'open')");
$stmt->bind_param("ii", $job_id, $client_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();
$stmt->close();

// If the job doesn't exist or isn't editable, show an error
if (!$job) {
    echo "<div class='container'><p class='error-message'>This job cannot be edited or was not found.</p></div>";
    require_once 'includes/footer.php';
    exit();
}

?>

<div class="container">
    <div class="dashboard-header">
        <h1>Edit Job Posting</h1>
        <p>Update the details for your project below.</p>
    </div>
    
    <?php if ($message): ?><p class="success-message"><?php echo $message; ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>

    <div class="form-container" style="margin: 2rem auto;">
        <form action="edit_job.php?job_id=<?php echo $job_id; ?>" method="POST">
            <div class="form-group">
                <label for="title">Project Title</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Project Description</label>
                <textarea name="description" id="description" rows="8" required><?php echo htmlspecialchars($job['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="budget">Budget (NPR)</label>
                <input type="number" name="budget" id="budget" step="100" min="500" value="<?php echo htmlspecialchars($job['budget']); ?>" required>
            </div>
            <button type="submit" name="update_job" class="btn">Save Changes</button>
            <a href="client_dashboard.php" style="text-align: center; display: block; margin-top: 1rem;">Cancel</a>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>