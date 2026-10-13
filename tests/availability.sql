\set ON_ERROR_STOP on
BEGIN;
-- Transaction-local fixtures: all changes (including existing schedules) roll back.
UPDATE public.instructor_availability SET active=false;
INSERT INTO public.lesson_types(id,name,duration_minutes,price,required_horse_count) VALUES ('test-lesson','Test private',60,1,1);
INSERT INTO public.customers(id,first_name,last_name,email) VALUES ('test-customer','Test','Customer','test@example.com');
INSERT INTO public.horses(id,name) VALUES ('test-h1','Availability Test One'),('test-h2','Availability Test Two'),('test-h3','Availability Test Three');
INSERT INTO public.horse_lesson_types(id,horse_id,lesson_type_id) SELECT 'test-e-'||id,id,'test-lesson' FROM public.horses WHERE id LIKE 'test-h%';
INSERT INTO public.instructor_availability(id,day_of_week,start_time,end_time) VALUES ('test-i',extract(dow from date '2099-01-05'), '09:00','12:00');
INSERT INTO public.horse_recurring_availability(id,horse_id,day_of_week,start_time,end_time) SELECT 'test-a-'||id,id,extract(dow from date '2099-01-05'),'09:00','12:00' FROM public.horses WHERE id LIKE 'test-h%';
UPDATE public.horses SET active=false WHERE id IN ('test-h2','test-h3');
CREATE FUNCTION pg_temp.check_result(label text, ok boolean) RETURNS void LANGUAGE plpgsql AS $$ BEGIN
 IF ok IS DISTINCT FROM true THEN RAISE EXCEPTION 'FAIL: %', label; END IF;
 RAISE NOTICE 'PASS: %', label;
END $$;

