<?php
/**
 * Email Functions
 * 
 * This file contains functions for sending emails and managing email templates
 * for the online auction system.
 */

/**
 * Send an email using PHPMailer
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $alt_body Plain text alternative body
 * @param array $attachments Optional array of attachments
 * @return array Result with success status and message
 */
function send_email($to, $subject, $body, $alt_body = '', $attachments = []) {
    global $config;
    
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // If PHPMailer is not available, use PHP's mail function as fallback
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$config['site_name']} <{$config['site_email']}>" . "\r\n";
        
        $mail_sent = mail($to, $subject, $body, $headers);
        
        if ($mail_sent) {
            return ['success' => true, 'message' => 'Email sent successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to send email.'];
        }
    }
    
    // Use PHPMailer
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        if ($config['smtp_enabled']) {
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_username'];
            $mail->Password = $config['smtp_password'];
            $mail->SMTPSecure = $config['smtp_secure'];
            $mail->Port = $config['smtp_port'];
        }
        
        // Recipients
        $mail->setFrom($config['site_email'], $config['site_name']);
        $mail->addAddress($to);
        $mail->addReplyTo($config['site_email'], $config['site_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $alt_body ?: strip_tags($body);
        
        // Attachments
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }
        }
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo];
    }
}

/**
 * Get email template and replace placeholders
 * 
 * @param string $template_name Template name
 * @param array $replacements Key-value pairs for replacements
 * @return string Processed email template
 */
function get_email_template($template_name, $replacements = []) {
    global $config;
    
    // Default template path
    $template_path = dirname(__DIR__, 2) . '/templates/emails/' . $template_name . '.html';
    
    // Check if template exists
    if (!file_exists($template_path)) {
        // Use default template
        $template_path = dirname(__DIR__, 2) . '/templates/emails/default.html';
        
        // If default template doesn't exist, return basic template
        if (!file_exists($template_path)) {
            return get_basic_email_template($replacements);
        }
    }
    
    // Read template file
    $template = file_get_contents($template_path);
    
    // Add common replacements
    $common_replacements = [
        '{{site_name}}' => $config['site_name'],
        '{{site_url}}' => $config['site_url'],
        '{{site_email}}' => $config['site_email'],
        '{{current_year}}' => date('Y'),
    ];
    
    // Merge with custom replacements
    $all_replacements = array_merge($common_replacements, $replacements);
    
    // Replace placeholders
    foreach ($all_replacements as $placeholder => $value) {
        $template = str_replace($placeholder, $value, $template);
    }
    
    return $template;
}

/**
 * Get a basic email template when no template file is available
 * 
 * @param array $replacements Key-value pairs for replacements
 * @return string Basic email template
 */
function get_basic_email_template($replacements = []) {
    global $config;
    
    // Extract content from replacements
    $title = isset($replacements['{{title}}']) ? $replacements['{{title}}'] : 'Notification';
    $content = isset($replacements['{{content}}']) ? $replacements['{{content}}'] : '';
    $button_text = isset($replacements['{{button_text}}']) ? $replacements['{{button_text}}'] : '';
    $button_url = isset($replacements['{{button_url}}']) ? $replacements['{{button_url}}'] : '';
    
    // Basic HTML email template
    $template = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $title . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4e73df;
            padding: 20px;
            text-align: center;
            color: white;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            background-color: #4e73df;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #777777;
            background-color: #f8f9fc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . $config['site_name'] . '</h1>
        </div>
        <div class="content">
            <h2>' . $title . '</h2>
            ' . $content . '
            ' . ($button_url && $button_text ? '<a href="' . $button_url . '" class="button">' . $button_text . '</a>' : '') . '
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' ' . $config['site_name'] . '. All rights reserved.</p>
            <p>If you have any questions, please contact us at <a href="mailto:' . $config['site_email'] . '">' . $config['site_email'] . '</a></p>
        </div>
    </div>
</body>
</html>';
    
    return $template;
}

/**
 * Send verification email to user
 * 
 * @param int $user_id User ID
 * @param string $email User email
 * @param string $token Verification token
 * @return array Result with success status and message
 */
