CREATE TABLE organization (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(255) NOT NULL,
    incorporation_date DATE,
    full_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO permission (name, description) VALUES
('organization:create', 'Permission to create an organization'),
('organization:update', 'Permission to update an organization'),
('organization:delete', 'Permission to delete an organization'),
('organization:list', 'Permission to list organizations');

INSERT INTO role_permission (role_id, permission_id) VALUES
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'organization:create')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'organization:update')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'organization:delete')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'organization:list'));