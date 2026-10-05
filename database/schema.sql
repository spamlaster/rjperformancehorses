-- RJ Performance Horses - Initial PostgreSQL Schema
-- This schema is designed for Phase 2+ backend implementation
-- Phase 1 uses in-memory/localStorage data

-- Riders table
CREATE TABLE IF NOT EXISTS riders (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
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
CREATE TABLE IF NOT EXISTS horses (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name VARCHAR(100) NOT NULL UNIQUE,
  breed VARCHAR(50),
  age INT,
  color VARCHAR(50),
  height VARCHAR(20),
  temperament TEXT,
  experience_level VARCHAR(50) DEFAULT 'beginner_friendly',
  available BOOLEAN DEFAULT TRUE,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP
);

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

-- Time slots for availability
CREATE TABLE IF NOT EXISTS time_slots (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  horse_id UUID NOT NULL REFERENCES horses(id) ON DELETE CASCADE,
  date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  status VARCHAR(50) DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(horse_id, date, start_time)
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  lesson_type_id UUID NOT NULL REFERENCES lesson_types(id),
  rider_id UUID NOT NULL REFERENCES riders(id),
  horse_id UUID NOT NULL REFERENCES horses(id),
  start_time TIMESTAMP NOT NULL,
  end_time TIMESTAMP NOT NULL,
  status VARCHAR(50) DEFAULT 'pending',
  booking_type VARCHAR(50) DEFAULT 'regular',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP
);

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
CREATE TABLE IF NOT EXISTS waivers (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  title VARCHAR(255) NOT NULL,
  version INT DEFAULT 1,
  content TEXT NOT NULL,
  effective_date DATE NOT NULL,
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Waiver acceptances
CREATE TABLE IF NOT EXISTS waiver_acceptances (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  waiver_id UUID NOT NULL REFERENCES waivers(id),
  rider_id UUID NOT NULL REFERENCES riders(id),
  signer_name VARCHAR(255) NOT NULL,
  signer_email VARCHAR(255),
  relationship_to_rider VARCHAR(100),
  accepted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  signature_data TEXT,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP
);

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

-- Admin users
CREATE TABLE IF NOT EXISTS admin_users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(100),
  role VARCHAR(50) DEFAULT 'staff',
  active BOOLEAN DEFAULT TRUE,
  last_login TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX idx_riders_email ON riders(email);
CREATE INDEX idx_riders_deleted_at ON riders(deleted_at);
CREATE INDEX idx_horses_name ON horses(name);
CREATE INDEX idx_horses_deleted_at ON horses(deleted_at);
CREATE INDEX idx_bookings_rider_id ON bookings(rider_id);
CREATE INDEX idx_bookings_horse_id ON bookings(horse_id);
CREATE INDEX idx_bookings_start_time ON bookings(start_time);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_deleted_at ON bookings(deleted_at);
CREATE INDEX idx_time_slots_horse_id ON time_slots(horse_id);
CREATE INDEX idx_time_slots_date ON time_slots(date);
CREATE INDEX idx_waiver_acceptances_rider_id ON waiver_acceptances(rider_id);
CREATE INDEX idx_waiver_acceptances_deleted_at ON waiver_acceptances(deleted_at);
CREATE INDEX idx_makeup_credits_rider_id ON makeup_credits(rider_id);
CREATE INDEX idx_makeup_credits_status ON makeup_credits(status);
