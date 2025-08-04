CREATE TABLE role (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
CREATE TABLE user_role (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE
);
CREATE TABLE permission (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
CREATE TABLE role_permission (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permission(id) ON DELETE CASCADE
);

INSERT INTO role (name, description) VALUES
('Admin', 'Administrator with full access'),
('Collaborator', 'A collaborator with limited access'),
('Organization', 'An organization registered user');

INSERT INTO permission (name, description) VALUES
-- evaluations
('evaluation:create', 'Permission to create an evaluation'),
('evaluation:update', 'Permission to update an evaluation'),
('evaluation:delete', 'Permission to delete an evaluation'),
('evaluation:view', 'Permission to view an evaluation'),
-- users
('user:read', 'Permission to view a user'),
('user:delete', 'Permission to delete a user'),
('user:list', 'Permission to list users'),
-- roles
('role:create', 'Permission to create a role'),
('role:update', 'Permission to update a role'),
('role:read', 'Permission to view a role'),
('role:delete', 'Permission to delete a role'),
('role:list', 'Permission to list roles');

INSERT INTO role_permission (role_id, permission_id) VALUES
-- Admin permissions
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'evaluation:create')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'evaluation:update')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'evaluation:delete')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'evaluation:view')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'user:read')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'user:delete')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'user:list')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'role:create')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'role:update')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'role:read')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'role:delete')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'role:list')),
-- Collaborator permissions
((SELECT id FROM role WHERE name = 'Collaborator'), (SELECT id FROM permission WHERE name = 'evaluation:create')),
((SELECT id FROM role WHERE name = 'Collaborator'), (SELECT id FROM permission WHERE name = 'evaluation:update')),
((SELECT id FROM role WHERE name = 'Collaborator'), (SELECT id FROM permission WHERE name = 'evaluation:view')),
((SELECT id FROM role WHERE name = 'Collaborator'), (SELECT id FROM permission WHERE name = 'user:read')),
((SELECT id FROM role WHERE name = 'Collaborator'), (SELECT id FROM permission WHERE name = 'user:delete'));

--INSERT INTO user_role (user_id, role_id) VALUES
-- Assigning roles to users
--((SELECT id FROM user WHERE email = 'alex.cucc@hotmail.it'), (SELECT id FROM role WHERE name = 'Admin')),
--((SELECT id FROM user WHERE email = 'mthcucco@gmail.com'), (SELECT id FROM role WHERE name = 'Admin'));
