<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'auction_system');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Base URL
$base_url = 'http://localhost/online%20auction%20system/';

// Site settings
$site_name = 'AuctionHUB';
$site_email = 'admin@auctionsystem.com';

// Include functions
require_once 'functions/auction_functions.php';
// Optional helper extensions
if (file_exists(__DIR__ . '/functions/function_function.php')) {
    require_once __DIR__ . '/functions/function_function.php';
}
require_once 'functions/auth_functions.php';
require_once 'functions/admin_functions.php';

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Time zone
date_default_timezone_set('UTC');

// Define upload directory
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Ensure core tables required at runtime exist (idempotent)
try {
    // notifications table expected by create_notification() in functions
    $conn->query(
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type INT NOT NULL,
            data TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            INDEX idx_user_created (user_id, created_at),
            INDEX idx_user_read (user_id, is_read),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // bids table may exist with different amount column names; if it doesn't exist create a compatible one
    $result = $conn->query("SHOW TABLES LIKE 'bids'");
    if ($result && $result->num_rows === 0) {
        $conn->query(
            "CREATE TABLE IF NOT EXISTS bids (
                id INT AUTO_INCREMENT PRIMARY KEY,
                auction_id INT NOT NULL,
                user_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_auction_amount (auction_id, amount),
                INDEX idx_user (user_id),
                FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
} catch (Exception $e) {
    // Silent fail to avoid breaking runtime; detailed errors are shown elsewhere when needed
}

// Seed curated categories similar to eBay
try {
    $check = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($check && $check->num_rows > 0) {
        $desired = [
            // Top categories
            'Motors', 'Electronics', 'Collectibles & Art', 'Home & Garden',
            'Clothing, Shoes & Accessories', 'Sporting Goods', 'Business & Industrial',
            'Jewelry & Watches', 'Refurbished',
            // Extended list (from your screenshot)
            'Music', 'Musical Instruments & Gear', 'Pet Supplies', 'Pottery & Glass',
            'Real Estate', 'Specialty Services', 'Sports Mem, Cards & Fan Shop',
            'Stamps', 'Tickets & Experiences', 'Toys & Hobbies', 'Travel',
            'Video Games & Consoles', 'Everything Else', 'Books, Movies & Music',
            'Health & Beauty', 'Baby Essentials'
        ];

        // If empty or counts differ, reset to the curated list
        $countRes = $conn->query("SELECT COUNT(*) c FROM categories");
        $countRow = $countRes ? $countRes->fetch_assoc() : ['c' => 0];
        if ((int)$countRow['c'] !== count($desired)) {
            @$conn->query("TRUNCATE TABLE categories");
            $stmt = $conn->prepare("INSERT INTO categories(name) VALUES (?)");
            foreach ($desired as $name) {
                $stmt->bind_param("s", $name);
                @$stmt->execute();
            }
        }
    }
} catch (Throwable $e) {
    // Ignore seeding errors in production
}

// Define allowed image extensions
$allowed_image_extensions = ['jpg', 'jpeg', 'png', 'gif'];

// Define maximum file size (5MB)
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Define pagination limit
define('ITEMS_PER_PAGE', 12);

// Define auction status constants
define('AUCTION_STATUS_ACTIVE', 1);
define('AUCTION_STATUS_ENDED', 2);
define('AUCTION_STATUS_CANCELLED', 3);

// Define user roles
define('ROLE_ADMIN', 1);
define('ROLE_USER', 2);

// Define email verification status
define('EMAIL_VERIFIED', 1);
define('EMAIL_NOT_VERIFIED', 0);

// Define bid status
define('BID_ACTIVE', 1);
define('BID_OUTBID', 2);
define('BID_WON', 3);
define('BID_LOST', 4);

// Define notification types (guarded to avoid redefinition)
if (!defined('NOTIFICATION_BID')) { define('NOTIFICATION_BID', 1); }
if (!defined('NOTIFICATION_OUTBID')) { define('NOTIFICATION_OUTBID', 2); }
if (!defined('NOTIFICATION_WON')) { define('NOTIFICATION_WON', 3); }
if (!defined('NOTIFICATION_AUCTION_ENDED')) { define('NOTIFICATION_AUCTION_ENDED', 4); }
if (!defined('NOTIFICATION_NEW_AUCTION')) { define('NOTIFICATION_NEW_AUCTION', 5); }

// Define notification read status (guarded to avoid redefinition)
if (!defined('NOTIFICATION_READ')) { define('NOTIFICATION_READ', 1); }
if (!defined('NOTIFICATION_UNREAD')) { define('NOTIFICATION_UNREAD', 0); }

// Helper: resolve auction_images column name across schemas
function get_auction_image_column() {
    static $col = null; if ($col !== null) return $col; global $conn;
    foreach (['image_path','image_url'] as $c) {
        $like = $conn->real_escape_string($c);
        $res = $conn->query("SHOW COLUMNS FROM auction_images LIKE '$like'");
        if ($res && $res->num_rows > 0) { $col = $c; return $col; }
    }
    $col = 'image_path';
    return $col;
}

// Helper: resolve auctions end timestamp column across schemas
function get_auction_end_column() {
    static $col = null; if ($col !== null) return $col; global $conn;
    foreach (['end_time','end_date'] as $c) {
        $like = $conn->real_escape_string($c);
        $res = $conn->query("SHOW COLUMNS FROM auctions LIKE '$like'");
        if ($res && $res->num_rows > 0) { $col = $c; return $col; }
    }
    // default fallback
    $col = 'end_date';
    return $col;
}

// Function to get site settings
function get_site_settings() {
    global $conn;
    $settings = [];
    
    $sql = "SELECT * FROM settings";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings;
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate random string
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Function to format date
function format_date($date, $format = 'M d, Y h:i A') {
    return date($format, strtotime($date));
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == ROLE_ADMIN;
}

// Function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to set flash message
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Function to display flash message
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = $_SESSION['flash_message']['message'];
        
        echo "<div class='alert alert-$type'>$message</div>";
        
        // Clear the flash message
        unset($_SESSION['flash_message']);
    }
}

// Function to get current page URL
function get_current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    return $url;
}

// Function to check if email exists
function email_exists($email) {
    global $conn;
    
    $email = $conn->real_escape_string($email);
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    return $result && $result->num_rows > 0;
}

// Function to check if username exists
function username_exists($username) {
    global $conn;
    
    $username = $conn->real_escape_string($username);
    $sql = "SELECT id FROM users WHERE username = '$username'";
    $result = $conn->query($sql);
    
    return $result && $result->num_rows > 0;
}

// Function to get user by ID
function get_user_by_id($user_id) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    $sql = "SELECT * FROM users WHERE id = '$user_id'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to get user by email
function get_user_by_email($email) {
    global $conn;
    
    $email = $conn->real_escape_string($email);
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to get user by username
function get_user_by_username($username) {
    global $conn;
    
    $username = $conn->real_escape_string($username);
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to get featured auctions
if (!function_exists('get_featured_auctions')) {
function get_featured_auctions($limit = 6) {
    global $conn;
    
    $imgCol = get_auction_image_column();
    $endCol = get_auction_end_column();
    $sql = "SELECT a.*, u.username as seller_username,
                   (SELECT $imgCol FROM auction_images WHERE auction_id = a.id ORDER BY id ASC LIMIT 1) AS image_path
            FROM auctions a
            JOIN users u ON a.user_id = u.id
            WHERE a.status = " . AUCTION_STATUS_ACTIVE . "
            AND a.$endCol > NOW()
            ORDER BY a.created_at DESC
            LIMIT $limit";
    
    $result = $conn->query($sql);
    $auctions = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Provide safe placeholders when bid aggregates are not selected
            if (!isset($row['current_bid']) || $row['current_bid'] === null) {
                $row['current_bid'] = $row['starting_price'];
            }
            if (!isset($row['bid_count'])) {
                $row['bid_count'] = 0;
            }
            $auctions[] = $row;
        }
    }
    
    return $auctions;
}
}

// Function to get ending soon auctions
if (!function_exists('get_ending_soon_auctions')) {
function get_ending_soon_auctions($limit = 6) {
    global $conn;
    
    $imgCol = get_auction_image_column();
    $endCol = get_auction_end_column();
    $sql = "SELECT a.*, u.username as seller_username,
                   (SELECT $imgCol FROM auction_images WHERE auction_id = a.id ORDER BY id ASC LIMIT 1) AS image_path
            FROM auctions a
            JOIN users u ON a.user_id = u.id
            WHERE a.status = " . AUCTION_STATUS_ACTIVE . "
            AND a.$endCol > NOW()
            ORDER BY a.$endCol ASC
            LIMIT $limit";
    
    $result = $conn->query($sql);
    $auctions = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!isset($row['current_bid']) || $row['current_bid'] === null) {
                $row['current_bid'] = $row['starting_price'];
            }
            if (!isset($row['bid_count'])) {
                $row['bid_count'] = 0;
            }
            $auctions[] = $row;
        }
    }
    
    return $auctions;
}
}

