<?php
/**
 * Update auction status (guarded)
 */
if (!function_exists('update_auction_status')) {
function update_auction_status($auction_id, $status) {
    global $conn;
    $map = [
        'active' => AUCTION_STATUS_ACTIVE,
        'ended' => AUCTION_STATUS_ENDED,
        'cancelled' => AUCTION_STATUS_CANCELLED
    ];
    if (!isset($map[$status])) {
        return ['success' => false, 'message' => 'Invalid status'];
    }
    $sql = "UPDATE auctions SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $val = $map[$status];
    $stmt->bind_param("ii", $val, $auction_id);
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Status updated'];
    }
    return ['success' => false, 'message' => $stmt->error];
}
}

/** Add a category (guarded) */
if (!function_exists('add_category')) {
function add_category($name) {
    global $conn;
    $sql = "INSERT INTO categories(name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        return ['success' => true, 'id' => $stmt->insert_id];
    }
    return ['success' => false, 'message' => $stmt->error];
}
}

/** Delete a category (guarded) */
if (!function_exists('delete_category')) {
function delete_category($category_id) {
    global $conn;
    $check = $conn->prepare("SELECT COUNT(*) c FROM auctions WHERE category_id=?");
    $check->bind_param("i", $category_id);
    $check->execute();
    $c = $check->get_result()->fetch_assoc()['c'];
    if ($c > 0) {
        return ['success' => false, 'message' => 'Category has auctions'];
    }
    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
    $stmt->bind_param("i", $category_id);
    if ($stmt->execute()) {
        return ['success' => true];
    }
    return ['success' => false, 'message' => $stmt->error];
}
}

/** Count categories (guarded) */
if (!function_exists('count_categories')) {
function count_categories() {
    global $conn;
    $res = $conn->query("SELECT COUNT(*) c FROM categories");
    $row = $res->fetch_assoc();
    return (int)$row['c'];
}
}

/** Count auctions in a category (guarded) */
if (!function_exists('count_category_auctions')) {
function count_category_auctions($category_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM auctions WHERE category_id=?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int)$row['c'];
}
}
// From here on: Admin functions used by dashboard
/**
 * Admin Functions
 * 
 * This file contains functions for the admin dashboard
 */

// get_status_color() function is now defined in auction_functions.php

/**
 * Count total users
 * @param string $status Optional user status filter (active, inactive, all)
 * @return int Number of users
 */
function count_users($status = 'all') {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM users";
    $params = [];
    $types = "";
    
    if ($status !== 'all') {
        $sql .= " WHERE status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (int)$row['count'];
}

/**
 * Count total auctions
 * @param string $status Optional auction status filter (active, ended, cancelled, all)
 * @return int Number of auctions
 */
function count_auctions($status = 'all') {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM auctions";
    $params = [];
    $types = "";
    
    if ($status !== 'all') {
        $sql .= " WHERE status = ?";
        // Convert string status to integer constant
        if ($status === 'active') {
            $params[] = AUCTION_STATUS_ACTIVE;
        } elseif ($status === 'ended') {
            $params[] = AUCTION_STATUS_ENDED;
        } elseif ($status === 'cancelled') {
            $params[] = AUCTION_STATUS_CANCELLED;
        } else {
            $params[] = $status;
        }
        $types .= "i";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (int)$row['count'];
}

/**
 * Count total bids
 * @return int Number of bids
 */
function count_bids() {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM bids";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    return (int)$row['count'];
}

/**
 * Get recent users
 * @param int $limit Number of users to return
 * @return array Array of recent users
 */
function get_recent_users($limit = 5) {
    global $conn;
    
    $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

// get_recent_auctions() function is now defined in auction_functions.php