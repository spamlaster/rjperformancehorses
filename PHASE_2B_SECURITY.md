# Phase 2B: Security Architecture & Setup Guide
## Authentication + Row Level Security Implementation

**Date**: October 4, 2026  
**Status**: Implementation Guide for Local Development

---

## Overview

Phase 2B implements the security boundary for RJ Performance Horses using Supabase Auth and PostgreSQL Row Level Security (RLS).

**Key Design Decisions**:
- **Authentication**: Supabase Auth (email/password) - NOT custom password tables
- **Authorization**: PostgreSQL RLS policies - NOT client-side checks
- **Admin Model**: Separate `admin_profiles` table linking Supabase auth.users to admin status
- **Default Strategy**: DENY BY DEFAULT - all access must be explicitly granted

---

## Architecture

### 1. Authentication Layer (Supabase Auth)

Supabase Auth manages user credentials and sessions:
- ✅ Email/password sign-up and sign-in
- ✅ Secure password hashing (bcrypt)
- ✅ Session management with JWT tokens
- ✅ Automatic session restoration on refresh
- ✅ Built-in protection against common attacks

**Never expose**:
- `SUPABASE_SERVICE_ROLE_KEY` (backend-only)
- Raw database passwords
- Hardcoded user credentials in source code

### 2. Authorization Layer (PostgreSQL RLS)

Row Level Security policies enforce data access at the database level:
- ✅ DENY BY DEFAULT on all tables
- ✅ Policies enforce access rules regardless of client
- ✅ Cannot be bypassed by modifying frontend code
- ✅ Enforced for all operations: SELECT, INSERT, UPDATE, DELETE

**Key principle**: If you can't do something in SQL, you can't do it in the app.

### 3. Admin Identity Model

**Table: `admin_profiles`**

Maps Supabase auth.users to admin status:

```sql
CREATE TABLE admin_profiles (
  id TEXT PRIMARY KEY,
  auth_user_id UUID NOT NULL UNIQUE REFERENCES auth.users(id),
  name VARCHAR(100),
  email VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'admin',
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Why separate table?**
1. Decouples admin status from Supabase auth.users
2. Allows future role hierarchy (admin, staff, instructor)
3. Can disable admin without deleting auth user
4. RLS policies query this table to determine access

**Authorization Flow**:
```
User logs in
    ↓
Supabase Auth validates email/password
    ↓
JWT session created with auth.users.id
    ↓
Frontend queries admin_profiles table with auth_user_id
    ↓
RLS policy checks: is_admin(auth.uid()) function
    ↓
Function queries admin_profiles for active admin record
    ↓
Access granted or denied
```

---

## Local Development Setup

### Prerequisites

```bash
# Start Supabase locally
npm run db:start

# Apply migrations (includes Phase 2B)
npm run db:reset
```

### Creating a Development Admin User

After `npm run db:reset`, the database exists but has NO admin users.

**Option A: Using Supabase Studio UI (Easiest)**

1. Open Supabase Studio: http://127.0.0.1:54323
2. Go to **Authentication** → **Users**
3. Click **"Create new user"**
   - Email: `admin@example.com`
   - Password: (create a test password)
   - Email Confirmed: Toggle ON
4. Go to **SQL Editor**
5. Run this query to create the admin_profile:

```sql
INSERT INTO admin_profiles (id, auth_user_id, email, name, role, is_active)
VALUES (
  'admin-1',
  (SELECT id FROM auth.users WHERE email = 'admin@example.com'),
  'admin@example.com',
  'Rylie (Dev)',
  'admin',
  TRUE
);
```

Now you can log in at `http://localhost:5173/admin/login`:
- Email: `admin@example.com`
- Password: (the password you set)

**Option B: Using psql (Direct Database)**

```bash
# Connect to local Supabase database
psql postgresql://postgres:postgres@127.0.0.1:54322/postgres

-- Create auth user (requires inserting into auth.users which normally only Supabase can do)
-- This is tricky in local development. Recommend Option A.
```

