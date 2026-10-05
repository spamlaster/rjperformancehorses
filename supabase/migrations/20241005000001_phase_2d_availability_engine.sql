-- Phase 2D Part 1: Public Bookable Availability Engine
-- Server-side RPC function to calculate which lesson times can be booked
-- This function is SECURITY DEFINER to provide controlled access to scheduling data
-- while keeping the underlying tables private

-- Configuration: Default booking slot interval in minutes
-- Using 30-minute increments as a reasonable default for lesson scheduling
CREATE OR REPLACE FUNCTION get_bookable_slots(
  p_lesson_type_id UUID,
  p_date DATE
)
RETURNS TABLE (
  start_time TEXT,
  end_time TEXT,
  available BOOLEAN
) LANGUAGE plpgsql SECURITY DEFINER SET search_path = public AS $$
DECLARE
  v_lesson_duration_minutes INT;
  v_required_horse_count INT;
  v_day_of_week INT;
  v_booking_interval_minutes INT := 30;
  v_start_time TIME;
  v_end_time TIME;
  v_slot_start TIME;
  v_slot_end TIME;
  v_slot_start_ts TIMESTAMP;
  v_slot_end_ts TIMESTAMP;
  v_instructor_blocked BOOLEAN;
  v_available_horse_count INT;
  v_eligible_horse_exists BOOLEAN;
BEGIN
  -- Validate inputs
  IF p_lesson_type_id IS NULL OR p_date IS NULL THEN
    RETURN;
  END IF;

  -- Check if date is in the past (no bookings in past)
  IF p_date < CURRENT_DATE THEN
    RETURN;
  END IF;

  -- Get lesson duration, required horse count, and verify lesson type is active
  SELECT duration_minutes, required_horse_count INTO v_lesson_duration_minutes, v_required_horse_count
  FROM lesson_types
  WHERE id = p_lesson_type_id AND active = TRUE;

  IF v_lesson_duration_minutes IS NULL THEN
    -- Lesson type not found or inactive
    RETURN;
  END IF;

  -- Default to 1 horse if required_horse_count not set (for backward compatibility)
  IF v_required_horse_count IS NULL THEN
    v_required_horse_count := 1;
  END IF;

  -- Get day of week (0=Sunday, 1=Monday, ..., 6=Saturday)
  v_day_of_week := EXTRACT(DOW FROM p_date)::INT;

  -- Check if enough eligible horses exist with recurring availability
  SELECT EXISTS(
    SELECT 1
    FROM horses h
    INNER JOIN horse_lesson_types hlt ON h.id = hlt.horse_id
    INNER JOIN horse_recurring_availability hra ON h.id = hra.horse_id
    WHERE hlt.lesson_type_id = p_lesson_type_id
      AND hlt.eligible = TRUE
      AND h.active = TRUE
      AND h.deleted_at IS NULL
      AND hra.day_of_week = v_day_of_week
      AND hra.active = TRUE
    LIMIT v_required_horse_count
  ) INTO v_eligible_horse_exists;

  IF NOT v_eligible_horse_exists THEN
    -- Not enough eligible horses with recurring availability for this day
    RETURN;
  END IF;

  -- Iterate through instructor availability windows for this day
  FOR v_start_time, v_end_time IN
    SELECT start_time, end_time
    FROM instructor_availability
    WHERE day_of_week = v_day_of_week AND active = TRUE
    ORDER BY start_time
  LOOP
    -- Generate slots within this window
    v_slot_start := v_start_time;

    WHILE v_slot_start < v_end_time LOOP
      -- Calculate slot end time
      v_slot_end := v_slot_start + (v_lesson_duration_minutes || ' minutes')::INTERVAL;

      -- Check if slot fits completely within instructor availability window
      IF v_slot_end > v_end_time THEN
        EXIT;
      END IF;

      -- Convert to timestamps for date-based queries
      v_slot_start_ts := p_date || ' ' || v_slot_start;
      v_slot_end_ts := p_date || ' ' || v_slot_end;

      -- Check if instructor is blocked for this time slot
      v_instructor_blocked := EXISTS(
        SELECT 1
        FROM blocked_times
        WHERE horse_id IS NULL  -- Instructor/general block
          AND date = p_date
          AND start_time < v_slot_end
          AND end_time > v_slot_start
      );

      -- If instructor is not blocked, check if enough eligible horses are available
      IF NOT v_instructor_blocked THEN
        -- Count how many eligible horses are available for this slot
        SELECT COUNT(*) INTO v_available_horse_count
        FROM horses h
        INNER JOIN horse_lesson_types hlt ON h.id = hlt.horse_id
        WHERE hlt.lesson_type_id = p_lesson_type_id
          AND hlt.eligible = TRUE
          AND h.active = TRUE
          AND h.deleted_at IS NULL
          -- Check if horse has active recurring availability covering this slot
          AND EXISTS(
            SELECT 1
            FROM horse_recurring_availability hra
            WHERE hra.horse_id = h.id
              AND hra.day_of_week = v_day_of_week
              AND hra.active = TRUE
              AND hra.start_time <= v_slot_start
              AND hra.end_time >= v_slot_end
          )
          -- AND no horse-specific blocked time
          AND NOT EXISTS(
            SELECT 1
            FROM blocked_times bt
            WHERE bt.horse_id = h.id
              AND bt.date = p_date
              AND bt.start_time < v_slot_end
              AND bt.end_time > v_slot_start
          )
          -- AND no conflicting booking (pending/confirmed only)
          AND NOT EXISTS(
            SELECT 1
            FROM bookings b
            WHERE b.horse_id = h.id
              AND b.status IN ('pending', 'confirmed')
              AND b.deleted_at IS NULL
              AND b.start_time < v_slot_end_ts
              AND b.end_time > v_slot_start_ts
          );

        -- Return this slot if enough horses are available
        IF v_available_horse_count >= v_required_horse_count THEN
          RETURN QUERY SELECT
            TO_CHAR(v_slot_start, 'HH24:MI'::TEXT),
            TO_CHAR(v_slot_end, 'HH24:MI'::TEXT),
            TRUE;
        END IF;
      END IF;

      -- Move to next slot
      v_slot_start := v_slot_start + (v_booking_interval_minutes || ' minutes')::INTERVAL;
    END LOOP;
  END LOOP;
END;
$$;

-- Grant execute permission to public (for anonymous users)
-- This RPC can be called without authentication
GRANT EXECUTE ON FUNCTION get_bookable_slots(UUID, DATE) TO PUBLIC;

-- Create index to optimize instructor availability lookups
CREATE INDEX IF NOT EXISTS idx_instructor_availability_day_active
ON instructor_availability(day_of_week, active)
WHERE active = TRUE;

-- Create composite index for horse availability queries
CREATE INDEX IF NOT EXISTS idx_horse_recurring_avail_day_active
ON horse_recurring_availability(day_of_week, active, horse_id)
WHERE active = TRUE;

-- Create index for blocked times lookups
CREATE INDEX IF NOT EXISTS idx_blocked_times_date_range
ON blocked_times(date, start_time, end_time);
