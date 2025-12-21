CREATE TABLE organization_type (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE organization ADD COLUMN type_id INT DEFAULT NULL;

INSERT INTO organization_type (name)
SELECT DISTINCT type FROM organization WHERE type IS NOT NULL AND type <> '';

UPDATE organization o
JOIN organization_type ot
  ON ot.name COLLATE utf8mb4_unicode_ci = o.type COLLATE utf8mb4_unicode_ci
SET o.type_id = ot.id;

ALTER TABLE organization
    MODIFY type_id INT NOT NULL,
    ADD CONSTRAINT fk_organization_type FOREIGN KEY (type_id) REFERENCES organization_type(id);

ALTER TABLE organization DROP COLUMN type;

INSERT INTO permission (name, description) VALUES
('organization_type:manage', 'Permission to manage organization types');

INSERT INTO role_permission (role_id, permission_id)
VALUES (
    (SELECT id FROM role WHERE name = 'Admin' LIMIT 1),
    (SELECT id FROM permission WHERE name = 'organization_type:manage' LIMIT 1)
);