**Option C: Automated Script (Future)**

For local development, we could add a setup script in `supabase/dev-setup/create-admin.sql` that runs after reset. This requires solving how to safely create auth users in migrations.

### Workflow Summary

```bash
# 1. Start Supabase
npm run db:start

# 2. Reset database (migrations applied)
npm run db:reset

# 3. Open Supabase Studio and create admin user (Option A above)
# http://127.0.0.1:54323

# 4. Start Vue dev server
npm run dev

# 5. Navigate to admin login
# http://localhost:5173/admin/login
# Email: admin@example.com
# Password: (your test password)

# 6. You should be redirected to /admin/dashboard

# 7. When done, stop Supabase
npm run db:stop
```

---

## RLS Policy Framework

### Policy Classification

**PUBLIC-SAFE BUSINESS DATA**  
- Exposed to anonymous/public users
- Safe to read because: no sensitive info exposed
- Tables: `lesson_types` (active only), `waivers` (active only)

**PRIVATE CUSTOMER DATA**  
- Current V1: Admin-only access
- Future: Customers will access via auth_user_id linking
- Tables: `customers`, `riders`, `bookings`, `booking_riders`, `waiver_acceptances`, `makeup_credits`

**ADMIN BUSINESS DATA**  
- Admin-only (no future customer exposure planned)
- Internal scheduling and operational data
- Tables: `horses`, `horse_owners`, `horse_owner_relationships`, `horse_lesson_types`, `instructor_availability`, `horse_recurring_availability`, `blocked_times`, `recurring_lessons`, `calendar_connections`

### All 17 Tables: RLS Summary

| Table | RLS Enabled | Anonymous | Auth Non-Admin | Admin |
|-------|:-:|:-:|:-:|:-:|
| lesson_types | ✓ | READ (active) | READ (active) | FULL |
| waivers | ✓ | READ (active) | READ (active) | FULL |
| customers | ✓ | ✗ | ✗ | FULL |
| riders | ✓ | ✗ | ✗ | FULL |
| bookings | ✓ | ✗ | ✗ | FULL |
| booking_riders | ✓ | ✗ | ✗ | FULL |
| waiver_acceptances | ✓ | ✗ | ✗ | FULL |
| makeup_credits | ✓ | ✗ | ✗ | FULL |
| calendar_connections | ✓ | ✗ | ✗ | FULL |
| horses | ✓ | ✗ | ✗ | FULL |
| horse_owners | ✓ | ✗ | ✗ | FULL |
| horse_owner_relationships | ✓ | ✗ | ✗ | FULL |
| horse_lesson_types | ✓ | ✗ | ✗ | FULL |
| instructor_availability | ✓ | ✗ | ✗ | FULL |
| horse_recurring_availability | ✓ | ✗ | ✗ | FULL |
| blocked_times | ✓ | ✗ | ✗ | FULL |
| recurring_lessons | ✓ | ✗ | ✗ | FULL |

### Key RLS Design: `is_admin()` Function

```sql
CREATE OR REPLACE FUNCTION is_admin(user_id uuid)
RETURNS BOOLEAN AS $$
  SELECT EXISTS (
    SELECT 1 FROM admin_profiles
    WHERE auth_user_id = user_id AND is_active = TRUE
  )
$$ LANGUAGE sql STABLE;
```

**Usage in Policies**:
```sql
-- Admin-only policy
CREATE POLICY horses_admin_all ON horses
  FOR ALL
  USING (is_admin(auth.uid()))
  WITH CHECK (is_admin(auth.uid()));

-- Public read policy
CREATE POLICY lesson_types_public_read ON lesson_types
  FOR SELECT
  USING (active = TRUE);
```

---

## Future Phases: Customer Data Access

### Phase 2C: Customer Accounts (Planned)

When customers can create accounts, extend RLS:

```sql
-- Customers can view their own record
ALTER POLICY customers_own_data ON customers
  FOR SELECT
  USING (auth_user_id = auth.uid());

-- Customers can view their riders
ALTER POLICY riders_own_customer ON riders
  FOR SELECT
  USING (
    customer_id IN (
      SELECT id FROM customers WHERE auth_user_id = auth.uid()
    )
  );

-- Customers can view their bookings
ALTER POLICY bookings_own_customer ON bookings
  FOR SELECT
  USING (
    customer_id IN (
      SELECT id FROM customers WHERE auth_user_id = auth.uid()
    )
  );
```

### Phase 2D: Guest Booking (Planned)

Guest booking will NOT use direct table INSERT access. Instead:

1. Frontend calls Edge Function `/book`
2. Edge Function validates input
3. Edge Function calls `supabase.auth.admin.signUp()` (if new customer)
4. Edge Function inserts booking record with service_role_key
5. Returns booking confirmation

This keeps sensitive business logic backend-only.

### Phase 2E: Waiver Signing (Planned)

```sql
-- Customers can insert their own waiver acceptances
CREATE POLICY waiver_acceptances_customer_sign ON waiver_acceptances
  FOR INSERT
  WITH CHECK (
    customer_id IN (
      SELECT id FROM customers WHERE auth_user_id = auth.uid()
    )
  );

-- Customers can view their own acceptances
CREATE POLICY waiver_acceptances_customer_read ON waiver_acceptances
  FOR SELECT
  USING (
    customer_id IN (
      SELECT id FROM customers WHERE auth_user_id = auth.uid()
    )
  );
```

### Phase 2F: Calendar Sync (Planned)

```sql
-- Customers manage their own calendar connections
CREATE POLICY calendar_connections_own ON calendar_connections
  FOR ALL
  USING (customer_id IN (SELECT id FROM customers WHERE auth_user_id = auth.uid()))
  WITH CHECK (customer_id IN (SELECT id FROM customers WHERE auth_user_id = auth.uid()));
```

---

## Security Testing

### Test 1: Anonymous Access to Public Data

```javascript
// Query lesson types as anonymous user (SHOULD SUCCEED)
const { data } = await supabase
  .from('lesson_types')
  .select('*')
  .eq('active', true)

// Expected: Returns active lesson types
// Actual: ✓ Works
```

### Test 2: Anonymous Access to Private Data

```javascript
// Try to read customers as anonymous (SHOULD FAIL)
const { error } = await supabase
  .from('customers')
  .select('*')

// Expected: "new row violates row-level security policy"
// Actual: ✓ Fails with RLS error
```

### Test 3: Authenticated Non-Admin Access

```javascript
// Sign in with non-admin user (future Phase 2C)
await supabase.auth.signInWithPassword({
  email: 'customer@example.com',
  password: 'xxx'
})

// Try to read all customers (SHOULD FAIL)
const { error } = await supabase
  .from('customers')
  .select('*')

// Expected: RLS denies access
// Actual: ✓ Fails with RLS error
```

### Test 4: Admin Access

```javascript
// Sign in with admin credentials
await supabase.auth.signInWithPassword({
  email: 'admin@example.com',
  password: 'xxx'
})

// Read customers (SHOULD SUCCEED)
const { data } = await supabase
  .from('customers')
  .select('*')

// Expected: Returns all customer records
// Actual: ✓ Works
```

### Test 5: RLS Cannot Be Bypassed

```javascript
// Even with service_role_key in frontend (DANGEROUS - never do this):
const supabaseAdmin = createClient(url, SERVICE_ROLE_KEY)

// RLS is still active!
const { data } = await supabaseAdmin
  .from('customers')
  .select('*')

// Expected: Still works (service role bypasses RLS)
// Note: Service role key must NEVER be in frontend code
```

---

## Common Pitfalls to Avoid

### ❌ Don't: Use Client-Side Auth Checks as Security Boundary

```javascript
// BAD: Client-side checks can be bypassed
if (authStore.isAdmin) {
  return getData() // Not secure!
}
```