// Function to get recently added auctions
if (!function_exists('get_recently_added_auctions')) {
function get_recently_added_auctions($limit = 6) {
    global $conn;
    
    $imgCol = get_auction_image_column();
    $endCol = get_auction_end_column();
    $sql = "SELECT a.*, u.username as seller_username,
                   (SELECT $imgCol FROM auction_images WHERE auction_id = a.id ORDER BY id ASC LIMIT 1) AS image_path
            FROM auctions a
            JOIN users u ON a.user_id = u.id
            WHERE a.status = " . AUCTION_STATUS_ACTIVE . "
            AND a.$endCol > NOW()
            ORDER BY a.created_at DESC
            LIMIT $limit";
    
    $result = $conn->query($sql);
    $auctions = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (!isset($row['current_bid']) || $row['current_bid'] === null) {
                $row['current_bid'] = $row['starting_price'];
            }
            if (!isset($row['bid_count'])) {
                $row['bid_count'] = 0;
            }
            $auctions[] = $row;
        }
    }
    
    return $auctions;
}
}

// Function to get auction by ID
if (!function_exists('get_auction_by_id')) {
function get_auction_by_id($auction_id) {
    global $conn;
    
    $auction_id = $conn->real_escape_string($auction_id);
    $sql = "SELECT a.*, 
                  a.starting_price as current_bid,
                  0 as bid_count,
                  u.username as seller_username,
                  u.email as seller_email
           FROM auctions a
           JOIN users u ON a.user_id = u.id
           WHERE a.id = '$auction_id'";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $auction = $result->fetch_assoc();
        
        // Set default current bid if no bids yet
        if ($auction['current_bid'] === null) {
            $auction['current_bid'] = $auction['starting_price'];
        }
        
        return $auction;
    }
    
    return null;
}
}

