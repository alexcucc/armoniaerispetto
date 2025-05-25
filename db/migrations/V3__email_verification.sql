-- Add email_verified to user table
ALTER TABLE user 
ADD COLUMN email_verified TINYINT(1) DEFAULT 0;

-- Create email_verification_token table
CREATE TABLE email_verification_token (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT uk_token UNIQUE (token)
);

-- Add indexes for better performance
CREATE INDEX idx_token ON email_verification_token (token);
CREATE INDEX idx_user_id ON email_verification_token (user_id);
CREATE INDEX idx_expires_at ON email_verification_token (expires_at);

-- Mark email verified existing users
UPDATE user
SET email_verified = 1;