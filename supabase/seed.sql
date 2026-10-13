-- RJ Performance Horses - Development Seed Data
-- Phase 2A: Local development environment sample data
-- NOTE: All customer, rider, and horse data is fictional and for development only

-- Lesson Types (8 offerings from approved pricing)
INSERT INTO lesson_types (id, name, description, duration_minutes, price, difficulty_level, max_group_size, active) VALUES
  ('lesson-1', 'Walk & Trot', 'Perfect for first-time riders. Learn basics of saddle, posture, and horse handling.', 30, 45.00, 'beginner', 1, TRUE),
  ('lesson-2', 'Intermediate Riding', 'Build on fundamentals with canter and basic jumping.', 45, 55.00, 'intermediate', 1, TRUE),
  ('lesson-3', 'Advanced Horsemanship', 'Advanced techniques and competition preparation.', 60, 65.00, 'advanced', 1, TRUE),
  ('lesson-4', 'Sibling/Joined Lesson', 'Two riders learning together on separate horses.', 60, 100.00, 'beginner', 2, TRUE),
  ('lesson-5', 'Extended Group Lesson', 'Extended group lesson with more time for instruction and practice.', 90, 120.00, 'beginner', 2, TRUE),
  ('lesson-6', 'Barrel Racing', 'Specialized training for barrel racing techniques.', 60, 75.00, 'intermediate', 1, TRUE),
  ('lesson-7', 'Pole Bending', 'Learn precise weaving patterns and speed control.', 60, 75.00, 'intermediate', 1, TRUE),
  ('lesson-8', 'Weekly Barrel Training', 'Two 60-minute barrel racing lessons per week for committed riders.', 120, 130.00, 'intermediate', 1, TRUE);

-- Sibling/Joined: two riders on separate horses (other lessons keep the 1/1 defaults)
UPDATE lesson_types SET required_rider_count = 2, required_horse_count = 2 WHERE id = 'lesson-4';

-- Customers (development-only fictional accounts)
-- Example 1: Parent with two child riders
INSERT INTO customers (id, first_name, last_name, email, phone) VALUES
  ('customer-1', 'Rebecca', 'Jensen', 'rebecca.jensen.dev@example.com', '555-111-0001');

-- Example 2: Adult customer who is also a rider (self-booking)
INSERT INTO customers (id, first_name, last_name, email, phone) VALUES
  ('customer-2', 'Michael', 'Brown', 'michael.brown.dev@example.com', '555-222-0002');

-- Example 3: Single rider parent
INSERT INTO customers (id, first_name, last_name, email, phone) VALUES
  ('customer-3', 'Sarah', 'Johnson', 'sarah.johnson.dev@example.com', '555-333-0003');

-- Example 4: Group lesson participant
INSERT INTO customers (id, first_name, last_name, email, phone) VALUES
  ('customer-4', 'Lisa', 'Smith', 'lisa.smith.dev@example.com', '555-444-0004');

-- Riders
-- Children (belong to customer-1)
INSERT INTO riders (id, customer_id, first_name, last_name, email, date_of_birth, riding_experience) VALUES
  ('rider-1', 'customer-1', 'Emma', 'Jensen', 'emma.jensen.dev@example.com', '2010-03-22', 'beginner'),
  ('rider-2', 'customer-1', 'Hailey', 'Jensen', 'hailey.jensen.dev@example.com', '2012-08-10', 'beginner');

-- Adult customer who is also a rider (customer-2)
INSERT INTO riders (id, customer_id, first_name, last_name, email, date_of_birth, riding_experience) VALUES
  ('rider-3', 'customer-2', 'Michael', 'Brown', 'michael.brown.dev@example.com', '1988-11-05', 'advanced');

-- Single rider (customer-3)
INSERT INTO riders (id, customer_id, first_name, last_name, email, date_of_birth, riding_experience) VALUES
  ('rider-4', 'customer-3', 'Sarah', 'Johnson', 'sarah.johnson.dev@example.com', '1995-06-15', 'intermediate');

