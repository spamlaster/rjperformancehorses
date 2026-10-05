-- RJ Performance Horses - Initial Schema Migration
-- Phase 2A: Local Supabase development environment

-- Customers table
-- Represents account holders: parents, guardians, or adult riders booking for themselves
CREATE TABLE IF NOT EXISTS customers (
  id TEXT PRIMARY KEY,
  auth_user_id UUID UNIQUE, -- Will reference Supabase auth.users.id in Phase 2C
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP,
  CONSTRAINT email_not_empty CHECK (email <> '')
);

CREATE INDEX idx_customers_auth_user_id ON customers(auth_user_id);
CREATE INDEX idx_customers_email ON customers(email);
CREATE INDEX idx_customers_deleted_at ON customers(deleted_at);

-- Riders table
-- Persons who actually ride lessons
-- Can belong to a customer (for children/dependents) or be their own customer (adults)
CREATE TABLE IF NOT EXISTS riders (
  id TEXT PRIMARY KEY,
  customer_id TEXT REFERENCES customers(id) ON DELETE CASCADE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255),
  phone VARCHAR(20),
  date_of_birth DATE,
  address TEXT,
  emergency_contact_name VARCHAR(100),
  emergency_contact_phone VARCHAR(20),
  riding_experience VARCHAR(20) DEFAULT 'beginner',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP
);

CREATE INDEX idx_riders_customer_id ON riders(customer_id);
CREATE INDEX idx_riders_email ON riders(email);
CREATE INDEX idx_riders_deleted_at ON riders(deleted_at);

-- Horses table
-- Horses are scheduling resources
-- A horse may be owned by Rylie or boarded/shared (tracked in horse_owners)
CREATE TABLE IF NOT EXISTS horses (
  id TEXT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  breed VARCHAR(50),
  age INT,
  color VARCHAR(50),
  height VARCHAR(20),
  temperament TEXT,
  experience_level VARCHAR(50) DEFAULT 'beginner_friendly',
  active BOOLEAN DEFAULT TRUE,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP
);

CREATE INDEX idx_horses_name ON horses(name);
CREATE INDEX idx_horses_active ON horses(active);
CREATE INDEX idx_horses_deleted_at ON horses(deleted_at);

-- Horse owners table (for future use)
CREATE TABLE IF NOT EXISTS horse_owners (
  id TEXT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255),
  phone VARCHAR(20),
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Horse/owner relationships
CREATE TABLE IF NOT EXISTS horse_owner_relationships (
  id TEXT PRIMARY KEY,
  horse_id TEXT NOT NULL REFERENCES horses(id) ON DELETE CASCADE,
  owner_id TEXT NOT NULL REFERENCES horse_owners(id) ON DELETE CASCADE,
  ownership_type VARCHAR(50) DEFAULT 'owned',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(horse_id, owner_id)
);

-- Lesson types table
CREATE TABLE IF NOT EXISTS lesson_types (
  id TEXT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  duration_minutes INT NOT NULL,
  price DECIMAL(8, 2) NOT NULL,
  difficulty_level VARCHAR(50) DEFAULT 'beginner',
  max_group_size INT DEFAULT 1,
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_lesson_types_active ON lesson_types(active);

-- Horse/lesson type eligibility
CREATE TABLE IF NOT EXISTS horse_lesson_types (
  id TEXT PRIMARY KEY,
  horse_id TEXT NOT NULL REFERENCES horses(id) ON DELETE CASCADE,
  lesson_type_id TEXT NOT NULL REFERENCES lesson_types(id) ON DELETE CASCADE,
  eligible BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(horse_id, lesson_type_id)
);

CREATE INDEX idx_horse_lesson_types_horse_id ON horse_lesson_types(horse_id);
CREATE INDEX idx_horse_lesson_types_lesson_id ON horse_lesson_types(lesson_type_id);

-- Instructor (Rylie) recurring availability
-- Defines normal weekly availability windows
CREATE TABLE IF NOT EXISTS instructor_availability (
  id TEXT PRIMARY KEY,
  day_of_week INT NOT NULL CHECK (day_of_week >= 0 AND day_of_week <= 6),
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(day_of_week, start_time)
);

CREATE INDEX idx_instructor_availability_day ON instructor_availability(day_of_week);

-- Horse recurring availability
-- Defines normal weekly availability windows per horse
CREATE TABLE IF NOT EXISTS horse_recurring_availability (
  id TEXT PRIMARY KEY,
  horse_id TEXT NOT NULL REFERENCES horses(id) ON DELETE CASCADE,
  day_of_week INT NOT NULL CHECK (day_of_week >= 0 AND day_of_week <= 6),
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(horse_id, day_of_week, start_time)
);

CREATE INDEX idx_horse_recurring_avail_horse_id ON horse_recurring_availability(horse_id);

-- Blocked times (date-specific exceptions to normal availability)
-- Used for both instructor and horse blocks
CREATE TABLE IF NOT EXISTS blocked_times (
  id TEXT PRIMARY KEY,
  horse_id TEXT REFERENCES horses(id) ON DELETE CASCADE,
  date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  reason VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_blocked_times_horse_id ON blocked_times(horse_id);
CREATE INDEX idx_blocked_times_date ON blocked_times(date);

-- Recurring lessons (must be before bookings since bookings references it)
CREATE TABLE IF NOT EXISTS recurring_lessons (
  id TEXT PRIMARY KEY,
  rider_id TEXT NOT NULL REFERENCES riders(id),
  lesson_type_id TEXT NOT NULL REFERENCES lesson_types(id),
  horse_id TEXT REFERENCES horses(id),
  day_of_week INT NOT NULL,
  start_time TIME NOT NULL,
  duration_minutes INT NOT NULL,
  status VARCHAR(50) DEFAULT 'active',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP
);

CREATE INDEX idx_recurring_lessons_rider_id ON recurring_lessons(rider_id);
CREATE INDEX idx_recurring_lessons_horse_id ON recurring_lessons(horse_id);

-- Bookings table
-- Individual scheduled lesson occurrences
CREATE TABLE IF NOT EXISTS bookings (
  id TEXT PRIMARY KEY,
  customer_id TEXT NOT NULL REFERENCES customers(id),
  lesson_type_id TEXT NOT NULL REFERENCES lesson_types(id),
  horse_id TEXT NOT NULL REFERENCES horses(id),
  start_time TIMESTAMP NOT NULL,
  end_time TIMESTAMP NOT NULL,
  status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'completed', 'cancelled', 'no_show')),
  booking_type VARCHAR(50) DEFAULT 'regular' CHECK (booking_type IN ('regular', 'trial', 'makeup', 'recurring')),
  recurring_lesson_id TEXT REFERENCES recurring_lessons(id),
  version INT DEFAULT 1,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP
);

