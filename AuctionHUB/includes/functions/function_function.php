<?php
// Minimal helper functions to support edit toggle and shared utilities

if (!function_exists('can_edit_auction')) {
function can_edit_auction($auction, $userId) {
    if (!$auction || !is_array($auction)) return false;
    if ((int)$auction['user_id'] !== (int)$userId) return false;
    // Allow edit only when there are no bids yet
    return (int)($auction['bid_count'] ?? 0) === 0;
}
}

if (!function_exists('ensure_system_sql')) {
function ensure_system_sql(mysqli $conn) {
    // Provide a location to import additional SQL if needed
    $path = __DIR__ . '/../../database/system_auction.sql';
    if (!file_exists($path)) return true;
    $sql = file_get_contents($path);
    if (!$sql) return true;
    // Split on semicolons cautiously
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if ($stmt === '') continue;
        @$conn->query($stmt);
    }
    return true;
}
}

