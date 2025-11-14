<?php
/**
 * Auction Functions
 * This file contains functions related to auction management, bidding, and auction-related operations
 */

// Fallback constants and helpers (guarded) to prevent runtime errors if not defined elsewhere
if (!defined('NOTIFICATION_UNREAD')) { define('NOTIFICATION_UNREAD', 0); }
if (!defined('NOTIFICATION_READ')) { define('NOTIFICATION_READ', 1); }
if (!defined('NOTIFICATION_NEW_BID')) { define('NOTIFICATION_NEW_BID', 100); }
if (!defined('NOTIFICATION_OUTBID')) { define('NOTIFICATION_OUTBID', 101); }
if (!defined('NOTIFICATION_NEW_FEEDBACK')) { define('NOTIFICATION_NEW_FEEDBACK', 102); }
if (!defined('NOTIFICATION_AUCTION_ENDED')) { define('NOTIFICATION_AUCTION_ENDED', 103); }
if (!defined('NOTIFICATION_AUCTION_WON')) { define('NOTIFICATION_AUCTION_WON', 104); }

if (!function_exists('format_currency')) {
	function format_currency($amount) {
		$number = (float) $amount;
		return '₹' . number_format($number, 2);
	}
}

if (!function_exists('get_username_by_id')) {
	function get_username_by_id($user_id) {
		global $conn;
		if ($conn instanceof mysqli) {
			$sql = "SELECT username FROM users WHERE id = ? LIMIT 1";
			$stmt = $conn->prepare($sql);
			if ($stmt) {
				$stmt->bind_param("i", $user_id);
				if ($stmt->execute()) {
					$result = $stmt->get_result();
					if ($result && $result->num_rows === 1) {
						$row = $result->fetch_assoc();
						return $row['username'];
					}
				}
			}
		}
		return 'User #' . (int) $user_id;
	}
}

/**
 * Check if a table has a column (portable across MariaDB/MySQL)
 */
function table_has_column($table, $column) {
    global $conn;
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $res && $res->num_rows > 0;
}

/**
 * Resolve bids table column names across schema variants
 */
function get_bids_amount_column() {
    static $col = null;
    if ($col !== null) return $col;
    global $conn;
    $candidates = ['bid_amount','amount','value'];
    foreach ($candidates as $name) {
        $like = $conn->real_escape_string($name);
        $res = $conn->query("SHOW COLUMNS FROM bids LIKE '$like'");
        if ($res && $res->num_rows > 0) {
            $col = $name; return $col;
        }
    }
    // Fallback to bid_amount for compatibility
    $col = 'bid_amount';
    return $col;
}

function get_bids_created_column() {
    static $col = null;
    if ($col !== null) return $col;
    global $conn;
    $candidates = ['created_at','bid_time'];
    foreach ($candidates as $name) {
        $like = $conn->real_escape_string($name);
        $res = $conn->query("SHOW COLUMNS FROM bids LIKE '$like'");
        if ($res && $res->num_rows > 0) {
            $col = $name; return $col;
        }
    }
    $col = 'created_at';
    return $col;
}

/**
 * Convert a timestamp to a human-readable time elapsed string (e.g., "2 hours ago")
 * @param string $datetime The timestamp to convert
 * @param bool $full Whether to show the full date/time
 * @return string Human-readable time elapsed string
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = (int) floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $parts = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    ];

    $labels = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    $string = [];
    foreach ($parts as $k => $value) {
        if ($value) {
            $string[$k] = $value . ' ' . $labels[$k] . ($value > 1 ? 's' : '');
        }
    }

    if (!$full) {
        $string = array_slice($string, 0, 1);
    }
    
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

/**
 * Create a new auction
 * @param int $user_id User ID of the auction creator
 * @param string $title Auction title
 * @param string $description Auction description
 * @param float $starting_price Starting price
 * @param float $reserve_price Reserve price (optional)
 * @param int $category_id Category ID
 * @param string $end_date End date (YYYY-MM-DD HH:MM:SS format)
 * @param array $images Array of image files from $_FILES
 * @return array Result of the auction creation attempt
 */
function create_auction($user_id, $title, $description, $starting_price, $reserve_price, $category_id, $end_date, $images = []) {
    global $conn;
    
    // Sanitize inputs
    $title = sanitize_input($title);
    $description = sanitize_input($description);
    $starting_price = (float) $starting_price;
    $reserve_price = (float) $reserve_price;
    $category_id = (int) $category_id;
    $end_date = sanitize_input($end_date);
    
    // Validate inputs
    if (empty($title) || empty($description) || $starting_price <= 0 || empty($end_date) || $category_id <= 0) {
        return [
            'success' => false,
            'message' => 'All required fields must be filled out correctly'
        ];
    }
    
    // Validate end date is in the future
    $current_date = date('Y-m-d H:i:s');
    if (strtotime($end_date) <= strtotime($current_date)) {
        return [
            'success' => false,
            'message' => 'End date must be in the future'
        ];
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert auction into database
        $sql = "INSERT INTO auctions (user_id, title, description, starting_price, reserve_price, current_price, category_id, start_date, end_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)"; 
        
        $stmt = $conn->prepare($sql);
        $status = AUCTION_STATUS_ACTIVE;
        $current_price = $starting_price; // Initially, current price equals starting price
        
        $stmt->bind_param("issdddisi", $user_id, $title, $description, $starting_price, $reserve_price, $current_price, $category_id, $end_date, $status);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create auction: ' . $stmt->error);
        }
        
        $auction_id = $stmt->insert_id;
        
        // Process images if any
        if (!empty($images) && is_array($images)) {
            $image_paths = upload_auction_images($auction_id, $images);
            
            if (!$image_paths['success']) {
                throw new Exception($image_paths['message']);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Auction created successfully',
            'auction_id' => $auction_id
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Upload auction images
 * @param int $auction_id Auction ID
 * @param array $images Array of image files from $_FILES
 * @return array Result of the upload attempt
 */
function upload_auction_images($auction_id, $images) {
    global $conn, $allowed_image_extensions;
    
    // Check if auction exists
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        return [
            'success' => false,
            'message' => 'Auction not found'
        ];
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = UPLOAD_DIR . 'auction_images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $uploaded_images = [];
    $errors = [];
    
    // Process each image
    foreach ($images['name'] as $key => $name) {
        // Skip if no file was uploaded
        if ($images['error'][$key] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        
        // Check for upload errors
        if ($images['error'][$key] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading file {$name}: " . $images['error'][$key];
            continue;
        }
        
        // Check file size
        if ($images['size'][$key] > MAX_FILE_SIZE) {
            $errors[] = "File {$name} is too large. Maximum size is " . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
            continue;
        }
        
        // Check file extension
        $file_info = pathinfo($name);
        $extension = strtolower($file_info['extension']);
        
        if (!in_array($extension, $allowed_image_extensions)) {
            $errors[] = "File {$name} has invalid type. Allowed types: " . implode(', ', $allowed_image_extensions);
            continue;
        }
        
        // Generate unique filename
        $filename = 'auction_' . $auction_id . '_' . time() . '_' . $key . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($images['tmp_name'][$key], $filepath)) {
            // Insert image into database
            $relative_path = 'auction_images/' . $filename;
            $sql = "INSERT INTO auction_images (auction_id, image_path) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $auction_id, $relative_path);
            
            if ($stmt->execute()) {
                $uploaded_images[] = [
                    'id' => $stmt->insert_id,
                    'path' => $relative_path
                ];
            } else {
                $errors[] = "Failed to save image {$name} to database: " . $stmt->error;
                // Remove the uploaded file if database insert fails
                unlink($filepath);
            }
        } else {
            $errors[] = "Failed to move uploaded file {$name}";
        }
    }
    
    // Return results
    if (empty($errors) && !empty($uploaded_images)) {
        return [
            'success' => true,
            'message' => count($uploaded_images) . ' images uploaded successfully',
            'images' => $uploaded_images
        ];
    } elseif (!empty($uploaded_images) && !empty($errors)) {
        return [
            'success' => true,
            'message' => count($uploaded_images) . ' images uploaded successfully, but with some errors: ' . implode('; ', $errors),
            'images' => $uploaded_images,
            'errors' => $errors
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to upload images: ' . implode('; ', $errors),
            'errors' => $errors
        ];
    }
}

/**
 * Update an existing auction
 * @param int $auction_id Auction ID
 * @param int $user_id User ID of the auction creator (for verification)
 * @param array $data Auction data to update
 * @return array Result of the update attempt
 */
function update_auction($auction_id, $user_id, $data) {
    global $conn;
    
    // Get auction
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        return [
            'success' => false,
            'message' => 'Auction not found'
        ];
    }
    
    // Check if user is the auction creator
    if ($auction['user_id'] != $user_id) {
        return [
            'success' => false,
            'message' => 'You do not have permission to update this auction'
        ];
    }
    
    // Check if auction has bids
    if ($auction['bid_count'] > 0) {
        return [
            'success' => false,
            'message' => 'Cannot update auction that already has bids'
        ];
    }
    
    // Build update query
    $sql = "UPDATE auctions SET ";
    $params = [];
    $types = "";
    
    // Add fields to update
    foreach ($data as $key => $value) {
        // Skip non-updatable fields
        if (in_array($key, ['id', 'user_id', 'created_at', 'status', 'current_price', 'bid_count'])) {
            continue;
        }
        
        // Add field to query
        $sql .= "$key = ?, ";
        $params[] = $value;
        
        // Determine parameter type
        if (in_array($key, ['starting_price', 'reserve_price'])) {
            $types .= "d"; // Double for prices
        } elseif (in_array($key, ['category_id'])) {
            $types .= "i"; // Integer for IDs
        } else {
            $types .= "s"; // String for everything else
        }
    }
    
    // Remove trailing comma and space
    $sql = rtrim($sql, ", ");
    
    // Add WHERE clause
    $sql .= " WHERE id = ? AND user_id = ?";
    $params[] = $auction_id;
    $params[] = $user_id;
    $types .= "ii";
    
    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    
    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Auction updated successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Auction update failed: ' . $stmt->error
        ];
    }
}

