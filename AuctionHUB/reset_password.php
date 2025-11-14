<?php
// Include configuration file
require_once 'includes/config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to home page
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';
$token = '';
$email = '';
$token_valid = false;

// Check if token and email are provided in URL
if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = trim($_GET['token']);
    $email = trim($_GET['email']);
    
    // Validate token
    $token_valid = validate_password_reset_token($email, $token);
    
    if (!$token_valid) {
        $error = 'Invalid or expired password reset link. Please request a new one.';
    }
} else {
    // Redirect to forgot password page if token or email is missing
    header("Location: forgot_password.php");
    exit();
}

// Process reset password form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    // Get form data
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validate form data
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please enter both password fields';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Attempt to reset password
        $reset_result = reset_user_password($email, $token, $new_password);
        
        if ($reset_result['success']) {
            $success = $reset_result['message'];
            $token_valid = false; // Prevent further form submissions
        } else {
            $error = $reset_result['message'];
        }
    }
}

// Set page title
$page_title = "Reset Password";

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Reset Password</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <p class="mt-2 mb-0">You can now <a href="login.php">login</a> with your new password.</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($token_valid): ?>
                        <p class="mb-4">Please enter your new password below.</p>
                        
                        <form method="post" action="" id="reset-password-form">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Reset Password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light">
                    <a href="login.php">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>