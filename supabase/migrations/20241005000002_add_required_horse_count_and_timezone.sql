-- Phase 2D Part 1: Add required_horse_count and timezone configuration

-- Add required_horse_count to lesson_types
-- Specifies how many horses are needed for this lesson type
-- Private = 1 horse, Joined/sibling = 2+ horses
ALTER TABLE lesson_types
ADD COLUMN required_horse_count INTEGER NOT NULL DEFAULT 1
ADD CONSTRAINT required_horse_count_positive CHECK (required_horse_count >= 1);

-- Update existing lesson types with appropriate required horse counts
-- Private lessons require 1 horse (most lessons)
UPDATE lesson_types
SET required_horse_count = 1
WHERE active = TRUE
  AND name IN (
    'Private Lesson',
    'Barrel Racing Private',
    'Pole Bending Private',
    'Trail Riding Private'
  );

-- Joined/sibling lessons require 2 horses
UPDATE lesson_types
SET required_horse_count = 2
WHERE active = TRUE
  AND name IN (
    'Joined Barrel Racing',
    'Joined Pole Bending',
    'Beginner Group Lesson',
    'Intermediate Group Lesson',
    'Advanced Group Lesson'
  );

-- Group lessons with more riders may require more horses
UPDATE lesson_types
SET required_horse_count = CASE
  WHEN max_group_size > 2 THEN 2
  ELSE 1
END
WHERE active = TRUE
  AND required_horse_count IS NULL;

-- Create configuration table for business settings
CREATE TABLE IF NOT EXISTS business_config (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  config_key VARCHAR(100) NOT NULL UNIQUE,
  config_value TEXT NOT NULL,
  description TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(100)
);

-- Insert business timezone configuration
-- Default: America/Denver (Rylie's location)
-- This can be updated via admin interface in the future
INSERT INTO business_config (config_key, config_value, description, updated_by)
VALUES (
  'business_timezone',
  'America/Denver',
  'Business timezone for all scheduling calculations (IANA format)',
  'system_init'
)
ON CONFLICT (config_key) DO UPDATE SET config_value = 'America/Denver';

-- Grant appropriate permissions
GRANT SELECT ON business_config TO public;
GRANT SELECT, INSERT, UPDATE ON business_config TO authenticated;

-- Create index for fast config lookups
CREATE INDEX IF NOT EXISTS idx_business_config_key ON business_config(config_key);

-- Document the timezone:
-- All times in instructor_availability, horse_recurring_availability, blocked_times
-- are stored as TIME (not TIMESTAMP), so they represent times in the business timezone.
-- When calculating availability for a DATE, that date is interpreted in business_timezone.
--
-- Example:
-- Date: 2026-10-15 (America/Denver)
-- Instructor available: 09:00 - 17:00 (in business timezone)
-- Public user sees: slots available 09:00-17:00 Denver time
--
-- Frontend sends dates as YYYY-MM-DD in business timezone context
-- RPC interprets them in business_timezone for lookup