/**
 * Delete an auction
 * @param int $auction_id Auction ID
 * @param int $user_id User ID of the auction creator (for verification)
 * @return array Result of the deletion attempt
 */
function delete_auction($auction_id, $user_id = null) {
    global $conn;
    
    // Get auction
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        return [
            'success' => false,
            'message' => 'Auction not found'
        ];
    }
    
    // Resolve user and admin from session if not supplied
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = (int) $_SESSION['user_id'];
    }
    $is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] == ROLE_ADMIN;
    if ($auction['user_id'] != $user_id && !$is_admin) {
        return [
            'success' => false,
            'message' => 'You do not have permission to delete this auction'
        ];
    }
    
    // Check if auction has bids and user is not admin
    if ($auction['bid_count'] > 0 && !$is_admin) {
        return [
            'success' => false,
            'message' => 'Cannot delete auction that already has bids'
        ];
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete auction images from database
        $sql = "SELECT image_path FROM auction_images WHERE auction_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $auction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $image_paths = [];
        while ($row = $result->fetch_assoc()) {
            $image_paths[] = $row['image_path'];
        }
        
        // Delete auction images from database
        $sql = "DELETE FROM auction_images WHERE auction_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $auction_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete auction images from database: ' . $stmt->error);
        }
        
        // Delete bids
        $sql = "DELETE FROM bids WHERE auction_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $auction_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete auction bids: ' . $stmt->error);
        }
        
        // Delete watchlist entries
        $sql = "DELETE FROM watchlist WHERE auction_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $auction_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete auction watchlist entries: ' . $stmt->error);
        }
        
        // Delete auction
        $sql = "DELETE FROM auctions WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $auction_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete auction: ' . $stmt->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Delete image files
        foreach ($image_paths as $path) {
            $filepath = UPLOAD_DIR . $path;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        return [
            'success' => true,
            'message' => 'Auction deleted successfully'
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Place a bid on an auction
 * @param int $auction_id Auction ID
 * @param int $user_id User ID of the bidder
 * @param float $bid_amount Bid amount
 * @return array Result of the bid attempt
 */
function place_bid($auction_id, $user_id, $bid_amount) {
    global $conn;
    
    // Get auction
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        return [
            'success' => false,
            'message' => 'Auction not found'
        ];
    }
    
    // Check if auction is active (support both int constants and string values)
    $statusValue = $auction['status'];
    $isActive = ($statusValue == AUCTION_STATUS_ACTIVE) || (is_string($statusValue) && strtolower($statusValue) === 'active');
    if (!$isActive) {
        return [
            'success' => false,
            'message' => 'This auction is not active'
        ];
    }
    
    // Check if auction has ended
    if (isset($auction['end_date']) && strtotime($auction['end_date']) <= time()) {
        return [
            'success' => false,
            'message' => 'This auction has ended'
        ];
    }
    
    // Check if user is the auction creator
    if ($auction['user_id'] == $user_id) {
        return [
            'success' => false,
            'message' => 'You cannot bid on your own auction'
        ];
    }
    
    // Validate bid amount
    $bid_amount = (float) $bid_amount;
    
    // If no bids yet, bid must be at least the starting price
    if ($auction['bid_count'] == 0 && $bid_amount < $auction['starting_price']) {
        return [
            'success' => false,
            'message' => 'Bid amount must be at least the starting price: ' . format_currency($auction['starting_price'])
        ];
    }
    
    // If there are bids, bid must be higher than current price by minimum increment
    if ($auction['bid_count'] > 0) {
        $min_bid = $auction['current_price'] + get_min_bid_increment($auction['current_price']);
        
        if ($bid_amount < $min_bid) {
            return [
                'success' => false,
                'message' => 'Bid amount must be at least ' . format_currency($min_bid)
            ];
        }
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert bid (created_at has default NOW())
        $amountCol = get_bids_amount_column();
        $sql = "INSERT INTO bids (auction_id, user_id, $amountCol) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iid", $auction_id, $user_id, $bid_amount);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to place bid: ' . $stmt->error);
        }
        
        $bid_id = $stmt->insert_id;
        
        // Update auction current_price only; bid_count is derived via COUNT(bids)
        $sql = 'UPDATE auctions SET current_price = ? WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $bid_amount, $auction_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update auction: ' . $stmt->error);
        }
        
        // Notify auction owner of new bid
        $notification_data = [
            'auction_id' => $auction_id,
            'auction_title' => $auction['title'],
            'bid_amount' => $bid_amount,
            'bidder_id' => $user_id,
            'bidder_username' => get_username_by_id($user_id)
        ];
        
        create_notification($auction['user_id'], NOTIFICATION_NEW_BID, $notification_data);
        
        // Notify previous high bidder that they've been outbid
        if ($auction['bid_count'] > 0) {
            $amountCol = get_bids_amount_column();
            $createdCol = get_bids_created_column();
            $sql = "SELECT user_id FROM bids WHERE auction_id = ? AND user_id != ? ORDER BY $amountCol DESC, $createdCol ASC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $auction_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $previous_bidder = $result->fetch_assoc();
                
                if ($previous_bidder['user_id'] != $user_id) {
                    $notification_data = [
                        'auction_id' => $auction_id,
                        'auction_title' => $auction['title'],
                        'bid_amount' => $bid_amount,
                        'bidder_id' => $user_id,
                        'bidder_username' => get_username_by_id($user_id)
                    ];
                    
                    create_notification($previous_bidder['user_id'], NOTIFICATION_OUTBID, $notification_data);
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Bid placed successfully',
            'bid_id' => $bid_id,
            'current_price' => $bid_amount
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get minimum bid increment based on current price
 * @param float $current_price Current auction price
 * @return float Minimum bid increment
 */
function get_min_bid_increment($current_price) {
    // Define bid increments based on price ranges
    if ($current_price < 10) {
        return 0.5; // ₹0.50 increment for items under ₹10
    } elseif ($current_price < 50) {
        return 1; // ₹1 increment for items ₹10-₹49.99
    } elseif ($current_price < 100) {
        return 2; // ₹2 increment for items ₹50-₹99.99
    } elseif ($current_price < 500) {
        return 5; // ₹5 increment for items ₹100-₹499.99
    } elseif ($current_price < 1000) {
        return 10; // ₹10 increment for items ₹500-₹999.99
    } elseif ($current_price < 5000) {
        return 50; // ₹50 increment for items ₹1000-₹4999.99
    } else {
        return 100; // ₹100 increment for items ₹5000+
    }
}

/**
 * Add auction to user's watchlist
 * @param int $auction_id Auction ID
 * @param int $user_id User ID
 * @return array Result of the operation
 */
function add_to_watchlist($auction_id, $user_id) {
    global $conn;
    
    // Check if auction exists
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        return [
            'success' => false,
            'message' => 'Auction not found'
        ];
    }
    
    // Check if already in watchlist
    $sql = "SELECT id FROM watchlist WHERE auction_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $auction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return [
            'success' => true,
            'message' => 'Auction is already in your watchlist'
        ];
    }
    
    // Add to watchlist
    $sql = "INSERT INTO watchlist (auction_id, user_id, added_date) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $auction_id, $user_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Auction added to watchlist'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add auction to watchlist: ' . $stmt->error
        ];
    }
}