// Function to get auction images
if (!function_exists('get_auction_images')) {
function get_auction_images($auction_id) {
    global $conn;
    
    $auction_id = $conn->real_escape_string($auction_id);
    $sql = "SELECT * FROM auction_images WHERE auction_id = '$auction_id' ORDER BY is_primary DESC, id ASC";
    $result = $conn->query($sql);
    $images = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
    }
    
    return $images;
}
}

// Function to get auction bids
if (!function_exists('get_auction_bids')) {
function get_auction_bids($auction_id) {
    global $conn;
    
    $auction_id = $conn->real_escape_string($auction_id);
    // Ensure bids table exists
    $check_sql = "SHOW TABLES LIKE 'bids'";
    $check_result = $conn->query($check_sql);
    if (!$check_result || $check_result->num_rows == 0) { return []; }

    // Detect column names for portability
    $amountCol = 'bid_amount';
    $chk = $conn->query("SHOW COLUMNS FROM bids LIKE 'bid_amount'");
    if (!$chk || $chk->num_rows === 0) { $amountCol = 'amount'; }
    $createdCol = 'created_at';
    $chk2 = $conn->query("SHOW COLUMNS FROM bids LIKE 'created_at'");
    if (!$chk2 || $chk2->num_rows === 0) { $createdCol = 'bid_time'; }
    
    $sql = "SELECT 
                b.id,
                b.user_id,
                u.username,
                b.".$amountCol." AS amount,
                b.".$createdCol." AS bid_date
            FROM bids b
            JOIN users u ON b.user_id = u.id
            WHERE b.auction_id = '$auction_id'
            ORDER BY b.".$amountCol." DESC, b.".$createdCol." ASC";
    
    $result = $conn->query($sql);
    $bids = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bids[] = $row;
        }
    }
    
    return $bids;
}
}

