ALTER TABLE application
    ADD COLUMN organization_id INT NOT NULL,
    ADD COLUMN project_name VARCHAR(255) NOT NULL,
    ADD COLUMN project_description TEXT NOT NULL,
    ADD CONSTRAINT fk_application_organization FOREIGN KEY (organization_id) REFERENCES organization(id) ON DELETE CASCADE;

ALTER TABLE application
    ADD COLUMN supervisor_id INT NOT NULL,
    ADD CONSTRAINT fk_application_supervisor FOREIGN KEY (supervisor_id) REFERENCES supervisor(id) ON DELETE CASCADE;

INSERT INTO permission (name, description) VALUES
('application:create', 'Permission to create an application'),
('application:update', 'Permission to update an application'),
('application:delete', 'Permission to delete an application'),
('application:list',   'Permission to list applications');

INSERT INTO role_permission (role_id, permission_id) VALUES
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'application:create')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'application:update')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'application:delete')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'application:list'));
