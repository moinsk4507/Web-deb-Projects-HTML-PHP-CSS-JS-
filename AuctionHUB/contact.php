<?php
// Include configuration file
require_once 'includes/config.php';

$error = '';
$success = '';

// Process contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Validate form data
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Send email
        $to = ADMIN_EMAIL;
        $headers = "From: " . $email . "\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $email_message = "<html><body>";
        $email_message .= "<h2>Contact Form Submission</h2>";
        $email_message .= "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>";
        $email_message .= "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        $email_message .= "<p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>";
        $email_message .= "<p><strong>Message:</strong></p>";
        $email_message .= "<p>" . nl2br(htmlspecialchars($message)) . "</p>";
        $email_message .= "</body></html>";
        
        if (mail($to, "Contact Form: " . $subject, $email_message, $headers)) {
            $success = 'Your message has been sent successfully. We will get back to you soon!';
            // Clear form data after successful submission
            $name = $email = $subject = $message = '';
        } else {
            $error = 'There was a problem sending your message. Please try again later.';
        }
    }
}

// Set page title
$page_title = "Contact Us";

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="text-center mb-4">Contact Us</h1>
            <p class="text-center mb-5">Have questions or feedback? We'd love to hear from you. Fill out the form below and we'll get back to you as soon as possible.</p>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-5 mb-4 mb-md-0">
                            <h4 class="mb-3">Get In Touch</h4>
                            <p>We're here to help and answer any questions you might have. We look forward to hearing from you.</p>
                            
                            <div class="mt-4">
                                <div class="d-flex mb-3">
                                    <div class="contact-icon">
                                        <i class="fas fa-map-marker-alt text-primary"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Address</h6>
                                        <p class="mb-0">123 Auction Street, City, Country</p>
                                    </div>
                                </div>
                                
                                <div class="d-flex mb-3">
                                    <div class="contact-icon">
                                        <i class="fas fa-envelope text-primary"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Email</h6>
                                        <p class="mb-0"><?php echo ADMIN_EMAIL; ?></p>
                                    </div>
                                </div>
                                
                                <div class="d-flex mb-3">
                                    <div class="contact-icon">
                                        <i class="fas fa-phone-alt text-primary"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Phone</h6>
                                        <p class="mb-0">+1 (123) 456-7890</p>
                                    </div>
                                </div>
                                
                                <div class="d-flex">
                                    <div class="contact-icon">
                                        <i class="fas fa-clock text-primary"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1">Working Hours</h6>
                                        <p class="mb-0">Monday-Friday: 9am-5pm</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h6>Follow Us</h6>
                                <div class="social-links">
                                    <a href="#" class="me-2"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-instagram"></i></a>
                                    <a href="#" class="me-2"><i class="fab fa-linkedin-in"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-7">
                            <h4 class="mb-3">Send Us a Message</h4>
                            <form method="post" action="" id="contact-form">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Send Message</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Map Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body p-0">
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.215266754809!2d-73.98784492426385!3d40.75798657138946!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25855c6480299%3A0x55194ec5a1ae072e!2sTimes%20Square!5e0!3m2!1sen!2sus!4v1710320813000!5m2!1sen!2sus" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>