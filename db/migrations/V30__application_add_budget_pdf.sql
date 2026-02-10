ALTER TABLE application
    ADD COLUMN budget_pdf_path VARCHAR(255) DEFAULT NULL AFTER application_pdf_path;