// Function to get highest bid for an auction
if (!function_exists('get_highest_bid')) {
function get_highest_bid($auction_id) {
    global $conn;
    
    $auction_id = $conn->real_escape_string($auction_id);
    
    // Ensure bids table exists
    $check_sql = "SHOW TABLES LIKE 'bids'";
    $check_result = $conn->query($check_sql);
    if (!$check_result || $check_result->num_rows == 0) {
        return null;
    }

    // Detect column names
    $amountCol = 'bid_amount';
    $col = $conn->query("SHOW COLUMNS FROM bids LIKE 'bid_amount'");
    if (!$col || $col->num_rows === 0) { $amountCol = 'amount'; }
    $createdCol = 'created_at';
    $col2 = $conn->query("SHOW COLUMNS FROM bids LIKE 'created_at'");
    if (!$col2 || $col2->num_rows === 0) { $createdCol = 'bid_time'; }

    // Return the top bid row with amount and user
    $sql = "SELECT user_id, $amountCol AS amount, $createdCol AS created_at
            FROM bids
            WHERE auction_id = '$auction_id'
            ORDER BY $amountCol DESC, $createdCol ASC
            LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}
}

// Function to get categories
function get_categories() {
    global $conn;
    
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);
    $categories = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Function to get category by ID
function get_category_by_id($category_id) {
    global $conn;
    
    $category_id = $conn->real_escape_string($category_id);
    $sql = "SELECT * FROM categories WHERE id = '$category_id'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to check if auction belongs to user
function is_auction_owner($auction_id, $user_id) {
    global $conn;
    
    $auction_id = $conn->real_escape_string($auction_id);
    $user_id = $conn->real_escape_string($user_id);
    
    $sql = "SELECT id FROM auctions WHERE id = '$auction_id' AND user_id = '$user_id'";
    $result = $conn->query($sql);
    
    return $result && $result->num_rows > 0;
}

// Function to check if user has bid on auction
function has_user_bid($auction_id, $user_id) {
    global $conn;
    
    $auction_id = $conn->real_escape_string($auction_id);
    $user_id = $conn->real_escape_string($user_id);
    
    $sql = "SELECT id FROM bids WHERE auction_id = '$auction_id' AND user_id = '$user_id'";
    $result = $conn->query($sql);
    
    return $result && $result->num_rows > 0;
}

// Function to get user's auctions
function get_user_auctions($user_id, $status = 'all', $sort = 'newest', $limit = 10, $offset = 0) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    
    // Build WHERE clause based on status
    $where_clause = "WHERE a.user_id = '$user_id'";
    if ($status !== 'all') {
        $status_value = ($status === 'active') ? AUCTION_STATUS_ACTIVE : AUCTION_STATUS_ENDED;
        $where_clause .= " AND a.status = $status_value";
    }
    
    // Build ORDER BY clause based on sort
    $order_clause = "ORDER BY a.created_at DESC";
    switch ($sort) {
        case 'ending':
            $order_clause = "ORDER BY a.end_date ASC";
            break;
        case 'price_low':
            $order_clause = "ORDER BY a.starting_price ASC";
            break;
        case 'price_high':
            $order_clause = "ORDER BY a.starting_price DESC";
            break;
        case 'bids':
            $order_clause = "ORDER BY bid_count DESC";
            break;
        case 'newest':
        default:
            $order_clause = "ORDER BY a.created_at DESC";
            break;
    }
    
    $sql = "SELECT a.*, 
                  a.starting_price as current_bid,
                  0 as bid_count,
                  c.name AS category_name
           FROM auctions a
           LEFT JOIN categories c ON a.category_id = c.id
           $where_clause
           $order_clause
           LIMIT $limit OFFSET $offset";
    
    $result = $conn->query($sql);
    $auctions = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Set default current bid if no bids yet
            if ($row['current_bid'] === null) {
                $row['current_bid'] = $row['starting_price'];
            }
            $auctions[] = $row;
        }
    }
    
    return $auctions;
}