/**
 * Remove auction from user's watchlist
 * @param int $auction_id Auction ID
 * @param int $user_id User ID
 * @return array Result of the operation
 */
function remove_from_watchlist($auction_id, $user_id) {
    global $conn;
    
    // Delete from watchlist
    $sql = "DELETE FROM watchlist WHERE auction_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $auction_id, $user_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Auction removed from watchlist'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to remove auction from watchlist: ' . $stmt->error
        ];
    }
}

/**
 * Get user's watchlist
 * @param int $user_id User ID
 * @param int $page Page number
 * @param int $per_page Items per page
 * @return array Watchlist items with pagination info
 */
function get_user_watchlist($user_id, $page = 1, $per_page = 10) {
    global $conn;
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $sql = "SELECT COUNT(*) as total FROM watchlist WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total = $row['total'];
    
    // Get watchlist items with auction details
    $sql = "SELECT w.id, w.added_date, a.*, 
            (SELECT image_path FROM auction_images WHERE auction_id = a.id ORDER BY id ASC LIMIT 1) as image_path,
            c.name as category_name, u.username as seller_username
            FROM watchlist w
            JOIN auctions a ON w.auction_id = a.id
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.user_id = u.id
            WHERE w.user_id = ?
            ORDER BY w.added_date DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    // Calculate pagination info
    $total_pages = ceil($total / $per_page);
    
    return [
        'items' => $items,
        'pagination' => [
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => $total_pages
        ]
    ];
}

/**
 * Get user's auctions (created by user)
 * @param int $user_id User ID
 * @param int $page Page number
 * @param int $per_page Items per page
 * @param string $status Filter by status (optional)
 * @return array Auctions with pagination info
 */
