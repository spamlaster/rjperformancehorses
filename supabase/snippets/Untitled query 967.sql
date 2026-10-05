INSERT INTO public.admin_profiles (
    id,
    auth_user_id,
    name,
    email,
    role,
    is_active
)
VALUES (
    gen_random_uuid()::text,
    '5c89a533-2bd4-4861-a7c8-b384fe752437',
    'Nick',
    'nick@admin.com',
    'admin',
    true
);