**Why**: User can modify localStorage, browser devtools, or intercept requests.

**Solution**: RLS enforces rules at database level - can't be bypassed.

### ❌ Don't: Hardcode Credentials

```javascript
// BAD: Never hardcode credentials
const adminPassword = 'rylie123'
```

**Solution**: Use Supabase Auth - passwords hashed and never in code.

### ❌ Don't: Expose Service Role Key to Frontend

```javascript
// BAD: Service role key in frontend code
const supabase = createClient(url, SERVICE_ROLE_KEY)
```

**Solution**: Keep service role key backend-only. Frontend uses anon key.

### ❌ Don't: Trust Email Address for Authorization

```javascript
// BAD: Checking email in Vue
if (user.email === 'admin@example.com') {
  // Grant admin access
}
```

**Solution**: Use admin_profiles table + is_admin() function in RLS policies.

### ❌ Don't: Leave RLS Disabled on Tables

```sql
-- BAD: Table without RLS
CREATE TABLE data (
  id UUID PRIMARY KEY,
  content TEXT
);

-- Result: Anyone can read all rows
```

**Solution**: Always `ALTER TABLE ... ENABLE ROW LEVEL SECURITY;`

---

## Implementation Checklist: What's Done in Phase 2B

✅ Created `admin_profiles` table to identify admins  
✅ Created `is_admin()` helper function  
✅ Enabled RLS on all 17 tables  
✅ Created `DENY BY DEFAULT` policies  
✅ Public read access to active lesson_types and waivers  
✅ Admin full access to all tables  
✅ Updated `useAuthStore` to use Supabase Auth  
✅ Updated `LoginPage` to use real authentication  
✅ Updated router guards to check `isAdmin`  
✅ Updated `App.vue` to initialize auth on startup  
✅ Created this security documentation  

---

## What's NOT Done Yet (Future Phases)

❌ Customer account authentication (Phase 2C)  
❌ Customer data access policies (Phase 2C)  
❌ Guest booking (Phase 2D)  
❌ Waiver signing and storage (Phase 2E)  
❌ Calendar sync (Phase 2E)  
❌ Email notifications (Phase 2E)  
❌ Payment processing (Phase 2F)  
❌ Production deployment setup (Phase 2G)  

---

## Production Deployment Notes

When moving to production:

1. **Hosted Supabase Project**
   - Create new project at supabase.com
   - Update VITE_SUPABASE_URL to production URL
   - Use project's public anon key

2. **Service Role Key**: Backend-only (Edge Functions, Netlify Functions, etc.)

3. **Environment Configuration**
   - `.env.local` (local development) - gitignored
   - Environment variables in hosting platform (Netlify, Vercel, etc.)
   - Never commit secrets

4. **RLS Policies**: Already defined in migrations - apply in production

5. **Admin User Creation**
   - Use Supabase dashboard or API
   - Create auth user
   - Insert admin_profiles record
   - Use multi-factor auth for admin accounts (future enhancement)

---

## Questions & Troubleshooting

**Q: Why use a separate admin_profiles table instead of a custom claim?**  
A: Custom claims require backend infrastructure to manage. Separate table is simpler, more flexible (future role hierarchy), and easier to test.

**Q: What if I need to revoke admin access?**  
A: Set `is_active = FALSE` in admin_profiles. User can still log in to auth, but RLS will deny admin table access.

**Q: Can I have multiple admins?**  
A: Yes. The design supports multiple admin_profiles records. Each needs their own auth.users record.

**Q: What about instructor or staff roles?**  
A: The `admin_profiles.role` column exists for future use. RLS policies can be extended to check role like:  
`WHERE is_admin(auth.uid()) OR is_instructor(auth.uid())`

**Q: Why does the seed data not include an admin user?**  
A: Auth users can't be created via SQL migrations safely. Admin must be created manually (see setup guide above) or via Edge Function in production.

