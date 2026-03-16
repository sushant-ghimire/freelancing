<?php
// Include the header
require_once 'includes/header.php';


// If a user is already logged in, redirect them to their dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'freelancer') {
        header("Location: freelancer_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'client') {
        header("Location: client_dashboard.php");
        exit();
    }
}
?>

<div class="landing-page">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-container">
            <div class="hero-content fade-in">
                <h1>Unlock Local Potential <br><span class="highlight">Nepal's Premier</span> Freelancing Hub</h1>
                <p class="txt-small">Connecting visionary clients with the finest Nepali talent. Your next big project starts here.</p>
                <div class="hero-actions">
                    <a href="signup.php" class="btn btn-sm">Start Your Journey</a>
                    <a href="freelancer_dashboard.php" class="btn btn-logout btn-sm">Browse Projects</a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">5k+</span>
                        <span class="stat-label">Freelancers</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">1k+</span>
                        <span class="stat-label">Active Projects</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">NPR 10M+</span>
                        <span class="stat-label">Paid to Talent</span>
                    </div>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-image-wrapper">
                    <img src="hero_nepal_freelancing_v4_1771869060727.png" alt="Collaborative Freelancers Nepal" class="hero-main-img">
                </div>
            </div>                
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="features-section">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose <span class="highlight">Our Platform?</span></h2>
                <p class="txt-small">We provide the tools and security you need to succeed in the Nepali digital economy.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-illustration">
                        <img src="secure_payments_nepal_v2_1771866333451.png" alt="Secure Payments Nepal">
                    </div>
                    <div class="feature-icon"><i class="fas fa-wallet"></i></div>
                    <h3>Local Payments</h3>
                    <p class="txt-small">Secured transactions tailored for Nepal, including eSewa and direct bank transfers.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-illustration">
                        <img src="trusted_talent_nepal_v2_1771866624148.png" alt="Trusted Talent Nepal">
                    </div>
                    <div class="feature-icon"><i class="fas fa-user-check"></i></div>
                    <h3>Trusted Talent</h3>
                    <p class="txt-small">Verified professionals across design, development, writing, and more.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-illustration">
                        <img src="escrow_security_nepal_v1_1771869290552.png" alt="Escrow Security Nepal">
                    </div>
                    <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
                    <h3>Escrow Security</h3>
                    <p class="txt-small">Funds are held securely until you're 100% satisfied with the work.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>Mastering the <span class="highlight">Workflow</span></h2>
                <p class="txt-small">Getting started is easy, whether you're hiring or looking for work.</p>
            </div>
            <div class="steps-container">
                <div class="step-card">
                    <span class="step-num">01</span>
                    <h3>Create Profile</h3>
                    <p class="txt-small">Sign up and showcase your skills or post your project requirements.</p>
                </div>
                <div class="step-card">
                    <span class="step-num">02</span>
                    <h3>Connect</h3>
                    <p class="txt-small">Receive proposals or find the perfect project that matches your expertise.</p>
                </div>
                <div class="step-card">
                    <span class="step-num">03</span>
                    <h3>Work & Grow</h3>
                    <p class="txt-small">Collaborate through our platform and get paid securely upon completion.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-card">
                <h3>Ready to elevate your business or career?</h3>
                <p class="txt-small">Join thousands of Nepalis who are already thriving on our platform.</p>
                <a href="signup.php" class="btn btn-white">Join the Hub Now</a>
            </div>
        </div>
    </section>
</div>

<?php
// Include the footer
require_once 'includes/footer.php';
?>

