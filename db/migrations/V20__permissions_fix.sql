DELETE FROM role_permission 
WHERE
	role_id = (SELECT id FROM role WHERE name = 'Admin') AND
	permission_id = (SELECT id FROM permission WHERE name = 'application:review');
DELETE FROM role_permission 
WHERE
	role_id = (SELECT id FROM role WHERE name = 'Admin') AND
	permission_id = (SELECT id FROM permission WHERE name = 'evaluation:create');
DELETE FROM role_permission 
WHERE
	role_id = (SELECT id FROM role WHERE name = 'Supervisor') AND
	permission_id = (SELECT id FROM permission WHERE name = 'application:list');
INSERT INTO role_permission(role_id, permission_id) VALUES
((SELECT id FROM role WHERE name = 'Evaluator'), (SELECT id FROM permission WHERE name = 'evaluation:create'));
