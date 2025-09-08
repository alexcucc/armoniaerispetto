INSERT INTO permission (name, description) VALUES
('application:review', 'Permission to review an application');

INSERT INTO role_permission (role_id, permission_id) VALUES
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'application:review')),
((SELECT id FROM role WHERE name = 'Supervisor'), (SELECT id FROM permission WHERE name = 'application:review'));

INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM role r, permission p
WHERE r.name = 'Supervisor' AND p.name = 'application:list'
  AND NOT EXISTS (
      SELECT 1 FROM role_permission rp WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

ALTER TABLE application
    ADD COLUMN checklist_path VARCHAR(255) DEFAULT NULL;
