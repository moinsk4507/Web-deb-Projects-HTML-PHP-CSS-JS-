<?php
// Include configuration file
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with return URL
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle success message from URL parameter
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Migrate existing notifications to new format
migrate_notifications();

// Process notification actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'mark_all_read') {
        // Mark all notifications as read
        $result = mark_all_notifications_read($user_id);
        
        if ($result['success']) {
            // Redirect to avoid resubmission and show success message
            header("Location: notifications.php?success=" . urlencode("All notifications marked as read."));
            exit();
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'delete_all') {
        // Delete all notifications (both read and unread)
        $result = delete_all_notifications($user_id);
        
        if ($result['success']) {
            // Redirect to avoid resubmission and show success message
            header("Location: notifications.php?success=" . urlencode($result['message']));
            exit();
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'mark_read' && isset($_GET['id']) && is_numeric($_GET['id'])) {
        // Mark specific notification as read
        $notification_id = intval($_GET['id']);
        $result = mark_notification_read($notification_id, $user_id);
        
        if ($result['success']) {
            // Redirect to avoid resubmission and show success message
            header("Location: notifications.php?success=" . urlencode("Notification marked as read."));
            exit();
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'delete' && isset($_GET['id']) && is_numeric($_GET['id'])) {
        // Delete specific notification
        $notification_id = intval($_GET['id']);
        $result = delete_notification($notification_id, $user_id);
        
        if ($result['success']) {
            // Redirect to avoid resubmission and show success message
            header("Location: notifications.php?success=" . urlencode("Notification deleted."));
            exit();
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'create_samples') {
        // Create sample notifications for testing
        create_sample_notifications($user_id);
        header("Location: notifications.php?success=" . urlencode("Sample notifications created successfully."));
        exit();
    }
}

// Get notifications with pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 15;
$offset = ($page - 1) * $items_per_page;

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get notifications
$notifications = get_user_notifications($user_id, $offset, $items_per_page, $filter);
$total_items = count_user_notifications($user_id, $filter);

// Note: Removed automatic sample notification creation to prevent interference with delete operations

// Calculate total pages
$total_pages = ceil($total_items / $items_per_page);

// Set page title
$page_title = 'My Notifications';

// Include header
include 'includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">My Notifications</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 me-3">Notifications</h5>
                        
                        <!-- Filter Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php 
                                switch ($filter) {
                                    case 'unread':
                                        echo 'Unread';
                                        break;
                                    case 'read':
                                        echo 'Read';
                                        break;
                                    default:
                                        echo 'All';
                                }
                                ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                <li><a class="dropdown-item <?php echo $filter === 'all' ? 'active' : ''; ?>" href="?filter=all">All</a></li>
                                <li><a class="dropdown-item <?php echo $filter === 'unread' ? 'active' : ''; ?>" href="?filter=unread">Unread</a></li>
                                <li><a class="dropdown-item <?php echo $filter === 'read' ? 'active' : ''; ?>" href="?filter=read">Read</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div>
                        <?php if (!empty($notifications)): ?>
                            <a href="notifications.php?action=mark_all_read" class="btn btn-sm btn-outline-primary me-2">Mark All as Read</a>
                            <a href="notifications.php?action=delete_all" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete all notifications?');">Delete All</a>
                        <?php else: ?>
                            <a href="notifications.php?action=create_samples" class="btn btn-sm btn-outline-success">Create Sample Notifications</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (empty($notifications)): ?>
                    <div class="card-body text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h5>No notifications</h5>
                        <p class="text-muted">
                            <?php 
                            switch ($filter) {
                                case 'unread':
                                    echo 'You have no unread notifications.';
                                    break;
                                case 'read':
                                    echo 'You have no read notifications.';
                                    break;
                                default:
                                    echo 'You have no notifications yet.';
                            }
                            ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item list-group-item-action py-3 <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge bg-primary me-2">New</span>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo $notification['link']; ?>" class="notification-link" data-id="<?php echo $notification['id']; ?>">
                                            <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                        </a>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted me-3"><?php echo time_elapsed_string($notification['created_at']); ?></small>
                                        
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link text-muted" type="button" id="notificationActions<?php echo $notification['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationActions<?php echo $notification['id']; ?>">
                                                <?php if (!$notification['is_read']): ?>
                                                    <li><a class="dropdown-item" href="notifications.php?action=mark_read&id=<?php echo $notification['id']; ?>">Mark as Read</a></li>
                                                <?php endif; ?>
                                                <li><a class="dropdown-item" href="notifications.php?action=delete&id=<?php echo $notification['id']; ?>" onclick="return confirm('Are you sure you want to delete this notification?');">Delete</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <p class="mb-1 mt-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer bg-white">
                            <nav aria-label="Notifications pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Mark notification as read when clicked
    document.addEventListener('DOMContentLoaded', function() {
        const notificationLinks = document.querySelectorAll('.notification-link');
        
        notificationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const notificationId = this.getAttribute('data-id');
                
                // Send AJAX request to mark as read
                fetch('notifications.php?action=mark_read&id=' + notificationId, {
                    method: 'GET'
                }).then(response => {
                    // No need to handle the response
                }).catch(error => {
                    console.error('Error marking notification as read:', error);
                });
            });
        });
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>