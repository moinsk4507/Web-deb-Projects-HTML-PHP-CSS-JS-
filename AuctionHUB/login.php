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

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username_or_email = isset($_POST['username_or_email']) ? trim($_POST['username_or_email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    // Validate form data
    if (empty($username_or_email) || empty($password)) {
        $error = 'Please enter both username/email and password';
    } else {
        // Attempt to login
        $login_result = login_user($username_or_email, $password, $remember_me);
        
        if ($login_result['success']) {
            // Redirect based on user role
            if ($_SESSION['user_role'] == ROLE_ADMIN) {
                header("Location: admin/dashboard.php");
            } else {
                // Redirect to intended page if set, otherwise to home page
                $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
            }
            exit();
        } else {
            $error = $login_result['message'];
        }
    }
}

// Set page title
$page_title = "Login";

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Login to Your Account</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="" id="login-form" autocomplete="on">
                        <div class="mb-3">
                            <label for="username_or_email" class="form-label">Email or Username</label>
                            <input type="text" class="form-control" id="username_or_email" name="username_or_email" placeholder="Enter your email" inputmode="email" autocomplete="email" value="<?php echo isset($_POST['username_or_email']) ? htmlspecialchars($_POST['username_or_email']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" id="togglePasswordBtn">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me">Remember me</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between">
                        <a href="forgot_password.php">Forgot Password?</a>
                        <a href="register.php">Don't have an account? Register</a>
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