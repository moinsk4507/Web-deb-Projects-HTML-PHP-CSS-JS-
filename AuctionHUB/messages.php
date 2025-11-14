<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Process message actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'mark_all_read') {
        // Mark all messages as read
        if (mark_all_messages_read($user_id)) {
            $success = "All messages marked as read.";
        } else {
            $error = "Failed to mark messages as read.";
        }
    } elseif ($action === 'mark_read' && isset($_GET['id']) && is_numeric($_GET['id'])) {
        // Mark specific message as read
        $message_id = intval($_GET['id']);
        if (mark_message_read($message_id, $user_id)) {
            $success = "Message marked as read.";
        } else {
            $error = "Failed to mark message as read.";
        }
    } elseif ($action === 'delete' && isset($_GET['id']) && is_numeric($_GET['id'])) {
        // Delete specific message
        $message_id = intval($_GET['id']);
        if (delete_message($message_id, $user_id)) {
            $success = "Message deleted.";
        } else {
            $error = "Failed to delete message.";
        }
    }
}

// Get messages with pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 15;
$offset = ($page - 1) * $items_per_page;

// Get messages
$messages = get_user_messages($user_id, $items_per_page, $offset);

// Get unread count
$unread_count = count_unread_messages($user_id);

$page_title = 'My Messages';
include 'includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">My Messages</h1>
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-primary fs-6"><?php echo $unread_count; ?> unread</span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Inbox</h5>
                    <?php if (!empty($messages)): ?>
                        <a href="messages.php?action=mark_all_read" class="btn btn-sm btn-outline-primary">Mark All as Read</a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($messages)): ?>
                    <div class="card-body text-center py-5">
                        <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
                        <h5>No messages</h5>
                        <p class="text-muted">You haven't received any messages yet.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($messages as $message): ?>
                            <div class="list-group-item list-group-item-action py-3 <?php echo $message['is_read'] ? '' : 'bg-light border-start border-primary border-3'; ?>">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <?php if (!$message['is_read']): ?>
                                                <span class="badge bg-primary me-2">New</span>
                                            <?php endif; ?>
                                            <h6 class="mb-0 me-2"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                        </div>
                                        
                                        <p class="text-muted mb-2">
                                            <strong>From:</strong> <?php echo htmlspecialchars($message['from_username']); ?> | 
                                            <strong>About:</strong> 
                                            <a href="auction.php?id=<?php echo $message['auction_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($message['auction_title']); ?>
                                            </a>
                                        </p>
                                        
                                        <p class="mb-2"><?php echo htmlspecialchars(substr($message['message'], 0, 150)); ?><?php echo strlen($message['message']) > 150 ? '...' : ''; ?></p>
                                        
                                        <small class="text-muted">
                                            <?php echo date('M j, Y, g:i a', strtotime($message['created_at'])); ?>
                                        </small>
                                    </div>
                                    
                                    <div class="d-flex align-items-center ms-3">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link text-muted" type="button" id="messageActions<?php echo $message['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="messageActions<?php echo $message['id']; ?>">
                                                <li>
                                                    <a class="dropdown-item" href="conversation.php?auction=<?php echo $message['auction_id']; ?>&user=<?php echo $message['from_user_id']; ?>">
                                                        <i class="fas fa-reply me-2"></i>Reply
                                                    </a>
                                                </li>
                                                <?php if (!$message['is_read']): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="messages.php?action=mark_read&id=<?php echo $message['id']; ?>">
                                                            <i class="fas fa-check me-2"></i>Mark as Read
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="messages.php?action=delete&id=<?php echo $message['id']; ?>" 
                                                       onclick="return confirm('Are you sure you want to delete this message?');">
                                                        <i class="fas fa-trash me-2"></i>Delete
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
