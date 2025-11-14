<?php
// Include configuration file
require_once 'includes/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Process user status change
if (isset($_GET['action']) && isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $target_user_id = intval($_GET['user_id']);
    $action = $_GET['action'];
    
    // Get user details
    $target_user = get_user_by_id($target_user_id);
    
    if (!$target_user) {
        $error = 'User not found.';
    } else {
        if ($action == 'activate') {
            // Activate user
            $result = update_user_status($target_user_id, 'active');
            if ($result['success']) {
                $success = "User {$target_user['username']} has been activated.";
            } else {
                $error = $result['message'];
            }
        } elseif ($action == 'deactivate') {
            // Deactivate user
            $result = update_user_status($target_user_id, 'inactive');
            if ($result['success']) {
                $success = "User {$target_user['username']} has been deactivated.";
            } else {
                $error = $result['message'];
            }
        } elseif ($action == 'make_admin') {
            // Make user an admin
            $result = update_user_role($target_user_id, 1);
            if ($result['success']) {
                $success = "{$target_user['username']} has been granted admin privileges.";
            } else {
                $error = $result['message'];
            }
        } elseif ($action == 'remove_admin') {
            // Remove admin privileges
            $result = update_user_role($target_user_id, 0);
            if ($result['success']) {
                $success = "Admin privileges have been removed from {$target_user['username']}.";
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Process auction status change
if (isset($_GET['auction_action']) && isset($_GET['auction_id']) && is_numeric($_GET['auction_id'])) {
    $auction_id = intval($_GET['auction_id']);
    $action = $_GET['auction_action'];
    
    // Get auction details
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        $error = 'Auction not found.';
    } else {
        if ($action == 'activate') {
            // Activate auction
            if (function_exists('update_auction_status')) {
                $result = update_auction_status($auction_id, 'active');
                if ($result && isset($result['success']) && $result['success']) {
                    $success = "Auction #{$auction_id} has been activated.";
                } else {
                    $error = isset($result['message']) ? $result['message'] : 'Failed to activate auction.';
                }
            } else {
                $error = "Internal error: update_auction_status function not found.";
            }
        } elseif ($action == 'cancel') {
            // Cancel auction
            if (function_exists('update_auction_status')) {
                $result = update_auction_status($auction_id, 'cancelled');
                if ($result && isset($result['success']) && $result['success']) {
                    $success = "Auction #{$auction_id} has been cancelled.";
                } else {
                    $error = isset($result['message']) ? $result['message'] : 'Failed to cancel auction.';
                }
            } else {
                $error = "Internal error: update_auction_status function not found.";
            }
        } elseif ($action == 'delete') {
            // Delete auction (admin override)
            $result = delete_auction($auction_id, null);
            if ($result['success']) {
                $success = "Auction #{$auction_id} has been deleted.";
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Process category actions
if (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    
    if (empty($category_name)) {
        $error = 'Category name cannot be empty.';
    } else {
        $result = add_category($category_name);
        if ($result['success']) {
            $success = "Category '{$category_name}' has been added.";
        } else {
            $error = $result['message'];
        }
    }
} elseif (isset($_GET['delete_category']) && is_numeric($_GET['delete_category'])) {
    $category_id = intval($_GET['delete_category']);
    $result = delete_category($category_id);
    
    if ($result['success']) {
        $success = "Category has been deleted.";
    } else {
        $error = $result['message'];
    }
}

// Get dashboard statistics
$total_users = count_users();
$total_auctions = count_auctions();
$total_active_auctions = count_auctions('active');
$total_ended_auctions = count_auctions('ended');
$total_bids = count_bids();
$total_categories = count_categories();

// Get recent users
$recent_users = get_recent_users(5);

// Get recent auctions
$recent_auctions = get_recent_auctions(5);

// Get all categories
$categories = get_all_categories();

// Set page title
$page_title = 'Admin Dashboard';

// Include header
include 'includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 mb-4">
            <div class="list-group shadow-sm">
                <a href="#dashboard" class="list-group-item list-group-item-action active" data-bs-toggle="list">Dashboard</a>
                <a href="#users" class="list-group-item list-group-item-action" data-bs-toggle="list">Manage Users</a>
                <a href="#auctions" class="list-group-item list-group-item-action" data-bs-toggle="list">Manage Auctions</a>
                <a href="#categories" class="list-group-item list-group-item-action" data-bs-toggle="list">Manage Categories</a>
                <a href="#settings" class="list-group-item list-group-item-action" data-bs-toggle="list">System Settings</a>
                <a href="#reports" class="list-group-item list-group-item-action" data-bs-toggle="list">Reports</a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="tab-content">
                <!-- Dashboard Tab -->
                <div class="tab-pane fade show active" id="dashboard">
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h2 class="card-title">Admin Dashboard</h2>
                                    <p class="text-muted">Welcome to the auction system administration panel.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Total Users</h6>
                                            <h2 class="mb-0"><?php echo $total_users; ?></h2>
                                        </div>
                                        <i class="fas fa-users fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0 text-white">
                                    <a href="#users" class="text-white text-decoration-none" data-bs-toggle="list">Manage Users <i class="fas fa-arrow-right ms-1"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Active Auctions</h6>
                                            <h2 class="mb-0"><?php echo $total_active_auctions; ?></h2>
                                        </div>
                                        <i class="fas fa-gavel fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0 text-white">
                                    <a href="#auctions" class="text-white text-decoration-none" data-bs-toggle="list">Manage Auctions <i class="fas fa-arrow-right ms-1"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title">Total Bids</h6>
                                            <h2 class="mb-0"><?php echo $total_bids; ?></h2>
                                        </div>
                                        <i class="fas fa-chart-line fa-3x opacity-50"></i>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0 text-white">
                                    <a href="#reports" class="text-white text-decoration-none" data-bs-toggle="list">View Reports <i class="fas fa-arrow-right ms-1"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Recent Users</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Username</th>
                                                    <th>Email</th>
                                                    <th>Joined</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_users as $user): ?>
                                                    <tr>
                                                        <td>
                                                            <a href="profile.php?id=<?php echo $user['id']; ?>">
                                                                <?php echo htmlspecialchars($user['username']); ?>
                                                            </a>
                                                            <?php if ($user['is_admin']): ?>
                                                                <span class="badge bg-dark ms-1">Admin</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                        <td>
                                                            <?php if ($user['status'] == 'active'): ?>
                                                                <span class="badge bg-success">Active</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">Inactive</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer bg-white text-end">
                                    <a href="#users" class="btn btn-sm btn-primary" data-bs-toggle="list">View All Users</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Recent Auctions</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Seller</th>
                                                    <th>Current Price</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_auctions as $auction): ?>
                                                    <tr>
                                                        <td>
                                                            <a href="auction.php?id=<?php echo $auction['id']; ?>">
                                                                <?php echo htmlspecialchars(substr($auction['title'], 0, 30) . (strlen($auction['title']) > 30 ? '...' : '')); ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($auction['username']); ?></td>
                                                        <td>₹<?php echo number_format($auction['current_price'], 2); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo get_status_color($auction['status']); ?>">
                                                                <?php echo ucfirst($auction['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer bg-white text-end">
                                    <a href="#auctions" class="btn btn-sm btn-primary" data-bs-toggle="list">View All Auctions</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Users Tab -->
                <div class="tab-pane fade" id="users">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Manage Users</h5>
                            <form class="d-flex">
                                <input type="text" class="form-control me-2" placeholder="Search users..." id="userSearchInput">
                                <button type="button" class="btn btn-outline-primary">Search</button>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="usersTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Name</th>
                                            <th>Joined</th>
                                            <th>Status</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- This will be populated via AJAX -->
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Loading users...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <nav aria-label="Users pagination" id="usersPagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <!-- Pagination will be added via JavaScript -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                
                <!-- Auctions Tab -->
                <div class="tab-pane fade" id="auctions">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Manage Auctions</h5>
                            <form class="d-flex">
                                <select class="form-select me-2" id="auctionStatusFilter">
                                    <option value="all">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="ended">Ended</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <input type="text" class="form-control me-2" placeholder="Search auctions..." id="auctionSearchInput">
                                <button type="button" class="btn btn-outline-primary">Search</button>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="auctionsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Seller</th>
                                            <th>Category</th>
                                            <th>Current Price</th>
                                            <th>Bids</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- This will be populated via AJAX -->
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Loading auctions...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <nav aria-label="Auctions pagination" id="auctionsPagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <!-- Pagination will be added via JavaScript -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                
                <!-- Categories Tab -->
                <div class="tab-pane fade" id="categories">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Add New Category</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <div class="mb-3">
                                            <label for="category_name" class="form-label">Category Name</label>
                                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                                        </div>
                                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Manage Categories</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Category Name</th>
                                                    <th>Auctions</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($categories as $category): ?>
                                                    <tr>
                                                        <td><?php echo $category['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                        <td>
                                                            <?php 
                                                            $category_auction_count = count_category_auctions($category['id']);
                                                            echo $category_auction_count;
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="browse.php?category=<?php echo $category['id']; ?>" class="btn btn-outline-primary" title="View Auctions">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-outline-secondary" title="Edit" data-bs-toggle="modal" data-bs-target="#editCategoryModal" data-category-id="<?php echo $category['id']; ?>" data-category-name="<?php echo htmlspecialchars($category['name']); ?>">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <?php if ($category_auction_count == 0): ?>
                                                                    <a href="#" class="btn btn-outline-danger" title="Delete" onclick="confirmDeleteCategory(<?php echo $category['id']; ?>, '<?php echo addslashes(htmlspecialchars($category['name'])); ?>'); return false;">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <button type="button" class="btn btn-outline-danger" disabled title="Cannot delete category with auctions">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Tab -->
                <div class="tab-pane fade" id="settings">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">System Settings</h5>
                        </div>
                        <div class="card-body">
                            <form id="settingsForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="mb-3">General Settings</h6>
                                        <div class="mb-3">
                                            <label for="site_name" class="form-label">Site Name</label>
                                            <input type="text" class="form-control" id="site_name" name="site_name" value="AuctionHUB">
                                        </div>
                                        <div class="mb-3">
                                            <label for="site_email" class="form-label">Site Email</label>
                                            <input type="email" class="form-control" id="site_email" name="site_email" value="admin@example.com">
                                        </div>
                                        <div class="mb-3">
                                            <label for="items_per_page" class="form-label">Items Per Page</label>
                                            <input type="number" class="form-control" id="items_per_page" name="items_per_page" value="12">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6 class="mb-3">Auction Settings</h6>
                                        <div class="mb-3">
                                            <label for="min_bid_increment" class="form-label">Minimum Bid Increment (₹)</label>
                                            <input type="number" class="form-control" id="min_bid_increment" name="min_bid_increment" value="0.01" step="0.01">
                                        </div>
                                        <div class="mb-3">
                                            <label for="auction_fee_percent" class="form-label">Auction Fee (%)</label>
                                            <input type="number" class="form-control" id="auction_fee_percent" name="auction_fee_percent" value="5" step="0.1">
                                        </div>
                                        <div class="mb-3">
                                            <label for="featured_auction_fee" class="form-label">Featured Auction Fee (₹)</label>
                                            <input type="number" class="form-control" id="featured_auction_fee" name="featured_auction_fee" value="10" step="0.01">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <h6 class="mb-3">Email Settings</h6>
                                        <div class="mb-3">
                                            <label for="smtp_host" class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="smtp.example.com">
                                        </div>
                                        <div class="mb-3">
                                            <label for="smtp_port" class="form-label">SMTP Port</label>
                                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="587">
                                        </div>
                                        <div class="mb-3">
                                            <label for="smtp_username" class="form-label">SMTP Username</label>
                                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="user@example.com">
                                        </div>
                                        <div class="mb-3">
                                            <label for="smtp_password" class="form-label">SMTP Password</label>
                                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="password">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6 class="mb-3">Security Settings</h6>
                                        <div class="mb-3">
                                            <label for="login_attempts" class="form-label">Max Login Attempts</label>
                                            <input type="number" class="form-control" id="login_attempts" name="login_attempts" value="5">
                                        </div>
                                        <div class="mb-3">
                                            <label for="password_reset_expiry" class="form-label">Password Reset Expiry (hours)</label>
                                            <input type="number" class="form-control" id="password_reset_expiry" name="password_reset_expiry" value="24">
                                        </div>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="require_email_verification" name="require_email_verification" checked>
                                            <label class="form-check-label" for="require_email_verification">Require Email Verification</label>
                                        </div>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="enable_recaptcha" name="enable_recaptcha">
                                            <label class="form-check-label" for="enable_recaptcha">Enable reCAPTCHA</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Reports Tab -->
                <div class="tab-pane fade" id="reports">
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">System Reports</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title">User Statistics</h6>
                                                    <p class="card-text">View detailed user registration and activity statistics.</p>
                                                    <a href="#" class="btn btn-sm btn-primary">Generate Report</a>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title">Auction Performance</h6>
                                                    <p class="card-text">Analyze auction success rates, bid patterns, and category performance.</p>
                                                    <a href="#" class="btn btn-sm btn-primary">Generate Report</a>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title">Financial Summary</h6>
                                                    <p class="card-text">View transaction history, fees collected, and revenue reports.</p>
                                                    <a href="#" class="btn btn-sm btn-primary">Generate Report</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">Custom Report</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Report Type</label>
                                                                <select class="form-select">
                                                                    <option>User Activity</option>
                                                                    <option>Auction Performance</option>
                                                                    <option>Financial Summary</option>
                                                                    <option>Category Analysis</option>
                                                                    <option>Bid Patterns</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Date Range</label>
                                                                <select class="form-select">
                                                                    <option>Last 7 Days</option>
                                                                    <option>Last 30 Days</option>
                                                                    <option>Last 90 Days</option>
                                                                    <option>Last Year</option>
                                                                    <option>Custom Range</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Format</label>
                                                                <select class="form-select">
                                                                    <option>PDF</option>
                                                                    <option>Excel</option>
                                                                    <option>CSV</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="text-end">
                                                            <button type="submit" class="btn btn-primary">Generate Custom Report</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Category Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the category <strong id="deleteCategoryName"></strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteCategoryBtn" class="btn btn-danger">Delete Category</a>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm" method="post" action="update_category.php">
                    <input type="hidden" id="edit_category_id" name="category_id">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editCategoryForm" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to confirm category deletion
    function confirmDeleteCategory(categoryId, categoryName) {
        document.getElementById('deleteCategoryName').textContent = categoryName;
        document.getElementById('deleteCategoryBtn').href = 'admin_dashboard.php?delete_category=' + categoryId;
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
        deleteModal.show();
    }
    
    // Initialize edit category modal
    document.addEventListener('DOMContentLoaded', function() {
        var editCategoryModal = document.getElementById('editCategoryModal');
        if (editCategoryModal) {
            editCategoryModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var categoryId = button.getAttribute('data-category-id');
                var categoryName = button.getAttribute('data-category-name');
                
                var modalCategoryId = document.getElementById('edit_category_id');
                var modalCategoryName = document.getElementById('edit_category_name');
                
                modalCategoryId.value = categoryId;
                modalCategoryName.value = categoryName;
            });
        }
        
        // Load users data via AJAX
        loadUsers(1);
        
        // Load auctions data via AJAX
        loadAuctions(1);
        
        // Settings form submission
        var settingsForm = document.getElementById('settingsForm');
        if (settingsForm) {
            settingsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                // Simulate saving settings
                setTimeout(function() {
                    alert('Settings saved successfully!');
                }, 500);
            });
        }
    });
    
    // Function to load users data
    function loadUsers(page) {
        // Simulate AJAX request
        setTimeout(function() {
            var usersTable = document.getElementById('usersTable').getElementsByTagName('tbody')[0];
            var html = '';
            
            // Sample user data
            for (var i = 1; i <= 10; i++) {
                var userId = (page - 1) * 10 + i;
                var isAdmin = Math.random() > 0.8;
                var status = Math.random() > 0.9 ? 'inactive' : 'active';
                
                html += '<tr>';
                html += '<td>' + userId + '</td>';
                html += '<td>user' + userId + ' ' + (isAdmin ? '<span class="badge bg-dark ms-1">Admin</span>' : '') + '</td>';
                html += '<td>user' + userId + '@example.com</td>';
                html += '<td>User ' + userId + '</td>';
                html += '<td>' + new Date(2023, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1).toLocaleDateString() + '</td>';
                html += '<td><span class="badge bg-' + (status === 'active' ? 'success' : 'danger') + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span></td>';
                html += '<td>' + (isAdmin ? 'Admin' : 'User') + '</td>';
                html += '<td>';
                html += '<div class="btn-group btn-group-sm">';
                html += '<a href="profile.php?id=' + userId + '" class="btn btn-outline-primary" title="View Profile"><i class="fas fa-eye"></i></a>';
                html += '<a href="admin_dashboard.php?action=' + (status === 'active' ? 'deactivate' : 'activate') + '&user_id=' + userId + '" class="btn btn-outline-' + (status === 'active' ? 'warning' : 'success') + '" title="' + (status === 'active' ? 'Deactivate' : 'Activate') + '"><i class="fas fa-' + (status === 'active' ? 'ban' : 'check') + '"></i></a>';
                html += '<a href="admin_dashboard.php?action=' + (isAdmin ? 'remove_admin' : 'make_admin') + '&user_id=' + userId + '" class="btn btn-outline-' + (isAdmin ? 'danger' : 'dark') + '" title="' + (isAdmin ? 'Remove Admin' : 'Make Admin') + '"><i class="fas fa-' + (isAdmin ? 'user-minus' : 'user-shield') + '"></i></a>';
                html += '</div>';
                html += '</td>';
                html += '</tr>';
            }
            
            usersTable.innerHTML = html;
            
            // Update pagination
            var pagination = document.getElementById('usersPagination').getElementsByTagName('ul')[0];
            var paginationHtml = '';
            
            paginationHtml += '<li class="page-item ' + (page === 1 ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="loadUsers(' + (page - 1) + '); return false;">Previous</a></li>';
            
            for (var i = 1; i <= 5; i++) {
                paginationHtml += '<li class="page-item ' + (page === i ? 'active' : '') + '"><a class="page-link" href="#" onclick="loadUsers(' + i + '); return false;">' + i + '</a></li>';
            }
            
            paginationHtml += '<li class="page-item ' + (page === 5 ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="loadUsers(' + (page + 1) + '); return false;">Next</a></li>';
            
            pagination.innerHTML = paginationHtml;
        }, 500);
    }
    
    // Function to load auctions data
    function loadAuctions(page) {
        // Simulate AJAX request
        setTimeout(function() {
            var auctionsTable = document.getElementById('auctionsTable').getElementsByTagName('tbody')[0];
            var html = '';
            
            // Sample auction data
            for (var i = 1; i <= 10; i++) {
                var auctionId = (page - 1) * 10 + i;
                var status = ['active', 'ended', 'cancelled'][Math.floor(Math.random() * 3)];
                var price = (Math.random() * 1000).toFixed(2);
                var bids = Math.floor(Math.random() * 20);
                
                html += '<tr>';
                html += '<td>' + auctionId + '</td>';
                html += '<td><a href="auction.php?id=' + auctionId + '">Auction Item ' + auctionId + '</a></td>';
                html += '<td>user' + Math.floor(Math.random() * 100) + '</td>';
                html += '<td>Category ' + Math.floor(Math.random() * 10 + 1) + '</td>';
                html += '<td>₹' + price + '</td>';
                html += '<td>' + bids + '</td>';
                html += '<td>' + new Date(2023, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1).toLocaleDateString() + '</td>';
                html += '<td><span class="badge bg-' + (status === 'active' ? 'success' : (status === 'ended' ? 'secondary' : 'danger')) + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span></td>';
                html += '<td>';
                html += '<div class="btn-group btn-group-sm">';
                html += '<a href="auction.php?id=' + auctionId + '" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>';
                
                if (status === 'active') {
                    html += '<a href="admin_dashboard.php?auction_action=cancel&auction_id=' + auctionId + '" class="btn btn-outline-warning" title="Cancel"><i class="fas fa-ban"></i></a>';
                } else if (status === 'cancelled' && bids === 0) {
                    html += '<a href="admin_dashboard.php?auction_action=activate&auction_id=' + auctionId + '" class="btn btn-outline-success" title="Activate"><i class="fas fa-check"></i></a>';
                }
                
                if (bids === 0) {
                    html += '<a href="admin_dashboard.php?auction_action=delete&auction_id=' + auctionId + '" class="btn btn-outline-danger" title="Delete"><i class="fas fa-trash-alt"></i></a>';
                }
                
                html += '</div>';
                html += '</td>';
                html += '</tr>';
            }
            
            auctionsTable.innerHTML = html;
            
            // Update pagination
            var pagination = document.getElementById('auctionsPagination').getElementsByTagName('ul')[0];
            var paginationHtml = '';
            
            paginationHtml += '<li class="page-item ' + (page === 1 ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="loadAuctions(' + (page - 1) + '); return false;">Previous</a></li>';
            
            for (var i = 1; i <= 5; i++) {
                paginationHtml += '<li class="page-item ' + (page === i ? 'active' : '') + '"><a class="page-link" href="#" onclick="loadAuctions(' + i + '); return false;">' + i + '</a></li>';
            }
            
            paginationHtml += '<li class="page-item ' + (page === 5 ? 'disabled' : '') + '"><a class="page-link" href="#" onclick="loadAuctions(' + (page + 1) + '); return false;">Next</a></li>';
            
            pagination.innerHTML = paginationHtml;
        }, 500);
    }
</script>

<?php
// Include footer
include 'includes/footer.php';
?>