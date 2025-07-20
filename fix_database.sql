-- Fix database for blog system
-- Run this SQL to add missing tables and fix existing data

-- Create newsletter_subscribers table
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','unsubscribed') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: Fix image paths for blogs that don't have uploads/ prefix
-- Uncomment and run only if needed:

-- UPDATE blogs SET image = CONCAT('uploads/', image) 
-- WHERE image IS NOT NULL 
-- AND image != '' 
-- AND image NOT LIKE 'uploads/%'
-- AND EXISTS (SELECT 1 FROM information_schema.FILES WHERE FILE_NAME = CONCAT('uploads/', image));

-- You can run this to see current image paths:
-- SELECT id, title, image FROM blogs WHERE image IS NOT NULL AND image != '';