// Function to get user's bids
function get_user_bids($user_id, $status = 'all', $sort = 'newest', $limit = 10, $offset = 0) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    
    // Sort clause
    $order_clause = "ORDER BY a.end_date DESC";
    if ($sort === 'newest') $order_clause = "ORDER BY a.end_date DESC";
    elseif ($sort === 'oldest') $order_clause = "ORDER BY a.end_date ASC";

    // Detect bid columns
    $amountCol = 'bid_amount';
    $chk = $conn->query("SHOW COLUMNS FROM bids LIKE 'bid_amount'");
    if (!$chk || $chk->num_rows === 0) { $amountCol = 'amount'; }
    $createdCol = 'created_at';
    $chk2 = $conn->query("SHOW COLUMNS FROM bids LIKE 'created_at'");
    if (!$chk2 || $chk2->num_rows === 0) { $createdCol = 'bid_time'; }

    // Base dataset: one row per auction the user has bid on
    $sql = "SELECT 
              a.id AS auction_id,
              a.title AS auction_title,
              a.end_date,
              a.reserve_price,
              a.current_price,
              a.status AS auction_status_num,
              u.username AS seller_username,
              (SELECT MAX(b1.$amountCol) FROM bids b1 WHERE b1.auction_id = a.id AND b1.user_id = '$user_id') AS your_amount,
              (SELECT $createdCol FROM bids b2 WHERE b2.auction_id = a.id AND b2.user_id = '$user_id' ORDER BY b2.$amountCol DESC, b2.$createdCol ASC LIMIT 1) AS your_time,
              (SELECT MAX(b3.$amountCol) FROM bids b3 WHERE b3.auction_id = a.id) AS top_amount,
              (SELECT user_id FROM bids b4 WHERE b4.auction_id = a.id ORDER BY b4.$amountCol DESC, b4.$createdCol ASC LIMIT 1) AS top_user_id
            FROM auctions a
            JOIN bids bx ON bx.auction_id = a.id AND bx.user_id = '$user_id'
            JOIN users u ON a.user_id = u.id
            GROUP BY a.id
            $order_clause
            LIMIT $limit OFFSET $offset";

    $result = $conn->query($sql);
    $rows = [];
    if ($result && $result->num_rows > 0) {
        while ($r = $result->fetch_assoc()) {
            $r['amount'] = (float)($r['your_amount'] ?? 0);
            $r['bid_date'] = $r['your_time'];
            $r['auction_status'] = ((int)$r['auction_status_num'] === AUCTION_STATUS_ACTIVE) ? 'active' : 'ended';
            $r['is_highest'] = ((int)$r['top_user_id'] === (int)$user_id);
            $rows[] = $r;
        }
    }

    // Filter by requested status
    $filtered = [];
    foreach ($rows as $row) {
        $include = true;
        switch ($status) {
            case 'active':
                $include = ($row['auction_status'] === 'active');
                break;
            case 'ended':
                $include = ($row['auction_status'] === 'ended');
                break;
            case 'winning':
                $include = ($row['auction_status'] === 'active' && $row['is_highest']);
                break;
            case 'outbid':
                $include = ($row['auction_status'] === 'active' && !$row['is_highest']);
                break;
            case 'won':
                $include = ($row['auction_status'] === 'ended' && $row['is_highest']);
                break;
            case 'lost':
                $include = ($row['auction_status'] === 'ended' && !$row['is_highest']);
                break;
            case 'all':
            default:
                $include = true;
        }
        if ($include) { $filtered[] = $row; }
    }

    return $filtered;
}

