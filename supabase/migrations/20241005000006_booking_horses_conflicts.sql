-- Horse conflicts use booking_horses (authoritative, supports multi-horse bookings).
-- get_bookable_slots and create_guest_booking share one horse rule so they cannot disagree.

-- generate_id() uses now()/random(); IMMUTABLE let plans cache one value, so multi-horse
-- bookings (and repeat bookings on a pooled connection) hit duplicate primary keys.
ALTER FUNCTION public.generate_id() VOLATILE;

-- Internal: eligible horses free for [p_starts, p_ends). Not callable by clients (exposes horse ids).
CREATE OR REPLACE FUNCTION public.available_horse_ids(p_lesson_type_id text, p_starts timestamp, p_ends timestamp)
RETURNS SETOF text
LANGUAGE sql
STABLE SECURITY DEFINER
SET search_path TO 'pg_catalog'
AS $$
  SELECT h.id FROM public.horses h
  WHERE h.active AND h.deleted_at IS NULL
    AND EXISTS (SELECT 1 FROM public.horse_lesson_types e
      WHERE e.horse_id = h.id AND e.lesson_type_id = p_lesson_type_id AND e.eligible)
    AND EXISTS (SELECT 1 FROM public.horse_recurring_availability a WHERE a.horse_id = h.id
      AND a.active AND a.day_of_week = extract(dow FROM p_starts::date)
      AND p_starts::date + a.start_time <= p_starts AND p_starts::date + a.end_time >= p_ends)
    AND NOT EXISTS (SELECT 1 FROM public.blocked_times b WHERE b.horse_id = h.id
      AND b.date = p_starts::date AND p_starts::date + b.start_time < p_ends AND p_starts::date + b.end_time > p_starts)
    AND NOT EXISTS (SELECT 1 FROM public.booking_horses bh
      JOIN public.bookings b ON b.id = bh.booking_id
      WHERE bh.horse_id = h.id AND b.status IN ('pending','confirmed') AND b.deleted_at IS NULL
      AND b.start_time < p_ends AND b.end_time > p_starts)
  ORDER BY h.id;
$$;

REVOKE ALL ON FUNCTION public.available_horse_ids(text,timestamp,timestamp) FROM PUBLIC, anon, authenticated;

CREATE OR REPLACE FUNCTION public.get_bookable_slots(p_lesson_type_id text, p_date date)
 RETURNS TABLE(start_time text, end_time text, available boolean)
 LANGUAGE sql
 STABLE SECURITY DEFINER
 SET search_path TO 'pg_catalog'
AS $function$
WITH config AS (
  SELECT config_value AS tz FROM public.business_config WHERE config_key = 'business_timezone'
), lesson AS (
  SELECT duration_minutes, required_horse_count FROM public.lesson_types
  WHERE id = p_lesson_type_id AND active AND duration_minutes > 0
), candidates AS (
  SELECT DISTINCT s AS starts, s + make_interval(mins => l.duration_minutes) AS ends,
    l.required_horse_count, c.tz
  FROM lesson l CROSS JOIN config c
  JOIN public.instructor_availability i ON i.active AND i.day_of_week = extract(dow FROM p_date)
  CROSS JOIN LATERAL generate_series(p_date + i.start_time,
    p_date + i.end_time - make_interval(mins => l.duration_minutes), interval '30 minutes') s
  WHERE p_date >= (now() AT TIME ZONE c.tz)::date
)
SELECT to_char(c.starts, 'HH24:MI'), to_char(c.ends, 'HH24:MI'), true
FROM candidates c
WHERE c.starts AT TIME ZONE c.tz > now()
  -- Reject nonexistent spring-forward wall times and lessons crossing a DST change.
  AND (c.starts AT TIME ZONE c.tz) AT TIME ZONE c.tz = c.starts
  AND (c.ends AT TIME ZONE c.tz) AT TIME ZONE c.tz = c.ends
  AND (c.ends AT TIME ZONE c.tz) - (c.starts AT TIME ZONE c.tz) = c.ends - c.starts
  AND NOT EXISTS (SELECT 1 FROM public.blocked_times b WHERE b.horse_id IS NULL
    AND b.date = p_date AND p_date + b.start_time < c.ends AND p_date + b.end_time > c.starts)
  -- A single instructor cannot teach overlapping bookings, regardless of horse.
  AND NOT EXISTS (SELECT 1 FROM public.bookings b WHERE b.status IN ('pending','confirmed')
    AND b.deleted_at IS NULL AND b.start_time < c.ends AND b.end_time > c.starts)
  AND (SELECT count(*) FROM public.available_horse_ids(p_lesson_type_id, c.starts, c.ends)) >= c.required_horse_count
ORDER BY c.starts;
$function$;

