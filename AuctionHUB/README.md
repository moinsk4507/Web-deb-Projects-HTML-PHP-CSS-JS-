# 🏆 AuctionHUB - Online Auction Platform

A comprehensive web-based auction platform built with PHP, MySQL, and modern web technologies. AuctionHUB allows users to create, browse, and bid on auctions in a secure and user-friendly environment.

## 🌟 Features

### 🔐 User Management
- **User Registration & Authentication** - Secure account creation with email verification
- **Profile Management** - Complete user profiles with image uploads
- **Password Security** - Password reset functionality with secure tokens
- **User Statistics** - Track auctions created, bids placed, and wins

### 🎯 Auction System
- **Create Auctions** - Multi-step auction creation with image uploads
- **Browse & Search** - Advanced filtering by category, price, and keywords
- **Bidding System** - Real-time bidding with automatic bid increments
- **Auction Management** - Edit auctions, view bid history, and manage listings
- **Watchlist** - Save interesting auctions for later bidding

### 💰 Currency System
- **Indian Rupee Support** - All prices displayed in ₹ (Indian Rupees)
- **Real-time Price Updates** - Current bid tracking and display
- **Bid Increments** - Automatic minimum bid calculations

### 💬 Communication
- **Messaging System** - Direct communication between users
- **Seller Contact** - Easy contact forms for auction inquiries
- **Notifications** - Real-time notifications for bids, wins, and messages

### 🔔 Notification Center
- **Real-time Alerts** - Bid notifications, auction endings, and messages
- **Email Notifications** - Automated email alerts for important events
- **Notification Management** - Mark as read, delete, and filter notifications

### 👨‍💼 Admin Panel
- **User Management** - Oversee user accounts and activities
- **Auction Moderation** - Manage and moderate auction listings
- **System Statistics** - View platform usage and performance metrics
- **Category Management** - Organize and manage auction categories

## 🛠️ Technology Stack

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL/MariaDB** - Database management
- **Apache/Nginx** - Web server

### Frontend
- **HTML5** - Markup structure
- **CSS3** - Styling and responsive design
- **JavaScript (ES6+)** - Interactive functionality
- **Bootstrap 5** - UI framework
- **Font Awesome** - Icons and graphics

### Security Features
- **Password Hashing** - Secure password storage using PHP's password_hash()
- **SQL Injection Prevention** - Prepared statements throughout
- **XSS Protection** - Input sanitization and output escaping
- **CSRF Protection** - Token-based form protection
- **File Upload Security** - Type and size validation for uploads

## 📁 Project Structure

```
AuctionHUB/
├── assets/
│   ├── css/
│   │   ├── style.css          # Main stylesheet
│   │   ├── homepage.css       # Homepage specific styles
│   │   └── about.css          # About page styles
│   ├── js/
│   │   ├── script.js          # Main JavaScript functionality
│   │   └── homepage.js        # Homepage specific scripts
│   └── images/
│       ├── logo-icon.svg      # Site logo
│       ├── hero-image.svg     # Hero section image
│       └── default-profile.jpg # Default user avatar
├── includes/
│   ├── config.php             # Database and system configuration
│   ├── header.php             # Site header template
│   ├── footer.php             # Site footer template
│   └── functions/
│       ├── auction_functions.php  # Core auction operations
│       ├── auth_functions.php     # Authentication functions
│       ├── email_functions.php    # Email system functions
│       ├── admin_functions.php    # Admin panel functions
│       └── function_function.php  # Utility functions
├── uploads/
│   ├── auction_images/        # Auction product images
│   └── profile_images/        # User profile pictures
├── database/
│   ├── auction_system.sql     # Database schema
│   └── system_auction.sql     # System data
├── logs/
│   └── emails.log             # Email sending logs
├── index.php                  # Homepage
├── browse.php                 # Auction browser
├── auction.php                # Individual auction view
├── create_auction.php         # Auction creation form
├── auction_edit.php           # Auction editing
├── profile.php                # User profile management
├── my_auctions.php            # User's created auctions
├── my_bids.php                # User's bid history
├── watchlist.php              # User's saved auctions
├── login.php                  # User login
├── register.php               # User registration
├── forgot_password.php        # Password recovery
├── reset_password.php         # Password reset
├── verify_email.php           # Email verification
├── logout.php                 # User logout
├── messages.php               # Message center
├── conversation.php           # Individual conversation
├── contact_seller.php         # Contact auction seller
├── contact.php                # General contact form
├── notifications.php          # Notification center
├── admin_dashboard.php        # Admin control panel
├── about.php                  # About page
├── help.php                   # Help and FAQ
└── README.md                  # This file
```

## 🚀 Installation Guide

### Prerequisites
- **Web Server** (Apache/Nginx)
- **PHP 7.4 or higher**
- **MySQL 5.7 or MariaDB 10.3+**
- **Web Browser** (Chrome, Firefox, Safari, Edge)

### Step 1: Download and Setup
1. Download or clone the AuctionHUB project
2. Place the project folder in your web server directory
3. Ensure proper file permissions (755 for directories, 644 for files)

