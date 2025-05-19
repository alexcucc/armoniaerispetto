ALTER TABLE user
DROP COLUMN username;

ALTER TABLE user
ADD COLUMN first_name VARCHAR(255) NOT NULL,
ADD COLUMN last_name VARCHAR(255) NOT NULL;

UPDATE user
SET first_name = firstname,
    last_name = lastname;

ALTER TABLE user
DROP COLUMN firstname,
DROP COLUMN lastname;
