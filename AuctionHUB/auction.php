<?php
// Include configuration file
require_once 'includes/config.php';

// Helper function to safely escape values
function safe_escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Check if auction ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to browse page
    header("Location: browse.php");
    exit();
}

$auction_id = intval($_GET['id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$error = '';
$success = '';

// Get auction details
$auction = get_auction_by_id($auction_id);
$is_active = isset($auction['status']) && ($auction['status'] === 'active' || $auction['status'] == AUCTION_STATUS_ACTIVE);

if (!$auction) {
    // Auction not found
    $_SESSION['temp_message'] = array(
        'type' => 'error',
        'text' => 'Auction not found or has been removed.'
    );
    header("Location: browse.php");
    exit();
}

// Get auction images
$images = get_auction_images($auction_id);

// Get seller information
$seller = get_user_by_id($auction['user_id']);

// Get current highest bid
$highest_bid = get_highest_bid($auction_id);
$current_price = $highest_bid ? $highest_bid['amount'] : $auction['starting_price'];
$min_bid = $current_price + 0.01;

// Update auction current_price in database to keep it in sync
if ($highest_bid && $current_price != $auction['current_price']) {
    $update_sql = "UPDATE auctions SET current_price = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("di", $current_price, $auction_id);
    $stmt->execute();
}

// Get bid history
$bids = get_auction_bids($auction_id);

// Get similar auctions
$similar_auctions = get_similar_auctions($auction_id, $auction['category_id'], 4);

// Check if user is watching this auction
$is_watching = $user_id ? check_watchlist($user_id, $auction_id) : false;

// Process bid form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_bid'])) {
    // Check if user is logged in
    if (!$user_id) {
        // Set redirect after login
        $_SESSION['redirect_after_login'] = 'auction.php?id=' . $auction_id;
        // Redirect to login page
        header("Location: login.php");
        exit();
    }
    
    // Check if auction is still active
    if (!$is_active) {
        $error = 'This auction has ended and is no longer accepting bids.';
    }
    // Check if user is the seller
    elseif ($auction['user_id'] == $user_id) {
        $error = 'You cannot bid on your own auction.';
    }
    else {
        // Get bid amount
        $bid_amount = isset($_POST['bid_amount']) ? floatval($_POST['bid_amount']) : 0;
        
        // Validate bid amount
        if ($bid_amount <= 0) {
            $error = 'Please enter a valid bid amount.';
        } elseif ($bid_amount < $min_bid) {
            $error = 'Your bid must be at least ₹' . number_format($min_bid, 2) . '.';
        } else {
            // Place bid
            $bid_result = place_bid($auction_id, $user_id, $bid_amount);
            
            if ($bid_result['success']) {
                $success = $bid_result['message'];
                
                // Refresh auction data
                $auction = get_auction_by_id($auction_id);
                $highest_bid = get_highest_bid($auction_id);
                $current_price = $highest_bid ? $highest_bid['amount'] : $auction['starting_price'];
                $min_bid = $current_price + 0.01;
                $bids = get_auction_bids($auction_id);
                
                // Update auction current_price in database to keep it in sync
                if ($highest_bid && $current_price != $auction['current_price']) {
                    $update_sql = "UPDATE auctions SET current_price = ? WHERE id = ?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("di", $current_price, $auction_id);
                    $stmt->execute();
                }
            } else {
                $error = $bid_result['message'];
            }
        }
    }
}

// Process watchlist form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['watchlist_action'])) {
    // Check if user is logged in
    if (!$user_id) {
        // Set redirect after login
        $_SESSION['redirect_after_login'] = 'auction.php?id=' . $auction_id;
        // Redirect to login page
        header("Location: login.php");
        exit();
    }
    
    $action = $_POST['watchlist_action'];
    
    if ($action == 'add') {
        // Add to watchlist
        $watchlist_result = add_to_watchlist($user_id, $auction_id);
        
        if ($watchlist_result['success']) {
            $is_watching = true;
            $success = $watchlist_result['message'];
        } else {
            $error = $watchlist_result['message'];
        }
    } elseif ($action == 'remove') {
        // Remove from watchlist
        $watchlist_result = remove_from_watchlist($user_id, $auction_id);
        
        if ($watchlist_result['success']) {
            $is_watching = false;
            $success = $watchlist_result['message'];
        } else {
            $error = $watchlist_result['message'];
        }
    }
}

