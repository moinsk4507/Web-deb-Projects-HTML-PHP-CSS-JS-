<?php
// Include configuration file
require_once 'includes/config.php';

// Helper function to safely escape values
function safe_escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Initialize variables
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12; // Items per page
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$condition = isset($_GET['condition']) ? $_GET['condition'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'active';

// Get all categories for filter
$categories = get_all_categories();

// Get auctions based on filters
$search_result = search_auctions($search, $category_id, $sort, $min_price, $max_price, $condition, $status, $limit, $offset);

// Extract auctions and pagination info from search result
$auctions = isset($search_result['auctions']) ? $search_result['auctions'] : [];
$total_auctions = isset($search_result['pagination']['total']) ? $search_result['pagination']['total'] : 0;
$total_pages = isset($search_result['pagination']['total_pages']) ? $search_result['pagination']['total_pages'] : 0;

// Set page title
$page_title = 'Browse Auctions';
if (!empty($search)) {
    $page_title = 'Search Results for "' . safe_escape($search) . '"';
} elseif ($category_id > 0) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $category_id) {
            $page_title = safe_escape($cat['name']) . ' Auctions';
            break;
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2"><?php echo $page_title; ?></h1>
            <p class="text-muted">
                <?php echo $total_auctions; ?> auction<?php echo $total_auctions != 1 ? 's' : ''; ?> found
            </p>
        </div>
        <div class="col-md-4">
            <form action="browse.php" method="get" class="d-flex">
                <?php if ($category_id): ?>
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                <?php endif; ?>
                <input type="text" name="search" class="form-control" placeholder="Search auctions..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary ms-2"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
    
    <!-- Filters and Results -->
    <div class="row">
        <!-- Filters Sidebar (hidden; use offcanvas instead) -->
        <div class="col-lg-3 mb-4 d-none">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Filters</h5>
                </div>
                <div class="card-body">
                    <form action="browse.php" method="get" id="filter-form">
                        <!-- Preserve search query if exists -->
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        
                        <!-- Categories -->
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select" onchange="this.form.submit()">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo safe_escape($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="mb-3">
                            <label class="form-label">Price Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Item Condition -->
                        <div class="mb-3">
                            <label class="form-label">Item Condition</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="condition" id="condition-all" value="" <?php echo $condition === '' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="condition-all">All</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="condition" id="condition-new" value="New" <?php echo $condition === 'New' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="condition-new">New</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="condition" id="condition-used" value="Used" <?php echo $condition === 'Used' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="condition-used">Used</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="condition" id="condition-refurbished" value="Refurbished" <?php echo $condition === 'Refurbished' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="condition-refurbished">Refurbished</label>
                            </div>
                        </div>
                        
                        <!-- Auction Status -->
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status-active" value="active" <?php echo $status === 'active' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status-active">Active</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status-ended" value="ended" <?php echo $status === 'ended' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status-ended">Ended</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status-all" value="all" <?php echo $status === 'all' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status-all">All</label>
                            </div>
                        </div>
                        
                        <!-- Sort Order -->
                        <div class="mb-3">
                            <label class="form-label">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="ending" <?php echo $sort === 'ending' ? 'selected' : ''; ?>>Ending Soon</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="bids" <?php echo $sort === 'bids' ? 'selected' : ''; ?>>Most Bids</option>
                            </select>
                        </div>
                        
                        <!-- Apply Filters Button -->
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        
                        <!-- Reset Filters -->
                        <a href="browse.php" class="btn btn-outline-secondary w-100 mt-2">Reset Filters</a>
                    </form>
                </div>
            </div>
        </div>
        
         <!-- Auction Results -->
         <div class="col-12">
            <?php if (!empty($auctions)): ?>
                <div class="row">
                    <?php foreach ($auctions as $auction): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card auction-card h-100 shadow-sm">
                                <div class="auction-card-img">
                                    <?php 
                                    $auctionId = isset($auction['id']) ? (int)$auction['id'] : 0;
                                    $auction_images = $auctionId > 0 ? get_auction_images($auctionId) : [];
                                    $first = !empty($auction_images) ? $auction_images[0] : null;
                                    $imgRel = $first ? ($first['image_path'] ?? '') : '';
                                    $image_url = $imgRel !== '' ? 'uploads/' . $imgRel : 'assets/images/no-image.jpg';
                                    ?>
                                    <img src="<?php echo $image_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($auction['title'] ?? ''); ?>">
                                    <?php $statusValue = $auction['status'] ?? 'ended'; ?>
                                    <span class="badge bg-<?php echo get_status_color($statusValue); ?> auction-status">
                                        <?php echo is_int($statusValue) ? ($statusValue==AUCTION_STATUS_ACTIVE?'Active':($statusValue==AUCTION_STATUS_ENDED?'Ended':'Cancelled')) : ucfirst($statusValue); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <?php 
                                        // Normalize fields defensively
                                        $statusValue = $auction['status'] ?? AUCTION_STATUS_ENDED;
                                        $titleSafe = (string) ($auction['title'] ?? '');
                                        $descSafe = (string) ($auction['description'] ?? '');
                                        $categorySafe = (string) ($auction['category_name'] ?? '');
                                        $bidCount = (int) ($auction['bid_count'] ?? 0);
                                        $price = (float) ($auction['current_price'] ?? ($auction['starting_price'] ?? 0));
                                    ?>
                                    <h5 class="card-title">
                                        <a href="auction.php?id=<?php echo $auctionId; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars(substr($titleSafe, 0, 50) . (strlen($titleSafe) > 50 ? '...' : '')); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted small">
                                        <?php echo htmlspecialchars(substr($descSafe, 0, 100) . (strlen($descSafe) > 100 ? '...' : '')); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($categorySafe); ?></span>
                                        <span class="badge bg-light text-dark"><?php echo $bidCount; ?> bid<?php echo $bidCount != 1 ? 's' : ''; ?></span>
                                    </div>
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">₹<?php echo number_format($price, 2); ?></span>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ((isset($auction['status']) && ($auction['status'] == 'active' || $auction['status'] == AUCTION_STATUS_ACTIVE)) && isset($auction['end_date'])): ?>
                                                <small class="text-muted countdown" data-end="<?php echo strtotime($auction['end_date']); ?>"></small>
                                            <?php else: ?>
                                                <small class="text-muted">Ended</small>
                                            <?php endif; ?>
                                            <a href="auction.php?id=<?php echo $auctionId; ?>" class="btn btn-sm btn-primary">View</a>
                                            <?php if (($statusValue == 'active') || ($statusValue == AUCTION_STATUS_ACTIVE)): ?>
                                                <a href="auction.php?id=<?php echo $auctionId; ?>#bid-form" class="btn btn-sm btn-outline-primary">Place Bid</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Auction pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo get_pagination_url($page - 1); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&laquo;</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            // Calculate range of page numbers to display
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            if ($end_page - $start_page < 4 && $start_page > 1) {
                                $start_page = max(1, $end_page - 4);
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo get_pagination_url($i); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo get_pagination_url($page + 1); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">No auctions found!</h4>
                    <p>We couldn't find any auctions matching your criteria. Try adjusting your filters or search terms.</p>
                    <hr>
                    <p class="mb-0">
                        <a href="browse.php" class="alert-link">View all auctions</a> or 
                        <a href="create_auction.php" class="alert-link">create your own auction</a>.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Helper function to generate pagination URLs
function get_pagination_url($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return 'browse.php?' . http_build_query($params);
}

// Include footer
include 'includes/footer.php';
?>