-- Fix Blog Status Script
-- This script will help you publish any draft blogs

-- 1. First, check what statuses you have
SELECT status, COUNT(*) as count FROM blogs GROUP BY status;

-- 2. See all draft blogs
SELECT id, title, status, category, created_at FROM blogs WHERE status = 'draft' ORDER BY created_at DESC;

-- 3. OPTIONAL: Update all draft blogs to published (uncomment if you want to publish all)
-- UPDATE blogs SET status = 'published' WHERE status = 'draft';

-- 4. OPTIONAL: Update specific blog by ID (replace 123 with actual ID)
-- UPDATE blogs SET status = 'published' WHERE id = 123;

-- 5. Verify the changes
SELECT status, COUNT(*) as count FROM blogs GROUP BY status;