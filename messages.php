<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Fetch job details to verify user access
$job_stmt = $conn->prepare("SELECT client_id, approved_freelancer_id, title FROM jobs WHERE id = ? AND status = 'in_progress'");
if ($job_stmt === false) {
    echo "System error: Could not verify conversation access.";
    exit();
}
$job_stmt->bind_param("i", $job_id);
$job_stmt->execute();
$job_result = $job_stmt->get_result();
$job = $job_result->fetch_assoc();
$job_stmt->close();

if (!$job) {
    echo "Conversation not found or project is not in progress.";
    exit();
}

// Check if the current user is either the client or the approved freelancer
if (!($user_id == $job['client_id'] || $user_id == $job['approved_freelancer_id'])) {
    echo "You do not have permission to view this conversation.";
    exit();
}

// Determine the other person in the conversation
$receiver_id = ($user_id == $job['client_id']) ? $job['approved_freelancer_id'] : $job['client_id'];

// Handle message sending (CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message_text = sanitize_text($_POST['message_text']); 
    if (!empty($message_text)) {
        $msg_stmt = $conn->prepare("INSERT INTO messages (job_id, sender_id, receiver_id, message_text) VALUES (?, ?, ?, ?)");
        if ($msg_stmt === false) {
            $error = "System error: Could not send message.";
        } else {
            $msg_stmt->bind_param("iiis", $job_id, $user_id, $receiver_id, $message_text);
            $msg_stmt->execute();
            $msg_stmt->close();
            // Refresh to show the new message
            header("Location: messages.php?job_id=$job_id");
            exit();
        }
    }
}
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Conversation for "<?php echo htmlspecialchars($job['title']); ?>"</h1>
    </div>

    <div class="message-container">
        <?php
        // Fetch all messages for this job (READ)
        $messages_stmt = $conn->prepare("SELECT m.message_text, m.created_at, m.sender_id, u.full_name 
                                        FROM messages m
                                        JOIN users u ON m.sender_id = u.id
                                        WHERE m.job_id = ? ORDER BY m.created_at ASC");
        if ($messages_stmt === false) {
            echo "<p>System error: Could not load messages.</p>";
        } else {
            $messages_stmt->bind_param("i", $job_id);
            $messages_stmt->execute();
            $messages = $messages_stmt->get_result();

        if ($messages->num_rows > 0) {
            while ($message = $messages->fetch_assoc()) {
                $message_class = ($message['sender_id'] == $user_id) ? 'sent' : 'received';
                ?>
                <div class="message <?php echo $message_class; ?>">
                    <div class="message-header">
                        <strong class="message-sender"><?php echo htmlspecialchars($message['full_name']); ?></strong>
                        <span class="message-time"><?php echo date('M j, g:i A', strtotime($message['created_at'])); ?></span>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($message['message_text'])); ?></p>
                </div>
                <?php
            }
        } else {
            echo "<p>No messages yet. Start the conversation!</p>";
        }
                $messages_stmt->close();
            }
        ?>
    </div>

    <div class="message-form" style="margin-top: 2rem;">
        <form action="messages.php?job_id=<?php echo $job_id; ?>" method="POST">
            <div class="form-group">
                <textarea name="message_text" rows="4"
                    placeholder="Type your message... Remember not to share contact details." required></textarea>
            </div>
            <button type="submit" name="send_message" class="btn">Send Message</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>