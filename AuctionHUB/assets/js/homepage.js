/**
 * Homepage Specific JavaScript
 * This file contains JavaScript functionality specific to the homepage
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize homepage-specific features
    initCategoryHover();
    initFeaturedAuctionCarousel();
});

/**
 * Initialize hover effects for category cards
 */
function initCategoryHover() {
    const categoryCards = document.querySelectorAll('.category-card');
    
    if (!categoryCards.length) return;
    
    categoryCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('shadow-lg');
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('shadow-lg');
            this.style.transform = 'translateY(0)';
        });
    });
}

/**
 * Initialize featured auction carousel functionality
 */
function initFeaturedAuctionCarousel() {
    const featuredSection = document.querySelector('.featured-auctions-section');
    
    if (!featuredSection) return;
    
    // Add carousel navigation if there are multiple featured auctions
    const featuredItems = featuredSection.querySelectorAll('.auction-card');
    
    if (featuredItems.length > 4) {
        // Add navigation buttons functionality here if needed
        console.log('Featured auctions carousel initialized');
    }
}