SELECT pg_temp.check_result('01 instructor candidates', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=5);
UPDATE instructor_availability SET active=false WHERE id='test-i';
SELECT pg_temp.check_result('02 unavailable instructor', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=0);
UPDATE instructor_availability SET active=true,end_time='10:00' WHERE id='test-i'; INSERT INTO instructor_availability(id,day_of_week,start_time,end_time) VALUES ('test-i2',extract(dow from date '2099-01-05'),'11:00','12:00');
SELECT pg_temp.check_result('03 multiple windows', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=2);
SELECT pg_temp.check_result('04 gap remains unavailable', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='10:00'));
SELECT pg_temp.check_result('05 duration cannot extend window', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:30'));
INSERT INTO blocked_times(id,date,start_time,end_time) VALUES ('test-block','2099-01-05','09:30','10:00');
SELECT pg_temp.check_result('06 instructor overlap removed', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00'));
UPDATE blocked_times SET start_time='10:00',end_time='11:00' WHERE id='test-block';
SELECT pg_temp.check_result('07 back to back block boundary', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=2);
DELETE FROM blocked_times WHERE id='test-block'; DELETE FROM instructor_availability WHERE id='test-i2'; UPDATE instructor_availability SET end_time='12:00' WHERE id='test-i';
SELECT pg_temp.check_result('08 eligible active horse', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=5);
UPDATE horse_lesson_types SET eligible=false WHERE id='test-e-test-h1';
SELECT pg_temp.check_result('09 no eligible horse', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=0);
UPDATE horse_lesson_types SET eligible=true WHERE id='test-e-test-h1'; UPDATE horses SET active=false WHERE id='test-h1';
SELECT pg_temp.check_result('10 inactive horse excluded', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=0);
UPDATE horses SET active=true WHERE id='test-h1'; DELETE FROM horse_recurring_availability WHERE id='test-a-test-h1';
SELECT pg_temp.check_result('11 zero recurring rows unavailable', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=0);
INSERT INTO horse_recurring_availability(id,horse_id,day_of_week,start_time,end_time) VALUES ('test-a-test-h1','test-h1',extract(dow from date '2099-01-05'),'13:00','14:00');
SELECT pg_temp.check_result('12 horse outside window', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=0);
UPDATE horse_recurring_availability SET start_time='09:00',end_time='12:00' WHERE id='test-a-test-h1'; INSERT INTO blocked_times(id,horse_id,date,start_time,end_time) VALUES ('test-hblock','test-h1','2099-01-05','09:00','10:00');
SELECT pg_temp.check_result('13 horse block removes horse', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00'));
UPDATE horses SET active=true WHERE id='test-h2';
SELECT pg_temp.check_result('14 second horse preserves slot', EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00'));
UPDATE horses SET active=false WHERE id='test-h2'; DELETE FROM blocked_times WHERE id='test-hblock'; UPDATE horse_recurring_availability SET end_time='09:30' WHERE id='test-a-test-h1';
SELECT pg_temp.check_result('15 horse must cover full lesson', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=0);
UPDATE horse_recurring_availability SET end_time='12:00' WHERE id='test-a-test-h1';
SELECT pg_temp.check_result('16 requires one with one', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=5);
UPDATE horses SET active=false WHERE id='test-h1';
SELECT pg_temp.check_result('17 requires one with zero', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=0);
UPDATE horses SET active=true WHERE id IN ('test-h1','test-h2'); UPDATE lesson_types SET required_horse_count=2 WHERE id='test-lesson';
SELECT pg_temp.check_result('18 requires two with two', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=5);
UPDATE horses SET active=false WHERE id='test-h2';
SELECT pg_temp.check_result('19 requires two with one', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=0);
UPDATE horses SET active=true WHERE id IN ('test-h2','test-h3');
SELECT pg_temp.check_result('20 requires two with three', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=5);
UPDATE horses SET active=false WHERE id='test-h3'; INSERT INTO blocked_times(id,horse_id,date,start_time,end_time) VALUES ('test-hblock','test-h1','2099-01-05','09:00','10:00');
SELECT pg_temp.check_result('21 two with one blocked', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00'));
UPDATE horses SET active=true WHERE id='test-h3';
SELECT pg_temp.check_result('22 three with one blocked', EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00'));
DELETE FROM blocked_times WHERE id='test-hblock'; INSERT INTO bookings(id,customer_id,lesson_type_id,horse_id,start_time,end_time,status) VALUES ('test-booking','test-customer','test-lesson','test-h1','2099-01-05 09:00','2099-01-05 10:00','confirmed'); INSERT INTO booking_horses(id,booking_id,horse_id) VALUES ('test-bh','test-booking','test-h1');
SELECT pg_temp.check_result('23 multi horse booking also occupies instructor', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00'));
UPDATE bookings SET status='pending' WHERE id='test-booking';
SELECT pg_temp.check_result('24 pending blocks', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00'));
UPDATE bookings SET status='confirmed' WHERE id='test-booking';
SELECT pg_temp.check_result('25 confirmed blocks', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00'));
UPDATE bookings SET status='cancelled' WHERE id='test-booking';
SELECT pg_temp.check_result('26 cancelled restores slot', EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00'));
UPDATE bookings SET status='completed' WHERE id='test-booking';
SELECT pg_temp.check_result('27 completed does not block', EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00'));
UPDATE bookings SET status='confirmed' WHERE id='test-booking';
SELECT pg_temp.check_result('28 adjacent booking allowed', EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='10:00'));
SELECT pg_temp.check_result('29 overlap rejected', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:30'));
DELETE FROM bookings WHERE id='test-booking'; UPDATE lesson_types SET active=false WHERE id='test-lesson';
SELECT pg_temp.check_result('30 inactive lesson', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=0);
SELECT pg_temp.check_result('31 invalid lesson safe', (SELECT count(*) FROM public.get_bookable_slots('not-a-uuid-or-id','2099-01-05'))=0);
UPDATE lesson_types SET active=true,duration_minutes=90 WHERE id='test-lesson';
SELECT pg_temp.check_result('32 duration respected', (SELECT end_time FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00')='10:30');
UPDATE horses SET active=false WHERE id IN ('test-h2','test-h3'); UPDATE lesson_types SET duration_minutes=60,required_horse_count=1 WHERE id='test-lesson';
SELECT pg_temp.check_result('33 changing horse count changes result', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=5);
SELECT pg_temp.check_result('34 past date', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2000-01-01'))=0);
-- booking_horses is authoritative for horse conflicts; create_guest_booking must agree with get_bookable_slots.
CREATE FUNCTION pg_temp.book(t timestamp, email text) RETURNS jsonb LANGUAGE sql AS $$
  SELECT public.create_guest_booking('test-lesson', t, t + make_interval(mins => (SELECT duration_minutes FROM public.lesson_types WHERE id='test-lesson')),
    'Test','Guest',email,NULL,'[{"firstName":"Test","lastName":"Rider","dateOfBirth":"2010-01-01"}]') $$;
-- Every candidate start: offered by get_bookable_slots <=> create_guest_booking succeeds (attempts rolled back).
CREATE FUNCTION pg_temp.rpcs_agree() RETURNS boolean LANGUAGE plpgsql AS $$
DECLARE t timestamp; offered boolean; booked boolean; BEGIN
  FOR t IN SELECT generate_series(timestamp '2099-01-05 09:00', timestamp '2099-01-05 11:30', interval '30 minutes') LOOP
    offered := EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time=to_char(t,'HH24:MI'));
    BEGIN
      booked := (pg_temp.book(t,'agree@example.com')->>'success') IS NOT NULL;
      RAISE EXCEPTION 'rollback attempt';
    EXCEPTION WHEN raise_exception THEN NULL; END;
    IF offered IS DISTINCT FROM booked THEN RAISE NOTICE 'disagree at %: offered=% booked=%', t, offered, booked; RETURN false; END IF;
  END LOOP;
  RETURN true;
END $$;
UPDATE horse_recurring_availability SET active=false WHERE id='test-a-test-h1';
SELECT pg_temp.check_result('58 inactive horse window not counted', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-01-05'))=0);
SELECT pg_temp.check_result('59 RPCs agree with no horse', pg_temp.rpcs_agree());
UPDATE horse_recurring_availability SET active=true WHERE id='test-a-test-h1'; UPDATE horses SET active=true WHERE id IN ('test-h2','test-h3');
SELECT pg_temp.check_result('60 one-horse booking created', (pg_temp.book('2099-01-05 09:00','one@example.com')->>'success')='true');
SELECT pg_temp.check_result('61 one-horse booking reserves its horse via booking_horses', (SELECT array_agg(bh.horse_id) FROM booking_horses bh JOIN bookings b ON b.id=bh.booking_id WHERE b.start_time='2099-01-05 09:00')=ARRAY['test-h1']);
SELECT pg_temp.check_result('62 booked horse removed from availability', (SELECT array_agg(id) FROM public.available_horse_ids('test-lesson','2099-01-05 09:30','2099-01-05 10:30') id)=ARRAY['test-h2','test-h3']);
SELECT pg_temp.check_result('63 booked time no longer offered or bookable', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='09:00')
  AND pg_temp.book('2099-01-05 09:00','dup@example.com')->>'error'='The requested time is no longer available');
UPDATE bookings SET status='cancelled' WHERE start_time='2099-01-05 09:00';
SELECT pg_temp.check_result('64 cancelled booking releases horse', (SELECT count(*) FROM public.available_horse_ids('test-lesson','2099-01-05 09:00','2099-01-05 10:00'))=3);
UPDATE bookings SET status='completed' WHERE start_time='2099-01-05 09:00';
SELECT pg_temp.check_result('65 completed booking does not hold horse', (SELECT count(*) FROM public.available_horse_ids('test-lesson','2099-01-05 09:00','2099-01-05 10:00'))=3);
UPDATE lesson_types SET required_horse_count=2 WHERE id='test-lesson';
SELECT pg_temp.check_result('66 two-horse booking created', (pg_temp.book('2099-01-05 10:00','two@example.com')->>'success')='true');
SELECT pg_temp.check_result('67 two-horse booking reserves two distinct horses', (SELECT count(DISTINCT bh.horse_id) FROM booking_horses bh JOIN bookings b ON b.id=bh.booking_id WHERE b.start_time='2099-01-05 10:00' AND b.status='pending')=2);
SELECT pg_temp.check_result('68 both booked horses excluded from overlapping availability', (SELECT array_agg(id) FROM public.available_horse_ids('test-lesson','2099-01-05 10:30','2099-01-05 11:30') id)=ARRAY['test-h3']);
SELECT pg_temp.check_result('69 RPCs agree after two-horse booking', pg_temp.rpcs_agree());
-- Horse count is the deciding factor at 11:00 (one of the two free horses blocked).
INSERT INTO blocked_times(id,horse_id,date,start_time,end_time) VALUES ('test-hblock2','test-h3','2099-01-05','11:00','12:00');
SELECT pg_temp.check_result('70 RPCs agree when horse count decides', pg_temp.rpcs_agree());
DELETE FROM blocked_times WHERE id='test-hblock2';
-- Legacy bookings.horse_id alone no longer reserves a horse (booking_horses is authoritative); instructor still blocked.
INSERT INTO bookings(id,customer_id,lesson_type_id,horse_id,start_time,end_time,status) VALUES ('test-legacy','test-customer','test-lesson','test-h3','2099-01-05 11:00','2099-01-05 12:00','confirmed');
SELECT pg_temp.check_result('71 bookings.horse_id without booking_horses ignored for horses', EXISTS(SELECT 1 FROM public.available_horse_ids('test-lesson','2099-01-05 11:00','2099-01-05 12:00') id WHERE id='test-h3')
  AND NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-01-05') WHERE start_time='11:00'));
DELETE FROM bookings WHERE id='test-legacy' OR start_time IN ('2099-01-05 09:00','2099-01-05 10:00'); UPDATE lesson_types SET required_horse_count=1 WHERE id='test-lesson'; UPDATE horses SET active=false WHERE id IN ('test-h2','test-h3');
INSERT INTO instructor_availability(id,day_of_week,start_time,end_time) VALUES ('test-time',extract(dow from (now() at time zone 'Pacific/Kiritimati')::date),'00:00','01:00') ON CONFLICT(day_of_week,start_time) DO UPDATE SET active=true; INSERT INTO horse_recurring_availability(id,horse_id,day_of_week,start_time,end_time) VALUES ('test-time-h','test-h1',extract(dow from (now() at time zone 'Pacific/Kiritimati')::date),'00:00','01:00'); UPDATE business_config SET config_value='Pacific/Kiritimati';
SELECT pg_temp.check_result('35 business timezone rejects elapsed local midnight', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson',(now() AT TIME ZONE 'Pacific/Kiritimati')::date) WHERE start_time='00:00'));
UPDATE business_config SET config_value='America/Denver';
SELECT pg_temp.check_result('36 Denver configuration used', (SELECT config_value FROM business_config WHERE config_key='business_timezone')='America/Denver');
UPDATE instructor_availability SET active=false; INSERT INTO instructor_availability(id,day_of_week,start_time,end_time) VALUES ('test-dst',0,'01:00','04:00'); INSERT INTO horse_recurring_availability(id,horse_id,day_of_week,start_time,end_time) VALUES ('test-dst-h','test-h1',0,'01:00','04:00');
SELECT pg_temp.check_result('37 DST spring gap excluded', NOT EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-03-08') WHERE start_time IN ('01:30','02:00','02:30')) AND EXISTS(SELECT 1 FROM public.get_bookable_slots('test-lesson','2099-03-08') WHERE start_time='03:00'));
SET LOCAL ROLE anon;
SELECT pg_temp.check_result('38 anon executes RPC', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-03-08'))>0);
SELECT pg_temp.check_result('39 anon cannot read instructor_availability', (SELECT count(*) FROM public.instructor_availability)=0);
SELECT pg_temp.check_result('40 anon cannot read horse_recurring_availability', (SELECT count(*) FROM public.horse_recurring_availability)=0);
SELECT pg_temp.check_result('41 anon cannot read blocked_times', (SELECT count(*) FROM public.blocked_times)=0);
SELECT pg_temp.check_result('72 anon cannot call internal horse helper', NOT has_function_privilege('anon','public.available_horse_ids(text,timestamp,timestamp)','EXECUTE'));
SELECT pg_temp.check_result('42 anon cannot read bookings', (SELECT count(*) FROM public.bookings)=0);
SELECT pg_temp.check_result('43 anon cannot read customers', (SELECT count(*) FROM public.customers)=0);
SELECT pg_temp.check_result('44 anon cannot read riders', (SELECT count(*) FROM public.riders)=0);
SELECT pg_temp.check_result('45 only safe RPC output', (SELECT array_agg(key ORDER BY key) FROM jsonb_object_keys((SELECT to_jsonb(s) FROM public.get_bookable_slots('test-lesson','2099-03-08') s LIMIT 1)) key)=ARRAY['available','end_time','start_time']);
RESET ROLE;
SELECT pg_temp.check_result('46 fall DST repeated hour emitted once', (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-11-01') WHERE start_time='01:30')=1);
SELECT pg_temp.check_result('47 text RPC signature', to_regprocedure('public.get_bookable_slots(text,date)') IS NOT NULL);
SELECT pg_temp.check_result('48 PUBLIC execute revoked', NOT EXISTS(SELECT 1 FROM pg_proc p CROSS JOIN LATERAL aclexplode(p.proacl) a WHERE p.oid='public.get_bookable_slots(text,date)'::regprocedure AND a.grantee=0 AND a.privilege_type='EXECUTE'));
SELECT pg_temp.check_result('49 business config RLS enabled', (SELECT relrowsecurity FROM pg_class WHERE oid='public.business_config'::regclass));
SET LOCAL ROLE anon;
SELECT pg_temp.check_result('50 anon cannot read admin authorization records', (SELECT count(*) FROM public.admin_profiles)=0);
DO $$ BEGIN
  BEGIN
    INSERT INTO public.admin_profiles(id,auth_user_id,email) VALUES ('evil',gen_random_uuid(),'evil@example.com');
    RAISE EXCEPTION 'FAIL: anonymous admin promotion allowed';
  EXCEPTION WHEN insufficient_privilege THEN RAISE NOTICE 'PASS: 51 anonymous admin promotion denied'; END;
END $$;
RESET ROLE;
SET LOCAL ROLE authenticated;
WITH changed AS (UPDATE public.business_config SET config_value='UTC' RETURNING *)
SELECT pg_temp.check_result('52 nonadmin cannot change timezone', (SELECT count(*) FROM changed)=0);
DO $$ BEGIN
  BEGIN
    INSERT INTO public.admin_profiles(id,auth_user_id,email) VALUES ('evil',gen_random_uuid(),'evil@example.com');
    RAISE EXCEPTION 'FAIL: authenticated admin promotion allowed';
  EXCEPTION WHEN insufficient_privilege THEN RAISE NOTICE 'PASS: 53 authenticated admin promotion denied'; END;
END $$;
RESET ROLE;
DO $$ BEGIN
  BEGIN UPDATE public.lesson_types SET required_horse_count=0 WHERE id='test-lesson'; RAISE EXCEPTION 'FAIL: zero horse requirement';
  EXCEPTION WHEN check_violation THEN RAISE NOTICE 'PASS: 54 zero horse requirement rejected'; END;
  BEGIN UPDATE public.business_config SET config_value='invalid/timezone'; RAISE EXCEPTION 'FAIL: invalid timezone accepted';
  EXCEPTION WHEN raise_exception THEN
    IF SQLERRM <> 'Invalid IANA business timezone' THEN RAISE; END IF;
    RAISE NOTICE 'PASS: 55 invalid timezone rejected'; END;
END $$;
INSERT INTO public.instructor_availability(id,day_of_week,start_time,end_time) VALUES ('test-duplicate-window',0,'01:30','04:00');
SELECT pg_temp.check_result('56 overlapping instructor windows emit distinct slots',
 (SELECT count(*)=count(DISTINCT start_time) FROM public.get_bookable_slots('test-lesson','2099-11-01')));
UPDATE public.lesson_types SET duration_minutes=1500 WHERE id='test-lesson';
SELECT pg_temp.check_result('57 duration beyond day cannot wrap time',
 (SELECT count(*) FROM public.get_bookable_slots('test-lesson','2099-11-01'))=0);
ROLLBACK;