if (!function_exists('get_user_auctions')) {
function get_user_auctions($user_id, $page = 1, $per_page = 10, $status = null) {
    global $conn;
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Build query
    $where_clause = "WHERE a.user_id = ?";
    $params = [$user_id];
    $types = "i";
    
    if ($status !== null) {
        $where_clause .= " AND a.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Get total count
    $sql = "SELECT COUNT(*) as total FROM auctions a $where_clause";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total = $row['total'];
    
    // Get auctions
    $sql = "SELECT a.*, 
            (SELECT image_path FROM auction_images WHERE auction_id = a.id ORDER BY id ASC LIMIT 1) as image_path,
            c.name as category_name,
            (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
            (SELECT MAX(" . get_bids_amount_column() . ") FROM bids WHERE auction_id = a.id) as highest_bid
            FROM auctions a
            JOIN categories c ON a.category_id = c.id
            $where_clause
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $auctions = [];
    while ($row = $result->fetch_assoc()) {
        $auctions[] = $row;
    }
    
    // Calculate pagination info
    $total_pages = ceil($total / $per_page);
    
    return [
        'auctions' => $auctions,
        'pagination' => [
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => $total_pages
        ]
    ];
}
}

/**
 * Get user's bids
 * @param int $user_id User ID
 * @param int $page Page number
 * @param int $per_page Items per page
 * @param string $status Filter by auction status (optional)
 * @return array Bids with pagination info
 */
if (!function_exists('get_user_bids')) {
function get_user_bids($user_id, $page = 1, $per_page = 10, $status = null) {
    global $conn;
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Build query
    $where_clause = "WHERE b.user_id = ?";
    $params = [$user_id];
    $types = "i";
    
    if ($status !== null) {
        $where_clause .= " AND a.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Get total count
    $sql = "SELECT COUNT(DISTINCT a.id) as total 
            FROM bids b 
            JOIN auctions a ON b.auction_id = a.id 
            $where_clause";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total = $row['total'];
    
    // Get bids with auction details
    $sql = "SELECT a.*, 
            (SELECT image_path FROM auction_images WHERE auction_id = a.id ORDER BY id ASC LIMIT 1) as image_path,
            c.name as category_name, 
            u.username as seller_username,
            (SELECT MAX(" . get_bids_amount_column() . ") FROM bids WHERE auction_id = a.id AND user_id = ?) as your_bid,
            (SELECT " . get_bids_created_column() . " FROM bids WHERE auction_id = a.id AND user_id = ? ORDER BY " . get_bids_amount_column() . " DESC LIMIT 1) as your_bid_time,
            (SELECT MAX(" . get_bids_amount_column() . ") FROM bids WHERE auction_id = a.id) as highest_bid,
            (SELECT user_id FROM bids WHERE auction_id = a.id ORDER BY " . get_bids_amount_column() . " DESC, " . get_bids_created_column() . " ASC LIMIT 1) as highest_bidder_id
            FROM auctions a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.user_id = u.id
            JOIN bids b ON a.id = b.auction_id
            $where_clause
            GROUP BY a.id
            ORDER BY your_bid_time DESC
            LIMIT ? OFFSET ?";
    
    $params = array_merge([$user_id, $user_id], $params, [$per_page, $offset]);
    $types = "ii" . $types . "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bids = [];
    while ($row = $result->fetch_assoc()) {
        // Add winning status
        if ($row['status'] == AUCTION_STATUS_ENDED) {
            $row['is_winner'] = ($row['highest_bidder_id'] == $user_id);
        } else {
            $row['is_winner'] = null;
        }
        
        $bids[] = $row;
    }
    
    // Calculate pagination info
    $total_pages = ceil($total / $per_page);
    
    return [
        'bids' => $bids,
        'pagination' => [
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => $total_pages
        ]
    ];
}
}

/**
 * Get auction by ID
 * @param int $auction_id Auction ID
 * @return array|false Auction data or false if not found
 */
if (!function_exists('get_auction_by_id')) {
function get_auction_by_id($auction_id) {
    global $conn;
    
    $sql = "SELECT a.*, 
            a.start_date AS start_date,
            a.end_date AS end_date,
            c.name as category_name, 
            u.username as seller_username,
            u.email as seller_email,
            u.profile_image as seller_profile_image,
            (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
            (SELECT COUNT(*) FROM auction_images WHERE auction_id = a.id) as image_count
            FROM auctions a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.user_id = u.id
            WHERE a.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    } else {
        return false;
    }
}
}

/**
 * Get auction images
 * @param int $auction_id Auction ID
 * @return array Array of image paths
 */
if (!function_exists('get_auction_images')) {
function get_auction_images($auction_id) {
    global $conn;
    
    $sql = "SELECT id, image_path FROM auction_images WHERE auction_id = ? ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    
    return $images;
}
}

/**
 * Get auction bids
 * @param int $auction_id Auction ID
 * @param int $limit Number of bids to return (0 for all)
 * @return array Array of bids
 */
if (!function_exists('get_auction_bids')) {
function get_auction_bids($auction_id, $limit = 0) {
    global $conn;
    
    // Resolve column names across schema variants
    $amountCol = get_bids_amount_column();
    $createdCol = get_bids_created_column();
    
    $sql = "SELECT 
                b.id,
                b.auction_id,
                b.user_id,
                b.$amountCol AS amount,
                b.$createdCol AS bid_date,
                u.username 
            FROM bids b
            JOIN users u ON b.user_id = u.id
            WHERE b.auction_id = ?
            ORDER BY b.$amountCol DESC, b.$createdCol ASC";
    
    if ($limit > 0) {
        $sql .= " LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $auction_id, $limit);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $auction_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bids = [];
    while ($row = $result->fetch_assoc()) {
        $bids[] = $row;
    }
    
    return $bids;
}
}

/**
 * Check if user is watching an auction
 * @param int $auction_id Auction ID
 * @param int $user_id User ID
 * @return bool True if user is watching the auction
 */
function is_user_watching($auction_id, $user_id) {
    global $conn;
    
    $sql = "SELECT id FROM watchlist WHERE auction_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $auction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return ($result->num_rows > 0);
}

/**
 * Compatibility wrapper: check if a user is watching an auction
 * Previous code used check_watchlist($user_id, $auction_id)
 */
if (!function_exists('check_watchlist')) {
function check_watchlist($user_id, $auction_id) {
    return is_user_watching((int)$auction_id, (int)$user_id);
}
}

/**
 * Get similar auctions based on category
 * @param int $auction_id Current auction ID
 * @param int $category_id Category ID
 * @param int $limit Number of auctions to return
 * @return array Array of similar auctions
 */
if (!function_exists('get_similar_auctions')) {
function get_similar_auctions($auction_id, $category_id, $limit = 4) {
    global $conn;
    
    $sql = "SELECT a.*, 
            (SELECT image_path FROM auction_images WHERE auction_id = a.id ORDER BY id ASC LIMIT 1) as image_path,
            c.name as category_name, 
            u.username as seller_username
            FROM auctions a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.user_id = u.id
            WHERE a.category_id = ? AND a.id != ? AND a.status = ?
            ORDER BY a.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $status = AUCTION_STATUS_ACTIVE;
    $stmt->bind_param("iisi", $category_id, $auction_id, $status, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $auctions = [];
    while ($row = $result->fetch_assoc()) {
        $auctions[] = $row;
    }
    
    return $auctions;
}
}

/**
 * Get featured auctions
 * @param int $limit Number of auctions to return
 * @return array Array of featured auctions
 */
if (!function_exists('get_featured_auctions')) {
function get_featured_auctions($limit = 8) {
    global $conn;
    
    $sql = "SELECT a.*, 
            (SELECT image_path FROM auction_images WHERE auction_id = a.id ORDER BY id ASC LIMIT 1) as image_path,
            c.name as category_name, 
            u.username as seller_username,
            (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
            COALESCE((SELECT MAX(amount) FROM bids WHERE auction_id = a.id), a.starting_price) as current_price
            FROM auctions a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.user_id = u.id
            WHERE a.status = ?
            ORDER BY bid_count DESC, a.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $status = AUCTION_STATUS_ACTIVE;
    $stmt->bind_param("ii", $status, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $auctions = [];
    while ($row = $result->fetch_assoc()) {
        $auctions[] = $row;
    }
    
    return $auctions;
}
}

/**
 * Get ending soon auctions
 * @param int $limit Number of auctions to return
 * @param int $hours Hours threshold (default 24)
 * @return array Array of ending soon auctions
 */
if (!function_exists('get_ending_soon_auctions')) {
function get_ending_soon_auctions($limit = 8, $hours = 24) {
    global $conn;
    
    $sql = "SELECT a.*, 
            (SELECT image_path FROM auction_images WHERE auction_id = a.id ORDER BY id ASC LIMIT 1) as image_path,
            c.name as category_name, 
            u.username as seller_username,
            (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
            COALESCE((SELECT MAX(amount) FROM bids WHERE auction_id = a.id), a.starting_price) as current_price
            FROM auctions a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.user_id = u.id
            WHERE a.status = ? AND a.end_date <= DATE_ADD(NOW(), INTERVAL ? HOUR)
            ORDER BY a.end_date ASC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $status = AUCTION_STATUS_ACTIVE;
    $stmt->bind_param("iii", $status, $hours, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $auctions = [];
    while ($row = $result->fetch_assoc()) {
        $auctions[] = $row;
    }
    
    return $auctions;
}
}

/**
 * Get recently added auctions
 * @param int $limit Number of auctions to return
 * @return array Array of recently added auctions
 */
if (!function_exists('get_recent_auctions')) {
function get_recent_auctions($limit = 8) {
    global $conn;
    
    $sql = "SELECT a.*, 
            (SELECT image_path FROM auction_images WHERE auction_id = a.id ORDER BY id ASC LIMIT 1) as image_path,
            c.name as category_name, 
            u.username as seller_username,
            (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
            COALESCE((SELECT MAX(amount) FROM bids WHERE auction_id = a.id), a.starting_price) as current_price
            FROM auctions a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.user_id = u.id
            WHERE a.status = ?
            ORDER BY a.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $status = AUCTION_STATUS_ACTIVE;
    $stmt->bind_param("ii", $status, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $auctions = [];
    while ($row = $result->fetch_assoc()) {
        $auctions[] = $row;
    }
    
    return $auctions;
}
}

/**
 * Get recently added auctions (alias for get_recent_auctions)
 * @param int $limit Number of auctions to return
 * @return array Array of recently added auctions
 */
if (!function_exists('get_recently_added_auctions')) {
function get_recently_added_auctions($limit = 8) {
    return get_recent_auctions($limit);
}
}

/**
 * Search auctions
 * @param string $keyword Search keyword
 * @param int $category_id Category ID (optional)
 * @param float $min_price Minimum price (optional)
 * @param float $max_price Maximum price (optional)
 * @param string $sort_by Sort field (optional)
 * @param string $sort_order Sort order (optional)
 * @param int $page Page number
 * @param int $per_page Items per page
 * @return array Search results with pagination info
 */
if (!function_exists('search_auctions')) {
function search_auctions($keyword = '', $category_id = 0, $min_price = 0, $max_price = 0, $sort_by = 'created_at', $sort_order = 'DESC', $page = 1, $per_page = 12) {
    global $conn;
    
    // Support legacy call signature used by browse.php: 
    // search_auctions($keyword, $category_id, $sort, $min_price, $max_price, $condition, $status, $limit, $offset)
    if (is_string($min_price)) {
        $args = func_get_args();
        $legacy_sort = isset($args[2]) ? (string)$args[2] : 'newest';
        $legacy_min = isset($args[3]) ? (float)$args[3] : 0;
        $legacy_max = isset($args[4]) ? (float)$args[4] : 0;
        $legacy_status = isset($args[6]) ? (string)$args[6] : 'active';
        $legacy_limit = isset($args[7]) ? (int)$args[7] : 12;
        $legacy_offset = isset($args[8]) ? (int)$args[8] : 0;

        // Map sort
        switch ($legacy_sort) {
            case 'ending':
                $sort_by = 'end_date';
                $sort_order = 'ASC';
                break;
            case 'price_low':
                $sort_by = 'current_price';
                $sort_order = 'ASC';
                break;
            case 'price_high':
                $sort_by = 'current_price';
                $sort_order = 'DESC';
                break;
            case 'bids':
                $sort_by = 'bid_count';
                $sort_order = 'DESC';
                break;
            case 'newest':
            default:
                $sort_by = 'created_at';
                $sort_order = 'DESC';
                break;
        }

        $min_price = $legacy_min;
        $max_price = $legacy_max;
        $per_page = max(1, $legacy_limit);
        $page = (int) floor(($legacy_offset / max(1, $per_page)) + 1);

        // Add status filter hint by attaching to keyword to avoid changing signature; we'll handle below
        $keyword = (string)$keyword;
        $status_hint = $legacy_status;
    } else {
        $status_hint = null;
    }

    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Build query - handle both string and integer status values
    $where_clause = "WHERE a.status = ?";
    $params = ['active']; // Use string 'active' instead of constant
    $types = "s";

    // If legacy call explicitly asked for ended/all
    if ($status_hint === 'ended') {
        $where_clause = "WHERE a.status = ?";
        $params = ['ended'];
        $types = "s";
    } elseif ($status_hint === 'all') {
        $where_clause = "WHERE 1=1";
        $params = [];
        $types = "";
    }
    
    // Add keyword search
    if (!empty($keyword)) {
        $keyword = "%$keyword%";
        $where_clause .= " AND (a.title LIKE ? OR a.description LIKE ?)";
        $params[] = $keyword;
        $params[] = $keyword;
        $types .= "ss";
    }
    
    // Add category filter
    if ($category_id > 0) {
        $where_clause .= " AND a.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    // Add price range filter
    if ($min_price > 0) {
        $where_clause .= " AND COALESCE((SELECT MAX(amount) FROM bids WHERE auction_id = a.id), a.starting_price) >= ?";
        $params[] = $min_price;
        $types .= "d";
    }
    
    if ($max_price > 0) {
        $where_clause .= " AND COALESCE((SELECT MAX(amount) FROM bids WHERE auction_id = a.id), a.starting_price) <= ?";
        $params[] = $max_price;
        $types .= "d";
    }
    
    // Validate sort parameters
    $allowed_sort_fields = ['title', 'current_price', 'end_date', 'created_at', 'bid_count'];
    $allowed_sort_orders = ['ASC', 'DESC'];
    
    if (!in_array($sort_by, $allowed_sort_fields)) {
        $sort_by = 'created_at';
    }
    
    if (!in_array(strtoupper($sort_order), $allowed_sort_orders)) {
        $sort_order = 'DESC';
    }
    
    // Get total count
    $sql = "SELECT COUNT(*) as total FROM auctions a $where_clause";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total = $row['total'];
    
    // Get auctions
    $sql = "SELECT a.*, 
            (SELECT image_path FROM auction_images WHERE auction_id = a.id ORDER BY id ASC LIMIT 1) as image_path,
            c.name as category_name, 
            u.username as seller_username,
            (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
            COALESCE((SELECT MAX(amount) FROM bids WHERE auction_id = a.id), a.starting_price) as current_price
            FROM auctions a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.user_id = u.id
            $where_clause
            ORDER BY " . ($sort_by === 'current_price' ? 'COALESCE((SELECT MAX(amount) FROM bids WHERE auction_id = a.id), a.starting_price)' : "a.$sort_by") . " $sort_order
            LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $auctions = [];
    while ($row = $result->fetch_assoc()) {
        $auctions[] = $row;
    }
    
    // Calculate pagination info
    $total_pages = ceil($total / $per_page);
    
    return [
        'auctions' => $auctions,
        'pagination' => [
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => $total_pages
        ],
        'filters' => [
            'keyword' => $keyword ? trim($keyword, '%') : '',
            'category_id' => $category_id,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order
        ]
    ];
}

/**
 * Count search auctions (for browse.php compatibility)
 * @param string $keyword Search keyword
 * @param int $category_id Category ID
 * @param float $min_price Minimum price
 * @param float $max_price Maximum price
 * @param string $condition Item condition
 * @param string $status Auction status
 * @return int Number of auctions found
 */
function count_search_auctions($keyword = '', $category_id = 0, $min_price = 0, $max_price = 0, $condition = '', $status = 'active') {
    global $conn;
    
    // Build query - handle both string and integer status values
    $where_clause = "WHERE a.status = ?";
    $params = ['active']; // Use string 'active' instead of constant
    $types = "s";

    // Handle status filter
    if ($status === 'ended') {
        $where_clause = "WHERE a.status = ?";
        $params = ['ended'];
        $types = "s";
    } elseif ($status === 'all') {
        $where_clause = "WHERE 1=1";
        $params = [];
        $types = "";
    }
    
    // Add keyword search
    if (!empty($keyword)) {
        $keyword = "%$keyword%";
        $where_clause .= " AND (a.title LIKE ? OR a.description LIKE ?)";
        $params[] = $keyword;
        $params[] = $keyword;
        $types .= "ss";
    }
    
    // Add category filter
    if ($category_id > 0) {
        $where_clause .= " AND a.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    // Add price range filter
    if ($min_price > 0) {
        $where_clause .= " AND COALESCE((SELECT MAX(amount) FROM bids WHERE auction_id = a.id), a.starting_price) >= ?";
        $params[] = $min_price;
        $types .= "d";
    }
    
    if ($max_price > 0) {
        $where_clause .= " AND COALESCE((SELECT MAX(amount) FROM bids WHERE auction_id = a.id), a.starting_price) <= ?";
        $params[] = $max_price;
        $types .= "d";
    }
    
    // Get total count
    $sql = "SELECT COUNT(*) as total FROM auctions a $where_clause";
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (int)$row['total'];
}
}

/**
 * Get all categories
 * @return array Array of categories
 */
function get_all_categories() {
    global $conn;
    
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

/**
 * Get all categories with auction counts
 * @return array Array of categories with auction counts
 */
function get_all_categories_with_counts() {
    global $conn;
    
    $sql = "SELECT c.*, COUNT(a.id) as auction_count 
            FROM categories c
            LEFT JOIN auctions a ON c.id = a.category_id AND a.status = ?
            GROUP BY c.id
            ORDER BY c.name ASC";
    
    $stmt = $conn->prepare($sql);
    $status = AUCTION_STATUS_ACTIVE;
    $stmt->bind_param("i", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

/**
 * Get category by ID
 * @param int $category_id Category ID
 * @return array|false Category data or false if not found
 */
if (!function_exists('get_category_by_id')) {
function get_category_by_id($category_id) {
    global $conn;
    
    $sql = "SELECT * FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    } else {
        return false;
    }
}
}

/**
 * Create feedback for an auction
 * @param int $auction_id Auction ID
 * @param int $user_id User ID (reviewer)
 * @param int $rating Rating (1-5)
 * @param string $comment Comment
 * @param string $feedback_type Type of feedback (buyer or seller)
 * @return array Result of the feedback creation attempt
 */
function create_feedback($auction_id, $user_id, $rating, $comment, $feedback_type) {
    global $conn;
    
    // Get auction
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        return [
            'success' => false,
            'message' => 'Auction not found'
        ];
    }
    
    // Check if auction has ended
    if ($auction['status'] != AUCTION_STATUS_ENDED) {
        return [
            'success' => false,
            'message' => 'Feedback can only be left for ended auctions'
        ];
    }
    
    // Determine recipient based on feedback type
    if ($feedback_type == 'buyer') {
        // Seller leaving feedback for buyer
        if ($auction['user_id'] != $user_id) {
            return [
                'success' => false,
                'message' => 'Only the seller can leave buyer feedback'
            ];
        }
        
        // Get winning bidder
        $sql = "SELECT user_id FROM bids WHERE auction_id = ? ORDER BY bid_amount DESC, bid_time ASC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $auction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'No winning bidder found for this auction'
            ];
        }
        
        $winner = $result->fetch_assoc();
        $recipient_id = $winner['user_id'];
    } else {
        // Buyer leaving feedback for seller
        // Get winning bidder
        $sql = "SELECT user_id FROM bids WHERE auction_id = ? ORDER BY bid_amount DESC, bid_time ASC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $auction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'No winning bidder found for this auction'
            ];
        }
        
        $winner = $result->fetch_assoc();
        
        if ($winner['user_id'] != $user_id) {
            return [
                'success' => false,
                'message' => 'Only the winning bidder can leave seller feedback'
            ];
        }
        
        $recipient_id = $auction['user_id'];
    }
    
    // Check if feedback already exists
    $sql = "SELECT id FROM feedback WHERE auction_id = ? AND reviewer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $auction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return [
            'success' => false,
            'message' => 'You have already left feedback for this auction'
        ];
    }
    
    // Insert feedback
    $sql = "INSERT INTO feedback (auction_id, reviewer_id, recipient_id, rating, comment, feedback_type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisss", $auction_id, $user_id, $recipient_id, $rating, $comment, $feedback_type);
    
    if ($stmt->execute()) {
        // Notify recipient of new feedback
        $notification_data = [
            'auction_id' => $auction_id,
            'auction_title' => $auction['title'],
            'feedback_id' => $stmt->insert_id,
            'reviewer_id' => $user_id,
            'reviewer_username' => get_username_by_id($user_id),
            'rating' => $rating
        ];
        
        create_notification($recipient_id, NOTIFICATION_NEW_FEEDBACK, $notification_data);
        
        return [
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'feedback_id' => $stmt->insert_id
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to submit feedback: ' . $stmt->error
        ];
    }
}

/**
 * Compatibility wrapper: add auction feedback
 * Previous code used add_auction_feedback($auction_id, $user_id, $rating, $comment)
 * We infer feedback_type based on whether reviewer is seller (buyer feedback) or buyer (seller feedback).
 */
if (!function_exists('add_auction_feedback')) {
function add_auction_feedback($auction_id, $user_id, $rating, $comment) {
    $auction = get_auction_by_id((int)$auction_id);
    if (!$auction) {
        return [ 'success' => false, 'message' => 'Auction not found' ];
    }
    $feedback_type = ($auction['user_id'] == (int)$user_id) ? 'buyer' : 'seller';
    return create_feedback((int)$auction_id, (int)$user_id, (int)$rating, (string)$comment, $feedback_type);
}
}

/**
 * Get feedback for an auction
 * @param int $auction_id Auction ID
 * @return array Array of feedback
 */
function get_auction_feedback($auction_id) {
    global $conn;
    
    $sql = "SELECT f.*, u.username as reviewer_username 
            FROM feedback f
            JOIN users u ON f.reviewer_id = u.id
            WHERE f.auction_id = ?
            ORDER BY f.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $feedback = [];
    while ($row = $result->fetch_assoc()) {
        $feedback[] = $row;
    }
    
    return $feedback;
}

/**
 * Get user feedback
 * @param int $user_id User ID
 * @param string $type Feedback type (received or given)
 * @param int $page Page number
 * @param int $per_page Items per page
 * @return array Feedback with pagination info
 */
function get_user_feedback($user_id, $type = 'received', $page = 1, $per_page = 10) {
    global $conn;
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Build query based on type
    if ($type == 'received') {
        $where_clause = "WHERE f.recipient_id = ?";
    } else {
        $where_clause = "WHERE f.reviewer_id = ?";
    }
    
    // Get total count
    $sql = "SELECT COUNT(*) as total FROM feedback f $where_clause";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total = $row['total'];
    
    // Get feedback
    $sql = "SELECT f.*, 
            a.title as auction_title, 
            u.username as reviewer_username,
            r.username as recipient_username
            FROM feedback f
            JOIN auctions a ON f.auction_id = a.id
            JOIN users u ON f.reviewer_id = u.id
            JOIN users r ON f.recipient_id = r.id
            $where_clause
            ORDER BY f.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $feedback = [];
    while ($row = $result->fetch_assoc()) {
        $feedback[] = $row;
    }
    
    // Calculate pagination info
    $total_pages = ceil($total / $per_page);
    
    return [
        'feedback' => $feedback,
        'pagination' => [
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => $total_pages
        ]
    ];
}

/**
 * Get user feedback summary
 * @param int $user_id User ID
 * @return array Feedback summary
 */
function get_user_feedback_summary($user_id) {
    global $conn;
    
    $sql = "SELECT 
            COUNT(*) as total_feedback,
            AVG(rating) as average_rating,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM feedback
            WHERE recipient_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $summary = $result->fetch_assoc();
        
        // Calculate percentages
        if ($summary['total_feedback'] > 0) {
            $summary['five_star_percent'] = ($summary['five_star'] / $summary['total_feedback']) * 100;
            $summary['four_star_percent'] = ($summary['four_star'] / $summary['total_feedback']) * 100;
            $summary['three_star_percent'] = ($summary['three_star'] / $summary['total_feedback']) * 100;
            $summary['two_star_percent'] = ($summary['two_star'] / $summary['total_feedback']) * 100;
            $summary['one_star_percent'] = ($summary['one_star'] / $summary['total_feedback']) * 100;
        } else {
            $summary['five_star_percent'] = 0;
            $summary['four_star_percent'] = 0;
            $summary['three_star_percent'] = 0;
            $summary['two_star_percent'] = 0;
            $summary['one_star_percent'] = 0;
        }
        
        return $summary;
    } else {
        return [
            'total_feedback' => 0,
            'average_rating' => 0,
            'five_star' => 0,
            'four_star' => 0,
            'three_star' => 0,
            'two_star' => 0,
            'one_star' => 0,
            'five_star_percent' => 0,
            'four_star_percent' => 0,
            'three_star_percent' => 0,
            'two_star_percent' => 0,
            'one_star_percent' => 0
        ];
    }
}

/**
 * Create notification
 * @param int $user_id User ID to notify
 * @param string $type Notification type
 * @param array $data Notification data (should include 'title' and 'message')
 * @return int|false Notification ID or false on failure
 */
function create_notification($user_id, $type, $data = []) {
    global $conn;
    
    // Ensure required fields exist
    $notification_data = array_merge([
        'title' => 'Notification',
        'message' => 'You have a new notification',
        'link' => '#'
    ], $data);
    
    $sql = "INSERT INTO notifications (user_id, type, data, created_at, is_read) VALUES (?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    $json_data = json_encode($notification_data);
    $is_read = NOTIFICATION_UNREAD;
    
    $stmt->bind_param("issi", $user_id, $type, $json_data, $is_read);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    } else {
        return false;
    }
}

/**
 * Migrate existing notifications to new format
 * This function updates old notifications that don't have proper JSON data structure
 */
function migrate_notifications() {
    global $conn;
    
    // Get notifications that might need migration
    $sql = "SELECT id, data FROM notifications WHERE data IS NULL OR data = '' OR data = '[]' OR data = '{}'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $default_data = [
                'title' => 'Welcome to AuctionHUB',
                'message' => 'Thank you for joining our auction platform!',
                'link' => 'browse.php'
            ];
            
            $update_sql = "UPDATE notifications SET data = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $json_data = json_encode($default_data);
            $stmt->bind_param("si", $json_data, $row['id']);
            $stmt->execute();
        }
    }
}

/**
 * Create sample notifications for testing
 * @param int $user_id User ID to create notifications for
 */
function create_sample_notifications($user_id) {
    $sample_notifications = [
        [
            'type' => 0, // Use numeric type for welcome notification
            'data' => [
                'title' => 'Welcome to AuctionHUB!',
                'message' => 'Thank you for joining our auction platform. Start bidding on amazing items!',
                'link' => 'browse.php'
            ]
        ],
        [
            'type' => 1, // Use numeric type for bid notification
            'data' => [
                'title' => 'Bid Placed Successfully',
                'message' => 'Your bid has been placed on the Lenovo laptop auction.',
                'link' => 'auction.php?id=1'
            ]
        ],
        [
            'type' => 2, // Use numeric type for outbid notification
            'data' => [
                'title' => 'You\'ve Been Outbid',
                'message' => 'Someone placed a higher bid on the Lenovo laptop auction.',
                'link' => 'auction.php?id=1'
            ]
        ]
    ];
    
    foreach ($sample_notifications as $notification) {
        create_notification($user_id, $notification['type'], $notification['data']);
    }
}


/**
 * Count unread notifications
 * @param int $user_id User ID
 * @return int Number of unread notifications
 */
function count_unread_notifications($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = ?";
    $stmt = $conn->prepare($sql);
    $is_unread = NOTIFICATION_UNREAD;
    
    $stmt->bind_param("ii", $user_id, $is_unread);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Check if auctions have ended and update their status
 * This function should be called periodically (e.g., via cron job)
 * @return array Result of the update
 */
function check_ended_auctions() {
    global $conn;
    
    // Find auctions that have ended but still have active status
    $sql = "SELECT id, title, user_id, current_price, reserve_price FROM auctions 
            WHERE status = ? AND end_date <= NOW()";
    
    $stmt = $conn->prepare($sql);
    $status = AUCTION_STATUS_ACTIVE;
    $stmt->bind_param("i", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $updated_count = 0;
    $notified_count = 0;
    $ended_auctions = [];
    
    while ($auction = $result->fetch_assoc()) {
        // Update auction status
        $sql = "UPDATE auctions SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $ended_status = AUCTION_STATUS_ENDED;
        $stmt->bind_param("ii", $ended_status, $auction['id']);
        
        if ($stmt->execute()) {
            $updated_count++;
            $ended_auctions[] = $auction['id'];
            
            // Get highest bidder
            $sql = "SELECT user_id, bid_amount FROM bids 
                    WHERE auction_id = ? 
                    ORDER BY bid_amount DESC, bid_time ASC 
                    LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $auction['id']);
            $stmt->execute();
            $bid_result = $stmt->get_result();
            
            if ($bid_result->num_rows > 0) {
                $highest_bid = $bid_result->fetch_assoc();
                $winner_id = $highest_bid['user_id'];
                $winning_bid = $highest_bid['bid_amount'];
                
                // Check if reserve price was met
                $reserve_met = ($auction['reserve_price'] <= 0 || $winning_bid >= $auction['reserve_price']);
                
                // Notify seller
                $seller_data = [
                    'auction_id' => $auction['id'],
                    'auction_title' => $auction['title'],
                    'winning_bid' => $winning_bid,
                    'winner_id' => $winner_id,
                    'winner_username' => get_username_by_id($winner_id),
                    'reserve_met' => $reserve_met
                ];
                
                create_notification($auction['user_id'], NOTIFICATION_AUCTION_ENDED, $seller_data);
                
                // Notify winner
                $winner_data = [
                    'auction_id' => $auction['id'],
                    'auction_title' => $auction['title'],
                    'winning_bid' => $winning_bid,
                    'seller_id' => $auction['user_id'],
                    'seller_username' => get_username_by_id($auction['user_id']),
                    'reserve_met' => $reserve_met
                ];
                
                create_notification($winner_id, NOTIFICATION_AUCTION_WON, $winner_data);
                
                $notified_count += 2; // Seller and winner
            } else {
                // No bids - notify seller
                $seller_data = [
                    'auction_id' => $auction['id'],
                    'auction_title' => $auction['title'],
                    'no_bids' => true
                ];
                
                create_notification($auction['user_id'], NOTIFICATION_AUCTION_ENDED, $seller_data);
                $notified_count++;
            }
        }
    }
    
    return [
        'success' => true,
        'updated_count' => $updated_count,
        'notified_count' => $notified_count,
        'ended_auctions' => $ended_auctions
    ];
}
/**
 * Feedback summary for an auction
 * @param int $auction_id
 * @return array
 */
function get_feedback_summary($auction_id) {
    global $conn;
    // Ensure feedback table exists in case database schema differs
    if (!function_exists('ensure_feedback_table')) {
        function ensure_feedback_table() {
            global $conn;
            $conn->query("CREATE TABLE IF NOT EXISTS feedback (
                id INT AUTO_INCREMENT PRIMARY KEY,
                auction_id INT NOT NULL,
                reviewer_id INT NOT NULL,
                recipient_id INT NOT NULL,
                rating TINYINT(1) NOT NULL,
                comment TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
                FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
            )");
        }
    }
    ensure_feedback_table();

    $stmt = $conn->prepare(
        "SELECT 
            COUNT(*) as total_ratings,
            AVG(rating) as average_rating,
            SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) as one_star
         FROM feedback WHERE auction_id = ?"
    );
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $total = max(0, (int)($row['total_ratings'] ?? 0));
    $avg = (float)($row['average_rating'] ?? 0);
    $pct = function($n) use ($total, $row) {
        $v = (int)($row[$n] ?? 0);
        return $total > 0 ? round(($v / $total) * 100) : 0;
    };

    return [
        'total_ratings' => $total,
        'average_rating' => round($avg, 1),
        'five_star_percent' => $pct('five_star'),
        'four_star_percent' => $pct('four_star'),
        'three_star_percent' => $pct('three_star'),
        'two_star_percent' => $pct('two_star'),
        'one_star_percent' => $pct('one_star'),
    ];
}

/**
 * Count user notifications with filter
 * @param int $user_id User ID
 * @param string $filter Filter type (all, read, unread)
 * @return int Number of notifications
 */
function count_user_notifications($user_id, $filter = 'all') {
    global $conn;
    
    $where_clause = "WHERE user_id = ?";
    $params = [$user_id];
    $types = "i";
    
    if ($filter === 'unread') {
        $where_clause .= " AND is_read = ?";
        $params[] = NOTIFICATION_UNREAD;
        $types .= "i";
    } elseif ($filter === 'read') {
        $where_clause .= " AND is_read = ?";
        $params[] = NOTIFICATION_READ;
        $types .= "i";
    }
    
    $sql = "SELECT COUNT(*) as count FROM notifications $where_clause";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
    
    return 0;
}

/**
 * Delete all read notifications
 * @param int $user_id User ID
 * @return array Success/error result
 */
function delete_all_read_notifications($user_id) {
    global $conn;
    
    // First, check how many read notifications exist
    $count_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = ?";
    $count_stmt = $conn->prepare($count_sql);
    $is_read = NOTIFICATION_READ;
    $count_stmt->bind_param("ii", $user_id, $is_read);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result()->fetch_assoc();
    $read_count = $count_result['count'];
    
    if ($read_count == 0) {
        return [
            'success' => true,
            'message' => 'No read notifications to delete'
        ];
    }
    
    // Delete all read notifications
    $sql = "DELETE FROM notifications WHERE user_id = ? AND is_read = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $is_read);
    
    if ($stmt->execute()) {
        $deleted_count = $stmt->affected_rows;
        return [
            'success' => true,
            'message' => "Deleted $deleted_count read notifications"
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete notifications: ' . $stmt->error
        ];
    }
}

/**
 * Delete all notifications for a user (regardless of read status)
 * @param int $user_id User ID
 * @return array Success/error result
 */
function delete_all_notifications($user_id) {
    global $conn;
    
    // First, check how many notifications exist
    $count_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result()->fetch_assoc();
    $total_count = $count_result['count'];
    
    if ($total_count == 0) {
        return [
            'success' => true,
            'message' => 'No notifications to delete'
        ];
    }
    
    // Delete all notifications
    $sql = "DELETE FROM notifications WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $deleted_count = $stmt->affected_rows;
        return [
            'success' => true,
            'message' => "Deleted $deleted_count notifications"
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete notifications: ' . $stmt->error
        ];
    }
}

/**
 * Delete specific notification
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for verification)
 * @return array Success/error result
 */
function delete_notification($notification_id, $user_id) {
    global $conn;
    
    $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("ii", $notification_id, $user_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Notification deleted'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete notification: ' . $stmt->error
        ];
    }
}

/**
 * Get user notifications with pagination and filtering
 * @param int $user_id User ID
 * @param int $offset Offset for pagination
 * @param int $limit Limit for pagination
 * @param string $filter Filter type (all, read, unread)
 * @return array Array of notifications
 */
function get_user_notifications($user_id, $offset = 0, $limit = 15, $filter = 'all') {
    global $conn;
    
    $where_clause = "WHERE user_id = ?";
    $params = [$user_id];
    $types = "i";
    
    if ($filter === 'unread') {
        $where_clause .= " AND is_read = ?";
        $params[] = NOTIFICATION_UNREAD;
        $types .= "i";
    } elseif ($filter === 'read') {
        $where_clause .= " AND is_read = ?";
        $params[] = NOTIFICATION_READ;
        $types .= "i";
    }
    
    $sql = "SELECT * FROM notifications $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON data if it exists
        $data = [];
        if (isset($row['data']) && !empty($row['data'])) {
            $data = json_decode($row['data'], true) ?: [];
        }
        
        // Map notification data to expected fields
        $notification = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'type' => $row['type'],
            'is_read' => $row['is_read'],
            'created_at' => $row['created_at'],
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? 'You have a new notification',
            'link' => $data['link'] ?? '#',
            'data' => $data
        ];
        
        $notifications[] = $notification;
    }
    
    return $notifications;
}

