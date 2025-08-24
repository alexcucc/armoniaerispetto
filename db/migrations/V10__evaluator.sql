CREATE TABLE evaluator (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

INSERT INTO permission (name, description) VALUES
('evaluator:create', 'Permission to create an evaluator'),
('evaluator:delete', 'Permission to delete an evaluator'),
('evaluator:list', 'Permission to list evaluators');

INSERT INTO role_permission (role_id, permission_id) VALUES
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'evaluator:create')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'evaluator:delete')),
((SELECT id FROM role WHERE name = 'Admin'), (SELECT id FROM permission WHERE name = 'evaluator:list'));

INSERT INTO role (name, description) VALUES ('Evaluator', 'User who can evaluate submissions');
