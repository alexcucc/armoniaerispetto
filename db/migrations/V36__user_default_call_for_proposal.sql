ALTER TABLE user
ADD COLUMN default_call_for_proposal_id INT NULL;

ALTER TABLE user
ADD CONSTRAINT fk_user_default_call_for_proposal
FOREIGN KEY (default_call_for_proposal_id) REFERENCES call_for_proposal(id) ON DELETE SET NULL;

CREATE INDEX idx_user_default_call_for_proposal_id
ON user (default_call_for_proposal_id);