### Step 2: Database Configuration
1. Create a new MySQL database for the project
2. Import the database schema from `database/auction_system.sql`
3. Update database credentials in `includes/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'auction_hub');
   ```

### Step 3: File Permissions
```bash
# Set proper permissions for upload directories
chmod 755 uploads/
chmod 755 uploads/auction_images/
chmod 755 uploads/profile_images/
chmod 755 logs/
```

### Step 4: Configuration
1. Update `includes/config.php` with your system settings:
   ```php
   // Site configuration
   define('SITE_NAME', 'AuctionHUB');
   define('SITE_URL', 'http://your-domain.com');
   define('ADMIN_EMAIL', 'admin@yourdomain.com');
   
   // File upload settings
   define('MAX_FILE_SIZE', 2097152); // 2MB
   define('UPLOAD_DIR', 'uploads/');
   ```

### Step 5: Email Configuration
Configure SMTP settings for email functionality:
```php
// Email settings in config.php
define('SMTP_HOST', 'your-smtp-server.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@domain.com');
define('SMTP_PASSWORD', 'your-email-password');
```

## 📊 Database Schema

### Core Tables
- **`users`** - User accounts and profile information
- **`auctions`** - Auction listings with details
- **`bids`** - Bid records and history
- **`categories`** - Auction categories
- **`auction_images`** - Image storage for auctions
- **`watchlist`** - User saved auctions
- **`notifications`** - System notifications
- **`messages`** - User-to-user communications
- **`feedback`** - User feedback and ratings

### Key Relationships
- Users can create multiple auctions (1:many)
- Auctions can have multiple bids (1:many)
- Users can bid on multiple auctions (many:many)
- Auctions belong to categories (many:1)

## 🔧 Configuration Options

### System Settings
```php
// User roles
define('ROLE_USER', 1);
define('ROLE_ADMIN', 2);

// Auction statuses
define('AUCTION_STATUS_ACTIVE', 1);
define('AUCTION_STATUS_ENDED', 2);
define('AUCTION_STATUS_CANCELLED', 3);

// Notification types
define('NOTIFICATION_NEW_BID', 100);
define('NOTIFICATION_OUTBID', 101);
define('NOTIFICATION_AUCTION_ENDED', 103);
define('NOTIFICATION_AUCTION_WON', 104);
```

### File Upload Settings
```php
// Allowed image types
$allowed_image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Maximum file sizes
define('MAX_PROFILE_IMAGE_SIZE', 1048576); // 1MB
define('MAX_AUCTION_IMAGE_SIZE', 2097152); // 2MB
```

## 🎨 Customization

### Styling
- Modify `assets/css/style.css` for main styling
- Update `assets/css/homepage.css` for homepage design
- Customize Bootstrap variables for consistent theming

### Functionality
- Add new features by extending functions in `includes/functions/`
- Modify bid increment logic in `get_min_bid_increment()`
- Customize email templates in `includes/functions/email_functions.php`

### Currency
The system is configured for Indian Rupees (₹). To change currency:
1. Update `format_currency()` function in `auction_functions.php`
2. Modify `formatCurrency()` in `script.js`
3. Update currency symbols throughout the interface

## 🔒 Security Considerations

### Data Protection
- All user inputs are sanitized and validated
- Passwords are hashed using PHP's `password_hash()`
- SQL queries use prepared statements
- File uploads are validated for type and size

### Access Control
- User authentication required for sensitive operations
- Admin-only access to administrative functions
- CSRF tokens protect against cross-site request forgery

### Regular Maintenance
- Clean up old notifications and expired sessions
- Monitor file upload directories for unauthorized files
- Regular database backups recommended

## 📱 Browser Support

### Desktop Browsers
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

### Mobile Browsers
- iOS Safari 13+
- Chrome Mobile 80+
- Samsung Internet 12+

## 🤝 Contributing

### Development Guidelines
1. Follow PSR-12 coding standards for PHP
2. Use meaningful variable and function names
3. Add comments for complex logic
4. Test all functionality before submitting changes

### Reporting Issues
- Use descriptive titles for bug reports
- Include steps to reproduce issues
- Specify browser and PHP version
- Attach relevant error logs when possible

## 📄 License

This project is proprietary software. All rights reserved.

## 📞 Support

For technical support or questions:
- **Email:** admin@auctionhub.com
- **Documentation:** Check the `help.php` page
- **FAQ:** Available in the help section

## 🔄 Recent Updates

### Version 2.1 (Current)
- ✅ Currency conversion from USD to Indian Rupees (₹)
- ✅ Fixed password visibility toggle functionality
- ✅ Enhanced error handling and user feedback
- ✅ Improved mobile responsiveness
- ✅ Added missing utility functions for watchlist

### Version 2.0
- Complete user authentication system
- Auction creation and management
- Real-time bidding system
- Messaging and notification system
- Admin panel implementation

---

**AuctionHUB** - Your premier destination for online auctions. Built with modern web technologies and designed for optimal user experience.

*Last Updated: January 2025*
