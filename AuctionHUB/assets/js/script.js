/**
 * Online Auction System - Main JavaScript File
 * This file contains all client-side functionality for the auction system
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auction image gallery functionality
    initAuctionGallery();
    
    // Bid form validation
    initBidFormValidation();
    
    // Auction creation form validation
    initAuctionFormValidation();
    
    // Profile form validation
    initProfileFormValidation();
    
    // Initialize countdown timers
    initCountdownTimers();

    // Wire password toggle on login form
    const passwordToggle = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');
    
    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            const icon = this.querySelector('i');
            if (icon) {
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            }
        });
    }
    
    // Wire password toggles on all pages
    initPasswordToggles();
    
    // Re-initialize password toggles periodically to catch dynamically loaded content
    setInterval(function() {
        initPasswordToggles();
    }, 2000);
});

/**
 * Initialize password toggles for all password fields
 */
function initPasswordToggles() {
    const passwordToggles = document.querySelectorAll('.toggle-password');
    
    console.log('Found', passwordToggles.length, 'password toggle buttons');
    
    passwordToggles.forEach((toggle, index) => {
        // Remove any existing onclick handlers to avoid conflicts
        toggle.removeAttribute('onclick');
        
        // Only add event listener if it doesn't already have one
        if (!toggle.hasAttribute('data-listener-added')) {
            console.log('Adding listener to toggle button', index);
            
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Password toggle clicked');
                
                const inputGroup = this.closest('.input-group');
                if (!inputGroup) {
                    console.log('No input group found');
                    return;
                }
                
                const passwordInput = inputGroup.querySelector('input[type="password"], input[type="text"]');
                if (!passwordInput) {
                    console.log('No password input found');
                    return;
                }
                
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                console.log('Password type changed to:', type);
                
                // Toggle icon
                const icon = this.querySelector('i');
                if (icon) {
                    if (type === 'password') {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    } else {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    }
                    console.log('Icon toggled');
                }
            });
            
            // Mark as having listener added
            toggle.setAttribute('data-listener-added', 'true');
        }
    });
}

/**
 * Initialize auction image gallery functionality
 */
