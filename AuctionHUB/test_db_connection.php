<?php
// Include the database configuration
require_once 'config.php';

// Test the database connection
echo "<h1>Testing Database Connection</h1>";

// Check if the connection is successful
if ($conn) {
    echo "<p style='color: green;'>Connection to MySQL server successful!</p>";
    echo "<p>MySQL Server Info: " . $conn->server_info . "</p>";
    
    // Check if tables exist
    $tables = array('users', 'categories', 'auctions', 'auction_images', 'bids');
    echo "<h2>Database Tables:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<li style='color: green;'>Table '$table' exists.</li>";
        } else {
            echo "<li style='color: red;'>Table '$table' does not exist.</li>";
        }
    }
    echo "</ul>";
    
    // Close connection
    $conn->close();
} else {
    echo "<p style='color: red;'>Connection failed!</p>";
}
?>