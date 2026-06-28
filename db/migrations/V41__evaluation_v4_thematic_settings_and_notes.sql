ALTER TABLE call_for_proposal
    ADD COLUMN enable_thematic_safeguard TINYINT(1) NOT NULL DEFAULT 1 AFTER winner_publication_status,
    ADD COLUMN enable_thematic_repopulation TINYINT(1) NOT NULL DEFAULT 1 AFTER enable_thematic_safeguard,
    ADD COLUMN enable_thematic_cohabitation TINYINT(1) NOT NULL DEFAULT 1 AFTER enable_thematic_repopulation,
    ADD COLUMN enable_thematic_community_support TINYINT(1) NOT NULL DEFAULT 1 AFTER enable_thematic_cohabitation,
    ADD COLUMN enable_thematic_culture_education TINYINT(1) NOT NULL DEFAULT 1 AFTER enable_thematic_community_support;

ALTER TABLE evaluation_v4_general
    ADD COLUMN notes TEXT NULL AFTER overall_score,
    ADD COLUMN rejection_reason TEXT NULL AFTER notes;

ALTER TABLE evaluation_v4_proposing_entity
    ADD COLUMN notes TEXT NULL AFTER overall_score;

ALTER TABLE evaluation_v4_project
    ADD COLUMN notes TEXT NULL AFTER overall_score;

ALTER TABLE evaluation_v4_financial_plan
    ADD COLUMN notes TEXT NULL AFTER overall_score;

ALTER TABLE evaluation_v4_qualitative_elements
    ADD COLUMN notes TEXT NULL AFTER overall_score;

ALTER TABLE evaluation_v4_thematic_safeguard
    ADD COLUMN notes TEXT NULL AFTER weighted_score;

ALTER TABLE evaluation_v4_thematic_repopulation
    ADD COLUMN notes TEXT NULL AFTER weighted_score;

ALTER TABLE evaluation_v4_thematic_cohabitation
    ADD COLUMN notes TEXT NULL AFTER weighted_score;

ALTER TABLE evaluation_v4_thematic_community_support
    ADD COLUMN notes TEXT NULL AFTER weighted_score;

ALTER TABLE evaluation_v4_thematic_culture_education
    ADD COLUMN notes TEXT NULL AFTER weighted_score;
