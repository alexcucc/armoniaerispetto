ALTER TABLE call_for_proposal
    ADD COLUMN winner_publication_status VARCHAR(16) NOT NULL DEFAULT 'DRAFT' AFTER status;

CREATE INDEX idx_call_for_proposal_winner_publication_status
    ON call_for_proposal (winner_publication_status);