function send_verification_email($user_id, $email, $token) {
    global $config;
    
    // Generate verification URL
    $verification_url = $config['site_url'] . '/verify_email.php?token=' . $token . '&email=' . urlencode($email);
    
    // Get user details
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found.'];
    }
    
    // Prepare email content
    $replacements = [
        '{{title}}' => 'Verify Your Email Address',
        '{{username}}' => $user['username'],
        '{{content}}' => '<p>Thank you for registering with ' . $config['site_name'] . '. Please verify your email address by clicking the button below:</p>',
        '{{button_text}}' => 'Verify Email',
        '{{button_url}}' => $verification_url,
        '{{verification_url}}' => $verification_url
    ];
    
    $subject = 'Verify Your Email Address - ' . $config['site_name'];
    $body = get_email_template('verification', $replacements);
    
    // Send email
    return send_email($email, $subject, $body);
}

/**
 * Send password reset email
 * 
 * @param int $user_id User ID
 * @param string $email User email
 * @param string $token Reset token
 * @return array Result with success status and message
 */
function send_password_reset_email($user_id, $email, $token) {
    global $config;
    
    // Generate reset URL
    $reset_url = $config['site_url'] . '/reset_password.php?token=' . $token . '&email=' . urlencode($email);
    
    // Get user details
    $user = get_user_by_id($user_id);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found.'];
    }
    
    // Prepare email content
    $replacements = [
        '{{title}}' => 'Reset Your Password',
        '{{username}}' => $user['username'],
        '{{content}}' => '<p>We received a request to reset your password. If you did not make this request, you can ignore this email.</p><p>To reset your password, click the button below:</p>',
        '{{button_text}}' => 'Reset Password',
        '{{button_url}}' => $reset_url,
        '{{reset_url}}' => $reset_url
    ];
    
    $subject = 'Password Reset Request - ' . $config['site_name'];
    $body = get_email_template('password_reset', $replacements);
    
    // Send email
    return send_email($email, $subject, $body);
}

/**
 * Send new bid notification to seller
 * 
 * @param int $auction_id Auction ID
 * @param int $bid_id Bid ID
 * @return array Result with success status and message
 */
function send_new_bid_notification_email($auction_id, $bid_id) {
    global $config;
    
    // Get auction details
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        return ['success' => false, 'message' => 'Auction not found.'];
    }
    
    // Get bid details
    $bid = get_bid_by_id($bid_id);
    
    if (!$bid) {
        return ['success' => false, 'message' => 'Bid not found.'];
    }
    
    // Get seller details
    $seller = get_user_by_id($auction['user_id']);
    
    if (!$seller) {
        return ['success' => false, 'message' => 'Seller not found.'];
    }
    
    // Get bidder details
    $bidder = get_user_by_id($bid['user_id']);
    
    if (!$bidder) {
        return ['success' => false, 'message' => 'Bidder not found.'];
    }
    
    // Generate auction URL
    $auction_url = $config['site_url'] . '/auction.php?id=' . $auction_id;
    
    // Prepare email content
    $replacements = [
        '{{title}}' => 'New Bid on Your Auction',
        '{{username}}' => $seller['username'],
        '{{content}}' => '<p>A new bid has been placed on your auction "' . htmlspecialchars($auction['title']) . '".</p>' . 
                        '<p><strong>Bidder:</strong> ' . htmlspecialchars($bidder['username']) . '<br>' . 
                        '<strong>Bid Amount:</strong> $' . number_format($bid['amount'], 2) . '<br>' . 
                        '<strong>Bid Time:</strong> ' . date('M j, Y g:i A', strtotime($bid['created_at'])) . '</p>' . 
                        '<p>Your auction now has a total of ' . count_auction_bids($auction_id) . ' bids.</p>',
        '{{button_text}}' => 'View Auction',
        '{{button_url}}' => $auction_url,
        '{{auction_url}}' => $auction_url
    ];
    
    $subject = 'New Bid on Your Auction - ' . $config['site_name'];
    $body = get_email_template('new_bid', $replacements);
    
    // Send email
    return send_email($seller['email'], $subject, $body);
}

/**
 * Send outbid notification to previous highest bidder
 * 
 * @param int $auction_id Auction ID
 * @param int $outbid_user_id User ID of outbid user
 * @param float $new_bid_amount New highest bid amount
 * @return array Result with success status and message
 */
