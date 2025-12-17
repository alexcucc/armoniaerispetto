-- Allow storing partially filled evaluations by permitting NULL scores
ALTER TABLE evaluation_general
    MODIFY proposing_entity_score INT NULL,
    MODIFY general_project_score INT NULL,
    MODIFY financial_plan_score INT NULL,
    MODIFY qualitative_elements_score INT NULL,
    MODIFY thematic_criteria_score INT NULL,
    MODIFY overall_score INT NULL;

ALTER TABLE evaluation_proposing_entity
    MODIFY general_information_score INT NULL,
    MODIFY experience_score INT NULL,
    MODIFY organizational_capacity_score INT NULL,
    MODIFY policy_score INT NULL,
    MODIFY budget_score INT NULL,
    MODIFY purpose_and_local_involvement_score INT NULL,
    MODIFY partnership_and_visibility_score INT NULL,
    MODIFY overall_score INT NULL;

ALTER TABLE evaluation_project
    MODIFY needs_identification_and_problem_analysis_score INT NULL,
    MODIFY adherence_to_statuary_purposes_score INT NULL,
    MODIFY social_weight_score INT NULL,
    MODIFY objectives_score INT NULL,
    MODIFY expected_results_score INT NULL,
    MODIFY activity_score INT NULL,
    MODIFY local_purpose_score INT NULL,
    MODIFY partnership_and_relations_with_local_authorities_score INT NULL,
    MODIFY synergies_and_design_inefficiencies_score INT NULL,
    MODIFY communication_and_visibility_score INT NULL,
    MODIFY overall_score INT NULL;

ALTER TABLE evaluation_financial_plan
    MODIFY completeness_and_clarity_of_budget_score INT NULL,
    MODIFY consistency_with_objectives_score INT NULL,
    MODIFY cofinancing_score INT NULL,
    MODIFY flexibility_score INT NULL,
    MODIFY overall_score INT NULL;

ALTER TABLE evaluation_qualitative_elements
    MODIFY impact_score INT NULL,
    MODIFY relevance_score INT NULL,
    MODIFY congruity_score INT NULL,
    MODIFY innovation_score INT NULL,
    MODIFY rigor_and_scientific_validity_score INT NULL,
    MODIFY replicability_and_scalability_score INT NULL,
    MODIFY cohabitation_evidence_score INT NULL,
    MODIFY research_and_university_partnership_score INT NULL,
    MODIFY overall_score INT NULL;

ALTER TABLE evaluation_thematic_criteria_repopulation
    MODIFY habitat_score INT NULL,
    MODIFY threat_mitigation_strategy_score INT NULL,
    MODIFY local_community_involvement_score INT NULL,
    MODIFY multidisciplinary_sustainability_score INT NULL,
    MODIFY overall_score INT NULL;

ALTER TABLE evaluation_thematic_criteria_safeguard
    MODIFY systemic_approach_score INT NULL,
    MODIFY advocacy_and_legal_strengthening_score INT NULL,
    MODIFY habitat_safeguard_score INT NULL,
    MODIFY reservers_development_participation_score INT NULL,
    MODIFY crucial_species_activities_score INT NULL,
    MODIFY multistakeholder_involvement_score INT NULL,
    MODIFY multidisciplinary_sustainability_score INT NULL,
    MODIFY overall_score INT NULL;

ALTER TABLE evaluation_thematic_criteria_cohabitation
    MODIFY risk_reduction_strategy_score INT NULL,
    MODIFY biodiversity_protection_and_animal_integrity_score INT NULL,
    MODIFY local_community_involvement_score INT NULL,
    MODIFY circular_economy_development_score INT NULL,
    MODIFY multidisciplinary_sustainability_score INT NULL,
    MODIFY overall_score INT NULL;

ALTER TABLE evaluation_thematic_criteria_community_support
    MODIFY systemic_development_score INT NULL,
    MODIFY social_discrimination_fighting_score INT NULL,
    MODIFY habitat_protection_score INT NULL,
    MODIFY multistakeholder_involvement_score INT NULL,
    MODIFY multidisciplinary_sustainability_score INT NULL,
    MODIFY overall_score INT NULL;

ALTER TABLE evaluation_thematic_criteria_culture_education_awareness
    MODIFY dissemination_tools_score INT NULL,
    MODIFY advocacy_and_legal_strengthening_score INT NULL,
    MODIFY innovation_score INT NULL,
    MODIFY multistakeholder_involvement_score INT NULL,
    MODIFY multidisciplinary_sustainability_score INT NULL,
    MODIFY overall_score INT NULL;
