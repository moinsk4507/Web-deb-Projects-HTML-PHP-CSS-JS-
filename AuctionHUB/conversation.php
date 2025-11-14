<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];
$auction_id = isset($_GET['auction']) ? (int)$_GET['auction'] : 0;
$other_user_id = isset($_GET['user']) ? (int)$_GET['user'] : 0;

if ($auction_id <= 0 || $other_user_id <= 0) {
    header('Location: messages.php');
    exit();
}

// Get auction details
$auction = get_auction_by_id($auction_id);
if (!$auction) {
    header('Location: messages.php');
    exit();
}

// Get other user details
$other_user = get_user_by_id($other_user_id);
if (!$other_user) {
    header('Location: messages.php');
    exit();
}

// Check if user is either the seller or buyer
if ($user_id != $auction['user_id'] && $user_id != $other_user_id) {
    header('Location: messages.php');
    exit();
}

$error = '';
$success = '';

// Process reply form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($subject) || empty($message)) {
        $error = 'Please fill in all fields.';
    } else {
        // Send reply
        $result = reply_to_message($user_id, $other_user_id, $auction_id, $subject, $message);
        
        if ($result['success']) {
            $success = 'Reply sent successfully!';
            // Clear form
            $_POST = [];
        } else {
            $error = $result['message'];
        }
    }
}

// Get conversation
$conversation = get_conversation($user_id, $other_user_id, $auction_id);

// Mark all messages in this conversation as read
foreach ($conversation as $msg) {
    if ($msg['to_user_id'] == $user_id && !$msg['is_read']) {
        mark_message_read($msg['id'], $user_id);
    }
}

$page_title = 'Conversation';
include 'includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Conversation</h1>
                <a href="messages.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Messages
                </a>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Auction Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="card-title">
                                <a href="auction.php?id=<?php echo $auction_id; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($auction['title']); ?>
                                </a>
                            </h5>
                            <p class="text-muted mb-0">
                                <strong>Current Bid:</strong> $<?php echo number_format($auction['current_price'] ?? $auction['starting_price'], 2); ?> | 
                                <strong>End Date:</strong> <?php echo date('M j, Y, g:i a', strtotime($auction['end_date'])); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <p class="text-muted mb-0">
                                <strong>Conversation with:</strong><br>
                                <?php echo htmlspecialchars($other_user['username']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Messages -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Messages</h5>
                </div>
                
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if (empty($conversation)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No messages in this conversation yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversation as $msg): ?>
                            <div class="message-item mb-3 <?php echo $msg['from_user_id'] == $user_id ? 'text-end' : 'text-start'; ?>">
                                <div class="d-inline-block">
                                    <div class="card <?php echo $msg['from_user_id'] == $user_id ? 'bg-primary text-white' : 'bg-light'; ?>" style="max-width: 70%;">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0">
                                                    <?php echo htmlspecialchars($msg['subject']); ?>
                                                </h6>
                                                <small class="<?php echo $msg['from_user_id'] == $user_id ? 'text-white-50' : 'text-muted'; ?>">
                                                    <?php echo date('M j, g:i a', strtotime($msg['created_at'])); ?>
                                                </small>
                                            </div>
                                            <p class="card-text mb-0"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $msg['from_user_id'] == $user_id ? 'You' : htmlspecialchars($msg['from_username']); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Reply Form -->
                <div class="card-footer bg-white">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="<?php echo htmlspecialchars($_POST['subject'] ?? 'Re: ' . $auction['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" 
                                      placeholder="Type your message here..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" name="send_reply" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send Reply
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-scroll to bottom of messages
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.querySelector('.card-body[style*="overflow-y: auto"]');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
