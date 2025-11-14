-- Additional system-level SQL for AuctionHUB

-- Add missing indexes that improve bid lookups
CREATE INDEX IF NOT EXISTS idx_bids_auction_amount ON bids (auction_id, amount);
CREATE INDEX IF NOT EXISTS idx_bids_auction_created ON bids (auction_id, created_at);

-- In case older schema used bid_amount/bid_time, create compatible indexes if they exist
-- These will no-op if columns are not present
-- Note: IF NOT EXISTS on indexes varies across MySQL/MariaDB; ignore errors.
CREATE INDEX idx_bids_auction_bid_amount ON bids (auction_id, bid_amount);
CREATE INDEX idx_bids_auction_bid_time ON bids (auction_id, bid_time);