CREATE INDEX idx_bookings_customer_id ON bookings(customer_id);
CREATE INDEX idx_bookings_horse_id ON bookings(horse_id);
CREATE INDEX idx_bookings_start_time ON bookings(start_time);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_deleted_at ON bookings(deleted_at);

-- Booking riders (for group lessons)
CREATE TABLE IF NOT EXISTS booking_riders (
  id TEXT PRIMARY KEY,
  booking_id TEXT NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
  rider_id TEXT NOT NULL REFERENCES riders(id),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(booking_id, rider_id)
);

CREATE INDEX idx_booking_riders_booking_id ON booking_riders(booking_id);
CREATE INDEX idx_booking_riders_rider_id ON booking_riders(rider_id);

-- Waivers
-- Versioned legal documents
CREATE TABLE IF NOT EXISTS waivers (
  id TEXT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  version INT DEFAULT 1,
  content TEXT NOT NULL,
  effective_date DATE NOT NULL,
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(version)
);

-- Waiver acceptances
-- Immutable record of a rider/customer accepting a waiver at a specific time
CREATE TABLE IF NOT EXISTS waiver_acceptances (
  id TEXT PRIMARY KEY,
  waiver_id TEXT NOT NULL REFERENCES waivers(id),
  customer_id TEXT NOT NULL REFERENCES customers(id),
  rider_id TEXT NOT NULL REFERENCES riders(id),
  booking_id TEXT REFERENCES bookings(id),
  signer_name VARCHAR(255) NOT NULL,
  signer_email VARCHAR(255),
  relationship_to_rider VARCHAR(100),
  accepted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  signature_data TEXT,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT email_format CHECK (signer_email IS NULL OR signer_email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$')
);

CREATE INDEX idx_waiver_acceptances_rider_id ON waiver_acceptances(rider_id);
CREATE INDEX idx_waiver_acceptances_customer_id ON waiver_acceptances(customer_id);
CREATE INDEX idx_waiver_acceptances_booking_id ON waiver_acceptances(booking_id);

-- Makeup credits
CREATE TABLE IF NOT EXISTS makeup_credits (
  id TEXT PRIMARY KEY,
  rider_id TEXT NOT NULL REFERENCES riders(id),
  source_booking_id TEXT REFERENCES bookings(id),
  duration_minutes INT NOT NULL,
  issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expiration_date DATE,
  status VARCHAR(50) DEFAULT 'available',
  redeemed_booking_id TEXT REFERENCES bookings(id),
  redeemed_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_makeup_credits_rider_id ON makeup_credits(rider_id);
CREATE INDEX idx_makeup_credits_status ON makeup_credits(status);

-- Calendar connections (for future OAuth integration)
CREATE TABLE IF NOT EXISTS calendar_connections (
  id TEXT PRIMARY KEY,
  customer_id TEXT NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
  provider VARCHAR(50) NOT NULL,
  external_calendar_id VARCHAR(255) NOT NULL,
  active BOOLEAN DEFAULT FALSE,
  sync_enabled BOOLEAN DEFAULT FALSE,
  sync_direction VARCHAR(50) DEFAULT 'two_way',
  last_sync_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
