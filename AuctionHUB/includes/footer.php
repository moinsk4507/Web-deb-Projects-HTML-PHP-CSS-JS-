</main>
    
    <!-- Footer -->
    <footer class="site-footer bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>Online Auction System is a platform where users can buy and sell items through an auction process.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="browse.php" class="text-white">Browse Auctions</a></li>
                        <li><a href="about.php" class="text-white">About</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="create_auction.php" class="text-white">Create Auction</a></li>
                        <?php else: ?>
                            <li><a href="register.php" class="text-white">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt me-2"></i> 123 Auction Street, City, Country</p>
                        <p><i class="fas fa-phone me-2"></i> +1 (123) 456-7890</p>
                        <p><i class="fas fa-envelope me-2"></i> <a href="mailto:<?php echo $site_email; ?>" class="text-white"><?php echo $site_email; ?></a></p>
                    </address>
                    <div class="social-links mt-3">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Terms of Service | Privacy Policy</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
    
    <?php if (basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
    <!-- Homepage JS -->
    <script src="assets/js/homepage.js"></script>
    <?php endif; ?>
    
    <!-- Countdown JS -->
    <script>
        // Function to update countdown timers
        function updateCountdowns() {
            $('.time-left').each(function() {
                const endTime = new Date($(this).data('end')).getTime();
                const now = new Date().getTime();
                const distance = endTime - now;
                
                if (distance < 0) {
                    $(this).find('.countdown').html('Auction ended');
                    return;
                }
                
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                let countdownText = '';
                
                if (days > 0) {
                    countdownText += days + 'd ';
                }
                
                countdownText += hours + 'h ' + minutes + 'm ' + seconds + 's';
                
                $(this).find('.countdown').html(countdownText);
            });
        }
        
        // Update countdowns every second
        setInterval(updateCountdowns, 1000);
        
        // Initial update
        updateCountdowns();
    </script>
</body>
</html>