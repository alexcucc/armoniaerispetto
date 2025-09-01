INSERT INTO permission (name, description) VALUES
('call_for_proposal:create', 'Permission to create a call for proposal'),
('call_for_proposal:update', 'Permission to update a call for proposal'),
('call_for_proposal:delete', 'Permission to delete a call for proposal'),
('call_for_proposal:list', 'Permission to list calls for proposals');

INSERT INTO role_permission (role_id, permission_id) VALUES
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'call_for_proposal:create')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'call_for_proposal:update')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'call_for_proposal:delete')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'call_for_proposal:list'));
