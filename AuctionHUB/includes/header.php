<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . $site_name : $site_name; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <?php if (basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
    <!-- Homepage CSS -->
    <link rel="stylesheet" href="assets/css/homepage.css">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/logo-icon.svg" type="image/svg+xml">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <nav class="navbar navbar-expand-lg navbar-dark" style="background:#0052CC;">
            <div class="container">
                <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#filtersOffcanvas" aria-controls="filtersOffcanvas" title="Filters">
                    <i class="fas fa-ellipsis-vertical"></i>
                </button>
                <a class="navbar-brand d-flex align-items-center" href="index.php">
                    <img src="assets/images/logo-icon.svg" alt="AuctionHUB" height="28" class="me-2">
                    <strong>AuctionHUB</strong>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? ' active' : ''; ?>" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo (basename($_SERVER['PHP_SELF']) == 'browse.php') ? ' active' : ''; ?>" href="browse.php">Browse Auctions</a>
                        </li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link<?php echo (basename($_SERVER['PHP_SELF']) == 'create_auction.php') ? ' active' : ''; ?>" href="create_auction.php">Create Auction</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['about.php','contact.php','help.php'])) ? ' active' : ''; ?>" href="#" id="helpDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Help</a>
                            <ul class="dropdown-menu" aria-labelledby="helpDropdown">
                                <li><a class="dropdown-item" href="about.php">About AuctionHUB</a></li>
                                <li><a class="dropdown-item" href="contact.php">Contact Us</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="help.php#buying-basics">Buying basics</a></li>
                                <li><a class="dropdown-item" href="help.php#selling-basics">Selling basics</a></li>
                                <li><a class="dropdown-item" href="help.php#bidding-payments">Bidding & payments</a></li>
                                <li><a class="dropdown-item" href="help.php#shipping-returns">Shipping & returns</a></li>
                                <li><a class="dropdown-item" href="help.php#account-security">Account & security</a></li>
                            </ul>
                        </li>
                    </ul>
                    
                    <!-- Search Form -->
                    <form class="d-flex me-2" action="browse.php" method="get" style="min-width:360px;">
                        <input class="form-control me-2" type="search" name="search" placeholder="Search for anything" aria-label="Search">
                        <select class="form-select me-2" name="category">
                            <option value="0">All Categories</option>
                            <?php foreach (get_all_categories() as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-light" type="submit">Search</button>
                    </form>
                    
                    <!-- User Navigation -->
                    <ul class="navbar-nav">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- Messages Link -->
                            <li class="nav-item">
                                <a class="nav-link position-relative" href="messages.php">
                                    <i class="fas fa-envelope"></i>
                                    <?php 
                                    $unread_count = count_unread_messages($_SESSION['user_id']);
                                    if ($unread_count > 0): 
                                    ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?php echo $unread_count; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            
                            <!-- Notifications Link -->
                            <li class="nav-item">
                                <a class="nav-link position-relative" href="notifications.php">
                                    <i class="fas fa-bell"></i>
                                    <?php 
                                    $unread_notifications = count_unread_notifications($_SESSION['user_id']);
                                    if ($unread_notifications > 0): 
                                    ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?php echo $unread_notifications; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['username']; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                    <li><a class="dropdown-item" href="my_auctions.php">My Auctions</a></li>
                                    <li><a class="dropdown-item" href="my_bids.php">My Bids</a></li>
                                    <li><a class="dropdown-item" href="messages.php">
                                        Messages
                                        <?php if ($unread_count > 0): ?>
                                            <span class="badge bg-primary ms-2"><?php echo $unread_count; ?></span>
                                        <?php endif; ?>
                                    </a></li>
                                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == ROLE_ADMIN): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="admin/dashboard.php">Admin Dashboard</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link<?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? ' active' : ''; ?>" href="login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link<?php echo (basename($_SERVER['PHP_SELF']) == 'register.php') ? ' active' : ''; ?>" href="register.php">Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        <?php display_flash_message(); ?>

        <!-- Global Filters Offcanvas (available from header on all pages) -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="filtersOffcanvas" aria-labelledby="filtersOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="filtersOffcanvasLabel">Filters</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <form action="browse.php" method="get">
                    <div class="mb-3">
                        <label class="form-label">Search</label>
                        <input class="form-control" type="search" name="search" placeholder="Search for anything">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="0">All Categories</option>
                            <?php foreach (get_all_categories() as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort</label>
                        <select class="form-select" name="sort">
                            <option value="newest">Newest First</option>
                            <option value="ending">Ending Soon</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="bids">Most Bids</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Apply</button>
                </form>
            </div>
        </div>