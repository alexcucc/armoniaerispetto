INSERT INTO permission (name, description)
SELECT 'supervisor:monitor', 'Permission to monitor supervisor performance'
WHERE NOT EXISTS (
    SELECT 1 FROM permission WHERE name = 'supervisor:monitor'
);

INSERT INTO permission (name, description)
SELECT 'evaluator:monitor', 'Permission to monitor evaluator performance'
WHERE NOT EXISTS (
    SELECT 1 FROM permission WHERE name = 'evaluator:monitor'
);

INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM role r
JOIN permission p ON p.name = 'supervisor:monitor'
WHERE r.name = 'Admin'
  AND NOT EXISTS (
      SELECT 1
      FROM role_permission rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );

INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM role r
JOIN permission p ON p.name = 'evaluator:monitor'
WHERE r.name = 'Admin'
  AND NOT EXISTS (
      SELECT 1
      FROM role_permission rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );
