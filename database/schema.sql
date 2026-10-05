-- RJ Performance Horses - PostgreSQL Schema
-- Production-ready schema for Supabase integration
-- Cleaned up pre-Phase 2 with proper relationships

-- Customers table
-- Represents account holders: parents, guardians, or adult riders booking for themselves
CREATE TABLE IF NOT EXISTS customers (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  auth_user_id UUID UNIQUE, -- Will reference Supabase auth.users.id in Phase 2
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
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_id UUID REFERENCES customers(id) ON DELETE CASCADE, -- Nullable for migration/guests
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

-- Horses table
-- Horses are scheduling resources
-- A horse may be owned by Rylie or boarded/shared (tracked in horse_owners)
CREATE TABLE IF NOT EXISTS horses (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name VARCHAR(100) NOT NULL UNIQUE,
  breed VARCHAR(50),
  age INT,
  color VARCHAR(50),
  height VARCHAR(20),
  temperament TEXT,
  experience_level VARCHAR(50) DEFAULT 'beginner_friendly',
  active BOOLEAN DEFAULT TRUE, -- Renamed from 'available' for clarity
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
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255),
  phone VARCHAR(20),
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Horse/owner relationships
CREATE TABLE IF NOT EXISTS horse_owner_relationships (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  horse_id UUID NOT NULL REFERENCES horses(id) ON DELETE CASCADE,
  owner_id UUID NOT NULL REFERENCES horse_owners(id) ON DELETE CASCADE,
  ownership_type VARCHAR(50) DEFAULT 'owned',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(horse_id, owner_id)
);

-- Lesson types table
CREATE TABLE IF NOT EXISTS lesson_types (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
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

-- Horse/lesson type eligibility
CREATE TABLE IF NOT EXISTS horse_lesson_types (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  horse_id UUID NOT NULL REFERENCES horses(id) ON DELETE CASCADE,
  lesson_type_id UUID NOT NULL REFERENCES lesson_types(id) ON DELETE CASCADE,
  eligible BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(horse_id, lesson_type_id)
);

-- Instructor (Rylie) recurring availability
-- Defines normal weekly availability windows
-- Example: Monday 14:00-18:00, Tuesday 14:00-18:00
CREATE TABLE IF NOT EXISTS instructor_availability (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  day_of_week INT NOT NULL CHECK (day_of_week >= 0 AND day_of_week <= 6), -- 0=Sunday, 1=Monday, ...6=Saturday
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(day_of_week, start_time)
);

-- Horse recurring availability
-- Defines normal weekly availability windows per horse
-- Example: Ranger Monday 14:00-18:00, Tuesday 14:00-17:00
CREATE TABLE IF NOT EXISTS horse_recurring_availability (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  horse_id UUID NOT NULL REFERENCES horses(id) ON DELETE CASCADE,
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
-- Example: Rylie blocked Oct 20 14:00-16:00, or Ranger blocked all day Oct 21
CREATE TABLE IF NOT EXISTS blocked_times (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  horse_id UUID REFERENCES horses(id) ON DELETE CASCADE, -- NULL for instructor blocks
  date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  reason VARCHAR(255), -- "Vet appointment", "Farrier", "Competition", etc.
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- Allow NULL horse_id for instructor blocks
  CHECK (horse_id IS NOT NULL OR date IS NOT NULL) -- Ensure date is set
);

CREATE INDEX idx_blocked_times_horse_id ON blocked_times(horse_id);
CREATE INDEX idx_blocked_times_date ON blocked_times(date);

-- Bookings table
-- Individual scheduled lesson occurrences
-- Multiple riders can share one booking (via booking_riders) for group lessons
CREATE TABLE IF NOT EXISTS bookings (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_id UUID NOT NULL REFERENCES customers(id),
  lesson_type_id UUID NOT NULL REFERENCES lesson_types(id),
  horse_id UUID NOT NULL REFERENCES horses(id),
  start_time TIMESTAMP NOT NULL,
  end_time TIMESTAMP NOT NULL,
  status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'completed', 'cancelled', 'no_show')),
  booking_type VARCHAR(50) DEFAULT 'regular' CHECK (booking_type IN ('regular', 'trial', 'makeup', 'recurring')),
  recurring_lesson_id UUID REFERENCES recurring_lessons(id),
  version INT DEFAULT 1,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP
);

CREATE INDEX idx_bookings_customer_id ON bookings(customer_id);
CREATE INDEX idx_bookings_rider_id ON bookings(horse_id); -- For checking horse conflicts
CREATE INDEX idx_bookings_start_time ON bookings(start_time);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_deleted_at ON bookings(deleted_at);

-- Booking riders (for group lessons)
CREATE TABLE IF NOT EXISTS booking_riders (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_id UUID NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
  rider_id UUID NOT NULL REFERENCES riders(id),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(booking_id, rider_id)
);

-- Recurring lessons
CREATE TABLE IF NOT EXISTS recurring_lessons (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  rider_id UUID NOT NULL REFERENCES riders(id),
  lesson_type_id UUID NOT NULL REFERENCES lesson_types(id),
  horse_id UUID REFERENCES horses(id),
  day_of_week INT NOT NULL,
  start_time TIME NOT NULL,
  duration_minutes INT NOT NULL,
  status VARCHAR(50) DEFAULT 'active',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP
);

-- Waivers
-- Versioned legal documents
CREATE TABLE IF NOT EXISTS waivers (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
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
-- Preserves signature and document version reference
CREATE TABLE IF NOT EXISTS waiver_acceptances (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  waiver_id UUID NOT NULL REFERENCES waivers(id),
  customer_id UUID NOT NULL REFERENCES customers(id),
  rider_id UUID NOT NULL REFERENCES riders(id),
  booking_id UUID REFERENCES bookings(id), -- Link to booking that required this waiver
  signer_name VARCHAR(255) NOT NULL,
  signer_email VARCHAR(255),
  relationship_to_rider VARCHAR(100), -- "parent", "guardian", "self", etc.
  accepted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  signature_data TEXT, -- Base64 encoded image or reference to stored signature
  -- Phase 2+: pdf_path for stored signed PDF
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- No deleted_at: waivers are immutable audit records
  CONSTRAINT email_format CHECK (signer_email IS NULL OR signer_email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$')
);

CREATE INDEX idx_waiver_acceptances_rider_id ON waiver_acceptances(rider_id);
CREATE INDEX idx_waiver_acceptances_customer_id ON waiver_acceptances(customer_id);
CREATE INDEX idx_waiver_acceptances_booking_id ON waiver_acceptances(booking_id);

-- Makeup credits
CREATE TABLE IF NOT EXISTS makeup_credits (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  rider_id UUID NOT NULL REFERENCES riders(id),
  source_booking_id UUID REFERENCES bookings(id),
  duration_minutes INT NOT NULL,
  issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expiration_date DATE,
  status VARCHAR(50) DEFAULT 'available',
  redeemed_booking_id UUID REFERENCES bookings(id),
  redeemed_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Calendar connections (for future OAuth integration)
CREATE TABLE IF NOT EXISTS calendar_connections (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider VARCHAR(50) NOT NULL,
  external_calendar_id VARCHAR(255) NOT NULL,
  active BOOLEAN DEFAULT FALSE,
  sync_enabled BOOLEAN DEFAULT FALSE,
  sync_direction VARCHAR(50) DEFAULT 'two_way',
  last_sync_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- NOTE: admin_users table removed
-- Admin authentication will use Supabase Auth in Phase 2
-- Do NOT store passwords or authentication secrets in application tables
-- Admin identity will be determined by Supabase auth.users + RLS policies

-- Additional indexes for frequently queried relationships
CREATE INDEX idx_riders_customer_id ON riders(customer_id);
CREATE INDEX idx_riders_email ON riders(email);
CREATE INDEX idx_riders_deleted_at ON riders(deleted_at);

CREATE INDEX idx_lesson_types_active ON lesson_types(active);

CREATE INDEX idx_horse_lesson_types_horse_id ON horse_lesson_types(horse_id);
CREATE INDEX idx_horse_lesson_types_lesson_id ON horse_lesson_types(lesson_type_id);

CREATE INDEX idx_instructor_availability_day ON instructor_availability(day_of_week);

CREATE INDEX idx_booking_riders_booking_id ON booking_riders(booking_id);
CREATE INDEX idx_booking_riders_rider_id ON booking_riders(rider_id);

CREATE INDEX idx_recurring_lessons_rider_id ON recurring_lessons(rider_id);
CREATE INDEX idx_recurring_lessons_horse_id ON recurring_lessons(horse_id);

CREATE INDEX idx_makeup_credits_rider_id ON makeup_credits(rider_id);
CREATE INDEX idx_makeup_credits_status ON makeup_credits(status);

CREATE INDEX idx_horse_owners_owner_id ON horse_owners(id);
CREATE INDEX idx_horse_owner_relationships_owner_id ON horse_owner_relationships(owner_id);
