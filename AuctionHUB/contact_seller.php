<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];
$auction_id = isset($_GET['auction']) ? (int)$_GET['auction'] : 0;
$seller_id = isset($_GET['seller']) ? (int)$_GET['seller'] : 0;

if ($auction_id <= 0) {
    header('Location: browse.php');
    exit();
}

// Get auction details
$auction = get_auction_by_id($auction_id);
if (!$auction) {
    header('Location: browse.php');
    exit();
}

$seller_id = $auction['user_id'];
$seller = get_user_by_id($seller_id);

if (!$seller) {
    header('Location: browse.php');
    exit();
}

$error = '';
$success = '';

// Process contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($subject) || empty($message)) {
        $error = 'Please fill in all fields.';
    } else {
        // Send message
        $result = send_message_to_seller($user_id, $seller_id, $auction_id, $subject, $message);
        
        if ($result['success']) {
            // Redirect to conversation page
            header('Location: conversation.php?auction=' . $auction_id . '&user=' . $seller_id);
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

$page_title = 'Contact Seller';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Contact Seller</h4>
                    <p class="text-muted mb-0">Send a message about: <strong><?php echo htmlspecialchars($auction['title']); ?></strong></p>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Auction Details</h6>
                            <p class="text-muted">
                                <strong>Item:</strong> <?php echo htmlspecialchars($auction['title']); ?><br>
                                <strong>Current Bid:</strong> $<?php echo number_format($auction['current_price'] ?? $auction['starting_price'], 2); ?><br>
                                <strong>End Date:</strong> <?php echo date('M j, Y, g:i a', strtotime($auction['end_date'])); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Seller Information</h6>
                            <p class="text-muted">
                                <strong>Username:</strong> <?php echo htmlspecialchars($seller['username']); ?><br>
                                <strong>Member Since:</strong> <?php echo date('M Y', strtotime($seller['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="auction.php?id=<?php echo $auction_id; ?>" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
