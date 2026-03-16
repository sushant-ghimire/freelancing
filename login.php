<?php
// FILE: /login.php (UPDATED WITH SECURITY FIX)

require_once 'includes/header.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'client') {
        header('Location: client_dashboard.php');
    } else {
        header('Location: freelancer_dashboard.php');
    }
    exit();
}

$error_message = '';
$success_message = '';

if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success_message = "Registration successful! Please log in.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Email and password are required.";
    } else {
        // BUG FIX: Ensure this form only logs in clients and freelancers, not admins.
        // We now explicitly check that the role is NOT 'admin'.
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? AND role != 'admin'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role (this logic is now safer)
                if ($user['role'] === 'freelancer') {
                    header("Location: freelancer_dashboard.php");
                } elseif ($user['role'] === 'client') {
                    header("Location: client_dashboard.php");
                }
                exit();
            } else {
                $error_message = "Invalid email or password.";
            }
        } else {
            // This message is now more generic to prevent revealing which accounts exist.
            // It will correctly show for an admin trying to log in here.
            $error_message = "Invalid email or password.";
        }
        $stmt->close();
    }
}
?>

<div class="form-container">
    <form action="login.php" method="POST">
        <h1>Log In</h1>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p><?php endif; ?>
        <?php if ($success_message): ?>
            <p class="success-message"><?php echo $success_message; ?></p><?php endif; ?>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Log In</button>
        <p class="switch-form">Don't have an account? <a href="signup.php">Sign Up</a></p>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>