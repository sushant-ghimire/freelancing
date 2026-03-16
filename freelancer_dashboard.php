<?php
// FILE: /freelancer_dashboard.php (UPDATED AND FINAL)

require_once 'includes/auth_check.php';

if ($_SESSION['role'] == 'client') {
    header("Location: client_dashboard.php");
    exit();
}

require_once 'includes/header.php';
?>



<div class="container_search">
    <form method="GET" action="freelancer_dashboard.php" class="search-box">
        <i class="fas fa-search search-icon" style="color: var(--text-muted); margin-right: 10px;"></i>
        <input type="text" name="search" placeholder="Search projects by category (e.g., Design, Web)..." 
            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit" class="btn btn-search">
            <i class="fas fa-magnifying-glass"></i> Search
        </button>
    </form>
</div>

<div class="container">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        <p>Browse projects posted by clients across Nepal that are open for bidding.</p>
    </div>

    <div class="job-listings">
        <?php
        // Search filter
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        if ($search !== '') {
            // Search by category (partial match)
            $stmt = $conn->prepare("
                SELECT j.id, j.title, j.description, j.budget, j.catagory, u.full_name AS client_name
                FROM jobs j
                JOIN users u ON j.client_id = u.id
                WHERE j.status = 'open' 
                  AND j.catagory LIKE ?
                ORDER BY j.created_at DESC
            ");
            
            if ($stmt === false) {
                echo "<p>Error: Could not prepare search query.</p>";
            } else {
                $search_param = "%$search%";
                $stmt->bind_param("s", $search_param);
                $stmt->execute();
                $result = $stmt->get_result();
                display_jobs($result);
                $stmt->close();
            }

        } else {
            // Default: show all open jobs
            $stmt = $conn->prepare("
                SELECT j.id, j.title, j.description, j.budget, j.catagory, u.full_name AS client_name
                FROM jobs j
                JOIN users u ON j.client_id = u.id
                WHERE j.status = 'open'
                ORDER BY j.created_at DESC
            ");
            
            if ($stmt === false) {
                echo "<p>Error: Could not load projects.</p>";
            } else {
                $stmt->execute();
                $result = $stmt->get_result();
                display_jobs($result);
                $stmt->close();
            }
        }

        // Helper function to display jobs
        function display_jobs($result) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            ?>
                    <div class="job-card fade-in">
                        <div class="job-card-header">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="budget">NPR <?php echo number_format($row['budget']); ?></div>
                        </div>

                        <div class="job-meta">
                            <span class="category-tag"><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($row['catagory']); ?></span>
                            <span class="client-tag"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($row['client_name']); ?></span>
                        </div>

                        <p class="job-description"><?php echo nl2br(htmlspecialchars(substr($row['description'], 0, 250))); ?>...</p>

                        <div class="job-actions">
                            <a href="project.php?id=<?php echo $row['id']; ?>" class="btn">View & Send Proposal</a>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo "<p>No matching projects found!</p>";
            }
        }
        ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