function send_outbid_notification_email($auction_id, $outbid_user_id, $new_bid_amount) {
    global $config;
    
    // Get auction details
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        return ['success' => false, 'message' => 'Auction not found.'];
    }
    
    // Get outbid user details
    $user = get_user_by_id($outbid_user_id);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found.'];
    }
    
    // Generate auction URL
    $auction_url = $config['site_url'] . '/auction.php?id=' . $auction_id;
    
    // Prepare email content
    $replacements = [
        '{{title}}' => 'You Have Been Outbid',
        '{{username}}' => $user['username'],
        '{{content}}' => '<p>Someone has placed a higher bid on "' . htmlspecialchars($auction['title']) . '".</p>' . 
                        '<p>The current highest bid is now $' . number_format($new_bid_amount, 2) . '.</p>' . 
                        '<p>If you would like to place another bid, please visit the auction page.</p>',
        '{{button_text}}' => 'Place New Bid',
        '{{button_url}}' => $auction_url,
        '{{auction_url}}' => $auction_url
    ];
    
    $subject = 'You Have Been Outbid - ' . $config['site_name'];
    $body = get_email_template('outbid', $replacements);
    
    // Send email
    return send_email($user['email'], $subject, $body);
}

/**
 * Send auction ending soon notification to bidders and watchers
 * 
 * @param int $auction_id Auction ID
 * @return array Result with success status and message
 */
function send_auction_ending_soon_email($auction_id) {
    global $config;
    
    // Get auction details
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        return ['success' => false, 'message' => 'Auction not found.'];
    }
    
    // Get unique list of users who have bid or are watching this auction
    $users = get_auction_interested_users($auction_id);
    
    if (empty($users)) {
        return ['success' => false, 'message' => 'No interested users found.'];
    }
    
    // Generate auction URL
    $auction_url = $config['site_url'] . '/auction.php?id=' . $auction_id;
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($users as $user) {
        // Prepare email content
        $replacements = [
            '{{title}}' => 'Auction Ending Soon',
            '{{username}}' => $user['username'],
            '{{content}}' => '<p>An auction you are interested in is ending soon!</p>' . 
                            '<p><strong>Auction:</strong> ' . htmlspecialchars($auction['title']) . '<br>' . 
                            '<strong>Current Price:</strong> $' . number_format($auction['current_price'], 2) . '<br>' . 
                            '<strong>Ends:</strong> ' . date('M j, Y g:i A', strtotime($auction['end_date'])) . '</p>' . 
                            '<p>Don\'t miss your chance to win this auction!</p>',
            '{{button_text}}' => 'View Auction',
            '{{button_url}}' => $auction_url,
            '{{auction_url}}' => $auction_url
        ];
        
        $subject = 'Auction Ending Soon - ' . $config['site_name'];
        $body = get_email_template('auction_ending', $replacements);
        
        // Send email
        $result = send_email($user['email'], $subject, $body);
        
        if ($result['success']) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    if ($error_count > 0) {
        return [
            'success' => $success_count > 0,
            'message' => "Sent {$success_count} emails successfully. Failed to send {$error_count} emails."
        ];
    }
    
    return ['success' => true, 'message' => "Sent {$success_count} emails successfully."];
}

/**
 * Send auction won notification to winner
 * 
 * @param int $auction_id Auction ID
 * @param int $winner_id Winner user ID
 * @return array Result with success status and message
 */
function send_auction_won_email($auction_id, $winner_id) {
    global $config;
    
    // Get auction details
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        return ['success' => false, 'message' => 'Auction not found.'];
    }
    
    // Get winner details
    $winner = get_user_by_id($winner_id);
    
    if (!$winner) {
        return ['success' => false, 'message' => 'Winner not found.'];
    }
    
    // Get seller details
    $seller = get_user_by_id($auction['user_id']);
    
    if (!$seller) {
        return ['success' => false, 'message' => 'Seller not found.'];
    }
    
    // Generate auction URL
    $auction_url = $config['site_url'] . '/auction.php?id=' . $auction_id;
    
    // Prepare email content
    $replacements = [
        '{{title}}' => 'Congratulations! You Won the Auction',
        '{{username}}' => $winner['username'],
        '{{content}}' => '<p>Congratulations! You have won the auction for "' . htmlspecialchars($auction['title']) . '".</p>' . 
                        '<p><strong>Final Price:</strong> $' . number_format($auction['current_price'], 2) . '<br>' . 
                        '<strong>Seller:</strong> ' . htmlspecialchars($seller['username']) . '</p>' . 
                        '<p>Please contact the seller to arrange payment and delivery. You can use the messaging system on our website or contact them directly at ' . htmlspecialchars($seller['email']) . '.</p>',
        '{{button_text}}' => 'View Auction Details',
        '{{button_url}}' => $auction_url,
        '{{auction_url}}' => $auction_url
    ];
    
    $subject = 'Congratulations! You Won the Auction - ' . $config['site_name'];
    $body = get_email_template('auction_won', $replacements);
    
    // Send email
    return send_email($winner['email'], $subject, $body);
}

