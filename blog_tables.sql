-- Blog system database tables
-- Run this SQL to create the necessary tables for the blog system

-- Create newsletter_subscribers table if it doesn't exist
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','unsubscribed') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create uploads directory (you need to create this folder manually)
-- Create folder: uploads/ in your root directory with write permissions

-- Note: Your existing tables (blogs, blog_categories, blog_comments, likes) 
-- should work with the current code structure.
-- 
-- Make sure your existing tables have these columns:
-- 
-- blogs table should have:
-- - id, title, slug, content, image, author, seo_title, seo_description, 
--   tags, created_at, updated_at, status, category
--
-- blog_categories table should have:
-- - id, name
--
-- blog_comments table should have:
-- - id, blog_id, name, email, comment, parent_id, created_at, status
--
-- likes table should have:
-- - id, blog_id, user_ip, created_at