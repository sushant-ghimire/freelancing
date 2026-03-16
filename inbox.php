<?php
// FILE: /inbox.php (NEW FILE)

require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
?>

<div class="container">
    <div class="dashboard-header">
        <h1>My Conversations</h1>
        <p>Here are all your conversations for projects that are currently in progress.</p>
    </div>

    <div class="inbox-list">
        <?php
        $stmt = $conn->prepare("
            SELECT 
                j.id as job_id, 
                j.title, 
                client.full_name as client_name, 
                freelancer.full_name as freelancer_name,
                client.id as client_id
            FROM jobs j
            JOIN users client ON j.client_id = client.id
            JOIN users freelancer ON j.approved_freelancer_id = freelancer.id
            WHERE (j.client_id = ? OR j.approved_freelancer_id = ?) 
            AND j.status = 'in_progress'
            ORDER BY j.created_at DESC
        ");

        if ($stmt === false) {
            echo "<p>System error: Could not load conversations.</p>";
        } else {
            $stmt->bind_param("ii", $user_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($convo = $result->fetch_assoc()) {
                    // Determine the name of the "other person" in the chat
                    $other_person_name = ($user_id == $convo['client_id']) ? $convo['freelancer_name'] : $convo['client_name'];
            ?>
                <a href="messages.php?job_id=<?php echo $convo['job_id']; ?>" class="inbox-item-link">
                    <div class="job-card">
                        <h3><?php echo htmlspecialchars($convo['title']); ?></h3>
                        <p>Conversation with: <strong><?php echo htmlspecialchars($other_person_name); ?></strong></p>
                    </div>
                </a>
            <?php
                }
            } else {
                echo "<p>You have no active conversations. A conversation is created when you hire a freelancer for a job.</p>";
            }
            $stmt->close();
        }
        ?>
    </div>
</div>

<style>
/* Simple style to make the inbox items clickable */
.inbox-item-link { text-decoration: none; }
.inbox-item-link .job-card:hover {
    border-color: var(--primary-color);
}
</style>

<?php require_once 'includes/footer.php'; ?>