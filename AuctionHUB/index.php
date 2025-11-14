<?php
/**
 * Homepage for the Online Auction System
 * 
 * This file displays the homepage with featured auctions, categories,
 * ending soon auctions, and recently added auctions.
 */

// Include configuration and functions
require_once 'includes/config.php';

// Helper function to safely escape values
function safe_escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
require_once 'includes/functions/auction_functions.php';

// Get featured auctions
$featured_auctions = get_featured_auctions(8);

// Get ending soon auctions
$ending_soon = get_ending_soon_auctions(4);

// Get recently added auctions
$recent_auctions = get_recently_added_auctions(4);

// Get all categories with auction counts
$categories = get_all_categories_with_counts();

// Include header
include_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1>Discover Deals on Everything You Love</h1>
                <p class="lead">From electronics to collectibles, fashion to home & garden — bid, win, and save on AuctionHUB.</p>
                <div class="hero-buttons">
                    <a href="browse.php" class="btn btn-primary btn-lg">Browse Auctions</a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-outline-primary btn-lg">Register Now</a>
                    <?php else: ?>
                        <a href="create_auction.php" class="btn btn-outline-primary btn-lg">Sell an Item</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="assets/images/hero-image.svg" alt="Auction marketplace" class="img-fluid hero-image">
            </div>
        </div>
    </div>
</section>

