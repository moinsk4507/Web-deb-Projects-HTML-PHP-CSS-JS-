<?php
/**
 * Authentication Functions
 * This file contains functions related to user authentication, registration, and account management
 */

/**
 * Register a new user
 * @param string $username Username
 * @param string $email Email address
 * @param string $password Password
 * @param string $first_name First name
 * @param string $last_name Last name
 * @return array Result of the registration attempt
 */
function register_user($username, $email, $password, $first_name = '', $last_name = '') {
    global $conn;
    
    // Sanitize inputs
    $username = sanitize_input($username);
    $email = sanitize_input($email);
    $first_name = sanitize_input($first_name);
    $last_name = sanitize_input($last_name);
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        return [
            'success' => false,
            'message' => 'All fields are required'
        ];
    }
    
    // Check if username already exists
    if (username_exists($username)) {
        return [
            'success' => false,
            'message' => 'Username already exists'
        ];
    }
    
    // Check if email already exists
    if (email_exists($email)) {
        return [
            'success' => false,
            'message' => 'Email already exists'
        ];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate verification token
    $verification_token = generate_random_string(32);
    
    // Insert user into database
    $sql = "INSERT INTO users (username, email, password, first_name, last_name, verification_token, role_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"; 
    
    $stmt = $conn->prepare($sql);
    $role_id = ROLE_USER; // Default role is user
    
    $stmt->bind_param("ssssssi", $username, $email, $hashed_password, $first_name, $last_name, $verification_token, $role_id);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Send verification email
        send_verification_email($email, $verification_token);
        
        return [
            'success' => true,
            'message' => 'Registration successful. Please check your email to verify your account.',
            'user_id' => $user_id
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Registration failed: ' . $stmt->error
        ];
    }
}

/**
 * Login a user
 * @param string $username_or_email Username or email
 * @param string $password Password
 * @param bool $remember_me Remember me option
 * @return array Result of the login attempt
 */
function login_user($username_or_email, $password, $remember_me = false) {
    global $conn;
    
    // Sanitize inputs
    $username_or_email = sanitize_input($username_or_email);
    
    // Validate inputs
    if (empty($username_or_email) || empty($password)) {
        return [
            'success' => false,
            'message' => 'All fields are required'
        ];
    }
    
    // Check if input is email or username
    $is_email = filter_var($username_or_email, FILTER_VALIDATE_EMAIL);
    
    // Prepare SQL query based on input type
    if ($is_email) {
        $sql = "SELECT * FROM users WHERE email = ?";
    } else {
        $sql = "SELECT * FROM users WHERE username = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Check if email is verified (skip on localhost for development)
            $isLocalhost = isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);
            if ($user['email_verified'] == EMAIL_NOT_VERIFIED && !$isLocalhost) {
                return [
                    'success' => false,
                    'message' => 'Please verify your email address before logging in.'
                ];
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role_id'];
            
            // Set remember me cookie if requested
            if ($remember_me) {
                $token = generate_random_string(32);
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Store token in database
                $sql = "UPDATE users SET remember_token = ?, remember_token_expires = FROM_UNIXTIME(?) WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sii", $token, $expiry, $user['id']);
                $stmt->execute();
                
                // Set cookie
                setcookie('remember_token', $token, $expiry, '/', '', false, true);
            }
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid password'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }
}

/**
 * Logout a user
 */
function logout_user() {
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['remember_token'])) {
        // Remove token from database
        global $conn;
        $token = $_COOKIE['remember_token'];
        
        $sql = "UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE remember_token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        // Clear cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    // Destroy session
    session_unset();
    session_destroy();
}

/**
 * Check if user is logged in via remember me cookie
 */
function check_remember_me() {
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
        global $conn;
        $token = $_COOKIE['remember_token'];
        
        $sql = "SELECT * FROM users WHERE remember_token = ? AND remember_token_expires > NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role_id'];
            
            // Extend remember me cookie
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            // Update token expiry in database
            $sql = "UPDATE users SET remember_token_expires = FROM_UNIXTIME(?) WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $expiry, $user['id']);
            $stmt->execute();
            
            // Update cookie
            setcookie('remember_token', $token, $expiry, '/', '', false, true);
        }
    }
}

