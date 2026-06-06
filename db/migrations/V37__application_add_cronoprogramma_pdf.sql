ALTER TABLE application
    ADD COLUMN cronoprogramma_pdf_path VARCHAR(255) DEFAULT NULL AFTER budget_pdf_path;