REVOKE ALL ON FUNCTION public.get_bookable_slots(text,date) FROM PUBLIC;
GRANT EXECUTE ON FUNCTION public.get_bookable_slots(text,date) TO anon, authenticated;

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
SECURITY DEFINER
SET search_path TO 'pg_catalog', 'public'
AS $$
DECLARE
  v_booking_id text;
  v_customer_id text;
  v_lesson lesson_types;
  v_rider_count integer;
  v_required_riders integer;
  v_required_horses integer;
  v_available_horses text[];
  v_rider record;
  v_rider_id text;
BEGIN
  -- ============================================================
  -- VALIDATION: Inputs and lesson type
  -- ============================================================
  IF p_lesson_type_id IS NULL OR p_start_time IS NULL OR p_end_time IS NULL THEN
    RETURN jsonb_build_object('error', 'Missing required booking parameters');
  END IF;

  IF p_customer_email IS NULL OR p_customer_email = '' THEN
    RETURN jsonb_build_object('error', 'Customer email is required');
  END IF;

  -- lesson_types uses active flag, not deleted_at
  SELECT * INTO v_lesson FROM lesson_types
    WHERE id = p_lesson_type_id AND active;

  IF v_lesson IS NULL THEN
    RETURN jsonb_build_object('error', 'Lesson type not found or inactive');
  END IF;

  v_required_riders := v_lesson.required_rider_count;
  v_required_horses := v_lesson.required_horse_count;

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
  -- One instructor: serialize booking creation so concurrent requests cannot both pass the recheck.
  PERFORM pg_advisory_xact_lock(hashtext('public.create_guest_booking'));

  -- The requested time must be a slot get_bookable_slots offers right now (same rules, no drift).
  IF p_end_time - p_start_time <> make_interval(mins => v_lesson.duration_minutes)
     OR NOT EXISTS (
      SELECT 1 FROM public.get_bookable_slots(p_lesson_type_id, p_start_time::date) s
      WHERE p_start_time::date + s.start_time::time = p_start_time
  ) THEN
    RETURN jsonb_build_object('error', 'The requested time is no longer available');
  END IF;

  SELECT array_agg(id) INTO v_available_horses
    FROM public.available_horse_ids(p_lesson_type_id, p_start_time, p_end_time) id;

  IF COALESCE(array_length(v_available_horses, 1), 0) < v_required_horses THEN
    RETURN jsonb_build_object('error', 'The requested time is no longer available');
  END IF;

  -- ============================================================
  -- FIND/CREATE CUSTOMER (Conservative Deduplication)
  -- ============================================================
  SELECT id INTO v_customer_id FROM customers
    WHERE email = p_customer_email AND deleted_at IS NULL
    LIMIT 1;

  IF v_customer_id IS NULL THEN
    v_customer_id := public.generate_id();
    INSERT INTO customers(id, first_name, last_name, email, phone)
      VALUES(v_customer_id, p_customer_first_name, p_customer_last_name, p_customer_email, p_customer_phone);
  END IF;

  -- ============================================================
  -- CREATE/MATCH RIDERS (booking_riders inserted after booking exists)
  -- ============================================================
  FOR v_rider IN SELECT jsonb_array_elements(p_riders) AS rider_obj LOOP
    SELECT id INTO v_rider_id FROM riders r
      WHERE r.customer_id = v_customer_id
        AND r.first_name = (v_rider.rider_obj->>'firstName')
        AND r.last_name = (v_rider.rider_obj->>'lastName')
        AND (r.date_of_birth = (v_rider.rider_obj->>'dateOfBirth')::date OR v_rider.rider_obj->>'dateOfBirth' IS NULL)
        AND r.deleted_at IS NULL
      LIMIT 1;

    IF v_rider_id IS NULL THEN
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
  -- CREATE BOOKING
  -- ============================================================
  -- bookings.horse_id is kept for admin display only; booking_horses is authoritative.
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

  FOR i IN 1..v_required_horses LOOP
    INSERT INTO booking_horses(id, booking_id, horse_id)
      VALUES(public.generate_id(), v_booking_id, v_available_horses[i]);
  END LOOP;

  -- ============================================================
  -- CREATE BOOKING_RIDERS RELATIONSHIPS
  -- ============================================================
  FOR v_rider IN SELECT jsonb_array_elements(p_riders) AS rider_obj LOOP
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

REVOKE ALL ON FUNCTION public.create_guest_booking(text, timestamp, timestamp, text, text, text, text, jsonb) FROM PUBLIC;
GRANT EXECUTE ON FUNCTION public.create_guest_booking(text, timestamp, timestamp, text, text, text, text, jsonb) TO anon, authenticated;

NOTIFY pgrst, 'reload schema';