/**
 * Verify user email
 * @param string $token Verification token
 * @return array Result of the verification attempt
 */
function verify_email($token) {
    global $conn;
    
    // Sanitize input
    $token = sanitize_input($token);
    
    // Find user with this token
    $sql = "SELECT id FROM users WHERE verification_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Update user as verified
        $sql = "UPDATE users SET email_verified = ?, verification_token = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $verified = EMAIL_VERIFIED;
        $stmt->bind_param("ii", $verified, $user['id']);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Email verified successfully. You can now login.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Email verification failed: ' . $stmt->error
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Invalid verification token'
        ];
    }
}

/**
 * Verify user email with email and token
 * @param string $email User email
 * @param string $token Verification token
 * @return array Result of the verification attempt
 */
function verify_user_email($email, $token) {
    global $conn;
    
    // Sanitize inputs
    $email = sanitize_input($email);
    $token = sanitize_input($token);
    
    // Find user with this email and token
    $sql = "SELECT id FROM users WHERE email = ? AND verification_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Update user as verified
        $sql = "UPDATE users SET email_verified = ?, verification_token = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $verified = EMAIL_VERIFIED;
        $stmt->bind_param("ii", $verified, $user['id']);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Email verified successfully. You can now login.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Email verification failed: ' . $stmt->error
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Invalid verification link. Please check your email for the correct link.'
        ];
    }
}

/**
 * Send verification email
 * @param string $email User email
 * @param string $token Verification token
 * @return bool Whether the email was sent successfully
 */
function send_verification_email($email, $token) {
    global $base_url, $site_name;
    
    $subject = "$site_name - Verify Your Email Address";
    
    $verification_link = $base_url . 'verify_email.php?token=' . $token . '&email=' . urlencode($email);
    
    $message = "<html><body>";
    $message .= "<h2>Verify Your Email Address</h2>";
    $message .= "<p>Thank you for registering with $site_name. Please click the link below to verify your email address:</p>";
    $message .= "<p><a href='$verification_link'>$verification_link</a></p>";
    $message .= "<p>If you did not register for an account, please ignore this email.</p>";
    $message .= "<p>Regards,<br>$site_name Team</p>";
    $message .= "</body></html>";
    
    return send_email($email, $subject, $message);
}

/**
 * Request password reset
 * @param string $email User email
 * @return array Result of the request
 */
function request_password_reset($email) {
    global $conn;
    
    // Sanitize input
    $email = sanitize_input($email);
    
    // Check if email exists
    $user = get_user_by_email($email);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Email not found'
        ];
    }
    
    // Generate reset token
    $reset_token = generate_random_string(32);
    $reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Update user with reset token
    $sql = "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $reset_token, $reset_token_expires, $user['id']);
    
    if ($stmt->execute()) {
        // Send reset email
        if (send_reset_email($email, $reset_token)) {
            return [
                'success' => true,
                'message' => 'Password reset instructions have been sent to your email.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send reset email. Please try again later.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Password reset request failed: ' . $stmt->error
        ];
    }
}

/**
 * Send password reset email
 * @param string $email User email
 * @param string $token Reset token
 * @return bool Whether the email was sent successfully
 */
function send_reset_email($email, $token) {
    global $base_url, $site_name;
    
    $subject = "$site_name - Password Reset Request";
    
    $reset_link = $base_url . 'reset_password.php?token=' . $token . '&email=' . urlencode($email);
    
    $message = "<html><body>";
    $message .= "<h2>Password Reset Request</h2>";
    $message .= "<p>You have requested to reset your password. Please click the link below to reset your password:</p>";
    $message .= "<p><a href='$reset_link'>$reset_link</a></p>";
    $message .= "<p>This link will expire in 1 hour.</p>";
    $message .= "<p>If you did not request a password reset, please ignore this email.</p>";
    $message .= "<p>Regards,<br>$site_name Team</p>";
    $message .= "</body></html>";
    
    return send_email($email, $subject, $message);
}

