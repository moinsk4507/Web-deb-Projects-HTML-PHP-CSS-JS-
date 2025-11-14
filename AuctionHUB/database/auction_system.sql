-- Online Auction System Database Schema

-- Create Database
CREATE DATABASE IF NOT EXISTS auction_system;
USE auction_system;

--- Use the correct database
CREATE DATABASE IF NOT EXISTS auction_system;
USE auction_system;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    is_admin BOOLEAN DEFAULT FALSE,
    account_status ENUM('active', 'suspended', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Auctions Table
CREATE TABLE IF NOT EXISTS auctions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    starting_price DECIMAL(10,2) NOT NULL,
    current_price DECIMAL(10,2) NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('active', 'ended') DEFAULT 'active',
    user_id INT NOT NULL,
    category_id INT,
    reserve_price DECIMAL(10,2),
    buy_now_price DECIMAL(10,2),
    increment_amount DECIMAL(10,2) DEFAULT 1.00,
    is_featured BOOLEAN DEFAULT FALSE,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
    -- category_id foreign key can be added if categories table exists
);

-- Bids Table
CREATE TABLE IF NOT EXISTS bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    user_id INT NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    bid_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES auctions(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);

-- Auction Images Table
CREATE TABLE IF NOT EXISTS auction_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES auctions(id)
);

-- Watchlist Table
CREATE TABLE IF NOT EXISTS watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    auction_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (auction_id) REFERENCES auctions(id),
    UNIQUE KEY unique_watch (user_id, auction_id)
);

-- Messages Table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user_id) REFERENCES users(id),rice,
    FOREIGN KEY (to_user_id) REFERENCES users(id)_time,
);
time > NOW() AND b.bid_amount = a.current_price THEN TRUE 
-- Bid History View
CREATE OR REPLACE VIEW bid_history ASis_winning_bid
SELECT 
    b.id as bid_id,
    b.auction_id,ion_id = a.id
    b.user_id,ORDER BY b.bid_time DESC;
    u.username,
    b.bid_amount,for bid validation
    b.bid_time,DELIMITER //
    a.current_price,
    a.end_time,_bid_insert 
    CASE T ON bids
        WHEN a.end_time > NOW() AND b.bid_amount = a.current_price THEN TRUE ACH ROW
        ELSE FALSE 
    END as is_winning_bid;
FROM bids b
JOIN users u ON b.user_id = u.idDECLARE min_increment DECIMAL(10,2);
JOIN auctions a ON b.auction_id = a.id
ORDER BY b.bid_time DESC;
unt 
-- Triggers for bid validationement
DELIMITER //FROM auctions a WHERE a.id = NEW.auction_id;

CREATE TRIGGER before_bid_insert 
BEFORE INSERT ON bidse' THEN
FOR EACH ROW
BEGIN MESSAGE_TEXT = 'Cannot bid on inactive auction';
    DECLARE current_price DECIMAL(10,2);END IF;
    DECLARE auction_status VARCHAR(10);
    DECLARE min_increment DECIMAL(10,2);
    t_price + min_increment THEN
    -- Get auction details
    SELECT a.current_price, a.status, a.increment_amount  MESSAGE_TEXT = 'Bid must be higher than current price plus increment amount';
    INTO current_price, auction_status, min_incrementND IF;
    FROM auctions a WHERE a.id = NEW.auction_id;END//
    
    -- Check if auction is active_bid_insert 
    IF auction_status != 'active' THEN ON bids
        SIGNAL SQLSTATE '45000'ACH ROW
        SET MESSAGE_TEXT = 'Cannot bid on inactive auction';
    END IF;n current price
    
    -- Check if bid is high enoughd_amount 
    IF NEW.bid_amount <= current_price + min_increment THENWHERE id = NEW.auction_id;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Bid must be higher than current price plus increment amount';
    END IF;
END//TINCT user_id, CONCAT('You have been outbid on auction #', NEW.auction_id)

