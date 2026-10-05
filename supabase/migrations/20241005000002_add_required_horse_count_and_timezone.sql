-- Phase 2D RPC, after required_horse_count and timezone exist.
-- Existing bookings are TIMESTAMP WITHOUT TIME ZONE business-local wall times.
CREATE FUNCTION public.get_bookable_slots(p_lesson_type_id text, p_date date)
RETURNS TABLE(start_time text, end_time text, available boolean)
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = pg_catalog AS $$
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
  AND (SELECT count(DISTINCT h.id) FROM public.horses h
    JOIN public.horse_lesson_types e ON e.horse_id = h.id AND e.lesson_type_id = p_lesson_type_id AND e.eligible
    WHERE h.active AND h.deleted_at IS NULL
    AND EXISTS (SELECT 1 FROM public.horse_recurring_availability a WHERE a.horse_id = h.id
      AND a.active AND a.day_of_week = extract(dow FROM p_date)
      AND p_date + a.start_time <= c.starts AND p_date + a.end_time >= c.ends)
    AND NOT EXISTS (SELECT 1 FROM public.blocked_times b WHERE b.horse_id = h.id
      AND b.date = p_date AND p_date + b.start_time < c.ends AND p_date + b.end_time > c.starts)
    AND NOT EXISTS (SELECT 1 FROM public.bookings b WHERE b.horse_id = h.id
      AND b.status IN ('pending','confirmed') AND b.deleted_at IS NULL
      AND b.start_time < c.ends AND b.end_time > c.starts)
  ) >= c.required_horse_count
ORDER BY c.starts;
$$;
REVOKE ALL ON FUNCTION public.get_bookable_slots(text,date) FROM PUBLIC;
GRANT EXECUTE ON FUNCTION public.get_bookable_slots(text,date) TO anon, authenticated;
CREATE INDEX IF NOT EXISTS idx_instructor_availability_day_active ON public.instructor_availability(day_of_week) WHERE active;
CREATE INDEX IF NOT EXISTS idx_horse_recurring_avail_day_active ON public.horse_recurring_availability(day_of_week,horse_id) WHERE active;
CREATE INDEX IF NOT EXISTS idx_blocked_times_date_range ON public.blocked_times(date,start_time,end_time);
NOTIFY pgrst, 'reload schema';