// Function to send message to seller
function send_message_to_seller($from_user_id, $to_user_id, $auction_id, $subject, $message) {
    global $conn;
    
    // Ensure messages table exists
    $conn->query("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT NOT NULL,
        to_user_id INT NOT NULL,
        auction_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE
    )");
    
    $from_user_id = $conn->real_escape_string($from_user_id);
    $to_user_id = $conn->real_escape_string($to_user_id);
    $auction_id = $conn->real_escape_string($auction_id);
    $subject = $conn->real_escape_string($subject);
    $message = $conn->real_escape_string($message);
    
    $sql = "INSERT INTO messages (from_user_id, to_user_id, auction_id, subject, message) 
            VALUES ('$from_user_id', '$to_user_id', '$auction_id', '$subject', '$message')";
    
    if ($conn->query($sql)) {
        return [
            'success' => true,
            'message' => 'Message sent successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to send message: ' . $conn->error
        ];
    }
}

// Function to get user's received messages (inbox)
function get_user_messages($user_id, $limit = 20, $offset = 0) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    $limit = (int)$limit;
    $offset = (int)$offset;
    
    $sql = "SELECT m.*, 
                   u.username as from_username,
                   u.email as from_email,
                   a.title as auction_title,
                   a.id as auction_id
            FROM messages m
            JOIN users u ON m.from_user_id = u.id
            JOIN auctions a ON m.auction_id = a.id
            WHERE m.to_user_id = '$user_id'
            ORDER BY m.created_at DESC
            LIMIT $limit OFFSET $offset";
    
    $result = $conn->query($sql);
    $messages = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
    
    return $messages;
}

// Function to get conversation between two users for a specific auction
function get_conversation($user1_id, $user2_id, $auction_id) {
    global $conn;
    
    $user1_id = $conn->real_escape_string($user1_id);
    $user2_id = $conn->real_escape_string($user2_id);
    $auction_id = $conn->real_escape_string($auction_id);
    
    $sql = "SELECT m.*, 
                   u.username as from_username,
                   u.email as from_email,
                   a.title as auction_title
            FROM messages m
            JOIN users u ON m.from_user_id = u.id
            JOIN auctions a ON m.auction_id = a.id
            WHERE m.auction_id = '$auction_id' 
            AND ((m.from_user_id = '$user1_id' AND m.to_user_id = '$user2_id') 
                 OR (m.from_user_id = '$user2_id' AND m.to_user_id = '$user1_id'))
            ORDER BY m.created_at ASC";
    
    $result = $conn->query($sql);
    $messages = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
    
    return $messages;
}

// Function to reply to a message
function reply_to_message($from_user_id, $to_user_id, $auction_id, $subject, $message) {
    global $conn;
    
    $from_user_id = $conn->real_escape_string($from_user_id);
    $to_user_id = $conn->real_escape_string($to_user_id);
    $auction_id = $conn->real_escape_string($auction_id);
    $subject = $conn->real_escape_string($subject);
    $message = $conn->real_escape_string($message);
    
    $sql = "INSERT INTO messages (from_user_id, to_user_id, auction_id, subject, message) 
            VALUES ('$from_user_id', '$to_user_id', '$auction_id', '$subject', '$message')";
    
    if ($conn->query($sql)) {
        return [
            'success' => true,
            'message' => 'Reply sent successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to send reply: ' . $conn->error
        ];
    }
}

// Function to mark message as read
function mark_message_read($message_id, $user_id) {
    global $conn;
    
    $message_id = (int)$message_id;
    $user_id = $conn->real_escape_string($user_id);
    
    $sql = "UPDATE messages SET is_read = 1 
            WHERE id = $message_id AND to_user_id = '$user_id'";
    
    return $conn->query($sql);
}

// Function to mark all messages as read for a user
function mark_all_messages_read($user_id) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    
    $sql = "UPDATE messages SET is_read = 1 WHERE to_user_id = '$user_id'";
    
    return $conn->query($sql);
}

// Function to count unread messages
function count_unread_messages($user_id) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    
    $sql = "SELECT COUNT(*) as count FROM messages WHERE to_user_id = '$user_id' AND is_read = 0";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
    
    return 0;
}