<!-- Featured Auctions Section (moved above categories) -->
<section class="featured-auctions-section py-5 bg-light">
    <div class="container">
        <div class="section-title">
            <h2>Featured Auctions</h2>
            <p>Handpicked items you might love</p>
        </div>
        <div class="row">
            <?php if (!empty($featured_auctions)): ?>
                <?php foreach ($featured_auctions as $auction): ?>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card auction-card h-100">
                            <div class="auction-card-img">
                                <?php if (!empty($auction['image_path'])): ?>
                                    <img src="uploads/<?php echo safe_escape($auction['image_path']); ?>" class="card-img-top" alt="<?php echo safe_escape($auction['title']); ?>">
                                <?php else: ?>
                                    <img src="assets/images/no-image.jpg" class="card-img-top" alt="No Image Available">
                                <?php endif; ?>
                                <?php if ($auction['status'] === 'active'): ?>
                                    <div class="auction-badge badge bg-success">Active</div>
                                <?php elseif ($auction['status'] === 'ended'): ?>
                                    <div class="auction-badge badge bg-danger">Ended</div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="auction.php?id=<?php echo $auction['id']; ?>"><?php echo safe_escape($auction['title']); ?></a>
                                </h5>
                                <p class="card-text auction-price">Current Bid: ₹<?php echo number_format($auction['current_price'], 2); ?></p>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <?php if ($auction['status'] === 'active'): ?>
                                            <span class="countdown" data-end="<?php echo $auction['end_date']; ?>">Loading...</span>
                                        <?php else: ?>
                                            Ended: <?php echo date('M j, Y', strtotime($auction['end_date'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </p>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="auction.php?id=<?php echo $auction['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                <?php if ($auction['status'] === 'active'): ?>
                                    <a href="auction.php?id=<?php echo $auction['id']; ?>#bid-form" class="btn btn-sm btn-outline-primary">Place Bid</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No featured auctions available at the moment.</div>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="browse.php?featured=1" class="btn btn-outline-primary">View All Featured Auctions</a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section py-5">
    <div class="container">
        <div class="section-title">
            <h2>Popular Categories</h2>
            <p>Browse auctions by category</p>
        </div>
        <div class="row">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <div class="col-6 col-md-3 col-lg-2 mb-4">
                        <a href="browse.php?category=<?php echo $category['id']; ?>" class="category-card">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-tag'); ?> category-icon"></i>
                                    <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                                    <p class="card-text"><?php echo $category['auction_count']; ?> auctions</p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No categories found.</div>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="browse.php" class="btn btn-outline-primary">View All Categories</a>
        </div>
    </div>
</section>

<!-- (Removed duplicate Featured Auctions section below categories) -->

<!-- How It Works Section -->
<section class="how-it-works-section py-5">
    <div class="container">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Simple steps to start bidding or selling</p>
        </div>
        <div class="row">
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="how-it-works-item text-center">
                    <div class="how-icon">
                        <i class="fas fa-user-plus"></i>
                        <span class="step-number">1</span>
                    </div>
                    <h4>Create an Account</h4>
                    <p>Sign up for free and set up your profile to start your auction journey.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="how-it-works-item text-center">
                    <div class="how-icon">
                        <i class="fas fa-search"></i>
                        <span class="step-number">2</span>
                    </div>
                    <h4>Find Items</h4>
                    <p>Browse categories or search for specific items you're interested in.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="how-it-works-item text-center">
                    <div class="how-icon">
                        <i class="fas fa-gavel"></i>
                        <span class="step-number">3</span>
                    </div>
                    <h4>Place Bids</h4>
                    <p>Bid on items you want and keep track of your active auctions.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="how-it-works-item text-center">
                    <div class="how-icon">
                        <i class="fas fa-trophy"></i>
                        <span class="step-number">4</span>
                    </div>
                    <h4>Win & Pay</h4>
                    <p>If you win, complete the payment and arrange delivery with the seller.</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-5">
            <a href="register.php" class="btn btn-primary">Get Started Now</a>
        </div>
    </div>
</section>

<!-- Two Column Section: Ending Soon & Recently Added -->
<section class="two-column-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <!-- Ending Soon Auctions -->
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="section-title">
                    <h2>Ending Soon</h2>
                    <p>Don't miss these opportunities</p>
                </div>
                <?php if (!empty($ending_soon)): ?>
                    <div class="list-group auction-list">
                        <?php foreach ($ending_soon as $auction): ?>
                            <a href="auction.php?id=<?php echo $auction['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div class="auction-list-img">
                                        <?php if (!empty($auction['image_path'])): ?>
                                            <img src="uploads/<?php echo safe_escape($auction['image_path']); ?>" alt="<?php echo safe_escape($auction['title']); ?>">
                                        <?php else: ?>
                                            <img src="assets/images/no-image.jpg" alt="No Image Available">
                                        <?php endif; ?>
                                    </div>
                                    <div class="auction-list-content">
                                        <h5 class="mb-1"><?php echo safe_escape($auction['title']); ?></h5>
                                        <p class="mb-1">Current Bid: ₹<?php echo number_format($auction['current_price'], 2); ?></p>
                                    </div>
                                    <div class="auction-list-time">
                                        <span class="countdown" data-end="<?php echo $auction['end_date']; ?>">Loading...</span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="browse.php?sort=ending_soon" class="btn btn-outline-primary btn-sm">View All Ending Soon</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No auctions ending soon.</div>
                <?php endif; ?>
            </div>
            
            <!-- Recently Added Auctions -->
            <div class="col-lg-6">
                <div class="section-title">
                    <h2>Recently Added</h2>
                    <p>Check out the newest listings</p>
                </div>
                <?php if (!empty($recent_auctions)): ?>
                    <div class="list-group auction-list">
                        <?php foreach ($recent_auctions as $auction): ?>
                            <a href="auction.php?id=<?php echo $auction['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div class="auction-list-img">
                                        <?php if (!empty($auction['image_path'])): ?>
                                            <img src="uploads/<?php echo safe_escape($auction['image_path']); ?>" alt="<?php echo safe_escape($auction['title']); ?>">
                                        <?php else: ?>
                                            <img src="assets/images/no-image.jpg" alt="No Image Available">
                                        <?php endif; ?>
                                    </div>
                                    <div class="auction-list-content">
                                        <h5 class="mb-1"><?php echo safe_escape($auction['title']); ?></h5>
                                        <p class="mb-1">Current Bid: ₹<?php echo number_format($auction['current_price'], 2); ?></p>
                                    </div>
                                    <div class="auction-list-time">
                                        <small class="text-muted">Added <?php echo time_elapsed_string($auction['created_at']); ?></small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="browse.php?sort=newest" class="btn btn-outline-primary btn-sm">View All New Listings</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No recent auctions available.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>



<!-- Call to Action Section -->
<section class="cta-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2>Ready to Start Selling?</h2>
                <p class="lead">List your items for auction and reach thousands of potential buyers.</p>
                <div class="mt-4">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-primary btn-lg">Register Now</a>
                        <a href="login.php" class="btn btn-outline-primary btn-lg">Login</a>
                    <?php else: ?>
                        <a href="create_auction.php" class="btn btn-primary btn-lg">Create an Auction</a>
                        <a href="about.php" class="btn btn-outline-primary btn-lg">Learn More</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_once 'includes/footer.php';
?>