function initAuctionGallery() {
    const thumbnails = document.querySelectorAll('.auction-thumbnail');
    const mainImage = document.querySelector('.auction-main-image img');
    
    if (!thumbnails.length || !mainImage) return;
    
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            // Update main image source
            const imgSrc = this.querySelector('img').getAttribute('src');
            mainImage.setAttribute('src', imgSrc);
            
            // Update active class
            thumbnails.forEach(thumb => thumb.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

/**
 * Initialize bid form validation
 */
function initBidFormValidation() {
    const bidForm = document.getElementById('bid-form');
    
    if (!bidForm) return;
    
    bidForm.addEventListener('submit', function(e) {
        const bidAmount = parseFloat(document.getElementById('bid_amount').value);
        const currentBid = parseFloat(document.getElementById('current_bid').value);
        const minBidIncrement = parseFloat(document.getElementById('min_bid_increment').value);
        
        if (bidAmount <= currentBid) {
            e.preventDefault();
            showAlert('Your bid must be higher than the current bid.', 'danger');
            return false;
        }
        
        if (bidAmount < (currentBid + minBidIncrement)) {
            e.preventDefault();
            showAlert(`Your bid must be at least ₹${(currentBid + minBidIncrement).toFixed(2)}.`, 'danger');
            return false;
        }
        
        return true;
    });
}

/**
 * Initialize auction creation form validation
 */
function initAuctionFormValidation() {
    const auctionForm = document.getElementById('create-auction-form');
    
    if (!auctionForm) return;
    
    auctionForm.addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const startingPrice = parseFloat(document.getElementById('starting_price').value);
        const endTime = document.getElementById('end_time').value;
        const images = document.getElementById('images').files;
        
        let isValid = true;
        
        if (title === '') {
            showFieldError('title', 'Please enter a title for your auction.');
            isValid = false;
        }
        
        if (description === '') {
            showFieldError('description', 'Please enter a description for your auction.');
            isValid = false;
        }
        
        if (isNaN(startingPrice) || startingPrice <= 0) {
            showFieldError('starting_price', 'Please enter a valid starting price greater than zero.');
            isValid = false;
        }
        
        if (endTime === '') {
            showFieldError('end_time', 'Please select an end time for your auction.');
            isValid = false;
        } else {
            const endDateTime = new Date(endTime);
            const now = new Date();
            
            if (endDateTime <= now) {
                showFieldError('end_time', 'End time must be in the future.');
                isValid = false;
            }
        }
        
        if (images.length === 0) {
            showFieldError('images', 'Please upload at least one image.');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
}

/**
 * Initialize profile form validation
 */
function initProfileFormValidation() {
    const profileForm = document.getElementById('profile-form');
    
    if (!profileForm) return;
    
    profileForm.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        let isValid = true;
        
        if (username === '') {
            showFieldError('username', 'Please enter a username.');
            isValid = false;
        }
        
        if (email === '') {
            showFieldError('email', 'Please enter an email address.');
            isValid = false;
        } else if (!isValidEmail(email)) {
            showFieldError('email', 'Please enter a valid email address.');
            isValid = false;
        }
        
        // Only validate passwords if the user is trying to change them
        if (newPassword !== '') {
            if (currentPassword === '') {
                showFieldError('current_password', 'Please enter your current password.');
                isValid = false;
            }
            
            if (newPassword.length < 8) {
                showFieldError('new_password', 'Password must be at least 8 characters long.');
                isValid = false;
            }
            
            if (newPassword !== confirmPassword) {
                showFieldError('confirm_password', 'Passwords do not match.');
                isValid = false;
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
}

/**
 * Initialize countdown timers
 */
function initCountdownTimers() {
    // Support both legacy markup (wrapper .time-left > .countdown)
    // and direct elements with .countdown and data-end.
    const countdownDisplays = document.querySelectorAll('.countdown');
    if (!countdownDisplays.length) return;

    function parseEnd(attr) {
        if (!attr) return NaN;
        // If attr is a unix timestamp (seconds), convert; if ms or ISO, Date can parse
        const num = Number(attr);
        if (!Number.isNaN(num)) {
            // Heuristic: seconds vs ms
            return (num < 2e12 ? num * 1000 : num);
        }
        return new Date(attr).getTime();
    }

    function updateCountdowns() {
        countdownDisplays.forEach(display => {
            const endAttr = display.getAttribute('data-end');
            const endTime = parseEnd(endAttr);
            if (Number.isNaN(endTime)) return;

            const now = Date.now();
            const distance = endTime - now;

            if (distance <= 0) {
                display.textContent = 'Auction ended';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            let text = '';
            if (days > 0) text += days + 'd ';
            text += hours + 'h ' + minutes + 'm ' + seconds + 's';
            display.textContent = text;
        });
    }

    updateCountdowns();
    setInterval(updateCountdowns, 1000);
}

/**
 * Show an alert message
 * @param {string} message - The message to display
 * @param {string} type - The type of alert (success, danger, warning, info)
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
    alertContainer.role = 'alert';
    alertContainer.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Find the main content area to insert the alert
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertBefore(alertContainer, mainContent.firstChild);
    } else {
        document.body.insertBefore(alertContainer, document.body.firstChild);
    }
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertContainer.classList.remove('show');
        setTimeout(() => alertContainer.remove(), 300);
    }, 5000);
}

/**
 * Show an error message for a form field
 * @param {string} fieldId - The ID of the field with the error
 * @param {string} message - The error message to display
 */
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    field.classList.add('is-invalid');
    
    // Check if error feedback element already exists
    let feedback = field.nextElementSibling;
    if (!feedback || !feedback.classList.contains('invalid-feedback')) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        field.parentNode.insertBefore(feedback, field.nextSibling);
    }
    
    feedback.textContent = message;
    
    // Add event listener to clear error when field is changed
    field.addEventListener('input', function() {
        this.classList.remove('is-invalid');
    }, { once: true });
}

/**
 * Validate an email address
 * @param {string} email - The email address to validate
 * @returns {boolean} - Whether the email is valid
 */
function isValidEmail(email) {
    const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return re.test(email);
}

/**
 * Format a number as currency
 * @param {number} amount - The amount to format
 * @param {string} currency - The currency code (default: INR)
 * @returns {string} - The formatted currency string
 */
function formatCurrency(amount, currency = 'INR') {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

/**
 * Format a date
 * @param {string|Date} date - The date to format
 * @param {object} options - Intl.DateTimeFormat options
 * @returns {string} - The formatted date string
 */
function formatDate(date, options = {}) {
    const defaultOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    
    return new Intl.DateTimeFormat('en-US', { ...defaultOptions, ...options }).format(dateObj);
}

/**
 * Confirm an action with a dialog
 * @param {string} message - The confirmation message
 * @param {function} callback - The function to call if confirmed
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Toggle password visibility
 * @param {string} inputId - The ID of the password input field
 * @param {HTMLElement} toggleButton - The toggle button element
 */
function togglePasswordVisibility(inputId, toggleButton) {
    const passwordInput = document.getElementById(inputId);
    
    if (!passwordInput || !toggleButton) {
        console.error('Password input or toggle button not found');
        return;
    }
    
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    
    // Toggle icon
    const icon = toggleButton.querySelector('i');
    if (icon) {
        if (type === 'password') {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }
    
    // Focus back to the input after toggle
    passwordInput.focus();
}

/**
 * Preview image before upload
 * @param {string} inputId - The ID of the file input field
 * @param {string} previewId - The ID of the preview element
 */
function previewImage(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (!input || !preview) return;
    
    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            
            reader.readAsDataURL(this.files[0]);
        }
    });
}

/**
 * Preview multiple images before upload
 * @param {string} inputId - The ID of the file input field
 * @param {string} previewContainerId - The ID of the preview container element
 */
function previewMultipleImages(inputId, previewContainerId) {
    const input = document.getElementById(inputId);
    const previewContainer = document.getElementById(previewContainerId);
    
    if (!input || !previewContainer) return;
    
    input.addEventListener('change', function() {
        // Clear previous previews
        previewContainer.innerHTML = '';
        
        if (this.files) {
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    
                    previewItem.appendChild(img);
                    previewContainer.appendChild(previewItem);
                };
                
                reader.readAsDataURL(file);
            });
        }
    });
}