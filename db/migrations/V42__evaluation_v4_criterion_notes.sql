ALTER TABLE evaluation_v4_proposing_entity
    ADD COLUMN general_information_notes TEXT NULL AFTER general_information_score,
    ADD COLUMN activities_consistency_notes TEXT NULL AFTER activities_consistency_score,
    ADD COLUMN experience_notes TEXT NULL AFTER experience_score,
    ADD COLUMN organizational_management_notes TEXT NULL AFTER organizational_management_score,
    ADD COLUMN budget_completeness_notes TEXT NULL AFTER budget_completeness_score,
    ADD COLUMN funding_sources_notes TEXT NULL AFTER funding_sources_score,
    ADD COLUMN financial_soundness_notes TEXT NULL AFTER financial_soundness_score,
    ADD COLUMN organizational_structure_notes TEXT NULL AFTER organizational_structure_score,
    ADD COLUMN local_purpose_involvement_notes TEXT NULL AFTER local_purpose_involvement_score,
    ADD COLUMN partnership_visibility_notes TEXT NULL AFTER partnership_visibility_score;

ALTER TABLE evaluation_v4_project
    ADD COLUMN needs_analysis_notes TEXT NULL AFTER needs_analysis_score,
    ADD COLUMN objectives_consistency_notes TEXT NULL AFTER objectives_consistency_score,
    ADD COLUMN objectives_ambition_notes TEXT NULL AFTER objectives_ambition_score,
    ADD COLUMN objectives_feasibility_notes TEXT NULL AFTER objectives_feasibility_score,
    ADD COLUMN expected_results_notes TEXT NULL AFTER expected_results_score,
    ADD COLUMN activities_notes TEXT NULL AFTER activities_score,
    ADD COLUMN local_purpose_notes TEXT NULL AFTER local_purpose_score,
    ADD COLUMN partnership_local_authorities_notes TEXT NULL AFTER partnership_local_authorities_score,
    ADD COLUMN synergies_efficiency_notes TEXT NULL AFTER synergies_efficiency_score,
    ADD COLUMN inefficiencies_notes TEXT NULL AFTER inefficiencies_score,
    ADD COLUMN communication_visibility_notes TEXT NULL AFTER communication_visibility_score;

ALTER TABLE evaluation_v4_financial_plan
    ADD COLUMN funding_limits_compliance_notes TEXT NULL AFTER funding_limits_compliance_score,
    ADD COLUMN budget_clarity_notes TEXT NULL AFTER budget_clarity_score,
    ADD COLUMN budget_consistency_notes TEXT NULL AFTER budget_consistency_score,
    ADD COLUMN cofinancing_notes TEXT NULL AFTER cofinancing_score,
    ADD COLUMN flexibility_notes TEXT NULL AFTER flexibility_score,
    ADD COLUMN project_value_soundness_notes TEXT NULL AFTER project_value_soundness_score,
    ADD COLUMN staff_cost_incidence_notes TEXT NULL AFTER staff_cost_incidence_score;

ALTER TABLE evaluation_v4_qualitative_elements
    ADD COLUMN new_project_notes TEXT NULL AFTER new_project_score,
    ADD COLUMN long_term_impact_notes TEXT NULL AFTER long_term_impact_score,
    ADD COLUMN context_relevance_notes TEXT NULL AFTER context_relevance_score,
    ADD COLUMN innovation_notes TEXT NULL AFTER innovation_score,
    ADD COLUMN scientific_rigor_notes TEXT NULL AFTER scientific_rigor_score,
    ADD COLUMN replicability_scalability_notes TEXT NULL AFTER replicability_scalability_score;

ALTER TABLE evaluation_v4_thematic_safeguard
    ADD COLUMN habitat_safeguard_notes TEXT NULL AFTER habitat_safeguard_score,
    ADD COLUMN prevention_notes TEXT NULL AFTER prevention_score,
    ADD COLUMN legal_contrast_notes TEXT NULL AFTER legal_contrast_score,
    ADD COLUMN liberation_actions_notes TEXT NULL AFTER liberation_actions_score,
    ADD COLUMN shelter_remedy_notes TEXT NULL AFTER shelter_remedy_score,
    ADD COLUMN protection_remedy_notes TEXT NULL AFTER protection_remedy_score,
    ADD COLUMN veterinary_rehabilitation_notes TEXT NULL AFTER veterinary_rehabilitation_score,
    ADD COLUMN relocation_remedy_notes TEXT NULL AFTER relocation_remedy_score,
    ADD COLUMN species_focus_notes TEXT NULL AFTER species_focus_score,
    ADD COLUMN facility_coparticipation_notes TEXT NULL AFTER facility_coparticipation_score,
    ADD COLUMN multidisciplinary_sustainability_notes TEXT NULL AFTER multidisciplinary_sustainability_score;

ALTER TABLE evaluation_v4_thematic_repopulation
    ADD COLUMN intervention_habitat_notes TEXT NULL AFTER intervention_habitat_score,
    ADD COLUMN threat_mitigation_strategy_notes TEXT NULL AFTER threat_mitigation_strategy_score,
    ADD COLUMN local_community_involvement_notes TEXT NULL AFTER local_community_involvement_score,
    ADD COLUMN multidisciplinary_sustainability_notes TEXT NULL AFTER multidisciplinary_sustainability_score;

ALTER TABLE evaluation_v4_thematic_cohabitation
    ADD COLUMN local_community_involvement_notes TEXT NULL AFTER local_community_involvement_score,
    ADD COLUMN biodiversity_integration_notes TEXT NULL AFTER biodiversity_integration_score,
    ADD COLUMN risk_reduction_strategy_notes TEXT NULL AFTER risk_reduction_strategy_score,
    ADD COLUMN circular_economy_support_notes TEXT NULL AFTER circular_economy_support_score,
    ADD COLUMN multidisciplinary_sustainability_notes TEXT NULL AFTER multidisciplinary_sustainability_score;

ALTER TABLE evaluation_v4_thematic_community_support
    ADD COLUMN systemic_development_notes TEXT NULL AFTER systemic_development_score,
    ADD COLUMN social_discrimination_contrast_notes TEXT NULL AFTER social_discrimination_contrast_score,
    ADD COLUMN habitat_safeguard_notes TEXT NULL AFTER habitat_safeguard_score,
    ADD COLUMN multistakeholder_involvement_notes TEXT NULL AFTER multistakeholder_involvement_score,
    ADD COLUMN multidisciplinary_sustainability_notes TEXT NULL AFTER multidisciplinary_sustainability_score;

ALTER TABLE evaluation_v4_thematic_culture_education
    ADD COLUMN dissemination_tools_notes TEXT NULL AFTER dissemination_tools_score,
    ADD COLUMN advocacy_legal_strengthening_notes TEXT NULL AFTER advocacy_legal_strengthening_score,
    ADD COLUMN innovation_degree_notes TEXT NULL AFTER innovation_degree_score,
    ADD COLUMN multistakeholder_involvement_notes TEXT NULL AFTER multistakeholder_involvement_score,
    ADD COLUMN multidisciplinary_sustainability_notes TEXT NULL AFTER multidisciplinary_sustainability_score;
