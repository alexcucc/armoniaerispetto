ALTER TABLE call_for_proposal
    ADD pdf_path VARCHAR(255) NOT NULL;

ALTER TABLE call_for_proposal
    DROP COLUMN url;
