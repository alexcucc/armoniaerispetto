-- Remove no-longer-used per-criterion columns from thematic evaluation tables.
-- Data migration: map current per-criterion scores to the new single
-- overall_score, constrained to the 0..10 range.

UPDATE evaluation_thematic_criteria_repopulation
SET overall_score = LEAST(10, GREATEST(0, ROUND(
    (
        COALESCE(habitat_score, 0)
        + COALESCE(threat_mitigation_strategy_score, 0)
        + COALESCE(local_community_involvement_score, 0)
        + COALESCE(multidisciplinary_sustainability_score, 0)
    ) / NULLIF(
        (habitat_score IS NOT NULL)
        + (threat_mitigation_strategy_score IS NOT NULL)
        + (local_community_involvement_score IS NOT NULL)
        + (multidisciplinary_sustainability_score IS NOT NULL),
        0
    )
)))
WHERE
    habitat_score IS NOT NULL
    OR threat_mitigation_strategy_score IS NOT NULL
    OR local_community_involvement_score IS NOT NULL
    OR multidisciplinary_sustainability_score IS NOT NULL;

UPDATE evaluation_thematic_criteria_safeguard
SET overall_score = LEAST(10, GREATEST(0, ROUND(
    (
        COALESCE(systemic_approach_score, 0)
        + COALESCE(advocacy_and_legal_strengthening_score, 0)
        + COALESCE(habitat_safeguard_score, 0)
        + COALESCE(reservers_development_participation_score, 0)
        + COALESCE(crucial_species_activities_score, 0)
        + COALESCE(multistakeholder_involvement_score, 0)
        + COALESCE(multidisciplinary_sustainability_score, 0)
    ) / NULLIF(
        (systemic_approach_score IS NOT NULL)
        + (advocacy_and_legal_strengthening_score IS NOT NULL)
        + (habitat_safeguard_score IS NOT NULL)
        + (reservers_development_participation_score IS NOT NULL)
        + (crucial_species_activities_score IS NOT NULL)
        + (multistakeholder_involvement_score IS NOT NULL)
        + (multidisciplinary_sustainability_score IS NOT NULL),
        0
    )
)))
WHERE
    systemic_approach_score IS NOT NULL
    OR advocacy_and_legal_strengthening_score IS NOT NULL
    OR habitat_safeguard_score IS NOT NULL
    OR reservers_development_participation_score IS NOT NULL
    OR crucial_species_activities_score IS NOT NULL
    OR multistakeholder_involvement_score IS NOT NULL
    OR multidisciplinary_sustainability_score IS NOT NULL;

UPDATE evaluation_thematic_criteria_cohabitation
SET overall_score = LEAST(10, GREATEST(0, ROUND(
    (
        COALESCE(risk_reduction_strategy_score, 0)
        + COALESCE(biodiversity_protection_and_animal_integrity_score, 0)
        + COALESCE(local_community_involvement_score, 0)
        + COALESCE(circular_economy_development_score, 0)
        + COALESCE(multidisciplinary_sustainability_score, 0)
    ) / NULLIF(
        (risk_reduction_strategy_score IS NOT NULL)
        + (biodiversity_protection_and_animal_integrity_score IS NOT NULL)
        + (local_community_involvement_score IS NOT NULL)
        + (circular_economy_development_score IS NOT NULL)
        + (multidisciplinary_sustainability_score IS NOT NULL),
        0
    )
)))
WHERE
    risk_reduction_strategy_score IS NOT NULL
    OR biodiversity_protection_and_animal_integrity_score IS NOT NULL
    OR local_community_involvement_score IS NOT NULL
    OR circular_economy_development_score IS NOT NULL
    OR multidisciplinary_sustainability_score IS NOT NULL;

UPDATE evaluation_thematic_criteria_community_support
SET overall_score = LEAST(10, GREATEST(0, ROUND(
    (
        COALESCE(systemic_development_score, 0)
        + COALESCE(social_discrimination_fighting_score, 0)
        + COALESCE(habitat_protection_score, 0)
        + COALESCE(multistakeholder_involvement_score, 0)
        + COALESCE(multidisciplinary_sustainability_score, 0)
    ) / NULLIF(
        (systemic_development_score IS NOT NULL)
        + (social_discrimination_fighting_score IS NOT NULL)
        + (habitat_protection_score IS NOT NULL)
        + (multistakeholder_involvement_score IS NOT NULL)
        + (multidisciplinary_sustainability_score IS NOT NULL),
        0
    )
)))
WHERE
    systemic_development_score IS NOT NULL
    OR social_discrimination_fighting_score IS NOT NULL
    OR habitat_protection_score IS NOT NULL
    OR multistakeholder_involvement_score IS NOT NULL
    OR multidisciplinary_sustainability_score IS NOT NULL;

UPDATE evaluation_thematic_criteria_culture_education_awareness
SET overall_score = LEAST(10, GREATEST(0, ROUND(
    (
        COALESCE(dissemination_tools_score, 0)
        + COALESCE(advocacy_and_legal_strengthening_score, 0)
        + COALESCE(innovation_score, 0)
        + COALESCE(multistakeholder_involvement_score, 0)
        + COALESCE(multidisciplinary_sustainability_score, 0)
    ) / NULLIF(
        (dissemination_tools_score IS NOT NULL)
        + (advocacy_and_legal_strengthening_score IS NOT NULL)
        + (innovation_score IS NOT NULL)
        + (multistakeholder_involvement_score IS NOT NULL)
        + (multidisciplinary_sustainability_score IS NOT NULL),
        0
    )
)))
WHERE
    dissemination_tools_score IS NOT NULL
    OR advocacy_and_legal_strengthening_score IS NOT NULL
    OR innovation_score IS NOT NULL
    OR multistakeholder_involvement_score IS NOT NULL
    OR multidisciplinary_sustainability_score IS NOT NULL;

ALTER TABLE evaluation_thematic_criteria_repopulation
    DROP COLUMN habitat_score,
    DROP COLUMN threat_mitigation_strategy_score,
    DROP COLUMN local_community_involvement_score,
    DROP COLUMN multidisciplinary_sustainability_score;

ALTER TABLE evaluation_thematic_criteria_safeguard
    DROP COLUMN systemic_approach_score,
    DROP COLUMN advocacy_and_legal_strengthening_score,
    DROP COLUMN habitat_safeguard_score,
    DROP COLUMN reservers_development_participation_score,
    DROP COLUMN crucial_species_activities_score,
    DROP COLUMN multistakeholder_involvement_score,
    DROP COLUMN multidisciplinary_sustainability_score;

ALTER TABLE evaluation_thematic_criteria_cohabitation
    DROP COLUMN risk_reduction_strategy_score,
    DROP COLUMN biodiversity_protection_and_animal_integrity_score,
    DROP COLUMN local_community_involvement_score,
    DROP COLUMN circular_economy_development_score,
    DROP COLUMN multidisciplinary_sustainability_score;

ALTER TABLE evaluation_thematic_criteria_community_support
    DROP COLUMN systemic_development_score,
    DROP COLUMN social_discrimination_fighting_score,
    DROP COLUMN habitat_protection_score,
    DROP COLUMN multistakeholder_involvement_score,
    DROP COLUMN multidisciplinary_sustainability_score;

ALTER TABLE evaluation_thematic_criteria_culture_education_awareness
    DROP COLUMN dissemination_tools_score,
    DROP COLUMN advocacy_and_legal_strengthening_score,
    DROP COLUMN innovation_score,
    DROP COLUMN multistakeholder_involvement_score,
    DROP COLUMN multidisciplinary_sustainability_score;
