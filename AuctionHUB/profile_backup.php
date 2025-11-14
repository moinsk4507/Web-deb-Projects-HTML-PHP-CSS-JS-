<?php
// Include configuration file
require_once 'includes/config.php';

// Helper function to safely escape values
function safe_escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Set redirect after login
    $_SESSION['redirect_after_login'] = 'profile.php';
    // Redirect to login page
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Get user data
$user = get_user_by_id($user_id);

if (!$user) {
    // Handle error - user not found
    header("Location: index.php");
    exit();
}

// Process profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $state = isset($_POST['state']) ? trim($_POST['state']) : '';
    $zip_code = isset($_POST['zip_code']) ? trim($_POST['zip_code']) : '';
    $country = isset($_POST['country']) ? trim($_POST['country']) : '';
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    
    // Validate form data
    if (empty($email)) {
        $error = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Handle profile image upload
        $profile_image = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $upload_result = upload_profile_image($user_id, $_FILES['profile_image']);
            if (!$upload_result['success']) {
                $error = $upload_result['message'];
            } else {
                $profile_image = $upload_result['image_path'];
            }
        }
        
        if (empty($error)) {
            // Update user profile
            $update_result = update_user_profile(
                $user_id, 
                $first_name, 
                $last_name, 
                $email, 
                $phone, 
                $address, 
                $city, 
                $state, 
                $zip_code, 
                $country, 
                $bio, 
                $profile_image
            );
            
            if ($update_result['success']) {
                $success = $update_result['message'];
                // Refresh user data
                $user = get_user_by_id($user_id);
            } else {
                $error = $update_result['message'];
            }
        }
    }
}

// Process password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Get form data
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

// Process email verification request
if (isset($_GET['resend_verification']) && $user['email_verified'] == 0) {
    $resend_result = resend_verification_email($user_id);
    
    if ($resend_result['success']) {
        $success = $resend_result['message'];
    } else {
        $error = $resend_result['message'];
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
                            <div class="col-6 mb-3">
                                <h5><?php echo $stats['auctions_created']; ?></h5>
                                <small class="text-muted">Auctions</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h5><?php echo $stats['bids_placed']; ?></h5>
                                <small class="text-muted">Bids</small>
                            </div>
                            <div class="col-6">
                                <h5><?php echo $stats['active_auctions']; ?></h5>
                                <small class="text-muted">Active</small>
                            </div>
                            <div class="col-6">
                                <h5><?php echo $stats['won_auctions']; ?></h5>
                                <small class="text-muted">Won</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-actions mt-3">
                        <a href="my_auctions.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-gavel"></i> My Auctions</a>
                        <a href="my_bids.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-hand-paper"></i> My Bids</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-8">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Profile Tabs -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">Profile</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">Security</button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
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
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo safe_escape($user['first_name']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo safe_escape($user['last_name']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo safe_escape($user['phone']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo safe_escape($user['address']); ?>">
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" value="<?php echo safe_escape($user['city']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="state" class="form-label">State/Province</label>
                                        <input type="text" class="form-control" id="state" name="state" value="<?php echo safe_escape($user['state']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                                        <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo safe_escape($user['zip_code']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="country" class="form-label">Country</label>
                                        <input type="text" class="form-control" id="country" name="country" value="<?php echo safe_escape($user['country']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo safe_escape($user['bio']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">Profile Image</label>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                                    <small class="text-muted">Max file size: 2MB. Recommended size: 300x300px</small>
                                    <div id="image-preview" class="mt-2 d-none">
                                        <img src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
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
            // Re-initialize password toggles after tab is shown
            setTimeout(function() {
                if (typeof initPasswordToggles === 'function') {
                    initPasswordToggles();
                }
            }, 100);
        });
    }
    
    // Also initialize password toggles immediately for any visible password fields
    setTimeout(function() {
        if (typeof initPasswordToggles === 'function') {
            initPasswordToggles();
        }
    }, 500);
    
    // Additional initialization for dynamically loaded content
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('toggle-password')) {
                        if (typeof initPasswordToggles === 'function') {
                            initPasswordToggles();
                        }
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>