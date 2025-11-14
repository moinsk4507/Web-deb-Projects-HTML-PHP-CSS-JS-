<?php
// Include configuration file
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Set redirect after login
    $_SESSION['redirect_after_login'] = 'create_auction.php';
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Do not block unverified users here (per requested separate posting flow)

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Get categories for dropdown
$categories = get_all_categories();

// Process auction creation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $starting_price = isset($_POST['starting_price']) ? floatval($_POST['starting_price']) : 0;
    $reserve_price = isset($_POST['reserve_price']) ? floatval($_POST['reserve_price']) : 0;
    $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
    $end_time = isset($_POST['end_time']) ? trim($_POST['end_time']) : '';
    $condition = isset($_POST['condition']) ? trim($_POST['condition']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $shipping_cost = isset($_POST['shipping_cost']) ? floatval($_POST['shipping_cost']) : 0;
    
    // Combine date and time
    $end_datetime = $end_date . ' ' . $end_time . ':00';
    
    // Validate form data
    if (empty($title) || empty($description) || $category_id <= 0 || $starting_price <= 0 || empty($end_date) || empty($end_time)) {
        $error = 'Please fill in all required fields';
    } elseif (strlen($title) > 100) {
        $error = 'Title must be less than 100 characters';
    } elseif ($starting_price < 0.01) {
        $error = 'Starting price must be at least 0.01';
    } elseif ($reserve_price > 0 && $reserve_price < $starting_price) {
        $error = 'Reserve price must be greater than or equal to starting price';
    } elseif (strtotime($end_datetime) <= time()) {
        $error = 'End date and time must be in the future';
    } else {
        // Create auction using positional signature
        $create_result = create_auction($user_id, $title, $description, $starting_price, $reserve_price, $category_id, $end_datetime);
        
        if ($create_result['success']) {
            $auction_id = $create_result['auction_id'];
            
            // Handle image uploads
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $upload_result = upload_auction_images($auction_id, $_FILES['images']);
                
                if (!$upload_result['success']) {
                    $error = $upload_result['message'];
                }
            }
            
            if (empty($error)) {
                // Set success message
                $_SESSION['temp_message'] = array(
                    'type' => 'success',
                    'text' => 'Your auction has been created successfully!'
                );
                
                // Redirect to auction page
                header("Location: auction.php?id=" . $auction_id);
                exit();
            }
        } else {
            $error = $create_result['message'];
        }
    }
}

// Set page title
$page_title = "Post Auction";

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Post New Auction</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="" enctype="multipart/form-data" id="create-auction-form">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                            <label for="title" class="form-label">Item Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" maxlength="100" required>
                                    <small class="text-muted">Maximum 100 characters</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($category_id) && $category_id == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="6" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                            <small class="text-muted">Provide detailed information about your item</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="starting_price" class="form-label">Starting Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="starting_price" name="starting_price" value="<?php echo isset($starting_price) ? htmlspecialchars($starting_price) : ''; ?>" min="0.01" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="reserve_price" class="form-label">Reserve Price (Optional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="reserve_price" name="reserve_price" value="<?php echo isset($reserve_price) ? htmlspecialchars($reserve_price) : ''; ?>" min="0" step="0.01">
                                    </div>
                                    <small class="text-muted">Minimum price for the item to sell</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="shipping_cost" class="form-label">Shipping Cost (Optional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="shipping_cost" name="shipping_cost" value="<?php echo isset($shipping_cost) ? htmlspecialchars($shipping_cost) : ''; ?>" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($end_date) ? htmlspecialchars($end_date) : ''; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" value="<?php echo isset($end_time) ? htmlspecialchars($end_time) : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="condition" class="form-label">Item Condition</label>
                                    <select class="form-select" id="condition" name="condition">
                                        <option value="">Select Condition</option>
                                        <option value="New" <?php echo (isset($condition) && $condition == 'New') ? 'selected' : ''; ?>>New</option>
                                        <option value="Like New" <?php echo (isset($condition) && $condition == 'Like New') ? 'selected' : ''; ?>>Like New</option>
                                        <option value="Excellent" <?php echo (isset($condition) && $condition == 'Excellent') ? 'selected' : ''; ?>>Excellent</option>
                                        <option value="Good" <?php echo (isset($condition) && $condition == 'Good') ? 'selected' : ''; ?>>Good</option>
                                        <option value="Fair" <?php echo (isset($condition) && $condition == 'Fair') ? 'selected' : ''; ?>>Fair</option>
                                        <option value="Poor" <?php echo (isset($condition) && $condition == 'Poor') ? 'selected' : ''; ?>>Poor</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Item Location</label>
                                    <input type="text" class="form-control" id="location" name="location" value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="images" class="form-label">Images (Maximum 5)</label>
                            <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                            <small class="text-muted">Accepted formats: JPG, JPEG, PNG, GIF. Max size: 2MB per image.</small>
                            <div id="image-preview-container" class="mt-2 d-flex flex-wrap gap-2"></div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Post Auction</button>
                            <a href="my_auctions.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>