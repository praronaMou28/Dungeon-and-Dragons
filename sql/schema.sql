CREATE DATABASE IF NOT EXISTS krishi_sohayota;
USE krishi_sohayota;

-- ---------- USERS ----------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('farmer','buyer','consumer','admin') NOT NULL,
  district VARCHAR(50),
  upazila VARCHAR(50),
  primary_crop VARCHAR(50) DEFAULT NULL,
  land_size VARCHAR(30) DEFAULT NULL,
  business_name VARCHAR(100) DEFAULT NULL,
  business_type VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- BUYER VERIFICATION ----------
CREATE TABLE verified_buyers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  trade_license VARCHAR(100) NOT NULL,
  nid VARCHAR(30) NOT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- FARMER CROP LISTINGS ----------
CREATE TABLE crop_listings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  crop_name VARCHAR(100) NOT NULL,
  quantity_kg DECIMAL(10,2) NOT NULL,
  price_per_kg DECIMAL(10,2) NOT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  status ENUM('available','sold') DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- SHOPPING CART (Buyer, crop purchases from farmers) ----------
CREATE TABLE crop_cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT NOT NULL,
  listing_id INT NOT NULL,
  quantity_kg DECIMAL(10,2) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_crop_cart_item (buyer_id, listing_id),
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES crop_listings(id) ON DELETE CASCADE
);

-- ---------- ORDERS (Buyer requests to buy from Farmer) ----------
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  listing_id INT NOT NULL,
  buyer_id INT NOT NULL,
  group_ref VARCHAR(40) DEFAULT NULL,
  quantity_kg DECIMAL(10,2) NOT NULL,
  delivery_address VARCHAR(255) DEFAULT NULL,
  delivery_phone VARCHAR(20) DEFAULT NULL,
  delivery_note VARCHAR(255) DEFAULT NULL,
  status ENUM('requested','accepted','rejected','out_for_delivery','completed','cancelled') DEFAULT 'requested',
  payment_method ENUM('bkash','card','cod') DEFAULT NULL,
  payment_status ENUM('unpaid','paid','cod') DEFAULT 'unpaid',
  paid_amount DECIMAL(10,2) DEFAULT NULL,
  transaction_ref VARCHAR(40) DEFAULT NULL,
  paid_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES crop_listings(id) ON DELETE CASCADE,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- STOREFRONT (Buyer sells to Consumer) ----------
CREATE TABLE storefront_listings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT NOT NULL,
  product_name VARCHAR(100) NOT NULL,
  category VARCHAR(30) DEFAULT 'Other',
  price_per_unit DECIMAL(10,2) NOT NULL,
  unit_label VARCHAR(20) DEFAULT 'unit',
  stock_qty DECIMAL(10,2) NOT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- SHOPPING CART (Consumer) ----------
CREATE TABLE cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  consumer_id INT NOT NULL,
  storefront_id INT NOT NULL,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_cart_item (consumer_id, storefront_id),
  FOREIGN KEY (consumer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (storefront_id) REFERENCES storefront_listings(id) ON DELETE CASCADE
);

-- ---------- CONSUMER ORDERS ----------
CREATE TABLE consumer_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  storefront_id INT NOT NULL,
  consumer_id INT NOT NULL,
  group_ref VARCHAR(40) DEFAULT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  delivery_address VARCHAR(255) DEFAULT NULL,
  delivery_phone VARCHAR(20) DEFAULT NULL,
  delivery_note VARCHAR(255) DEFAULT NULL,
  status ENUM('placed','confirmed','out_for_delivery','completed','cancelled') DEFAULT 'placed',
  payment_method ENUM('bkash','card','cod') DEFAULT NULL,
  payment_status ENUM('unpaid','paid','cod') DEFAULT 'unpaid',
  paid_amount DECIMAL(10,2) DEFAULT NULL,
  transaction_ref VARCHAR(40) DEFAULT NULL,
  paid_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (storefront_id) REFERENCES storefront_listings(id) ON DELETE CASCADE,
  FOREIGN KEY (consumer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- EQUIPMENT SHARING ----------
CREATE TABLE equipment_listings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NOT NULL,
  equipment_type VARCHAR(100) NOT NULL,
  availability ENUM('available','rented') DEFAULT 'available',
  contact_note VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- SEASONAL CROP CALENDAR ----------
CREATE TABLE crop_calendar (
  id INT AUTO_INCREMENT PRIMARY KEY,
  crop_name VARCHAR(100) NOT NULL,
  season VARCHAR(50) NOT NULL,
  planting_start TINYINT NOT NULL,
  planting_end TINYINT NOT NULL,
  harvesting_start TINYINT NOT NULL,
  harvesting_end TINYINT NOT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- NOTIFICATIONS ----------
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message VARCHAR(255) NOT NULL,
  link VARCHAR(100) DEFAULT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- CONSUMER FAVORITES (wishlist on the storefront) ----------
CREATE TABLE consumer_favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  consumer_id INT NOT NULL,
  storefront_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_favorite (consumer_id, storefront_id),
  FOREIGN KEY (consumer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (storefront_id) REFERENCES storefront_listings(id) ON DELETE CASCADE
);

-- ---------- REVIEWS & RATINGS ----------
CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reviewer_id INT NOT NULL,
  target_id INT NOT NULL,
  order_type ENUM('crop','storefront') NOT NULL,
  order_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_review (reviewer_id, order_type, order_id),
  FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (target_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- COMPLAINTS ----------
CREATE TABLE complaints (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subject VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('open','in_progress','resolved') DEFAULT 'open',
  admin_response TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