CREATE TRIGGER after_bid_insert tion_id 
AFTER INSERT ON bids.user_id
FOR EACH ROW
BEGIN(bid_amount) 
    -- Update auction current price
    UPDATE auctions  = NEW.auction_id 
    SET current_price = NEW.bid_amount   AND id != NEW.id
    WHERE id = NEW.auction_id;);
    
    -- Notify previous highest bidder
    INSERT INTO notifications (user_id, message)
    SELECT DISTINCT user_id, CONCAT('You have been outbid on auction #', NEW.auction_id)d, CONCAT('New bid placed on your auction #', NEW.auction_id)
    FROM bids 
    WHERE auction_id = NEW.auction_id HERE id = NEW.auction_id;
    AND user_id != NEW.user_idEND//
    AND bid_amount = (
        SELECT MAX(bid_amount) 
        FROM bids KEY,
        WHERE auction_id = NEW.auction_id L,
        AND id != NEW.id
    );  password VARCHAR(255) NOT NULL
    );
    -- Notify auction owner
    INSERT INTO notifications (user_id, message)
    SELECT user_id, CONCAT('New bid placed on your auction #', NEW.auction_id)
    FROM auctionsY KEY,
    WHERE id = NEW.auction_id;) NOT NULL,
END//
,
DELIMITER ; NOT NULL,

-- Bids Table  status ENUM('active', 'ended') DEFAULT 'active'
CREATE TABLE IF NOT EXISTS bids ();
    id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    user_id INT NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,IMARY KEY,
    bid_time DATETIME DEFAULT CURRENT_TIMESTAMP,LL,
    FOREIGN KEY (auction_id) REFERENCES auctions(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
ns(id),
-- Notifications Table  FOREIGN KEY (user_id) REFERENCES users(id)
CREATE TABLE IF NOT EXISTS notifications ();
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,ns (
    is_read BOOLEAN DEFAULT FALSE, PRIMARY KEY,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
AMP,
-- Settings table  FOREIGN KEY (user_id) REFERENCES users(id)
CREATE TABLE IF NOT EXISTS settings ();
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NULL UNIQUE,
);

-- Insert default settings  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
INSERT INTO settings (setting_key, setting_value) VALUES);
('site_name', 'Online Auction System'),
('site_email', 'admin@auctionsystem.com'),
('site_description', 'A platform for online auctions'),ing_value) VALUES
('currency', 'USD'),
('min_bid_increment', '1.00'),
('featured_auction_fee', '5.00'), 'A platform for online auctions'),
('commission_rate', '5'),
('max_images_per_auction', '5'),
('default_auction_duration', '7'),'5.00'),
('enable_buy_now', '1'),
('enable_reserve_price', '1'),
('enable_auto_extend', '1'),on', '7'),
('auto_extend_minutes', '5'),
('auto_extend_threshold', '5');),

-- Insert default admin user
INSERT INTO users (username, email, password) VALUES('auto_extend_threshold', '5');
('admin', 'admin@auctionsystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Create indexes for better performance
CREATE INDEX idx_auctions_user_id ON auctions(user_id);('admin', 'admin@auctionsystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
CREATE INDEX idx_auctions_category_id ON auctions(category_id);
CREATE INDEX idx_auctions_status ON auctions(status);
CREATE INDEX idx_auctions_end_time ON auctions(end_time);
CREATE INDEX idx_auctions_is_featured ON auctions(is_featured);egory_id);
CREATE INDEX idx_bids_auction_id ON bids(auction_id);
CREATE INDEX idx_bids_user_id ON bids(user_id);
CREATE INDEX idx_watchlist_user_id ON watchlist(user_id);featured);
CREATE INDEX idx_watchlist_auction_id ON watchlist(auction_id);n_id);
CREATE INDEX idx_messages_to_user_id ON messages(to_user_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);CREATE INDEX idx_notifications_is_read ON notifications(is_read);