/**
 * Send password reset email (wrapper function)
 * @param string $email User email
 * @return array Result of the request
 */
function send_password_reset_email($email) {
    // Call the request_password_reset function which handles token generation and sending email
    return request_password_reset($email);
}

/**
 * Resend verification email
 * @param int $user_id User ID
 * @return array Result of the resend attempt
 */
function resend_verification_email($user_id) {
    global $conn;
    
    // Get user
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }
    
    // Check if email is already verified
    if ($user['email_verified'] == EMAIL_VERIFIED) {
        return [
            'success' => false,
            'message' => 'Email is already verified'
        ];
    }
    
    // Generate new verification token
    $verification_token = generate_random_string(32);
    
    // Update user with new verification token
    $sql = "UPDATE users SET verification_token = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $verification_token, $user_id);
    
    if ($stmt->execute()) {
        // Send verification email
        if (send_verification_email($user['email'], $verification_token)) {
            return [
                'success' => true,
                'message' => 'Verification email has been sent to your email address.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send verification email. Please try again later.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Failed to generate new verification token: ' . $stmt->error
        ];
    }
}

/**
 * Validate password reset token
 * @param string $email User email
 * @param string $token Reset token
 * @return bool Whether the token is valid
 */
function validate_password_reset_token($email, $token) {
    global $conn;
    
    // Sanitize inputs
    $email = sanitize_input($email);
    $token = sanitize_input($token);
    
    // Find user with this email and token and check if token is still valid
    $sql = "SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_token_expires > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 1;
}

/**
 * Reset user password
 * @param string $email User email
 * @param string $token Reset token
 * @param string $new_password New password
 * @return array Result of the reset attempt
 */
function reset_user_password($email, $token, $new_password) {
    global $conn;
    
    // Sanitize inputs
    $email = sanitize_input($email);
    $token = sanitize_input($token);
    
    // Validate token
    if (!validate_password_reset_token($email, $token)) {
        return [
            'success' => false,
            'message' => 'Invalid or expired password reset link.'
        ];
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update user password and clear reset token
    $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE email = ? AND reset_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $hashed_password, $email, $token);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Password has been reset successfully. You can now login with your new password.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Password reset failed: ' . $stmt->error
        ];
    }
}

/**
 * Reset password
 * @param string $token Reset token
 * @param string $password New password
 * @return array Result of the reset attempt
 */
function reset_password($token, $password) {
    global $conn;
    
    // Sanitize input
    $token = sanitize_input($token);
    
    // Find user with this token and check if token is still valid
    $sql = "SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Hash new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user password and clear reset token
        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user['id']);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Password reset successfully. You can now login with your new password.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Password reset failed: ' . $stmt->error
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Invalid or expired reset token'
        ];
    }
}

/**
 * Change password
 * @param int $user_id User ID
 * @param string $current_password Current password
 * @param string $new_password New password
 * @return array Result of the change attempt
 */
function change_password($user_id, $current_password, $new_password) {
    global $conn;
    
    // Get user
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        return [
            'success' => false,
            'message' => 'Current password is incorrect'
        ];
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Password changed successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Password change failed: ' . $stmt->error
        ];
    }
}

/**
 * Change user password (alias for change_password)
 * @param int $user_id User ID
 * @param string $current_password Current password
 * @param string $new_password New password
 * @return array Result of the change attempt
 */
function change_user_password($user_id, $current_password, $new_password) {
    return change_password($user_id, $current_password, $new_password);
}

/**
 * Update user profile
 * @param int $user_id User ID
 * @param array $data Profile data to update
 * @return array Result of the update attempt
 */
