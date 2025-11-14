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

// Process watchlist actions
if (isset($_GET['action']) && isset($_GET['auction_id']) && is_numeric($_GET['auction_id'])) {
    $auction_id = intval($_GET['auction_id']);
    $action = $_GET['action'];
    
    if ($action === 'remove') {
        // Remove auction from watchlist
        $result = remove_from_watchlist($user_id, $auction_id);
        
        if ($result['success']) {
            $success = "Auction removed from your watchlist.";
        } else {
            $error = $result['message'];
        }
    }
}

// Get watchlist items with pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Get sort parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'added_desc';

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get watchlist items
$watchlist_items = get_user_watchlist($user_id, $offset, $items_per_page, $sort, $filter);
$total_items = count_user_watchlist($user_id, $filter);

// Calculate total pages
$total_pages = ceil($total_items / $items_per_page);

// Set page title
$page_title = 'My Watchlist';

// Include header
include 'includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">My Watchlist</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Saved Auctions</h5>
                    <div class="d-flex">
                        <!-- Filter Dropdown -->
                        <div class="dropdown me-2">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php 
                                switch ($filter) {
                                    case 'active':
                                        echo 'Active Auctions';
                                        break;
                                    case 'ending_soon':
                                        echo 'Ending Soon';
                                        break;
                                    case 'ended':
                                        echo 'Ended Auctions';
                                        break;
                                    default:
                                        echo 'All Auctions';
                                }
                                ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                <li><a class="dropdown-item <?php echo $filter === 'all' ? 'active' : ''; ?>" href="?filter=all<?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : ''; ?>">All Auctions</a></li>
                                <li><a class="dropdown-item <?php echo $filter === 'active' ? 'active' : ''; ?>" href="?filter=active<?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : ''; ?>">Active Auctions</a></li>
                                <li><a class="dropdown-item <?php echo $filter === 'ending_soon' ? 'active' : ''; ?>" href="?filter=ending_soon<?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : ''; ?>">Ending Soon</a></li>
                                <li><a class="dropdown-item <?php echo $filter === 'ended' ? 'active' : ''; ?>" href="?filter=ended<?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : ''; ?>">Ended Auctions</a></li>
                            </ul>
                        </div>
                        
                        <!-- Sort Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php 
                                switch ($sort) {
                                    case 'added_asc':
                                        echo 'Added (Oldest First)';
                                        break;
                                    case 'price_asc':
                                        echo 'Price (Low to High)';
                                        break;
                                    case 'price_desc':
                                        echo 'Price (High to Low)';
                                        break;
                                    case 'ending_soon':
                                        echo 'Ending Soon';
                                        break;
                                    default:
                                        echo 'Added (Newest First)';
                                }
                                ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                                <li><a class="dropdown-item <?php echo $sort === 'added_desc' ? 'active' : ''; ?>" href="?sort=added_desc<?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>">Added (Newest First)</a></li>
                                <li><a class="dropdown-item <?php echo $sort === 'added_asc' ? 'active' : ''; ?>" href="?sort=added_asc<?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>">Added (Oldest First)</a></li>
                                <li><a class="dropdown-item <?php echo $sort === 'price_asc' ? 'active' : ''; ?>" href="?sort=price_asc<?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>">Price (Low to High)</a></li>
                                <li><a class="dropdown-item <?php echo $sort === 'price_desc' ? 'active' : ''; ?>" href="?sort=price_desc<?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>">Price (High to Low)</a></li>
                                <li><a class="dropdown-item <?php echo $sort === 'ending_soon' ? 'active' : ''; ?>" href="?sort=ending_soon<?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>">Ending Soon</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($watchlist_items)): ?>
                    <div class="card-body text-center py-5">
                        <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                        <h5>Your watchlist is empty</h5>
                        <p class="text-muted">You haven't added any auctions to your watchlist yet.</p>
                        <a href="browse.php" class="btn btn-primary">Browse Auctions</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Auction</th>
                                    <th>Current Price</th>
                                    <th>Your Max Bid</th>
                                    <th>Bids</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($watchlist_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                $auction_image = get_auction_main_image($item['auction_id']);
                                                $image_url = !empty($auction_image) ? 'uploads/auctions/' . $auction_image : 'assets/img/no-image.jpg';
                                                ?>
                                                <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <a href="auction.php?id=<?php echo $item['auction_id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </a>
                                                    <div class="small text-muted">Added on <?php echo date('M j, Y', strtotime($item['added_on'])); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold">₹<?php echo number_format($item['current_price'], 2); ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $user_max_bid = get_user_max_bid($user_id, $item['auction_id']);
                                            if ($user_max_bid > 0) {
                                                echo '<span class="fw-bold">₹' . number_format($user_max_bid, 2) . '</span>';
                                            } else {
                                                echo '<span class="text-muted">No bids</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $item['bid_count']; ?>
                                        </td>
                                        <td>
                                            <?php if ($item['status'] === 'active'): ?>
                                                <div class="d-flex align-items-center">
                                                    <span class="countdown" data-end="<?php echo $item['end_date']; ?>"></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted"><?php echo date('M j, Y', strtotime($item['end_date'])); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo get_status_color($item['status']); ?>">
                                                <?php echo ucfirst($item['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="auction.php?id=<?php echo $item['auction_id']; ?>" class="btn btn-outline-primary" title="View Auction">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($item['status'] === 'active'): ?>
                                                    <a href="auction.php?id=<?php echo $item['auction_id']; ?>#bid-form" class="btn btn-outline-success" title="Place Bid">
                                                        <i class="fas fa-gavel"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="watchlist.php?action=remove&auction_id=<?php echo $item['auction_id']; ?>" class="btn btn-outline-danger" title="Remove from Watchlist" onclick="return confirm('Are you sure you want to remove this auction from your watchlist?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer bg-white">
                            <nav aria-label="Watchlist pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : ''; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : ''; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : ''; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="browse.php" class="btn btn-primary">Browse More Auctions</a>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>