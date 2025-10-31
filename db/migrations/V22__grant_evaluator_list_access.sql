INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM role r
JOIN permission p ON p.name = 'organization:list'
WHERE r.name = 'Evaluator'
  AND NOT EXISTS (
        SELECT 1
        FROM role_permission rp
        WHERE rp.role_id = r.id
          AND rp.permission_id = p.id
  );

INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM role r
JOIN permission p ON p.name = 'call_for_proposal:list'
WHERE r.name = 'Evaluator'
  AND NOT EXISTS (
        SELECT 1
        FROM role_permission rp
        WHERE rp.role_id = r.id
          AND rp.permission_id = p.id
  );

INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM role r
JOIN permission p ON p.name = 'application:list'
WHERE r.name = 'Evaluator'
  AND NOT EXISTS (
        SELECT 1
        FROM role_permission rp
        WHERE rp.role_id = r.id
          AND rp.permission_id = p.id
  );
INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM role r
JOIN permission p ON p.name = 'organization:list'
WHERE r.name = 'Evaluator'
  AND NOT EXISTS (
        SELECT 1
        FROM role_permission rp
        WHERE rp.role_id = r.id
          AND rp.permission_id = p.id
  );

INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM role r
JOIN permission p ON p.name = 'call_for_proposal:list'
WHERE r.name = 'Evaluator'
  AND NOT EXISTS (
        SELECT 1
        FROM role_permission rp
        WHERE rp.role_id = r.id
          AND rp.permission_id = p.id
  );
INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM role r
JOIN permission p ON p.name = 'organization:list'
WHERE r.name = 'Evaluator'
  AND NOT EXISTS (
        SELECT 1
        FROM role_permission rp
        WHERE rp.role_id = r.id
          AND rp.permission_id = p.id
  );

INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM role r
JOIN permission p ON p.name = 'call_for_proposal:list'
WHERE r.name = 'Evaluator'
  AND NOT EXISTS (
        SELECT 1
        FROM role_permission rp
        WHERE rp.role_id = r.id
          AND rp.permission_id = p.id
  );
