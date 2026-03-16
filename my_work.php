<?php
// FILE: /my_work.php (NEW FILE - FINAL)

require_once 'includes/auth_check.php';

// Ensure the user is a freelancer
if ($_SESSION['role'] !== 'freelancer') {
    header("Location: client_dashboard.php");
    exit();
}

require_once 'includes/header.php';

$freelancer_id = $_SESSION['user_id'];
?>

<div class="container">
    <div class="dashboard-header">
        <h1>My Work</h1>
        <p>Here is a list of all your active and completed projects.</p>
    </div>

    <div class="my-work-list">
        <?php
        // Fetch jobs assigned to this freelancer
        $stmt = $conn->prepare(
            "SELECT j.id, j.title, j.status, u.full_name as client_name 
             FROM jobs j
             JOIN users u ON j.client_id = u.id
             WHERE j.approved_freelancer_id = ? 
             ORDER BY FIELD(j.status, 'in_progress', 'completed'), j.created_at DESC"
        );
        $stmt->bind_param("i", $freelancer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($job = $result->fetch_assoc()) {
        ?>
                <div class="job-card">
                    <div class="job-item">
                         <div>
                            <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                            <p><strong>Client:</strong> <?php echo htmlspecialchars($job['client_name']); ?></p>
                         </div>
                         <span class="status status-<?php echo $job['status']; ?>">
                                <?php echo str_replace('_', ' ', $job['status']); ?>
                         </span>
                    </div>
                    <div class="job-actions">
                        <?php if ($job['status'] === 'in_progress'): ?>
                            <a href="messages.php?job_id=<?php echo $job['id']; ?>" class="btn">View Conversation</a>
                        <?php endif; ?>
                    </div>
                </div>
        <?php
            }
        } else {
            echo "<p>You have not been hired for any jobs yet.</p>";
        }
        $stmt->close();
        ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>