-- Group lesson participant (customer-4)
INSERT INTO riders (id, customer_id, first_name, last_name, email, date_of_birth, riding_experience) VALUES
  ('rider-5', 'customer-4', 'Ava', 'Smith', 'ava.smith.dev@example.com', '2008-01-18', 'intermediate');

-- Horses (fictional development names with different lesson eligibility)
-- Beginner-friendly horses
INSERT INTO horses (id, name, breed, age, color, height, temperament, experience_level, active) VALUES
  ('horse-1', 'DevSunset', 'Quarter Horse', 8, 'Bay', '15.2hh', 'Gentle and steady, suitable for beginners in development', 'beginner_friendly', TRUE),
  ('horse-2', 'DevRanger', 'Quarter Horse', 12, 'Palomino', '15.0hh', 'Calm and experienced, perfect for lesson use in development', 'beginner_friendly', TRUE);

-- Intermediate horses
INSERT INTO horses (id, name, breed, age, color, height, temperament, experience_level, active) VALUES
  ('horse-3', 'DevDakota', 'Paint', 6, 'Tobiano', '14.3hh', 'Energetic and responsive for intermediate development training', 'intermediate', TRUE),
  ('horse-4', 'DevMoonlight', 'Appaloosa', 7, 'Spotted', '14.2hh', 'Sweet and spirited for intermediate development work', 'intermediate', TRUE);

-- Advanced horses (for barrel/pole)
INSERT INTO horses (id, name, breed, age, color, height, temperament, experience_level, active) VALUES
  ('horse-5', 'DevThunder', 'Quarter Horse', 10, 'Chestnut', '15.1hh', 'Athletic and focused for advanced development training', 'advanced', TRUE),
  ('horse-6', 'DevSpirit', 'Mustang', 5, 'Bay', '15.0hh', 'Quick learner, barrel racing specialist for development', 'advanced', TRUE);

-- Horse Lesson Eligibility
-- DevSunset: private lessons only (beginner-friendly)
INSERT INTO horse_lesson_types (id, horse_id, lesson_type_id, eligible) VALUES
  ('hlt-1', 'horse-1', 'lesson-1', TRUE),  -- Walk & Trot
  ('hlt-2', 'horse-1', 'lesson-2', TRUE),  -- Intermediate Riding
  ('hlt-3', 'horse-1', 'lesson-4', TRUE),  -- Sibling/Joined
  ('hlt-4', 'horse-1', 'lesson-5', TRUE);  -- Extended Group

-- DevRanger: all lesson types (versatile beginner horse)
INSERT INTO horse_lesson_types (id, horse_id, lesson_type_id, eligible) VALUES
  ('hlt-5', 'horse-2', 'lesson-1', TRUE),
  ('hlt-6', 'horse-2', 'lesson-2', TRUE),
  ('hlt-7', 'horse-2', 'lesson-3', TRUE),
  ('hlt-8', 'horse-2', 'lesson-4', TRUE),
  ('hlt-9', 'horse-2', 'lesson-5', TRUE);

-- DevDakota: intermediate private and barrel/pole
INSERT INTO horse_lesson_types (id, horse_id, lesson_type_id, eligible) VALUES
  ('hlt-10', 'horse-3', 'lesson-2', TRUE),  -- Intermediate Riding
  ('hlt-11', 'horse-3', 'lesson-6', TRUE),  -- Barrel Racing
  ('hlt-12', 'horse-3', 'lesson-7', TRUE);  -- Pole Bending

-- DevMoonlight: intermediate private lessons only
INSERT INTO horse_lesson_types (id, horse_id, lesson_type_id, eligible) VALUES
  ('hlt-13', 'horse-4', 'lesson-2', TRUE),
  ('hlt-14', 'horse-4', 'lesson-4', TRUE);

