USE krishi_sohayota;

CREATE TABLE IF NOT EXISTS crop_calendar (
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

-- Common Bangladesh crops with typical planting/harvesting windows
INSERT INTO crop_calendar (crop_name, season, planting_start, planting_end, harvesting_start, harvesting_end, notes) VALUES
('Boro Rice', 'Rabi', 11, 1, 4, 6, 'Irrigated winter rice, highest yielding rice season'),
('Aman Rice', 'Kharif-2', 6, 8, 11, 12, 'Monsoon-fed rice, largest rice-growing season'),
('Aus Rice', 'Kharif-1', 3, 4, 7, 8, 'Pre-monsoon rice, shorter duration'),
('Wheat', 'Rabi', 11, 12, 3, 4, 'Needs cool, dry weather to mature'),
('Potato', 'Rabi', 10, 11, 1, 2, 'Best grown in cool winter months'),
('Jute', 'Kharif-1', 3, 5, 7, 9, 'Requires warm, humid climate'),
('Mustard', 'Rabi', 10, 11, 1, 2, 'Common winter oilseed crop'),
('Maize', 'Rabi', 10, 12, 2, 4, 'Also grown as a Kharif crop in some regions'),
('Lentil (Masur)', 'Rabi', 10, 11, 2, 3, 'Popular winter pulse crop'),
('Onion', 'Rabi', 10, 11, 2, 3, 'Needs dry weather during bulb formation'),
('Garlic', 'Rabi', 10, 11, 2, 3, 'Similar growing window to onion'),
('Tomato', 'Rabi', 9, 10, 12, 2, 'Winter vegetable, sensitive to frost'),
('Brinjal (Eggplant)', 'Kharif-1', 2, 3, 5, 7, 'Grown year-round in some areas'),
('Green Chili', 'Kharif-1', 2, 3, 5, 8, 'Sensitive to waterlogging'),
('Sugarcane', 'Kharif-1', 2, 4, 11, 3, 'Long-duration crop, 10-12 months');
