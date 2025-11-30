-- =============================================================================
-- Task Management Platform - MySQL Initialization Script
-- =============================================================================
-- This script runs automatically when the MySQL container is first created.
-- It sets up the database with proper character encoding and permissions.
-- =============================================================================

-- Ensure UTF-8 support
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Grant additional privileges to the application user
GRANT ALL PRIVILEGES ON task_management.* TO 'taskuser'@'%';
FLUSH PRIVILEGES;

-- Create indexes that might be helpful for performance
-- (These will be created by Laravel migrations, but this ensures they exist)
-- Note: Laravel migrations are the source of truth; this is a fallback

SELECT 'MySQL initialization complete.' AS status;
