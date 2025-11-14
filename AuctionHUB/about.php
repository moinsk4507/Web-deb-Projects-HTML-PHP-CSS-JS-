<?php
// Include configuration file
require_once 'includes/config.php';

// Function to check if image exists and provide fallback
function get_image_path($image_path, $fallback_path = 'assets/images/hero-image.svg') {
    if (file_exists($image_path)) {
        return $image_path;
    }
    return $fallback_path;
}

// Set page title
$page_title = "About Us";

// Include header
include 'includes/header.php';
?>

<style>
/* About Page Specific Styles */
.about-image {
    max-width: 100%;
    height: auto;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.image-fallback {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    border-radius: 8px;
    text-align: center;
}

.image-fallback i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.auction-showcase {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
}

.auction-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 1rem;
}

.auction-badges .badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

@media (max-width: 768px) {
    .about-image {
        margin-bottom: 1rem;
    }
    
    .auction-showcase {
        padding: 1rem;
    }
}
</style>

<div class="container my-5">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold mb-4">About Our Auction Platform</h1>
            <p class="lead">We're dedicated to creating a secure, transparent, and exciting online auction experience for buyers and sellers around the world.</p>
            <p>Our platform connects passionate collectors, savvy shoppers, and sellers looking to reach a global audience. Whether you're searching for rare collectibles, unique artwork, or everyday items at great prices, our auction system makes it easy to find what you're looking for.</p>
        </div>
        <div class="col-lg-6">
            <img src="<?php echo get_image_path('assets/images/about-hero.jpg', 'assets/images/hero-image.svg'); ?>" alt="About Our Auction Platform" class="img-fluid about-image">
        </div>
    </div>
    
    <!-- Online Auction Platform Showcase -->
    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h2 class="section-title">Our Online Auction Experience</h2>
        </div>
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-lg-6">
                            <div class="p-5">
                                <h3 class="h4 mb-3">Bidsquare Online Auction Platform</h3>
                                <p class="lead">Bidsquare is an online auctions site that is quickly becoming an increasingly popular option for buyers and collectors alike. Bidsquare provides a variety of auction-able items.</p>
                                <p>Our platform combines the excitement of traditional auctions with the convenience of modern technology, allowing users to participate in auctions from anywhere in the world.</p>
                                <div class="d-flex flex-wrap gap-2 mt-4">
                                    <span class="badge bg-primary fs-6 px-3 py-2">BID</span>
                                    <span class="badge bg-danger fs-6 px-3 py-2">SOLD</span>
                                    <span class="badge bg-success fs-6 px-3 py-2">ACTIVE</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="p-3">
                                <img src="assets/images/auction-platform-about.svg" alt="About Our Auction Platform" class="img-fluid about-image">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Our Mission -->
    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h2 class="section-title">Our Mission</h2>
        </div>
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <i class="fas fa-bullseye text-primary fa-3x mb-3"></i>
                    <p class="lead">To create a trusted marketplace where buyers and sellers can connect, transact with confidence, and enjoy the thrill of auctions in a secure online environment.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Our Values -->
    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h2 class="section-title">Our Values</h2>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <i class="fas fa-shield-alt text-primary fa-3x mb-3"></i>
                    <h4>Trust & Security</h4>
                    <p>We prioritize the security of every transaction and protect our users' personal information with advanced security measures.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <i class="fas fa-handshake text-primary fa-3x mb-3"></i>
                    <h4>Transparency</h4>
                    <p>We believe in clear, honest communication about our processes, fees, and policies to ensure a fair experience for everyone.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <i class="fas fa-users text-primary fa-3x mb-3"></i>
                    <h4>Community</h4>
                    <p>We foster a vibrant community of buyers and sellers who share passions and connect through our platform.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Our Story -->
    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h2 class="section-title">Our Story</h2>
        </div>
        <div class="col-lg-6 mb-4 mb-lg-0">
            <img src="<?php echo get_image_path('assets/images/our-story.jpg', 'assets/images/hero-image.svg'); ?>" alt="Our Story" class="img-fluid about-image">
        </div>
        <div class="col-lg-6">
            <p>Founded in 2023, our online auction platform began with a simple idea: to create a more accessible, user-friendly auction experience for everyone.</p>
            <p>What started as a small project has grown into a thriving marketplace where thousands of items find new homes every day. Our team of dedicated professionals works tirelessly to improve the platform, add new features, and ensure that every auction runs smoothly.</p>
            <p>We're proud of how far we've come, but we're even more excited about where we're going. As we continue to grow, we remain committed to our core values and to providing the best possible experience for our users.</p>
        </div>
    </div>
    
    <!-- Team Section -->
    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h2 class="section-title">Our Team</h2>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-img-top bg-primary d-flex align-items-center justify-content-center" style="height: 200px;">
                    <i class="fas fa-user fa-3x text-white"></i>
                </div>
                <div class="card-body text-center">
                    <h5 class="card-title mb-1">John Doe</h5>
                    <p class="text-muted">CEO & Founder</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-img-top bg-primary d-flex align-items-center justify-content-center" style="height: 200px;">
                    <i class="fas fa-user fa-3x text-white"></i>
                </div>
                <div class="card-body text-center">
                    <h5 class="card-title mb-1">Jane Smith</h5>
                    <p class="text-muted">CTO</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-img-top bg-primary d-flex align-items-center justify-content-center" style="height: 200px;">
                    <i class="fas fa-user fa-3x text-white"></i>
                </div>
                <div class="card-body text-center">
                    <h5 class="card-title mb-1">Michael Johnson</h5>
                    <p class="text-muted">Head of Operations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-img-top bg-primary d-flex align-items-center justify-content-center" style="height: 200px;">
                    <i class="fas fa-user fa-3x text-white"></i>
                </div>
                <div class="card-body text-center">
                    <h5 class="card-title mb-1">Sarah Williams</h5>
                    <p class="text-muted">Customer Support Lead</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div class="row">
        <div class="col-12">
            <div class="card bg-primary text-white border-0 shadow">
                <div class="card-body p-5 text-center">
                    <h3 class="mb-3">Ready to start bidding or selling?</h3>
                    <p class="lead mb-4">Join thousands of users who are already enjoying our auction platform.</p>
                    <a href="register.php" class="btn btn-light btn-lg">Create an Account</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>