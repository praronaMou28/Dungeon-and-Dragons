USE krishi_sohayota;

ALTER TABLE crop_listings
  ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER price_per_kg;

ALTER TABLE storefront_listings
  ADD COLUMN category VARCHAR(30) DEFAULT 'Other' AFTER product_name,
  ADD COLUMN unit_label VARCHAR(20) DEFAULT 'unit' AFTER price_per_unit,
  ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER stock_qty;

CREATE TABLE IF NOT EXISTS consumer_favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  consumer_id INT NOT NULL,
  storefront_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_favorite (consumer_id, storefront_id),
  FOREIGN KEY (consumer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (storefront_id) REFERENCES storefront_listings(id) ON DELETE CASCADE
);
