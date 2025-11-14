<?php
// Include configuration file
require_once 'includes/config.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Log out the user
    logout_user();
    
    // Set a success message
    $_SESSION['temp_message'] = array(
        'type' => 'success',
        'text' => 'You have been successfully logged out.'
    );
}

// Redirect to home page
header("Location: index.php");
exit();
?>