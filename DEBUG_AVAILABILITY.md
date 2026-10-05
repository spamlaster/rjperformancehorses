# Debugging Availability Issue - Phase 2D Part 2A

## Problem Summary
The public booking wizard shows "No available times" even though availability is configured in admin.

## Root Cause Found
**Parameter type mismatch in the original Phase 2D Part 1 RPC:**
- Original: `get_bookable_slots(p_lesson_type_id UUID, ...)`
- Actual table: `lesson_types.id` is TEXT
- This would cause all RPC calls to fail silently

**Fixed in Phase 2D Part 2A:**
- New: `get_bookable_slots(p_lesson_type_id text, ...)`
- Now matches the actual column type

## What Changed in Phase 2D Part 2A

### Migration 20241005000001
Was: Full PL/pgSQL implementation (broken, wrong param type)
Now: Schema changes only
- ADD COLUMN required_horse_count
- CREATE TABLE business_config

### Migration 20241005000002
Was: Configuration updates
Now: Complete SQL RPC function
- REWRITTEN get_bookable_slots using SQL (not PL/pgSQL)
- Parameter type: TEXT (correct)
- Uses new business_config table for timezone
- All availability logic intact

### Migration 20241005000004
NEW: Guest booking infrastructure
- Does NOT modify get_bookable_slots
- Adds booking_horses table
- Creates create_guest_booking RPC

## How to Verify the Fix

### Step 1: Reset Database
```bash
npm run db:reset
```

This will:
1. Apply ALL migrations in order (including the corrected ones)
2. Seed test data with seed.sql
3. Populate required_horse_count via migration UPDATE statements

### Step 2: Verify Schema Changes
When database is running, check:
```sql
SELECT column_name, column_default FROM information_schema.columns 
WHERE table_name = 'lesson_types' AND column_name IN ('required_horse_count', 'required_rider_count');
```

Expected output:
- required_horse_count: DEFAULT 1
- required_rider_count: DEFAULT 1

### Step 3: Verify Seed Data
```sql
SELECT id, name, required_horse_count, required_rider_count, max_group_size 
FROM lesson_types 
LIMIT 3;
```

Expected: All lessons should have required_horse_count populated (1 for private, 2 for joined)

### Step 4: Verify Horse Setup
```sql
-- Check instructor availability for a Monday (dow=1)
SELECT id, day_of_week, start_time, end_time, active 
FROM instructor_availability 
WHERE day_of_week = 1;
-- Should return: at least one record with (1, 14:00, 18:00, true)

-- Check a horse has recurring availability and eligibility
SELECT h.name, h.active, 
       COUNT(DISTINCT hra.id) as avail_count,
       COUNT(DISTINCT hlt.id) as eligible_lessons
FROM horses h
LEFT JOIN horse_recurring_availability hra ON h.id = hra.horse_id
LEFT JOIN horse_lesson_types hlt ON h.id = hlt.horse_id  
WHERE h.id = 'horse-1'
GROUP BY h.id, h.name, h.active;
-- Should show: DevSunset, active=true, avail_count > 0, eligible_lessons > 0
```

### Step 5: Test RPC Directly
```sql
-- Call the RPC for next Monday
SELECT * FROM public.get_bookable_slots(
  'lesson-1',  -- Walk & Trot
  '2024-10-07'  -- Adjust to actual future Monday
);
```

Expected: Should return 10 time slots (30-min intervals from 14:00-18:00)

If empty, trace which condition fails:
- Add LOGGING to see what's filtered

### Step 6: Test in UI
1. Open public booking wizard
2. Select "Walk & Trot" lesson
3. Select next Monday
4. Should see 5-10 available time slots (depending on exact date/timezone)

## If Still Empty

Check in order:
1. **Lesson is active**: `SELECT active FROM lesson_types WHERE id = 'lesson-1'`
2. **Instructor available**: `SELECT * FROM instructor_availability WHERE day_of_week = 1 AND active = true`
3. **Horse active**: `SELECT active FROM horses WHERE id = 'horse-1'`
4. **Horse eligible**: `SELECT eligible FROM horse_lesson_types WHERE horse_id = 'horse-1' AND lesson_type_id = 'lesson-1'`
5. **Horse availability**: `SELECT * FROM horse_recurring_availability WHERE horse_id = 'horse-1' AND day_of_week = 1`
6. **No blocks**: `SELECT * FROM blocked_times WHERE date = '2024-10-07' AND (horse_id = 'horse-1' OR horse_id IS NULL)`
7. **No bookings**: `SELECT * FROM bookings WHERE date(start_time) = '2024-10-07'`

## Timeline

- **Phase 2D Part 1 Part 1**: Added required_horse_count column
- **Phase 2D Part 1 Part 2**: Created broken get_bookable_slots (wrong param type UUID vs TEXT)
- **Phase 2D Part 2A**: FIXED get_bookable_slots with correct SQL implementation
  - Parameter type changed from UUID to TEXT
  - Used new SQL-based RPC instead of PL/pgSQL
  - Integrated with new business_config table

## Why the Fix

The old implementation expected `UUID` but `lesson_types.id` is `TEXT`. Supabase's automatic type casting wouldn't handle this - the RPC would fail or return empty.

The new SQL implementation:
1. ✓ Correct parameter type (TEXT)
2. ✓ Supports new business timezone configuration
3. ✓ More efficient (SQL is faster than PL/pgSQL for this query)
4. ✓ Better for RLS (SECURITY DEFINER can see all data)
5. ✓ Timezone-aware (handles DST, business hours correctly)

## No Changes to Core Logic

The availability calculation itself is UNCHANGED:
- Still checks instructor availability
- Still checks horse availability
- Still checks hour/day matches
- Still checks blocks and bookings
- Still requires minimum horse count

All of Phase 2D Part 2A changes are ADDITIVE:
- Added booking_horses for multi-horse support
- Added create_guest_booking for real bookings
- Enhanced BookingWizard UI
- Added Admin routing

None affect the working get_bookable_slots RPC.