// Function to delete a message
function delete_message($message_id, $user_id) {
    global $conn;
    
    $message_id = (int)$message_id;
    $user_id = $conn->real_escape_string($user_id);
    
    $sql = "DELETE FROM messages WHERE id = $message_id AND (from_user_id = '$user_id' OR to_user_id = '$user_id')";
    
    return $conn->query($sql);
}



// Function to get user's won auctions
function get_user_won_auctions($user_id, $limit = 10, $offset = 0) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    
    // Check if bids table exists
    $check_sql = "SHOW TABLES LIKE 'bids'";
    $check_result = $conn->query($check_sql);
    
    if (!$check_result || $check_result->num_rows == 0) {
        // Bids table doesn't exist, return empty array
        return [];
    }
    
    $sql = "SELECT a.*, a.starting_price as winning_bid, u.username as seller_username
           FROM auctions a
           JOIN users u ON a.user_id = u.id
           WHERE a.status = " . AUCTION_STATUS_ENDED . "
           ORDER BY a.end_date DESC
           LIMIT $limit OFFSET $offset";
    
    $result = $conn->query($sql);
    $auctions = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $auctions[] = $row;
        }
    }
    
    return $auctions;
}

// Function to count user's auctions
function count_user_auctions($user_id) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    $sql = "SELECT COUNT(*) as count FROM auctions WHERE user_id = '$user_id'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    return 0;
}

// Function to count user's active auctions
function count_user_active_auctions($user_id) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    $sql = "SELECT COUNT(*) as count FROM auctions WHERE user_id = '$user_id' AND status = " . AUCTION_STATUS_ACTIVE;
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    return 0;
}

// Function to count user's bids
function count_user_bids($user_id) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    $sql = "SELECT COUNT(*) as count FROM bids WHERE user_id = '$user_id'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    return 0;
}

// Function to count user's won auctions
function count_user_won_auctions($user_id) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    
    // Get all ended auctions
    $sql = "SELECT id FROM auctions WHERE status = " . AUCTION_STATUS_ENDED;
    $result = $conn->query($sql);
    
    $won_count = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $auction_id = $row['id'];
            
            // Get the highest bid for this auction (support bid_amount or amount)
            $amountCol = 'bid_amount';
            $col = $conn->query("SHOW COLUMNS FROM bids LIKE 'bid_amount'");
            if (!$col || $col->num_rows === 0) { $amountCol = 'amount'; }
            $max_sql = "SELECT MAX($amountCol) as max_bid FROM bids WHERE auction_id = '$auction_id'";
            $max_result = $conn->query($max_sql);
            
            if ($max_result && $max_result->num_rows > 0) {
                $max_row = $max_result->fetch_assoc();
                $max_bid = $max_row['max_bid'];
                
                // Check if this user has the highest bid
                $user_sql = "SELECT COUNT(*) as count FROM bids 
                            WHERE auction_id = '$auction_id' 
                            AND user_id = '$user_id' 
                            AND $amountCol = '$max_bid'";
                $user_result = $conn->query($user_sql);
                
                if ($user_result && $user_result->num_rows > 0) {
                    $user_row = $user_result->fetch_assoc();
                    if ($user_row['count'] > 0) {
                        $won_count++;
                    }
                }
            }
        }
    }
    
    return $won_count;
}

// Function to search auctions (guarded; app also defines a richer version elsewhere)
if (!function_exists('search_auctions')) {
function search_auctions($keyword, $category_id = null, $limit = 12, $offset = 0) {
    global $conn;
    
    $keyword = $conn->real_escape_string($keyword);
    
    $sql = "SELECT 
               a.id,
               a.title,
               a.description,
               a.created_at,
               a.end_date,
               a.status,
               a.starting_price,
               a.starting_price AS current_price,
               0 AS bid_count,
               c.name AS category_name,
               u.username AS seller_username
           FROM auctions a
           JOIN users u ON a.user_id = u.id
           LEFT JOIN categories c ON a.category_id = c.id
           WHERE a.status = " . AUCTION_STATUS_ACTIVE . "
           AND a.end_date > NOW()
           AND (a.title LIKE '%$keyword%' OR a.description LIKE '%$keyword%')";
    
    if ($category_id) {
        $category_id = $conn->real_escape_string($category_id);
        $sql .= " AND a.category_id = '$category_id'";
    }
    
    $sql .= " ORDER BY a.created_at DESC
              LIMIT $limit OFFSET $offset";
    
    $result = $conn->query($sql);
    $auctions = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Ensure required fields exist
            if (!isset($row['current_price']) || $row['current_price'] === null) {
                $row['current_price'] = $row['starting_price'];
            }
            $auctions[] = $row;
        }
    }
    
    return $auctions;
}
}

