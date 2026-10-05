-- Phase 2D prerequisites must precede the RPC (lesson IDs are TEXT).
ALTER TABLE public.lesson_types
  ADD COLUMN required_horse_count integer NOT NULL DEFAULT 1,
  ADD CONSTRAINT required_horse_count_positive CHECK (required_horse_count >= 1);
UPDATE public.lesson_types SET required_horse_count = 2
WHERE max_group_size = 2 OR name ~* '(sibling|joined)';

CREATE TABLE public.business_config (
  config_key text PRIMARY KEY CHECK (config_key = 'business_timezone'),
  config_value text NOT NULL,
  updated_at timestamptz NOT NULL DEFAULT now()
);
CREATE FUNCTION public.validate_business_timezone() RETURNS trigger
LANGUAGE plpgsql SET search_path = pg_catalog AS $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_catalog.pg_timezone_names WHERE name = NEW.config_value) THEN
    RAISE EXCEPTION 'Invalid IANA business timezone';
  END IF;
  RETURN NEW;
END $$;
CREATE TRIGGER validate_business_timezone BEFORE INSERT OR UPDATE ON public.business_config
FOR EACH ROW EXECUTE FUNCTION public.validate_business_timezone();
INSERT INTO public.business_config(config_key, config_value) VALUES ('business_timezone', 'America/Denver');
ALTER TABLE public.business_config ENABLE ROW LEVEL SECURITY;
CREATE POLICY business_config_public_read ON public.business_config FOR SELECT TO anon, authenticated USING (true);
CREATE POLICY business_config_admin_update ON public.business_config FOR UPDATE TO authenticated
USING (public.is_admin(auth.uid())) WITH CHECK (public.is_admin(auth.uid()));
REVOKE ALL ON public.business_config FROM PUBLIC, anon, authenticated;
GRANT SELECT ON public.business_config TO anon, authenticated;
GRANT UPDATE ON public.business_config TO authenticated;
