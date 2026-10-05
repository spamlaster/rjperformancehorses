-- Audit found the authorization source itself was not protected in Phase 2B.
-- Prevent anonymous reads and client-side promotion to admin; provisioning remains service-only.
ALTER TABLE public.admin_profiles ENABLE ROW LEVEL SECURITY;
CREATE POLICY admin_profiles_read_own ON public.admin_profiles
FOR SELECT TO authenticated USING (auth_user_id = auth.uid());
REVOKE ALL ON public.admin_profiles FROM PUBLIC, anon, authenticated;
GRANT SELECT ON public.admin_profiles TO anon, authenticated;
-- Keep invoker semantics: authenticated users can see their own admin record only.
ALTER FUNCTION public.is_admin(uuid) SET search_path = pg_catalog, public;
