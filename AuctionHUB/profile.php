<?php
// Profile page - Clean version
require_once 'includes/config.php';

// Helper function to safely escape values
function safe_escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'profile.php';
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Get user data
$user = get_user_by_id($user_id);

if (!$user) {
    header("Location: index.php");
    exit();
}

// Process password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validate form data
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields';
    } elseif (strlen($new_password) < 8) {
        $error = 'New password must be at least 8 characters long';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } else {
        // Change password
        $change_result = change_user_password($user_id, $current_password, $new_password);
        
        if ($change_result['success']) {
            $success = $change_result['message'];
        } else {
            $error = $change_result['message'];
        }
    }
}

// Get user statistics
$stats = array(
    'auctions_created' => count_user_auctions($user_id),
    'active_auctions' => count_user_active_auctions($user_id),
    'bids_placed' => count_user_bids($user_id),
    'won_auctions' => count_user_won_auctions($user_id)
);

// Set page title
$page_title = "My Profile";

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar / User Info -->
        <div class="col-md-4 mb-4">
            <div class="card shadow profile-sidebar">
                <div class="card-body text-center">
                    <div class="profile-image mb-3">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="uploads/<?php echo safe_escape($user['profile_image']); ?>" alt="Profile Image" class="img-fluid rounded-circle">
                        <?php else: ?>
                            <img src="assets/images/default-profile.jpg" alt="Default Profile" class="img-fluid rounded-circle">
                        <?php endif; ?>
                    </div>
                    
                    <h4><?php echo safe_escape($user['username']); ?></h4>
                    <p class="text-muted">
                        <?php echo !empty($user['first_name']) || !empty($user['last_name']) ? safe_escape($user['first_name'] . ' ' . $user['last_name']) : 'Member'; ?>
                    </p>
                    
                    <?php if ($user['email_verified'] == 1): ?>
                        <span class="badge bg-success mb-3"><i class="fas fa-check-circle"></i> Email Verified</span>
                    <?php else: ?>
                        <span class="badge bg-warning mb-2"><i class="fas fa-exclamation-circle"></i> Email Not Verified</span>
                        <div class="mb-3">
                            <a href="profile.php?resend_verification=1" class="btn btn-sm btn-outline-primary">Resend Verification</a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-stats">
                        <div class="row text-center">
                            <div class="col-6 mb-2">
                                <div class="stat-number"><?php echo $stats['auctions_created']; ?></div>
                                <div class="stat-label">Auctions</div>
                            </div>
                            <div class="col-6 mb-2">
                                <div class="stat-number"><?php echo $stats['bids_placed']; ?></div>
                                <div class="stat-label">Bids</div>
                            </div>
                            <div class="col-6">
                                <div class="stat-number"><?php echo $stats['active_auctions']; ?></div>
                                <div class="stat-label">Active</div>
                            </div>
                            <div class="col-6">
                                <div class="stat-number"><?php echo $stats['won_auctions']; ?></div>
                                <div class="stat-label">Won</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="my_auctions.php" class="btn btn-primary btn-sm me-2">My Auctions</a>
                        <a href="my_bids.php" class="btn btn-outline-primary btn-sm">My Bids</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Profile</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="true">Security</button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo safe_escape($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo safe_escape($success); ?></div>
                    <?php endif; ?>
                    
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <form method="post" action="" enctype="multipart/form-data" id="profile-form">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo safe_escape($user['username']); ?>" disabled>
                                        <small class="text-muted">Username cannot be changed</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo safe_escape($user['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo safe_escape($user['first_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo safe_escape($user['last_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo safe_escape($user['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo safe_escape($user['bio'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Security Tab -->
                        <div class="tab-pane fade show active" id="security" role="tabpanel" aria-labelledby="security-tab">
                            <form method="post" action="" id="password-form">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
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
                                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Re-initialize password toggles when security tab is shown
document.addEventListener('DOMContentLoaded', function() {
    const securityTab = document.getElementById('security-tab');
    if (securityTab) {
        securityTab.addEventListener('shown.bs.tab', function() {
            setTimeout(function() {
                if (typeof initPasswordToggles === 'function') {
                    initPasswordToggles();
                }
            }, 100);
        });
    }
    
    setTimeout(function() {
        if (typeof initPasswordToggles === 'function') {
            initPasswordToggles();
        }
    }, 500);
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
