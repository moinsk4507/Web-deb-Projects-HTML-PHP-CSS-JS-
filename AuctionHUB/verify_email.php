<?php
// Include configuration file
require_once 'includes/config.php';

$error = '';
$success = '';

// Check if token and email are provided in URL
if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = trim($_GET['token']);
    $email = trim($_GET['email']);
    
    // Verify email
    $verify_result = verify_user_email($email, $token);
    
    if ($verify_result['success']) {
        $success = $verify_result['message'];
    } else {
        $error = $verify_result['message'];
    }
} else {
    $error = 'Invalid verification link. Please check your email for the correct link.';
}

// Set page title
$page_title = "Email Verification";

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Email Verification</h4>
                </div>
                <div class="card-body p-4 text-center">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <p>If you're having trouble verifying your email, you can:</p>
                        <ul class="text-start">
                            <li>Check if you clicked the complete link from your email</li>
                            <li>Request a new verification email from your profile page</li>
                            <li>Contact our support team for assistance</li>
                        </ul>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                        <h5>Your email has been successfully verified!</h5>
                        <p>You can now enjoy all features of our auction system.</p>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="profile.php" class="btn btn-primary">Go to Profile</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary">Login to Your Account</a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-outline-secondary ms-2">Go to Homepage</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>