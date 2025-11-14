<?php
// Test database connection
echo "Testing database connection...\n";

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'auction_system');

// Connect to MySQL server without database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection to MySQL failed: " . $conn->connect_error);
    }
    
    echo "Connection to MySQL server successful!\n";
    echo "MySQL Server Info: " . $conn->server_info . "\n";
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($result->num_rows == 0) {
        echo "Database '" . DB_NAME . "' does not exist. Creating it...\n";
        
        // Create database
        if ($conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME) === TRUE) {
            echo "Database created successfully!\n";
            
            // Import SQL schema
            echo "Importing database schema from auction_system.sql...\n";
            
            // Select the database
            $conn->select_db(DB_NAME);
            
            // Execute SQL commands manually for each table
            $success = true;
            
            // Check if users table exists
            $result = $conn->query("SHOW TABLES LIKE 'users'");
            if ($result->num_rows == 0) {
                echo "Table 'users' does not exist. Creating...\n";
                // Create users table
                $query = "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    first_name VARCHAR(50),
                    last_name VARCHAR(50),
                    phone VARCHAR(20),
                    address TEXT,
                    city VARCHAR(50),
                    state VARCHAR(50),
                    zip_code VARCHAR(20),
                    country VARCHAR(50),
                    profile_image VARCHAR(255),
                    bio TEXT,
                    role_id INT NOT NULL DEFAULT 2,
                    email_verified TINYINT(1) NOT NULL DEFAULT 0,
                    verification_token VARCHAR(100),
                    reset_token VARCHAR(100),
                    reset_token_expires DATETIME,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                
                if ($conn->query($query) === FALSE) {
                    echo "Error creating users table: " . $conn->error . "\n";
                    $success = false;
                } else {
                    echo "Users table created successfully.\n";
                }
            } else {
                echo "Table 'users' already exists.\n";
            }
            
            // Check if categories table exists
            $result = $conn->query("SHOW TABLES LIKE 'categories'");
            if ($result->num_rows == 0) {
                echo "Table 'categories' does not exist. Creating...\n";
                // Create categories table
                $query = "CREATE TABLE IF NOT EXISTS categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    description TEXT,
                    parent_id INT,
                    image VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                
                if ($conn->query($query) === FALSE) {
                    echo "Error creating categories table: " . $conn->error . "\n";
                    $success = false;
                } else {
                    echo "Categories table created successfully.\n";
                }
            } else {
                echo "Table 'categories' already exists.\n";
            }
            
            // Check if auctions table exists
            $result = $conn->query("SHOW TABLES LIKE 'auctions'");
            if ($result->num_rows == 0) {
                echo "Table 'auctions' does not exist. Creating...\n";
                // Create auctions table
                $query = "CREATE TABLE IF NOT EXISTS auctions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    category_id INT,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    starting_price DECIMAL(10,2) NOT NULL,
                    reserve_price DECIMAL(10,2),
                    current_price DECIMAL(10,2),
                    buy_now_price DECIMAL(10,2),
                    bid_increment DECIMAL(10,2) DEFAULT 1.00,
                    start_date DATETIME NOT NULL,
                    end_date DATETIME NOT NULL,
                    status ENUM('active', 'pending', 'ended', 'cancelled') NOT NULL DEFAULT 'pending',
                    featured TINYINT(1) NOT NULL DEFAULT 0,
                    views INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
                )";
                
                if ($conn->query($query) === FALSE) {
                    echo "Error creating auctions table: " . $conn->error . "\n";
                    $success = false;
                } else {
                    echo "Auctions table created successfully.\n";
                }
            } else {
                echo "Table 'auctions' already exists.\n";
            }
            
            // Check if auction_images table exists
            $result = $conn->query("SHOW TABLES LIKE 'auction_images'");
            if ($result->num_rows == 0) {
                echo "Table 'auction_images' does not exist. Creating...\n";
                // Create auction_images table
                $query = "CREATE TABLE IF NOT EXISTS auction_images (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    auction_id INT NOT NULL,
                    image_path VARCHAR(255) NOT NULL,
                    is_primary TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE
                )";
                
                if ($conn->query($query) === FALSE) {
                    echo "Error creating auction_images table: " . $conn->error . "\n";
                    $success = false;
                } else {
                    echo "Auction_images table created successfully.\n";
                }
            } else {
                echo "Table 'auction_images' already exists.\n";
            }
            
            // Check if bids table exists
            $result = $conn->query("SHOW TABLES LIKE 'bids'");
            if ($result->num_rows == 0) {
                echo "Table 'bids' does not exist. Creating...\n";
                // Create bids table
                $query = "CREATE TABLE IF NOT EXISTS bids (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    auction_id INT NOT NULL,
                    user_id INT NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
                
                if ($conn->query($query) === FALSE) {
                    echo "Error creating bids table: " . $conn->error . "\n";
                    $success = false;
                } else {
                    echo "Bids table created successfully.\n";
                }
            } else {
                echo "Table 'bids' already exists.\n";
            }
            
            if ($success) {
                echo "Database schema imported successfully!\n";
            } else {
                echo "There were errors importing the database schema.\n";
            }
        } else {
            echo "Error creating database: " . $conn->error . "\n";
            exit;
        }
    } else {
        echo "Database '" . DB_NAME . "' already exists.\n";
    }
    
    // Select database
    $conn->select_db(DB_NAME);
    
    // Create tables if they don't exist
    createTables($conn);
    
    // Now connect to the database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connection successful!\n";
    echo "MySQL Server Info: " . $conn->server_info . "\n";
    
    // Check if tables exist
    $tables = array('users', 'categories', 'auctions');
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "Table '$table' exists.\n";
        } else {
            echo "Table '$table' does not exist.\n";
        }
    }
    
    // Close connection
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Function to create tables
function createTables($conn) {
    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        echo "Table 'users' does not exist. Creating...\n";
        // Create users table
        $query = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(50),
            state VARCHAR(50),
            zip_code VARCHAR(20),
            country VARCHAR(50),
            profile_image VARCHAR(255),
            bio TEXT,
            role_id INT NOT NULL DEFAULT 2,
            email_verified TINYINT(1) NOT NULL DEFAULT 0,
            verification_token VARCHAR(100),
            reset_token VARCHAR(100),
            reset_token_expires DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($query) === FALSE) {
            echo "Error creating users table: " . $conn->error . "\n";
        } else {
            echo "Users table created successfully.\n";
        }
    } else {
        echo "Table 'users' already exists.\n";
    }
    
    // Check if categories table exists
    $result = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($result->num_rows == 0) {
        echo "Table 'categories' does not exist. Creating...\n";
        // Create categories table
        $query = "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            parent_id INT,
            image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($query) === FALSE) {
            echo "Error creating categories table: " . $conn->error . "\n";
        } else {
            echo "Categories table created successfully.\n";
        }
    } else {
        echo "Table 'categories' already exists.\n";
    }
    
    // Check if auctions table exists
    $result = $conn->query("SHOW TABLES LIKE 'auctions'");
    if ($result->num_rows == 0) {
        echo "Table 'auctions' does not exist. Creating...\n";
        // Create auctions table
        $query = "CREATE TABLE IF NOT EXISTS auctions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category_id INT,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            starting_price DECIMAL(10,2) NOT NULL,
            reserve_price DECIMAL(10,2),
            current_price DECIMAL(10,2),
            buy_now_price DECIMAL(10,2),
            bid_increment DECIMAL(10,2) DEFAULT 1.00,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            status ENUM('active', 'pending', 'ended', 'cancelled') NOT NULL DEFAULT 'pending',
            featured TINYINT(1) NOT NULL DEFAULT 0,
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($query) === FALSE) {
            echo "Error creating auctions table: " . $conn->error . "\n";
        } else {
            echo "Auctions table created successfully.\n";
        }
    } else {
        echo "Table 'auctions' already exists.\n";
    }
    
    // Check if auction_images table exists
    $result = $conn->query("SHOW TABLES LIKE 'auction_images'");
    if ($result->num_rows == 0) {
        echo "Table 'auction_images' does not exist. Creating...\n";
        // Create auction_images table
        $query = "CREATE TABLE IF NOT EXISTS auction_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            auction_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            is_primary TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($query) === FALSE) {
            echo "Error creating auction_images table: " . $conn->error . "\n";
        } else {
            echo "Auction_images table created successfully.\n";
        }
    } else {
        echo "Table 'auction_images' already exists.\n";
    }
    
    // Check if bids table exists
    $result = $conn->query("SHOW TABLES LIKE 'bids'");
    if ($result->num_rows == 0) {
        echo "Table 'bids' does not exist. Creating...\n";
        // Create bids table
        $query = "CREATE TABLE IF NOT EXISTS bids (
            id INT AUTO_INCREMENT PRIMARY KEY,
            auction_id INT NOT NULL,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($query) === FALSE) {
            echo "Error creating bids table: " . $conn->error . "\n";
        } else {
            echo "Bids table created successfully.\n";
        }
    } else {
        echo "Table 'bids' already exists.\n";
    }

    // Check if notifications table exists
    $result = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($result->num_rows == 0) {
        echo "Table 'notifications' does not exist. Creating...\n";
        $query = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type INT NOT NULL,
            data TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) NOT NULL DEFAULT 0
        )";
        if ($conn->query($query) === FALSE) {
            echo "Error creating notifications table: " . $conn->error . "\n";
        } else {
            echo "Notifications table created successfully.\n";
        }
    } else {
        echo "Table 'notifications' already exists.\n";
    }

    // Check if watchlist table exists
    $result = $conn->query("SHOW TABLES LIKE 'watchlist'");
    if ($result->num_rows == 0) {
        echo "Table 'watchlist' does not exist. Creating...\n";
        $query = "CREATE TABLE IF NOT EXISTS watchlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            auction_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_watch (user_id, auction_id)
        )";
        if ($conn->query($query) === FALSE) {
            echo "Error creating watchlist table: " . $conn->error . "\n";
        } else {
            echo "Watchlist table created successfully.\n";
        }
    } else {
        echo "Table 'watchlist' already exists.\n";
    }
}
?>