/**
 * Send auction ended notification to seller
 * 
 * @param int $auction_id Auction ID
 * @param bool $has_winner Whether the auction has a winner
 * @param int $winner_id Winner user ID (if applicable)
 * @return array Result with success status and message
 */
function send_auction_ended_seller_email($auction_id, $has_winner, $winner_id = null) {
    global $config;
    
    // Get auction details
    $auction = get_auction_by_id($auction_id);
    
    if (!$auction) {
        return ['success' => false, 'message' => 'Auction not found.'];
    }
    
    // Get seller details
    $seller = get_user_by_id($auction['user_id']);
    
    if (!$seller) {
        return ['success' => false, 'message' => 'Seller not found.'];
    }
    
    // Generate auction URL
    $auction_url = $config['site_url'] . '/auction.php?id=' . $auction_id;
    
    // Prepare email content based on whether there's a winner
    if ($has_winner && $winner_id) {
        // Get winner details
        $winner = get_user_by_id($winner_id);
        
        if (!$winner) {
            return ['success' => false, 'message' => 'Winner not found.'];
        }
        
        $content = '<p>Your auction "' . htmlspecialchars($auction['title']) . '" has ended with a successful sale!</p>' . 
                  '<p><strong>Final Price:</strong> $' . number_format($auction['current_price'], 2) . '<br>' . 
                  '<strong>Winner:</strong> ' . htmlspecialchars($winner['username']) . '<br>' . 
                  '<strong>Winner Email:</strong> ' . htmlspecialchars($winner['email']) . '</p>' . 
                  '<p>Please contact the winner to arrange payment and delivery.</p>';
        
        $title = 'Your Auction Has Ended Successfully';
    } else {
        $content = '<p>Your auction "' . htmlspecialchars($auction['title']) . '" has ended without any bids.</p>' . 
                  '<p>You can relist the item if you wish to try again.</p>';
        
        $title = 'Your Auction Has Ended';
    }
    
    $replacements = [
        '{{title}}' => $title,
        '{{username}}' => $seller['username'],
        '{{content}}' => $content,
        '{{button_text}}' => 'View Auction Details',
        '{{button_url}}' => $auction_url,
        '{{auction_url}}' => $auction_url
    ];
    
    $subject = $title . ' - ' . $config['site_name'];
    $body = get_email_template('auction_ended_seller', $replacements);
    
    // Send email
    return send_email($seller['email'], $subject, $body);
}

/**
 * Send contact form email to admin
 * 
 * @param string $name Sender name
 * @param string $email Sender email
 * @param string $subject Email subject
 * @param string $message Email message
 * @return array Result with success status and message
 */
function send_contact_form_email($name, $email, $subject, $message) {
    global $config;
    
    // Prepare email content
    $replacements = [
        '{{title}}' => 'New Contact Form Submission',
        '{{content}}' => '<p>You have received a new message from the contact form on your website.</p>' . 
                        '<p><strong>Name:</strong> ' . htmlspecialchars($name) . '<br>' . 
                        '<strong>Email:</strong> ' . htmlspecialchars($email) . '<br>' . 
                        '<strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>' . 
                        '<p><strong>Message:</strong><br>' . nl2br(htmlspecialchars($message)) . '</p>'
    ];
    
    $email_subject = 'New Contact Form Submission: ' . $subject;
    $body = get_email_template('contact_form', $replacements);
    
    // Send email to admin
    return send_email($config['admin_email'], $email_subject, $body);
}

/**
 * Send auto-reply email to contact form submitter
 * 
 * @param string $name Recipient name
 * @param string $email Recipient email
 * @return array Result with success status and message
 */
function send_contact_form_autoreply($name, $email) {
    global $config;
    
    // Prepare email content
    $replacements = [
        '{{title}}' => 'Thank You for Contacting Us',
        '{{name}}' => $name,
        '{{content}}' => '<p>Thank you for contacting ' . $config['site_name'] . '. We have received your message and will respond to your inquiry as soon as possible.</p>' . 
                        '<p>This is an automated response, please do not reply to this email.</p>'
    ];
    
    $subject = 'Thank You for Contacting Us - ' . $config['site_name'];
    $body = get_email_template('contact_autoreply', $replacements);
    
    // Send email
    return send_email($email, $subject, $body);
}
?>