CREATE TABLE call_for_proposal_evaluator (
    call_for_proposal_id BIGINT NOT NULL,
    evaluator_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (call_for_proposal_id, evaluator_user_id),
    CONSTRAINT fk_call_for_proposal_evaluator_call
        FOREIGN KEY (call_for_proposal_id) REFERENCES call_for_proposal(id) ON DELETE CASCADE,
    CONSTRAINT fk_call_for_proposal_evaluator_evaluator
        FOREIGN KEY (evaluator_user_id) REFERENCES evaluator(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_call_for_proposal_evaluator_evaluator_user
    ON call_for_proposal_evaluator (evaluator_user_id);

INSERT INTO call_for_proposal_evaluator (call_for_proposal_id, evaluator_user_id)
SELECT c.id, e.user_id
FROM call_for_proposal c
CROSS JOIN evaluator e;
