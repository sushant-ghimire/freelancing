<?php
// FILE: /profile.php (NEW FILE)

require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle updating user details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
    $full_name = $_POST['full_name'];
    if (!empty($full_name)) {
        $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
        $stmt->bind_param("si", $full_name, $user_id);
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name; // Update session variable
            $message = "Profile details updated successfully!";
        } else {
            $error = "Failed to update details.";
        }
        $stmt->close();
    } else {
        $error = "Full name cannot be empty.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Fetch current password from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_new_password) {
            if (strlen($new_password) >= 6) { // Basic length check
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                if ($update_stmt->execute()) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Failed to update password.";
                }
                $update_stmt->close();
            } else {
                $error = "New password must be at least 6 characters long.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Incorrect current password.";
    }
    $stmt->close();
}

// Fetch current user data for the form
$user_stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

?>

<div class="container">
    <div class="dashboard-header">
        <h1>My Profile</h1>
        <p>Manage your account details and password.</p>
    </div>

    <?php if ($message): ?><p class="success-message"><?php echo $message; ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>

    <div class="client-dashboard-grid">
        <!-- Update Details Form -->
        <div class="post-job-form">
            <h2>Update Your Details</h2>
            <form action="profile.php" method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($current_user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address (Cannot be changed)</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" readonly>
                </div>
                <button type="submit" name="update_details" class="btn">Save Changes</button>
            </form>
        </div>

        <!-- Change Password Form -->
        <div class="post-job-form">
            <h2>Change Password</h2>
            <form action="profile.php" method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_new_password">Confirm New Password</label>
                    <input type="password" name="confirm_new_password" id="confirm_new_password" required>
                </div>
                <button type="submit" name="change_password" class="btn">Change Password</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>