function update_profile($user_id, $data) {
    global $conn;
    
    // Get user
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }
    
    // Build update query
    $sql = "UPDATE users SET ";
    $params = [];
    $types = "";
    
    // Add fields to update
    foreach ($data as $key => $value) {
        // Skip password field (handled separately)
        if ($key === 'password') continue;
        
        // Add field to query
        $sql .= "$key = ?, ";
        $params[] = $value;
        $types .= "s"; // Assume all fields are strings
    }
    
    // Remove trailing comma and space
    $sql = rtrim($sql, ", ");
    
    // Add WHERE clause
    $sql .= " WHERE id = ?";
    $params[] = $user_id;
    $types .= "i";
    
    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    
    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Profile updated successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Profile update failed: ' . $stmt->error
        ];
    }
}

/**
 * Upload profile image
 * @param int $user_id User ID
 * @param array $file File data from $_FILES
 * @return array Result of the upload attempt
 */
function upload_profile_image($user_id, $file) {
    global $conn, $allowed_image_extensions;
    
    // Get user
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }
    
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'File upload error: ' . $file['error']
        ];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return [
            'success' => false,
            'message' => 'File is too large. Maximum size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'
        ];
    }
    
    // Check file extension
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);
    
    if (!in_array($extension, $allowed_image_extensions)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowed_image_extensions)
        ];
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = UPLOAD_DIR . 'profile_images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Delete old profile image if exists
        if (!empty($user['profile_image'])) {
            $old_filepath = UPLOAD_DIR . $user['profile_image'];
            if (file_exists($old_filepath)) {
                unlink($old_filepath);
            }
        }
        
        // Update user profile image in database
        $relative_path = 'profile_images/' . $filename;
        $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $relative_path, $user_id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Profile image uploaded successfully',
                'image_path' => $relative_path
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update profile image in database: ' . $stmt->error
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Failed to move uploaded file'
        ];
    }
}

/**
 * Send email
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message (HTML)
 * @return bool Whether the email was sent successfully
 */
function send_email($to, $subject, $message) {
    global $site_name, $site_email;
    
    // Headers
    $headers = "From: $site_name <$site_email>\r\n";
    $headers .= "Reply-To: $site_email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Detect local/dev SMTP misconfiguration and fallback to logging
    $smtpHost = ini_get('SMTP');
    $smtpPort = ini_get('smtp_port');
    $isLocalHost = empty($smtpHost) || $smtpHost === 'localhost' || $smtpHost === '127.0.0.1';
    $isDev = (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) || getenv('APP_ENV') === 'local';

    // Try sending first; suppress warnings and handle failure below
    $sent = @mail($to, $subject, $message, $headers);
    if ($sent) {
        return true;
    }

    // Fallback: if on dev or SMTP is localhost/unset, log email instead of failing
    if ($isDev || $isLocalHost) {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/emails.log';
        $entry = "=== \n" . date('c') . "\nTO: $to\nSUBJECT: $subject\nHEADERS: " . str_replace("\r\n", ' | ', trim($headers)) . "\nBODY:\n$message\n\n";
        @file_put_contents($logFile, $entry, FILE_APPEND);
        return true; // Considered success in dev fallback
    }

    // Production and still failing
    return false;
}

/**
 * Update user status (activate/deactivate)
 * @param int $user_id User ID
 * @param string $status New status ('active' or 'inactive')
 * @return array Result of the update attempt
 */
function update_user_status($user_id, $status) {
    global $conn;
    
    // Get user
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        return [
            'success' => false,
            'message' => 'Invalid status value'
        ];
    }
    
    // Update status
    $sql = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $user_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'User status updated successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'User status update failed: ' . $stmt->error
        ];
    }
}

/**
 * Update user role
 * @param int $user_id User ID
 * @param int $role_id New role ID (1 for admin, 0 for regular user)
 * @return array Result of the update attempt
 */
function update_user_role($user_id, $role_id) {
    global $conn;
    
    // Get user
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }
    
    // Validate role_id
    if (!in_array($role_id, [0, 1])) {
        return [
            'success' => false,
            'message' => 'Invalid role value'
        ];
    }
    
    // Map role_id to actual role value in database
    $role = ($role_id == 1) ? ROLE_ADMIN : ROLE_USER;
    
    // Update role
    $sql = "UPDATE users SET role_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $role, $user_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'User role updated successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'User role update failed: ' . $stmt->error
        ];
    }
}