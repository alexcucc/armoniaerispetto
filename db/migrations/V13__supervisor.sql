CREATE TABLE supervisor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

INSERT INTO permission (name, description) VALUES
('supervisor:create', 'Permission to create a supervisor'),
('supervisor:delete', 'Permission to delete a supervisor'),
('supervisor:list', 'Permission to list supervisors');

INSERT INTO role_permission (role_id, permission_id) VALUES
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'supervisor:create')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'supervisor:delete')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'supervisor:list'));

INSERT INTO role (name, description) VALUES ('Supervisor', 'User who can supervise events');
