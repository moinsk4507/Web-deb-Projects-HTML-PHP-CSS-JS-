<?php
// Include configuration file
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Set redirect after login
    $_SESSION['redirect_after_login'] = 'my_auctions.php';
    // Redirect to login page
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Process auction deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $auction_id = intval($_GET['delete']);
    
    // Check if auction belongs to user
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction || $auction['user_id'] != $user_id) {
        $error = 'You do not have permission to delete this auction.';
    } else if ($auction['status'] != 'active' || count(get_auction_bids($auction_id)) > 0) {
        $error = 'You cannot delete an auction that has bids or has already ended.';
    } else {
        // Delete auction
        $delete_result = delete_auction($auction_id);
        
        if ($delete_result['success']) {
            $success = $delete_result['message'];
        } else {
            $error = $delete_result['message'];
        }
    }
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Get user's auctions
$auctions = get_user_auctions($user_id, $status, $sort);

// Count auctions by status
$active_count = 0;
$ended_count = 0;
$cancelled_count = 0;

foreach ($auctions as $auction) {
    if ($auction['status'] == 'active') {
        $active_count++;
    } elseif ($auction['status'] == 'ended') {
        $ended_count++;
    } elseif ($auction['status'] == 'cancelled') {
        $cancelled_count++;
    }
}

$total_count = count($auctions);

// Set page title
$page_title = 'My Auctions';

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2">My Auctions</h1>
            <p class="text-muted">
                Manage your auction listings
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="create_auction.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i> Create New Auction
            </a>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Filters</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="my_auctions.php?status=all" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status == 'all' ? 'active' : ''; ?>">
                            All Auctions
                            <span class="badge bg-primary rounded-pill"><?php echo $total_count; ?></span>
                        </a>
                        <a href="my_auctions.php?status=active" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status == 'active' ? 'active' : ''; ?>">
                            Active
                            <span class="badge bg-success rounded-pill"><?php echo $active_count; ?></span>
                        </a>
                        <a href="my_auctions.php?status=ended" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status == 'ended' ? 'active' : ''; ?>">
                            Ended
                            <span class="badge bg-secondary rounded-pill"><?php echo $ended_count; ?></span>
                        </a>
                        <a href="my_auctions.php?status=cancelled" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status == 'cancelled' ? 'active' : ''; ?>">
                            Cancelled
                            <span class="badge bg-danger rounded-pill"><?php echo $cancelled_count; ?></span>
                        </a>
                    </div>
                    
                    <hr>
                    
                    <form action="my_auctions.php" method="get">
                        <?php if ($status != 'all'): ?>
                            <input type="hidden" name="status" value="<?php echo $status; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Sort By</label>
                            <select name="sort" class="form-select" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="ending" <?php echo $sort == 'ending' ? 'selected' : ''; ?>>Ending Soon</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="bids" <?php echo $sort == 'bids' ? 'selected' : ''; ?>>Most Bids</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Auctions List -->
        <div class="col-lg-9">
            <?php if (empty($auctions)): ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">No auctions found!</h4>
                    <p>
                        <?php if ($status == 'all'): ?>
                            You haven't created any auctions yet.
                        <?php else: ?>
                            You don't have any <?php echo $status; ?> auctions.
                        <?php endif; ?>
                    </p>
                    <hr>
                    <p class="mb-0">
                        <a href="create_auction.php" class="alert-link">Create your first auction</a> to start selling!
                    </p>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Current Price</th>
                                    <th>Bids</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auctions as $auction): ?>
                                    <?php 
                                    $bids = get_auction_bids($auction['id']);
                                    $bid_count = count($bids);
                                    $highest_bid = $bid_count > 0 ? $bids[0]['amount'] : $auction['starting_price'];
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="auction.php?id=<?php echo $auction['id']; ?>" class="text-decoration-none fw-bold">
                                                <?php echo htmlspecialchars(substr($auction['title'], 0, 50) . (strlen($auction['title']) > 50 ? '...' : '')); ?>
                                            </a>
                                            <div class="small text-muted">
                                                ID: #<?php echo $auction['id']; ?> | Category: <?php echo htmlspecialchars($auction['category_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            ₹<?php echo number_format($highest_bid, 2); ?>
                                            <?php if ($auction['reserve_price'] > 0): ?>
                                                <?php if ($highest_bid >= $auction['reserve_price']): ?>
                                                    <span class="badge bg-success">Reserve Met</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Reserve Not Met</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $bid_count; ?></td>
                                        <td>
                                            <?php echo date('M j, Y, g:i a', strtotime($auction['end_date'])); ?>
                                            <?php if ($auction['status'] == 'active'): ?>
                                                <div class="small countdown" data-end="<?php echo strtotime($auction['end_date']); ?>"></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo get_status_color($auction['status']); ?>">
                                                <?php echo ucfirst($auction['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="auction.php?id=<?php echo $auction['id']; ?>" class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($auction['status'] == 'active' && $bid_count == 0): ?>
                                                <a href="auction_edit.php?id=<?php echo $auction['id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <a href="#" class="btn btn-outline-danger" title="Delete" 
                                                       onclick="confirmDelete(<?php echo $auction['id']; ?>, '<?php echo addslashes(htmlspecialchars($auction['title'])); ?>'); return false;">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($auction['status'] == 'active'): ?>
                                                    <a href="#" class="btn btn-outline-warning" title="Cancel Auction" 
                                                       onclick="confirmCancel(<?php echo $auction['id']; ?>, '<?php echo addslashes(htmlspecialchars($auction['title'])); ?>'); return false;">
                                                        <i class="fas fa-ban"></i>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the auction <strong id="deleteAuctionTitle"></strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteAuctionBtn" class="btn btn-danger">Delete Auction</a>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Auction Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Confirm Cancellation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel the auction <strong id="cancelAuctionTitle"></strong>?</p>
                <p class="text-warning">This will end the auction immediately and notify all bidders.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Active</button>
                <a href="#" id="cancelAuctionBtn" class="btn btn-warning">Yes, Cancel Auction</a>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(auctionId, auctionTitle) {
        document.getElementById('deleteAuctionTitle').textContent = auctionTitle;
        document.getElementById('deleteAuctionBtn').href = 'my_auctions.php?delete=' + auctionId;
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
    
    function confirmCancel(auctionId, auctionTitle) {
        document.getElementById('cancelAuctionTitle').textContent = auctionTitle;
        document.getElementById('cancelAuctionBtn').href = 'cancel_auction.php?id=' + auctionId;
        var cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
        cancelModal.show();
    }
</script>

<?php
// Include footer
include 'includes/footer.php';
?>