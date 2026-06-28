ALTER TABLE evaluation_v4_general
    DROP COLUMN notes,
    DROP COLUMN rejection_reason;

ALTER TABLE evaluation_v4_proposing_entity
    DROP COLUMN notes;

ALTER TABLE evaluation_v4_project
    DROP COLUMN notes;

ALTER TABLE evaluation_v4_financial_plan
    DROP COLUMN notes;

ALTER TABLE evaluation_v4_qualitative_elements
    DROP COLUMN notes;

ALTER TABLE evaluation_v4_thematic_safeguard
    DROP COLUMN notes;

ALTER TABLE evaluation_v4_thematic_repopulation
    DROP COLUMN notes;

ALTER TABLE evaluation_v4_thematic_cohabitation
    DROP COLUMN notes;

ALTER TABLE evaluation_v4_thematic_community_support
    DROP COLUMN notes;

ALTER TABLE evaluation_v4_thematic_culture_education
    DROP COLUMN notes;