// Function to count search results (guarded; app may provide its own)
if (!function_exists('count_search_results')) {
function count_search_results($keyword, $category_id = null) {
    global $conn;
    
    $keyword = $conn->real_escape_string($keyword);
    
    $sql = "SELECT COUNT(*) as count
           FROM auctions a
           WHERE a.status = " . AUCTION_STATUS_ACTIVE . "
           AND a.end_date > NOW()
           AND (a.title LIKE '%$keyword%' OR a.description LIKE '%$keyword%')";
    
    if ($category_id) {
        $category_id = $conn->real_escape_string($category_id);
        $sql .= " AND a.category_id = '$category_id'";
    }
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    return 0;
}
}

// Compatibility wrapper used by browse.php
if (!function_exists('count_search_auctions')) {
function count_search_auctions($keyword, $category_id = 0, $min_price = 0, $max_price = 0, $condition = '', $status = 'active') {
    return count_search_results($keyword, $category_id);
}
}

// Function to get pagination links
function get_pagination_links($total_items, $items_per_page, $current_page, $url_pattern) {
    $total_pages = ceil($total_items / $items_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<ul class="pagination">';
    
    // Previous page link
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $prev_page) . '">&laquo; Previous</a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><a class="page-link" href="#">&laquo; Previous</a></li>';
    }
    
    // Page links
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $pagination .= '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $i) . '">' . $i . '</a></li>';
        }
    }
    
    // Next page link
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $next_page) . '">Next &raquo;</a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><a class="page-link" href="#">Next &raquo;</a></li>';
    }
    
    $pagination .= '</ul>';
    
    return $pagination;
}

// Function to update expired auctions
function update_expired_auctions() {
    global $conn;
    
    // Use resolved column name to expire auctions
    $endCol = get_auction_end_column();
    $sql = "UPDATE auctions 
           SET status = " . AUCTION_STATUS_ENDED . "
           WHERE status = " . AUCTION_STATUS_ACTIVE . "
           AND $endCol < NOW()";
    
    $conn->query($sql);
}

// Update expired auctions on each page load
update_expired_auctions();

// Function to update user profile
function update_user_profile($user_id, $first_name, $last_name, $email, $phone, $address, $city, $state, $zip_code, $country, $bio, $profile_image = null) {
    global $conn;
    
    $user_id = $conn->real_escape_string($user_id);
    $first_name = $conn->real_escape_string($first_name);
    $last_name = $conn->real_escape_string($last_name);
    $email = $conn->real_escape_string($email);
    $phone = $conn->real_escape_string($phone);
    $address = $conn->real_escape_string($address);
    $city = $conn->real_escape_string($city);
    $state = $conn->real_escape_string($state);
    $zip_code = $conn->real_escape_string($zip_code);
    $country = $conn->real_escape_string($country);
    $bio = $conn->real_escape_string($bio);
    
    $sql = "UPDATE users SET 
            first_name = '$first_name',
            last_name = '$last_name',
            email = '$email',
            phone = '$phone',
            address = '$address',
            city = '$city',
            state = '$state',
            zip_code = '$zip_code',
            country = '$country',
            bio = '$bio'";
    
    // Add profile image to update if provided
    if ($profile_image !== null) {
        $profile_image = $conn->real_escape_string($profile_image);
        $sql .= ", profile_image = '$profile_image'";
    }
    
    $sql .= " WHERE id = '$user_id'";
    
    $result = $conn->query($sql);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Profile updated successfully!'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update profile. Please try again.'
        ];
    }
}