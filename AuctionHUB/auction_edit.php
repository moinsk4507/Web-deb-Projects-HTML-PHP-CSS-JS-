<?php
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$userId = (int)$_SESSION['user_id'];
$auctionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($auctionId <= 0) { header('Location: my_auctions.php'); exit(); }

$auction = get_auction_by_id($auctionId);
if (!$auction || (int)$auction['user_id'] !== $userId) {
    header('Location: my_auctions.php');
    exit();
}

// Prevent editing when bids exist
$bids = get_auction_bids($auctionId, 1);
$hasBids = count($bids) > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$hasBids) {
    $data = [
        'title' => $_POST['title'] ?? $auction['title'],
        'description' => $_POST['description'] ?? $auction['description'],
        'starting_price' => isset($_POST['starting_price']) ? (float)$_POST['starting_price'] : $auction['starting_price'],
        'reserve_price' => isset($_POST['reserve_price']) ? (float)$_POST['reserve_price'] : $auction['reserve_price'],
        'end_date' => $_POST['end_date'] ?? $auction['end_date'],
        'category_id' => isset($_POST['category_id']) ? (int)$_POST['category_id'] : $auction['category_id'],
    ];
    $result = update_auction($auctionId, $userId, $data);
    if ($result['success']) {
        header('Location: my_auctions.php');
        exit();
    }
    $error = $result['message'] ?? 'Failed to update auction';
}

$page_title = 'Edit Auction';
include __DIR__ . '/includes/header.php';
?>
<div class="container my-4">
  <div class="row">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><h5 class="mb-0">Edit Auction</h5></div>
        <div class="card-body">
          <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          <?php if ($hasBids): ?>
            <div class="alert alert-warning">This auction already has bids and cannot be edited.</div>
          <?php else: ?>
          <form method="post">
            <div class="mb-3">
              <label class="form-label">Title</label>
              <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($auction['title']); ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="6" required><?php echo htmlspecialchars($auction['description']); ?></textarea>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Starting Price</label>
                <input type="number" name="starting_price" step="0.01" class="form-control" value="<?php echo htmlspecialchars($auction['starting_price']); ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Reserve Price</label>
                <input type="number" name="reserve_price" step="0.01" class="form-control" value="<?php echo htmlspecialchars($auction['reserve_price']); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="datetime-local" name="end_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($auction['end_date'])); ?>" required>
              </div>
            </div>
            <div class="mt-3 text-end">
              <button type="submit" class="btn btn-primary">Save Changes</button>
              <a href="my_auctions.php" class="btn btn-secondary">Cancel</a>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>


