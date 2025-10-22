INSERT INTO permission (name, description)
SELECT 'user:impersonate', 'Permission to impersonate another user'
WHERE NOT EXISTS (
    SELECT 1 FROM permission WHERE name = 'user:impersonate'
);

INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM role r
CROSS JOIN permission p
WHERE r.name = 'Admin'
  AND p.name = 'user:impersonate'
  AND NOT EXISTS (
      SELECT 1 FROM role_permission
      WHERE role_id = r.id
        AND permission_id = p.id
  );