/**
 * Mark all notifications as read (with return array)
 * @param int $user_id User ID
 * @return array Success/error result
 */
function mark_all_notifications_read($user_id) {
    global $conn;
    
    $sql = "UPDATE notifications SET is_read = ? WHERE user_id = ? AND is_read = ?";
    $stmt = $conn->prepare($sql);
    $is_read = NOTIFICATION_READ;
    $is_unread = NOTIFICATION_UNREAD;
    
    $stmt->bind_param("iii", $is_read, $user_id, $is_unread);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'All notifications marked as read'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to mark notifications as read: ' . $stmt->error
        ];
    }
}

/**
 * Mark specific notification as read (with return array)
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for verification)
 * @return array Success/error result
 */
function mark_notification_read($notification_id, $user_id) {
    global $conn;
    
    $sql = "UPDATE notifications SET is_read = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $is_read = NOTIFICATION_READ;
    
    $stmt->bind_param("iii", $is_read, $notification_id, $user_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Notification marked as read'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to mark notification as read: ' . $stmt->error
        ];
    }
}

/**
 * Get notification statistics for a user
 * @param int $user_id User ID
 * @return array Notification statistics
 */
function get_notification_stats($user_id) {
    global $conn;
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = ? THEN 1 ELSE 0 END) as read_count,
                SUM(CASE WHEN is_read = ? THEN 1 ELSE 0 END) as unread_count
            FROM notifications 
            WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $is_read = NOTIFICATION_READ;
    $is_unread = NOTIFICATION_UNREAD;
    
    $stmt->bind_param("iii", $is_read, $is_unread, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'total' => (int)$row['total'],
            'read' => (int)$row['read_count'],
            'unread' => (int)$row['unread_count']
        ];
    }
    
    return [
        'total' => 0,
        'read' => 0,
        'unread' => 0
    ];
}

