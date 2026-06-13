CREATE TABLE call_for_proposal_winner (
    id BIGINT NOT NULL AUTO_INCREMENT,
    call_for_proposal_id BIGINT NOT NULL,
    application_id BIGINT NOT NULL,
    display_order INT NOT NULL,
    public_title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_call_for_proposal_winner_call
        FOREIGN KEY (call_for_proposal_id) REFERENCES call_for_proposal(id) ON DELETE CASCADE,
    CONSTRAINT fk_call_for_proposal_winner_application
        FOREIGN KEY (application_id) REFERENCES application(id) ON DELETE CASCADE,
    CONSTRAINT uq_call_for_proposal_winner_application UNIQUE (call_for_proposal_id, application_id),
    CONSTRAINT uq_call_for_proposal_winner_display_order UNIQUE (call_for_proposal_id, display_order)
);

CREATE INDEX idx_call_for_proposal_winner_call_id
    ON call_for_proposal_winner (call_for_proposal_id);

CREATE TABLE call_for_proposal_winner_image (
    id BIGINT NOT NULL AUTO_INCREMENT,
    winner_id BIGINT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NOT NULL,
    caption VARCHAR(255) NULL,
    display_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_call_for_proposal_winner_image_winner
        FOREIGN KEY (winner_id) REFERENCES call_for_proposal_winner(id) ON DELETE CASCADE,
    CONSTRAINT uq_call_for_proposal_winner_image_display_order UNIQUE (winner_id, display_order)
);

CREATE INDEX idx_call_for_proposal_winner_image_winner_id
    ON call_for_proposal_winner_image (winner_id);
