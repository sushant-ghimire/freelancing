<?php
// FILE: /includes/header.php (UPDATED AND FINAL)

require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Freelancing Platform - Nepal's Freelancing Hub</title>
    <link rel="stylesheet" href="/freelancing/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <header>
        <div class="container">
            <a href="/freelancing/index.php" class="logo">Local Freelancing Platform</a>
            <nav>
                <?php if (isset($_SESSION['user_id'])):
                    // SECURE - PREPARED STATEMENT ADDED
                    // Fetch user balance to display in header
                    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $balance_res = $stmt->get_result();
                    $current_balance = $balance_res->fetch_assoc()['balance'];
                    $stmt->close();
                    ?>
                    <span style="color: var(--success-color); margin-right: 1.5rem;">
                        NPR <?php echo number_format($current_balance, 2); ?>
                    </span>
                    <a href="/freelancing/wallet.php">Wallet</a>
                    <a href="/freelancing/inbox.php">Inbox</a>

                    <!-- NEW My Work link for Freelancers -->
                    <?php if ($_SESSION['role'] === 'freelancer'): ?>
                        <a href="/freelancing/my_work.php">My Work</a>
                    <?php endif; ?>

                    <a href="/freelancing/profile.php">Profile</a>

                    <a href="/freelancing/logout.php" class="btn btn-logout">Logout</a>
                <?php else: ?>
                    <a href="/freelancing/login.php">Log In</a>
                    <a href="/freelancing/signup.php" class="btn">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>