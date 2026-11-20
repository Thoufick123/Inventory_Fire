-- Run only after schema.sql. This creates no sample data.
-- Replace both placeholders before running:
--   1) Generate a bcrypt hash using PHP:
--      php -r "echo password_hash('YOUR-STRONG-PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
--   2) Replace the hash literal below with that output.
-- The account is forced to change its password on first login.
-- Existing users/roles are not modified; this is safe to rerun.

INSERT INTO `roles`
  (`id`, `name`, `slug`, `description`, `concern_scope`, `is_active`, `is_system`)
SELECT
  1, 'Super Admin', 'super-admin', 'Full system access and user administration', 'ALL', 1, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `id` = 1)
  AND NOT EXISTS (SELECT 1 FROM `roles` WHERE `slug` IN ('super-admin', 'super_admin'));

INSERT INTO `users`
  (`username`, `emp_id`, `email`, `password_hash`, `full_name`, `role_id`,
   `department`, `staff_role`, `default_concern_id`, `reports_access`,
   `is_active`, `must_change_password`, `created_at`)
SELECT
  'admin',
  'EMP-001',
  'admin@gamil.com',
  '$2y$10$zK0b8fSdl2BQdR2OiqMtTOlsMR.5LixCWXv4Xk6MDr99vnOH0miV',
  'System Administrator',
  r.`id`,
  'ADMINISTRATION',
  'SUPER_ADMIN',
  (SELECT c.`id` FROM `concerns` c WHERE c.`id` = 1 LIMIT 1),
  'ALL',
  1,
  1,
  NOW()
FROM `roles` r
WHERE r.`id` = 1
  AND r.`slug` IN ('super-admin', 'super_admin')
  AND NOT EXISTS (
    SELECT 1
    FROM `users` u
    WHERE u.`username` = 'admin'
       OR u.`email` = 'admin@gamil.com'
  );