// Process feedback form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    // Check if user is logged in
    if (!$user_id) {
        // Set redirect after login
        $_SESSION['redirect_after_login'] = 'auction.php?id=' . $auction_id;
        // Redirect to login page
        header("Location: login.php");
        exit();
    }
    
    // Get form data
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    // Validate form data
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5 stars.';
    } elseif (empty($comment)) {
        $error = 'Please enter a comment for your feedback.';
    } else {
        // Submit feedback
        $feedback_result = add_auction_feedback($auction_id, $user_id, $rating, $comment);
        
        if ($feedback_result['success']) {
            $success = $feedback_result['message'];
        } else {
            $error = $feedback_result['message'];
        }
    }
}

// Get auction feedback
$feedback = get_auction_feedback($auction_id);
$feedback_summary = get_feedback_summary($auction_id);

// Set page title
$page_title = $auction['title'];

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Auction Details -->
    <div class="row">
        <!-- Image Gallery -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-body p-0">
                    <?php if (!empty($images)): ?>
                        <div class="auction-gallery">
                            <div class="main-image mb-2">
                                <?php 
                                $mainPath = isset($images[0]['image_path']) ? 'uploads/' . $images[0]['image_path'] : 'assets/images/no-image.jpg';
                                ?>
                                <img src="<?php echo $mainPath; ?>" alt="<?php echo htmlspecialchars($auction['title'] ?? ''); ?>" class="img-fluid rounded">
                            </div>
                            <?php if (count($images) > 1): ?>
                                <div class="thumbnail-images d-flex">
                                    <?php foreach ($images as $index => $image): ?>
                                        <?php $thumb = isset($image['image_path']) ? 'uploads/' . $image['image_path'] : 'assets/images/no-image.jpg'; ?>
                                        <div class="thumbnail-image <?php echo $index === 0 ? 'active' : ''; ?>" data-src="<?php echo $thumb; ?>">
                                            <img src="<?php echo $thumb; ?>" alt="Thumbnail" class="img-fluid rounded">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <img src="assets/images/no-image.jpg" alt="No Image Available" class="img-fluid rounded">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Auction Info -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h1 class="h3 mb-0"><?php echo safe_escape($auction['title'] ?? ''); ?></h1>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="badge bg-<?php echo get_status_color($auction['status'] ?? 'active'); ?>">
                                <?php echo isset($auction['status']) ? ucfirst($auction['status']) : 'Active'; ?>
                            </span>
                            <span class="ms-2 text-muted">ID: #<?php echo $auction['id']; ?></span>
                        </div>
                        <div>
                            <?php if ($user_id && $user_id != $auction['user_id']): ?>
                                <form method="post" action="" class="d-inline">
                                    <input type="hidden" name="watchlist_action" value="<?php echo $is_watching ? 'remove' : 'add'; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $is_watching ? 'btn-danger' : 'btn-outline-primary'; ?>">
                                        <i class="fas <?php echo $is_watching ? 'fa-heart' : 'fa-heart'; ?>"></i>
                                        <?php echo $is_watching ? 'Remove from Watchlist' : 'Add to Watchlist'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="auction-price mb-3">
                        <h3 class="mb-0">₹<?php echo number_format($current_price, 2); ?></h3>
                        <small class="text-muted">
                            <?php echo $highest_bid ? 'Current Bid' : 'Starting Price'; ?>
                            <?php if ($auction['reserve_price'] > 0): ?>
                                <?php if ($current_price >= $auction['reserve_price']): ?>
                                    <span class="badge bg-success ms-2">Reserve Met</span>
                                <?php else: ?>
                                    <span class="badge bg-warning ms-2">Reserve Not Met</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    
                    <div class="auction-meta mb-4">
                        <div class="row">
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Seller</small>
                                <a href="profile.php?id=<?php echo $seller['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($seller['username']); ?>
                                </a>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Category</small>
                                <a href="browse.php?category=<?php echo $auction['category_id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($auction['category_name'] ?? ''); ?>
                                </a>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Bids</small>
                                <span><?php echo count($bids); ?></span>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Condition</small>
                                <span><?php echo htmlspecialchars(($auction['condition'] ?? '') !== '' ? $auction['condition'] : 'Not specified'); ?></span>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Location</small>
                                <span><?php echo htmlspecialchars(($auction['location'] ?? '') !== '' ? $auction['location'] : 'Not specified'); ?></span>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted d-block">Shipping</small>
                                <span>
                                    <?php echo isset($auction['shipping_cost']) && $auction['shipping_cost'] > 0 ? '₹' . number_format($auction['shipping_cost'], 2) : 'Not specified'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($is_active): ?>
                        <div class="auction-time mb-4">
                            <h5>Time Remaining</h5>
                            <div class="countdown" data-end="<?php echo strtotime($auction['end_date']); ?>"></div>
                        </div>
                        
                        <?php if ($user_id && $user_id != $auction['user_id']): ?>
                            <div class="bid-form mb-4">
                                <h5>Place Your Bid</h5>
                                <form method="post" action="" id="bid-form">
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="bid_amount" name="bid_amount" min="<?php echo $min_bid; ?>" step="0.01" value="<?php echo $min_bid; ?>" required>
                                        <button type="submit" name="place_bid" class="btn btn-primary">Place Bid</button>
                                    </div>
                                    <input type="hidden" id="current_bid" value="<?php echo (float)$current_price; ?>">
                                    <input type="hidden" id="min_bid_increment" value="<?php echo (float)get_min_bid_increment($current_price); ?>">
                                    <small class="text-muted">Enter ₹<?php echo number_format($min_bid, 2); ?> or more</small>
                                </form>
                            </div>
                        <?php elseif (!$user_id): ?>
                            <div class="alert alert-info mb-4">
                                <a href="login.php" class="alert-link">Login</a> or <a href="register.php" class="alert-link">Register</a> to place a bid on this auction.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-<?php echo get_status_color($auction['status']); ?> mb-4">
                            <?php if (!$is_active): ?>
                                This auction has ended on <?php echo date('F j, Y, g:i a', strtotime($auction['end_date'])); ?>.
                                <?php if ($highest_bid): ?>
                                    The winning bid was $<?php echo number_format($highest_bid['amount'], 2); ?>.
                                <?php else: ?>
                                    There were no bids on this auction.
                                <?php endif; ?>
                            <?php elseif ($auction['status'] == 'cancelled' || $auction['status'] == AUCTION_STATUS_CANCELLED): ?>
                                This auction has been cancelled by the seller.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabs Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs" id="auctionTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">Description</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="bids-tab" data-bs-toggle="tab" data-bs-target="#bids" type="button" role="tab" aria-controls="bids" aria-selected="false">Bid History (<?php echo count($bids); ?>)</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="feedback-tab" data-bs-toggle="tab" data-bs-target="#feedback" type="button" role="tab" aria-controls="feedback" aria-selected="false">Feedback</button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <div class="tab-content" id="auctionTabsContent">
                        <!-- Description Tab -->
                        <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                            <div class="auction-description">
                                <?php echo nl2br(safe_escape($auction['description'])); ?>
                            </div>
                        </div>
                        
                        <!-- Bids Tab -->
                        <div class="tab-pane fade" id="bids" role="tabpanel" aria-labelledby="bids-tab">
                            <?php if (!empty($bids)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Bidder</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bids as $index => $bid): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($user_id == $auction['user_id'] || $bid['user_id'] == $user_id): ?>
                                                            <a href="profile.php?id=<?php echo $bid['user_id']; ?>">
                                                                <?php echo htmlspecialchars($bid['username']); ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <?php echo substr(htmlspecialchars($bid['username']), 0, 1) . '****' . substr(htmlspecialchars($bid['username']), -1); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>$<?php echo number_format($bid['amount'], 2); ?></td>
                                                    <td><?php echo date('M j, Y, g:i a', strtotime($bid['bid_date'])); ?></td>
                                                    <td>
                                                        <?php if ($index === 0 && $auction['status'] == 'ended'): ?>
                                                            <span class="badge bg-success">Winner</span>
                                                        <?php elseif ($index === 0): ?>
                                                            <span class="badge bg-primary">Highest</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Outbid</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    No bids have been placed on this auction yet.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Feedback Tab -->
                        <div class="tab-pane fade" id="feedback" role="tabpanel" aria-labelledby="feedback-tab">
                            <!-- Feedback Summary -->
                            <div class="feedback-summary mb-4">
                                <div class="row align-items-center">
                                    <div class="col-md-3 text-center">
                                        <div class="overall-rating mb-2">
                                            <h2 class="mb-0"><?php echo number_format($feedback_summary['average_rating'], 1); ?></h2>
                                            <div class="stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= round($feedback_summary['average_rating'])): ?>
                                                        <i class="fas fa-star text-warning"></i>
                                                    <?php elseif ($i - 0.5 <= $feedback_summary['average_rating']): ?>
                                                        <i class="fas fa-star-half-alt text-warning"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star text-warning"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted"><?php echo $feedback_summary['total_ratings']; ?> ratings</small>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="rating-bars">
                                            <div class="rating-bar d-flex align-items-center mb-1">
                                                <div class="rating-label me-2">5 <i class="fas fa-star text-warning"></i></div>
                                                <div class="progress flex-grow-1" style="height: 10px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $feedback_summary['five_star_percent']; ?>%" aria-valuenow="<?php echo $feedback_summary['five_star_percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="rating-percent ms-2"><?php echo $feedback_summary['five_star_percent']; ?>%</div>
                                            </div>
                                            <div class="rating-bar d-flex align-items-center mb-1">
                                                <div class="rating-label me-2">4 <i class="fas fa-star text-warning"></i></div>
                                                <div class="progress flex-grow-1" style="height: 10px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $feedback_summary['four_star_percent']; ?>%" aria-valuenow="<?php echo $feedback_summary['four_star_percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="rating-percent ms-2"><?php echo $feedback_summary['four_star_percent']; ?>%</div>
                                            </div>
                                            <div class="rating-bar d-flex align-items-center mb-1">
                                                <div class="rating-label me-2">3 <i class="fas fa-star text-warning"></i></div>
                                                <div class="progress flex-grow-1" style="height: 10px;">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $feedback_summary['three_star_percent']; ?>%" aria-valuenow="<?php echo $feedback_summary['three_star_percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="rating-percent ms-2"><?php echo $feedback_summary['three_star_percent']; ?>%</div>
                                            </div>
                                            <div class="rating-bar d-flex align-items-center mb-1">
                                                <div class="rating-label me-2">2 <i class="fas fa-star text-warning"></i></div>
                                                <div class="progress flex-grow-1" style="height: 10px;">
                                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $feedback_summary['two_star_percent']; ?>%" aria-valuenow="<?php echo $feedback_summary['two_star_percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="rating-percent ms-2"><?php echo $feedback_summary['two_star_percent']; ?>%</div>
                                            </div>
                                            <div class="rating-bar d-flex align-items-center">
                                                <div class="rating-label me-2">1 <i class="fas fa-star text-warning"></i></div>
                                                <div class="progress flex-grow-1" style="height: 10px;">
                                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $feedback_summary['one_star_percent']; ?>%" aria-valuenow="<?php echo $feedback_summary['one_star_percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <div class="rating-percent ms-2"><?php echo $feedback_summary['one_star_percent']; ?>%</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Feedback List -->
                            <?php if (!empty($feedback)): ?>
                                <div class="feedback-list">
                                    <?php foreach ($feedback as $item): ?>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['username']); ?></h6>
                                                        <div class="stars">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <?php if ($i <= $item['rating']): ?>
                                                                    <i class="fas fa-star text-warning"></i>
                                                                <?php else: ?>
                                                                    <i class="far fa-star text-warning"></i>
                                                                <?php endif; ?>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></small>
                                                </div>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($item['comment'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    No feedback has been left for this auction yet.
                                </div>
                            <?php endif; ?>
                            
                            <!-- Feedback Form -->
                            <?php if ($auction['status'] == 'ended' && $user_id && $user_id != $auction['user_id']): ?>
                                <?php 
                                // Check if user has already left feedback
                                $has_feedback = false;
                                foreach ($feedback as $item) {
                                    if ($item['user_id'] == $user_id) {
                                        $has_feedback = true;
                                        break;
                                    }
                                }
                                
                                // Check if user has bid on this auction
                                $has_bid = false;
                                foreach ($bids as $bid) {
                                    if ($bid['user_id'] == $user_id) {
                                        $has_bid = true;
                                        break;
                                    }
                                }
                                ?>
                                
                                <?php if (!$has_feedback && $has_bid): ?>
                                    <div class="card mt-4">
                                        <div class="card-header bg-white">
                                            <h5 class="mb-0">Leave Feedback</h5>
                                        </div>
                                        <div class="card-body">
                                            <form method="post" action="" id="feedback-form">
                                                <div class="mb-3">
                                                    <label class="form-label">Rating</label>
                                                    <div class="rating-input">
                                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo isset($_POST['rating']) && $_POST['rating'] == $i ? 'checked' : ''; ?> required>
                                                            <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="comment" class="form-label">Comment</label>
                                                    <textarea class="form-control" id="comment" name="comment" rows="3" required><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                                                </div>
                                                
                                                <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Feedback</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php elseif ($has_feedback): ?>
                                    <div class="alert alert-info mt-4">
                                        You have already left feedback for this auction.
                                    </div>
                                <?php elseif (!$has_bid): ?>
                                    <div class="alert alert-info mt-4">
                                        Only users who have bid on this auction can leave feedback.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Similar Auctions -->
    <?php if (!empty($similar_auctions)): ?>
        <div class="row">
            <div class="col-12">
                <h3 class="section-title mb-4">Similar Auctions</h3>
                <div class="row">
                    <?php foreach ($similar_auctions as $similar): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card auction-card h-100 shadow-sm">
                                <div class="auction-card-img">
                                    <?php 
                                    $similar_image = get_auction_images($similar['id']);
                                    $image_url = !empty($similar_image) ? 'uploads/' . $similar_image[0]['image_path'] : 'assets/images/no-image.jpg';
                                    ?>
                                    <img src="<?php echo $image_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($similar['title']); ?>">
                                    <span class="badge bg-<?php echo get_status_color($similar['status']); ?> auction-status">
                                        <?php echo ucfirst($similar['status']); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="auction.php?id=<?php echo $similar['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars(substr($similar['title'], 0, 50) . (strlen($similar['title']) > 50 ? '...' : '')); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted small">
                                        <?php echo htmlspecialchars(substr($similar['description'], 0, 100) . (strlen($similar['description']) > 100 ? '...' : '')); ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">$<?php echo number_format($similar['current_price'], 2); ?></span>
                                        <?php if ($similar['status'] == 'active'): ?>
                                            <small class="text-muted countdown" data-end="<?php echo strtotime($similar['end_date']); ?>"></small>
                                        <?php else: ?>
                                            <small class="text-muted">Ended</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>