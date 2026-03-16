<?php
// FILE: /signup.php (UPDATED AND FINAL)

require_once 'includes/header.php';

// FIX: Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'client') {
        header('Location: client_dashboard.php');
    } else {
        header('Location: freelancer_dashboard.php');
    }
    exit();
}

$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // --- Validation ---
    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error_message = "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);

            if ($insert_stmt->execute()) {
                header("Location: login.php?registered=success");
                exit();
            } else {
                $error_message = "Error: Could not create account.";
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
?>

<div class="signup-split-view">
    <div class="signup-form-side">
        <div class="form-container" style="max-width: 500px; margin: 0 auto; background: transparent; border: none; box-shadow: none;">
            <form action="signup.php" method="POST" class="fade-in">
                <h1 style="text-align: left; margin-bottom: 0.5rem;">Join the Community</h1>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">Nepal's Premier Freelancing Hub. Fill in your details to get started.</p>

                <?php if ($error_message): ?>
                    <p class="error-message"><?php echo $error_message; ?></p>
                <?php endif; ?>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="example@email.com" required>
                </div>

                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" minlength="6" placeholder="Min 6 chars" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6" placeholder="Repeat password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">I am a:</label>
                    <select id="role" name="role" required>
                        <option value="" disabled selected>-- Select a Role --</option>
                        <option value="freelancer">Freelancer (Looking for work)</option>
                        <option value="client">Client (Looking to hire)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-search" style="width: 100%; margin-top: 1rem;">Create Account</button>
                <p class="switch-form" style="text-align: left;">Already have an account? <a href="login.php">Log In</a></p>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>