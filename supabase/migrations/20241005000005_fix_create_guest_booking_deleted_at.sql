-- Fix for create_guest_booking RPC: remove deleted_at check from lesson_types
-- lesson_types table uses 'active' flag, not 'deleted_at' column

DROP FUNCTION IF EXISTS public.create_guest_booking(text, timestamp, timestamp, text, text, text, text, jsonb);

CREATE OR REPLACE FUNCTION public.create_guest_booking(
  p_lesson_type_id text,
  p_start_time timestamp,
  p_end_time timestamp,
  p_customer_first_name text,
  p_customer_last_name text,
  p_customer_email text,
  p_customer_phone text,
  p_riders jsonb
) RETURNS jsonb
LANGUAGE plpgsql
SET search_path = pg_catalog, public
SECURITY DEFINER AS $$
DECLARE
  v_booking_id text;
  v_customer_id text;
  v_lesson lesson_types;
  v_rider_count integer;
  v_required_riders integer;
  v_required_horses integer;
  v_available_horses text[];
  v_horse_id text;
  v_rider record;
  v_rider_id text;
  v_config text;
  v_now timestamptz;
BEGIN
  -- Start transaction safety
  v_now := now();

  -- ============================================================
  -- VALIDATION: Inputs and lesson type
  -- ============================================================
  IF p_lesson_type_id IS NULL OR p_start_time IS NULL OR p_end_time IS NULL THEN
    RETURN jsonb_build_object('error', 'Missing required booking parameters');
  END IF;

  IF p_customer_email IS NULL OR p_customer_email = '' THEN
    RETURN jsonb_build_object('error', 'Customer email is required');
  END IF;

  -- Fetch lesson type and validate (lesson_types uses active flag, not deleted_at)
  SELECT * INTO v_lesson FROM lesson_types
    WHERE id = p_lesson_type_id AND active;

  IF v_lesson IS NULL THEN
    RETURN jsonb_build_object('error', 'Lesson type not found or inactive');
  END IF;

  v_required_riders := v_lesson.required_rider_count;
  v_required_horses := v_lesson.required_horse_count;

  -- Validate rider count
  v_rider_count := jsonb_array_length(p_riders);
  IF v_rider_count <> v_required_riders THEN
    RETURN jsonb_build_object(
      'error',
      format('This lesson requires exactly %s rider(s), but %s were provided',
        v_required_riders, v_rider_count)
    );
  END IF;

  -- ============================================================
  -- AVAILABILITY RECHECK (Critical for concurrency)
  -- ============================================================
  -- Verify instructor still available and no conflicts
  IF NOT EXISTS (
    SELECT 1 FROM instructor_availability i
    WHERE i.active
      AND i.day_of_week = EXTRACT(DOW FROM p_start_time)
      AND p_start_time::date + i.start_time <= p_start_time
      AND p_start_time::date + i.end_time >= p_end_time
  ) THEN
    RETURN jsonb_build_object('error', 'Instructor is not available at the requested time');
  END IF;

  -- Verify no instructor block
  IF EXISTS (
    SELECT 1 FROM blocked_times b
    WHERE b.horse_id IS NULL
      AND b.date = p_start_time::date
      AND p_start_time::date + b.start_time < p_end_time
      AND p_start_time::date + b.end_time > p_start_time
  ) THEN
    RETURN jsonb_build_object('error', 'Instructor is blocked at the requested time');
  END IF;

  -- Verify no existing conflicting booking
  IF EXISTS (
    SELECT 1 FROM bookings b
    WHERE b.status IN ('pending', 'confirmed')
      AND b.deleted_at IS NULL
      AND b.start_time < p_end_time
      AND b.end_time > p_start_time
  ) THEN
    RETURN jsonb_build_object('error', 'The requested time is no longer available');
  END IF;

  -- ============================================================
  -- FIND/CREATE CUSTOMER (Conservative Deduplication)
  -- ============================================================
  -- Use email as primary identifier (strongest for matching)
  SELECT id INTO v_customer_id FROM customers
    WHERE email = p_customer_email AND deleted_at IS NULL
    LIMIT 1;

  IF v_customer_id IS NULL THEN
    -- Create new customer
    v_customer_id := public.generate_id();
    INSERT INTO customers(id, first_name, last_name, email, phone)
      VALUES(v_customer_id, p_customer_first_name, p_customer_last_name, p_customer_email, p_customer_phone);
  END IF;

  -- ============================================================
  -- CREATE/MATCH RIDERS (Deferred insertion until after booking exists)
  -- ============================================================
  -- Note: We only create/find riders here, but insert booking_riders later
  -- after the booking is created (otherwise booking_id will be NULL)
  FOR v_rider IN SELECT jsonb_array_elements(p_riders) AS rider_obj LOOP
    -- Try to find existing rider matching this customer + name + dob
    SELECT id INTO v_rider_id FROM riders r
      WHERE r.customer_id = v_customer_id
        AND r.first_name = (v_rider.rider_obj->>'firstName')
        AND r.last_name = (v_rider.rider_obj->>'lastName')
        AND (r.date_of_birth = (v_rider.rider_obj->>'dateOfBirth')::date OR v_rider.rider_obj->>'dateOfBirth' IS NULL)
        AND r.deleted_at IS NULL
      LIMIT 1;

    IF v_rider_id IS NULL THEN
      -- Create new rider
      v_rider_id := public.generate_id();
      INSERT INTO riders(id, customer_id, first_name, last_name, date_of_birth, riding_experience)
        VALUES(
          v_rider_id,
          v_customer_id,
          v_rider.rider_obj->>'firstName',
          v_rider.rider_obj->>'lastName',
          (v_rider.rider_obj->>'dateOfBirth')::date,
          'beginner'
        );
    END IF;
  END LOOP;

  -- ============================================================
  -- FIND AVAILABLE HORSES (Atomic Selection)
  -- ============================================================
  SELECT ARRAY_AGG(DISTINCT h.id) INTO v_available_horses
    FROM horses h
    WHERE h.active AND h.deleted_at IS NULL
      AND EXISTS (
        SELECT 1 FROM horse_lesson_types e
        WHERE e.horse_id = h.id
          AND e.lesson_type_id = p_lesson_type_id
          AND e.eligible
      )
      AND EXISTS (
        SELECT 1 FROM horse_recurring_availability a
        WHERE a.horse_id = h.id
          AND a.active
          AND a.day_of_week = EXTRACT(DOW FROM p_start_time)
          AND p_start_time::date + a.start_time <= p_start_time
          AND p_start_time::date + a.end_time >= p_end_time
      )
      AND NOT EXISTS (
        SELECT 1 FROM blocked_times b
        WHERE b.horse_id = h.id
          AND b.date = p_start_time::date
          AND p_start_time::date + b.start_time < p_end_time
          AND p_start_time::date + b.end_time > p_start_time
      )
      AND NOT EXISTS (
        SELECT 1 FROM bookings b
        WHERE b.horse_id = h.id
          AND b.status IN ('pending', 'confirmed')
          AND b.deleted_at IS NULL
          AND b.start_time < p_end_time
          AND b.end_time > p_start_time
      );

  -- Check if we have enough horses
  IF ARRAY_LENGTH(v_available_horses, 1) < v_required_horses THEN
    RETURN jsonb_build_object(
      'error',
      format('Insufficient available horses (%s found, %s required)',
        COALESCE(ARRAY_LENGTH(v_available_horses, 1), 0),
        v_required_horses)
    );
  END IF;

  -- ============================================================
  -- CREATE BOOKING
  -- ============================================================
  v_booking_id := public.generate_id();
  INSERT INTO bookings(
    id, customer_id, lesson_type_id, horse_id,
    start_time, end_time, status, booking_type
  ) VALUES(
    v_booking_id,
    v_customer_id,
    p_lesson_type_id,
    v_available_horses[1],
    p_start_time,
    p_end_time,
    'pending',
    'regular'
  );

  -- ============================================================
  -- ASSIGN REQUIRED HORSES to booking_horses
  -- ============================================================
  FOR i IN 1..v_required_horses LOOP
    INSERT INTO booking_horses(id, booking_id, horse_id)
      VALUES(public.generate_id(), v_booking_id, v_available_horses[i]);
  END LOOP;

  -- ============================================================
  -- CREATE BOOKING_RIDERS RELATIONSHIPS (With real booking_id)
  -- ============================================================
  FOR v_rider IN SELECT jsonb_array_elements(p_riders) AS rider_obj LOOP
    -- Find the rider we created/matched earlier
    SELECT id INTO v_rider_id FROM riders r
      WHERE r.customer_id = v_customer_id
        AND r.first_name = (v_rider.rider_obj->>'firstName')
        AND r.last_name = (v_rider.rider_obj->>'lastName')
        AND (r.date_of_birth = (v_rider.rider_obj->>'dateOfBirth')::date OR v_rider.rider_obj->>'dateOfBirth' IS NULL)
        AND r.deleted_at IS NULL
      LIMIT 1;

    IF v_rider_id IS NOT NULL THEN
      INSERT INTO booking_riders(id, booking_id, rider_id)
        VALUES(public.generate_id(), v_booking_id, v_rider_id)
        ON CONFLICT (booking_id, rider_id) DO NOTHING;
    END IF;
  END LOOP;

  -- ============================================================
  -- SUCCESS: Return booking ID
  -- ============================================================
  RETURN jsonb_build_object(
    'success', true,
    'booking_id', v_booking_id,
    'customer_id', v_customer_id,
    'message', 'Booking created successfully'
  );

EXCEPTION WHEN OTHERS THEN
  RETURN jsonb_build_object(
    'error',
    format('Booking creation failed: %s', SQLERRM)
  );
END $$;

GRANT EXECUTE ON FUNCTION public.create_guest_booking(text, timestamp, timestamp, text, text, text, text, jsonb) TO anon, authenticated;

NOTIFY pgrst, 'reload schema';
