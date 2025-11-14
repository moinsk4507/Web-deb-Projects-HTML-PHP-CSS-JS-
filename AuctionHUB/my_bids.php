<?php
// Include configuration file
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Set redirect after login
    $_SESSION['redirect_after_login'] = 'my_bids.php';
    // Redirect to login page
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Get user's bids
$bids = get_user_bids($user_id, $status, $sort);

// Count bids by status
$active_count = 0;
$winning_count = 0;
$outbid_count = 0;
$won_count = 0;
$lost_count = 0;

foreach ($bids as $bid) {
    if ($bid['auction_status'] == 'active') {
        if ($bid['is_highest']) {
            $winning_count++;
        } else {
            $outbid_count++;
        }
        $active_count++;
    } elseif ($bid['auction_status'] == 'ended') {
        if ($bid['is_highest']) {
            $won_count++;
        } else {
            $lost_count++;
        }
    }
}

$total_count = count($bids);

// Set page title
$page_title = 'My Bids';

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2">My Bids</h1>
            <p class="text-muted">
                Track your bidding activity and auction status
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="browse.php" class="btn btn-primary">
                <i class="fas fa-search me-2"></i> Find Auctions to Bid
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Filters</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="my_bids.php?status=all" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status == 'all' ? 'active' : ''; ?>">
                            All Bids
                            <span class="badge bg-primary rounded-pill"><?php echo $total_count; ?></span>
                        </a>
                        <a href="my_bids.php?status=active" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status == 'active' ? 'active' : ''; ?>">
                            Active Auctions
                            <span class="badge bg-info rounded-pill"><?php echo $active_count; ?></span>
                        </a>
                        <a href="my_bids.php?status=winning" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status == 'winning' ? 'active' : ''; ?>">
                            Currently Winning
                            <span class="badge bg-success rounded-pill"><?php echo $winning_count; ?></span>
                        </a>
                        <a href="my_bids.php?status=outbid" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status == 'outbid' ? 'active' : ''; ?>">
                            Outbid
                            <span class="badge bg-warning rounded-pill"><?php echo $outbid_count; ?></span>
                        </a>
                        <a href="my_bids.php?status=won" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status == 'won' ? 'active' : ''; ?>">
                            Won
                            <span class="badge bg-success rounded-pill"><?php echo $won_count; ?></span>
                        </a>
                        <a href="my_bids.php?status=lost" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status == 'lost' ? 'active' : ''; ?>">
                            Lost
                            <span class="badge bg-danger rounded-pill"><?php echo $lost_count; ?></span>
                        </a>
                    </div>
                    
                    <hr>
                    
                    <form action="my_bids.php" method="get">
                        <?php if ($status != 'all'): ?>
                            <input type="hidden" name="status" value="<?php echo $status; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Sort By</label>
                            <select name="sort" class="form-select" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="ending" <?php echo $sort == 'ending' ? 'selected' : ''; ?>>Ending Soon</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Bid Amount: High to Low</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Bid Amount: Low to High</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Bids List -->
        <div class="col-lg-9">
            <?php if (empty($bids)): ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">No bids found!</h4>
                    <p>
                        <?php if ($status == 'all'): ?>
                            You haven't placed any bids yet.
                        <?php else: ?>
                            You don't have any <?php echo $status; ?> bids.
                        <?php endif; ?>
                    </p>
                    <hr>
                    <p class="mb-0">
                        <a href="browse.php" class="alert-link">Browse auctions</a> to find items to bid on!
                    </p>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Auction</th>
                                    <th>Your Bid</th>
                                    <th>Current Price</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bids as $bid): ?>
                                    <tr>
                                        <td>
                                            <a href="auction.php?id=<?php echo $bid['auction_id']; ?>" class="text-decoration-none fw-bold">
                                                <?php echo htmlspecialchars(substr($bid['auction_title'], 0, 50) . (strlen($bid['auction_title']) > 50 ? '...' : '')); ?>
                                            </a>
                                            <div class="small text-muted">
                                                Seller: <?php echo htmlspecialchars($bid['seller_username']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            ₹<?php echo number_format($bid['amount'], 2); ?>
                                            <div class="small text-muted">
                                                <?php echo date('M j, Y, g:i a', strtotime($bid['bid_date'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            ₹<?php echo number_format($bid['current_price'], 2); ?>
                                            <?php if ($bid['reserve_price'] > 0): ?>
                                                <?php if ($bid['current_price'] >= $bid['reserve_price']): ?>
                                                    <span class="badge bg-success">Reserve Met</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Reserve Not Met</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y, g:i a', strtotime($bid['end_date'])); ?>
                                            <?php if ($bid['auction_status'] == 'active'): ?>
                                                <div class="small countdown" data-end="<?php echo strtotime($bid['end_date']); ?>"></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($bid['auction_status'] == 'active'): ?>
                                                <?php if ($bid['is_highest']): ?>
                                                    <span class="badge bg-success">Winning</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Outbid</span>
                                                <?php endif; ?>
                                            <?php elseif ($bid['auction_status'] == 'ended'): ?>
                                                <?php if ($bid['is_highest']): ?>
                                                    <span class="badge bg-success">Won</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Lost</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Cancelled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="auction.php?id=<?php echo $bid['auction_id']; ?>" class="btn btn-outline-primary" title="View Auction">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($bid['auction_status'] == 'active'): ?>
                                                    <a href="auction.php?id=<?php echo $bid['auction_id']; ?>#bid-form" class="btn btn-outline-success" title="Place New Bid">
                                                        <i class="fas fa-gavel"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ((isset($bid['in_watchlist']) ? !$bid['in_watchlist'] : true) && $bid['auction_status'] == 'active'): ?>
                                                    <a href="add_to_watchlist.php?id=<?php echo $bid['auction_id']; ?>&redirect=my_bids.php" class="btn btn-outline-secondary" title="Add to Watchlist">
                                                        <i class="far fa-heart"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($bid['auction_status'] == 'ended' && $bid['is_highest']): ?>
                                                    <a href="contact_seller.php?auction=<?php echo $bid['auction_id']; ?>" class="btn btn-outline-info" title="Contact Seller">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($bid['auction_status'] == 'active'): ?>
                                                    <a href="contact_seller.php?auction=<?php echo $bid['auction_id']; ?>" class="btn btn-outline-primary" title="Contact Seller">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>