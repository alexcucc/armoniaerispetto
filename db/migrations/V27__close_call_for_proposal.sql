ALTER TABLE call_for_proposal
    ADD status ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    ADD closed_at DATETIME NULL;