-- DevThunder: advanced and barrel/pole
INSERT INTO horse_lesson_types (id, horse_id, lesson_type_id, eligible) VALUES
  ('hlt-15', 'horse-5', 'lesson-3', TRUE),  -- Advanced Horsemanship
  ('hlt-16', 'horse-5', 'lesson-6', TRUE),  -- Barrel Racing
  ('hlt-17', 'horse-5', 'lesson-7', TRUE),  -- Pole Bending
  ('hlt-18', 'horse-5', 'lesson-8', TRUE);  -- Weekly Barrel Training

-- DevSpirit: barrel/pole specialist
INSERT INTO horse_lesson_types (id, horse_id, lesson_type_id, eligible) VALUES
  ('hlt-19', 'horse-6', 'lesson-6', TRUE),  -- Barrel Racing
  ('hlt-20', 'horse-6', 'lesson-7', TRUE),  -- Pole Bending
  ('hlt-21', 'horse-6', 'lesson-8', TRUE);  -- Weekly Barrel Training

-- Instructor (Rylie) Recurring Availability
-- NOTE: This is sample development data and does NOT represent actual Rylie schedule
-- Monday-Friday: 10:00-12:00 (morning) and 14:00-18:00 (afternoon)
INSERT INTO instructor_availability (id, day_of_week, start_time, end_time, active) VALUES
  ('ia-1', 1, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Monday morning
  ('ia-2', 1, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Monday afternoon
  ('ia-3', 2, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Tuesday morning
  ('ia-4', 2, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Tuesday afternoon
  ('ia-5', 3, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Wednesday morning
  ('ia-6', 3, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Wednesday afternoon
  ('ia-7', 4, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Thursday morning
  ('ia-8', 4, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Thursday afternoon
  ('ia-9', 5, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Friday morning
  ('ia-10', 5, '14:00'::TIME, '18:00'::TIME, TRUE); -- Friday afternoon

-- Horse Recurring Availability
-- DevSunset: Mon-Fri 10:00-12:00 (morning) and 14:00-18:00 (afternoon)
INSERT INTO horse_recurring_availability (id, horse_id, day_of_week, start_time, end_time, active) VALUES
  ('hra-1', 'horse-1', 1, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Monday morning
  ('hra-2', 'horse-1', 1, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Monday afternoon
  ('hra-3', 'horse-1', 2, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Tuesday morning
  ('hra-4', 'horse-1', 2, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Tuesday afternoon
  ('hra-5', 'horse-1', 3, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Wednesday morning
  ('hra-6', 'horse-1', 3, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Wednesday afternoon
  ('hra-7', 'horse-1', 4, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Thursday morning
  ('hra-8', 'horse-1', 4, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Thursday afternoon
  ('hra-9', 'horse-1', 5, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Friday morning
  ('hra-10', 'horse-1', 5, '14:00'::TIME, '18:00'::TIME, TRUE);  -- Friday afternoon

-- DevRanger: Mon-Fri 10:00-12:00 (morning) and 14:00-18:00 (afternoon)
INSERT INTO horse_recurring_availability (id, horse_id, day_of_week, start_time, end_time, active) VALUES
  ('hra-11', 'horse-2', 1, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Monday morning
  ('hra-12', 'horse-2', 1, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Monday afternoon
  ('hra-13', 'horse-2', 2, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Tuesday morning
  ('hra-14', 'horse-2', 2, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Tuesday afternoon
  ('hra-15', 'horse-2', 3, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Wednesday morning
  ('hra-16', 'horse-2', 3, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Wednesday afternoon
  ('hra-17', 'horse-2', 4, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Thursday morning
  ('hra-18', 'horse-2', 4, '14:00'::TIME, '18:00'::TIME, TRUE),  -- Thursday afternoon
  ('hra-19', 'horse-2', 5, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Friday morning
  ('hra-20', 'horse-2', 5, '14:00'::TIME, '18:00'::TIME, TRUE);  -- Friday afternoon

-- DevDakota: Tue/Wed/Thu 10:00-12:00 and 14:00-17:00
INSERT INTO horse_recurring_availability (id, horse_id, day_of_week, start_time, end_time, active) VALUES
  ('hra-21', 'horse-3', 2, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Tuesday morning
  ('hra-22', 'horse-3', 2, '14:00'::TIME, '17:00'::TIME, TRUE),  -- Tuesday afternoon
  ('hra-23', 'horse-3', 3, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Wednesday morning
  ('hra-24', 'horse-3', 3, '14:00'::TIME, '17:00'::TIME, TRUE),  -- Wednesday afternoon
  ('hra-25', 'horse-3', 4, '10:00'::TIME, '12:00'::TIME, TRUE),  -- Thursday morning
  ('hra-26', 'horse-3', 4, '14:00'::TIME, '17:00'::TIME, TRUE);  -- Thursday afternoon

-- DevMoonlight: Mon/Wed 10:00-14:00
INSERT INTO horse_recurring_availability (id, horse_id, day_of_week, start_time, end_time, active) VALUES
  ('hra-27', 'horse-4', 1, '10:00'::TIME, '14:00'::TIME, TRUE),  -- Monday
  ('hra-28', 'horse-4', 3, '10:00'::TIME, '14:00'::TIME, TRUE);  -- Wednesday

-- DevThunder: Tue/Thu/Fri 15:00-18:00
INSERT INTO horse_recurring_availability (id, horse_id, day_of_week, start_time, end_time, active) VALUES
  ('hra-29', 'horse-5', 2, '15:00'::TIME, '18:00'::TIME, TRUE),  -- Tuesday
  ('hra-30', 'horse-5', 4, '15:00'::TIME, '18:00'::TIME, TRUE),  -- Thursday
  ('hra-31', 'horse-5', 5, '15:00'::TIME, '18:00'::TIME, TRUE);  -- Friday

-- DevSpirit: Mon/Wed/Fri 10:00-14:00
INSERT INTO horse_recurring_availability (id, horse_id, day_of_week, start_time, end_time, active) VALUES
  ('hra-32', 'horse-6', 1, '10:00'::TIME, '14:00'::TIME, TRUE),  -- Monday
  ('hra-33', 'horse-6', 3, '10:00'::TIME, '14:00'::TIME, TRUE),  -- Wednesday
  ('hra-34', 'horse-6', 5, '10:00'::TIME, '14:00'::TIME, TRUE);  -- Friday

-- Blocked Times (examples of date-specific exceptions)
-- NOTE: Using future dates for development
-- DevRanger blocked for vet appointment next week
INSERT INTO blocked_times (id, horse_id, date, start_time, end_time, reason) VALUES
  ('bt-1', 'horse-2', CURRENT_DATE + INTERVAL '7 days', '14:00'::TIME, '16:00'::TIME, 'Vet appointment');

-- DevThunder blocked for competition week
INSERT INTO blocked_times (id, horse_id, date, start_time, end_time, reason) VALUES
  ('bt-2', 'horse-5', CURRENT_DATE + INTERVAL '14 days', '00:00'::TIME, '23:59'::TIME, 'Competition event');

-- Waivers (Phase 1 placeholder)
INSERT INTO waivers (id, title, version, content, effective_date, active) VALUES
  ('waiver-1', 'Riding Waiver and Release of Liability', 1,
   'RELEASE OF LIABILITY

I acknowledge that horseback riding involves inherent risks including but not limited to falls, injuries, and property damage. I accept full responsibility for these risks.

I confirm that I am in good physical health and able to participate in this activity. I have disclosed any medical conditions that may affect my ability to ride.

I release and hold harmless RJ Performance Horses, its owners, instructors, employees, and agents from any liability for injuries or damages sustained during lessons or activities.

I understand that helmets are required and agree to follow all safety instructions.',
   CURRENT_DATE, TRUE);