/**
 * Clean up old notifications (older than specified days)
 * @param int $days Number of days to keep notifications
 * @return array Result of cleanup
 */
function cleanup_old_notifications($days = 30) {
    global $conn;
    
    $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $days);
    
    if ($stmt->execute()) {
        $deleted_count = $stmt->affected_rows;
        return [
            'success' => true,
            'message' => "Cleaned up $deleted_count old notifications",
            'deleted_count' => $deleted_count
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to cleanup old notifications: ' . $stmt->error
        ];
    }
}

/**
 * Get user's maximum bid for a specific auction
 * @param int $user_id User ID
 * @param int $auction_id Auction ID
 * @return float Maximum bid amount or 0 if no bids
 */
function get_user_max_bid($user_id, $auction_id) {
    global $conn;
    
    $amountCol = get_bids_amount_column();
    $sql = "SELECT MAX($amountCol) as max_bid FROM bids WHERE user_id = ? AND auction_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (float)$row['max_bid'];
    }
    
    return 0;
}

/**
 * Count user's watchlist items
 * @param int $user_id User ID
 * @param string $filter Filter type (optional)
 * @return int Number of watchlist items
 */
function count_user_watchlist($user_id, $filter = 'all') {
    global $conn;
    
    $where_clause = "WHERE w.user_id = ?";
    $params = [$user_id];
    $types = "i";
    
    if ($filter === 'active') {
        $where_clause .= " AND a.status = 'active'";
    } elseif ($filter === 'ending_soon') {
        $where_clause .= " AND a.status = 'active' AND a.end_date <= DATE_ADD(NOW(), INTERVAL 24 HOUR)";
    } elseif ($filter === 'ended') {
        $where_clause .= " AND a.status = 'ended'";
    }
    
    $sql = "SELECT COUNT(*) as total FROM watchlist w JOIN auctions a ON w.auction_id = a.id $where_clause";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    
    return 0;
}

/**
 * Get auction main image
 * @param int $auction_id Auction ID
 * @return string|false Image path or false if not found
 */
function get_auction_main_image($auction_id) {
    global $conn;
    
    $sql = "SELECT image_path FROM auction_images WHERE auction_id = ? ORDER BY id ASC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['image_path'];
    }
    
    return false;
}

/**
 * Get status color for badge display
 * @param string $status Status string
 * @return string Bootstrap color class
 */
function get_status_color($status) {
    switch (strtolower($status)) {
        case 'active':
            return 'success';
        case 'ended':
            return 'secondary';
        case 'cancelled':
            return 'danger';
        default:
            return 'primary';
    }
}
