-- RJ Performance Horses - Phase 2B: Authentication + Row Level Security
-- Date: October 4, 2026
--
-- This migration:
-- 1. Creates admin_profiles table to identify RJ admin users
-- 2. Enables Row Level Security (RLS) on all application tables
-- 3. Defines RLS policies for data access control
-- 4. Sets DENY BY DEFAULT for all tables

-- ============================================================
-- ADMIN PROFILES TABLE
-- ============================================================
-- Maps Supabase auth.users.id to admin status
-- This is the ONLY source of truth for admin authorization
-- Future: Could extend for staff roles, permissions, etc.

CREATE TABLE IF NOT EXISTS admin_profiles (
  id TEXT PRIMARY KEY,
  auth_user_id UUID NOT NULL UNIQUE REFERENCES auth.users(id) ON DELETE CASCADE,
  name VARCHAR(100),
  email VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'admin' CHECK (role IN ('admin', 'staff', 'instructor')),
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_admin_profiles_auth_user_id ON admin_profiles(auth_user_id);
CREATE INDEX idx_admin_profiles_email ON admin_profiles(email);
CREATE INDEX idx_admin_profiles_is_active ON admin_profiles(is_active);

-- ============================================================
-- ENABLE ROW LEVEL SECURITY ON ALL TABLES
-- ============================================================
-- Start with DENY BY DEFAULT - all access must be explicitly granted

ALTER TABLE customers ENABLE ROW LEVEL SECURITY;
ALTER TABLE riders ENABLE ROW LEVEL SECURITY;
ALTER TABLE horses ENABLE ROW LEVEL SECURITY;
ALTER TABLE horse_owners ENABLE ROW LEVEL SECURITY;
ALTER TABLE horse_owner_relationships ENABLE ROW LEVEL SECURITY;
ALTER TABLE lesson_types ENABLE ROW LEVEL SECURITY;
ALTER TABLE horse_lesson_types ENABLE ROW LEVEL SECURITY;
ALTER TABLE instructor_availability ENABLE ROW LEVEL SECURITY;
ALTER TABLE horse_recurring_availability ENABLE ROW LEVEL SECURITY;
ALTER TABLE blocked_times ENABLE ROW LEVEL SECURITY;
ALTER TABLE recurring_lessons ENABLE ROW LEVEL SECURITY;
ALTER TABLE bookings ENABLE ROW LEVEL SECURITY;
ALTER TABLE booking_riders ENABLE ROW LEVEL SECURITY;
ALTER TABLE waivers ENABLE ROW LEVEL SECURITY;
ALTER TABLE waiver_acceptances ENABLE ROW LEVEL SECURITY;
ALTER TABLE makeup_credits ENABLE ROW LEVEL SECURITY;
ALTER TABLE calendar_connections ENABLE ROW LEVEL SECURITY;

-- ============================================================
-- HELPER FUNCTION: Check if user is admin
-- ============================================================
-- Used in policy expressions for cleaner code

CREATE OR REPLACE FUNCTION is_admin(user_id uuid)
RETURNS BOOLEAN AS $$
  SELECT EXISTS (
    SELECT 1 FROM admin_profiles
    WHERE auth_user_id = user_id AND is_active = TRUE
  )
$$ LANGUAGE sql STABLE;

-- ============================================================
-- RLS POLICIES BY TABLE
-- ============================================================

-- ============================================================
-- LESSON TYPES - Public Read (active only), Admin Full Access
-- ============================================================
-- Business case: Public website shows active lesson offerings
-- Customers need to see available lesson types for booking

-- Anonymous: SELECT only active lesson types
CREATE POLICY lesson_types_public_read ON lesson_types
  FOR SELECT
  USING (active = TRUE);

-- Admin: Full access
CREATE POLICY lesson_types_admin_all ON lesson_types
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- ============================================================
-- CUSTOMERS - Admin Access Only, Prepare for Future Auth
-- ============================================================
-- Private customer data - NO anonymous access
-- Future: Customers will be able to see their own record

-- Admin: Full access
CREATE POLICY customers_admin_all ON customers
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- Future policy (commented, will enable in Phase 2C):
-- Customers can view their own record
-- CREATE POLICY customers_own_data ON customers
--   FOR SELECT
--   USING (auth_user_id = auth.uid());

-- ============================================================
-- RIDERS - Admin Access Only, Prepare for Future Auth
-- ============================================================
-- Private rider data (children, emergency contacts, etc)
-- NO anonymous access
-- Future: Customers can see riders under their account

-- Admin: Full access
CREATE POLICY riders_admin_all ON riders
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- ============================================================
-- HORSES - Admin Read/Write, Conservative Data Exposure
-- ============================================================
-- Horse scheduling data - internal only
-- Horses are NOT exposed to public website yet
-- If we add public horse profiles later, create a separate view

-- Admin: Full access
CREATE POLICY horses_admin_all ON horses
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- ============================================================
-- HORSE OWNERS - Admin Only
-- ============================================================
-- Internal ownership tracking - not exposed to customers

CREATE POLICY horse_owners_admin_all ON horse_owners
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- ============================================================
-- HORSE OWNER RELATIONSHIPS - Admin Only
-- ============================================================

CREATE POLICY horse_owner_relationships_admin_all ON horse_owner_relationships
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- ============================================================
-- HORSE LESSON TYPES - Admin Only
-- ============================================================
-- Internal lesson eligibility matrix

CREATE POLICY horse_lesson_types_admin_all ON horse_lesson_types
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- ============================================================
-- INSTRUCTOR AVAILABILITY - Admin Only
-- ============================================================
-- Rylie's availability schedule - not exposed to customers

CREATE POLICY instructor_availability_admin_all ON instructor_availability
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- ============================================================
-- HORSE RECURRING AVAILABILITY - Admin Only
-- ============================================================
-- Per-horse availability schedules

CREATE POLICY horse_recurring_availability_admin_all ON horse_recurring_availability
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- ============================================================
-- BLOCKED TIMES - Admin Only
-- ============================================================
-- Date-specific availability exceptions

CREATE POLICY blocked_times_admin_all ON blocked_times
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- ============================================================
-- RECURRING LESSONS - Admin Only
-- ============================================================
-- Recurring lesson schedules - internal admin data

CREATE POLICY recurring_lessons_admin_all ON recurring_lessons
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- ============================================================
-- BOOKINGS - Admin Read/Write, Prepare for Future Guest Booking
-- ============================================================
-- Booking records - admin manages, future guest booking via Edge Function
-- NO direct customer access yet (future phase)
-- NO anonymous INSERT access

-- Admin: Full access
CREATE POLICY bookings_admin_all ON bookings
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- Future policy (commented, Phase 2D guest booking):
-- Guest/customer booking will go through an Edge Function that validates
-- and creates bookings safely, rather than direct INSERT access

-- ============================================================
-- BOOKING RIDERS - Admin Only (for now)
-- ============================================================
-- Group lesson rider assignments

CREATE POLICY booking_riders_admin_all ON booking_riders
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- ============================================================
-- WAIVERS - Admin Full, Future Customer Read
-- ============================================================
-- Legal documents - admin manages versions and content
-- Customers may see active current waiver for signing

-- Admin: Full access
CREATE POLICY waivers_admin_all ON waivers
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- Anonymous/Authenticated: Read only active waivers (for waiver display/signing page)
CREATE POLICY waivers_public_read_active ON waivers
  FOR SELECT
  USING (active = TRUE);

-- ============================================================
-- WAIVER ACCEPTANCES - Admin Read, Customer Create (Future)
-- ============================================================
-- Immutable records of waiver signatures
-- Admin can view all acceptances
-- Future: Customers can view their own acceptances

-- Admin: Full access
CREATE POLICY waiver_acceptances_admin_all ON waiver_acceptances
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- Future policy (Phase 2E waiver signing):
-- Authenticated users can INSERT their own waiver acceptance
-- Authenticated users can VIEW their own acceptances

-- ============================================================
-- MAKEUP CREDITS - Admin Full, Customer Read Own (Future)
-- ============================================================
-- Makeup lesson credits - admin manages, customer views their own

-- Admin: Full access
CREATE POLICY makeup_credits_admin_all ON makeup_credits
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- Future policy (Phase 2C+):
-- Riders can view their own makeup credits
-- CREATE POLICY makeup_credits_own_rider ON makeup_credits
--   FOR SELECT
--   USING (
--     EXISTS (
--       SELECT 1 FROM riders r
--       WHERE r.id = makeup_credits.rider_id
--       AND r.customer_id = (
--         SELECT id FROM customers WHERE auth_user_id = auth.uid()
--       )
--     )
--   );

-- ============================================================
-- CALENDAR CONNECTIONS - Admin Full (Future Customer)
-- ============================================================
-- External calendar sync - admin manages, future customer controls own

-- Admin: Full access
CREATE POLICY calendar_connections_admin_all ON calendar_connections
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- Future policy (Phase 2E):
-- CREATE POLICY calendar_connections_own_customer ON calendar_connections
--   FOR ALL
--   USING (
--     (auth_user_id = auth.uid())
--     OR is_admin(auth.uid())
--   )
--   WITH CHECK (
--     (auth_user_id = auth.uid())
--     OR is_admin(auth.uid())
--   );

-- ============================================================
-- RLS POLICY SUMMARY
-- ============================================================
-- DENY BY DEFAULT principle applied to all tables
--
-- PUBLIC-SAFE BUSINESS DATA:
--   - lesson_types (active only, anonymous read)
--   - waivers (active only, for signature display)
--
-- PRIVATE CUSTOMER DATA (Admin + Future Auth):
--   - customers (admin full, future: auth_user_id = current_user)
--   - riders (admin full, future: via customer relationship)
--   - waiver_acceptances (admin full, future: customer read own)
--   - bookings (admin full, future: guest via Edge Function + auth customers read own)
--   - booking_riders (admin full)
--   - makeup_credits (admin full, future: customer read own)
--
-- ADMIN BUSINESS DATA (Admin Only):
--   - horse_owners (admin full)
--   - horse_owner_relationships (admin full)
--   - horses (admin full, conservative - no public exposure yet)
--   - horse_lesson_types (admin full)
--   - instructor_availability (admin full)
--   - horse_recurring_availability (admin full)
--   - blocked_times (admin full)
--   - recurring_lessons (admin full)
--   - calendar_connections (admin full, future: customer manage own)

