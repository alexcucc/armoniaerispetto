ALTER TABLE evaluation
    ADD COLUMN model_version VARCHAR(16) NOT NULL DEFAULT 'legacy' AFTER forced_weighted_total_score;

UPDATE evaluation
SET model_version = 'legacy'
WHERE model_version IS NULL OR model_version = '';

CREATE INDEX idx_evaluation_model_version ON evaluation (model_version);

CREATE TABLE evaluation_v4_general (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    proposing_entity_score DECIMAL(10,2) NULL,
    project_score DECIMAL(10,2) NULL,
    financial_plan_score DECIMAL(10,2) NULL,
    qualitative_elements_score DECIMAL(10,2) NULL,
    thematic_criteria_score DECIMAL(10,2) NULL,
    overall_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_general_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_general_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_proposing_entity (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    general_information_score INT NULL,
    activities_consistency_score INT NULL,
    experience_score INT NULL,
    organizational_management_score INT NULL,
    budget_completeness_score INT NULL,
    funding_sources_score INT NULL,
    financial_soundness_score INT NULL,
    organizational_structure_score INT NULL,
    local_purpose_involvement_score INT NULL,
    partnership_visibility_score INT NULL,
    overall_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_proposing_entity_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_proposing_entity_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_project (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    needs_analysis_score INT NULL,
    objectives_consistency_score INT NULL,
    objectives_ambition_score INT NULL,
    objectives_feasibility_score INT NULL,
    expected_results_score INT NULL,
    activities_score INT NULL,
    local_purpose_score INT NULL,
    partnership_local_authorities_score INT NULL,
    synergies_efficiency_score INT NULL,
    inefficiencies_score INT NULL,
    communication_visibility_score INT NULL,
    overall_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_project_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_project_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_financial_plan (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    funding_limits_compliance_score INT NULL,
    budget_clarity_score INT NULL,
    budget_consistency_score INT NULL,
    cofinancing_score INT NULL,
    flexibility_score INT NULL,
    project_value_soundness_score INT NULL,
    staff_cost_incidence_score INT NULL,
    overall_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_financial_plan_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_financial_plan_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_qualitative_elements (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    new_project_score INT NULL,
    long_term_impact_score INT NULL,
    context_relevance_score INT NULL,
    innovation_score INT NULL,
    scientific_rigor_score INT NULL,
    replicability_scalability_score INT NULL,
    overall_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_qualitative_elements_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_qualitative_elements_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_thematic_safeguard (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    habitat_safeguard_score INT NULL,
    prevention_score INT NULL,
    legal_contrast_score INT NULL,
    liberation_actions_score INT NULL,
    shelter_remedy_score INT NULL,
    protection_remedy_score INT NULL,
    veterinary_rehabilitation_score INT NULL,
    relocation_remedy_score INT NULL,
    species_focus_score INT NULL,
    facility_coparticipation_score INT NULL,
    multidisciplinary_sustainability_score INT NULL,
    average_score DECIMAL(10,2) NULL,
    weighted_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_thematic_safeguard_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_thematic_safeguard_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_thematic_repopulation (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    intervention_habitat_score INT NULL,
    threat_mitigation_strategy_score INT NULL,
    local_community_involvement_score INT NULL,
    multidisciplinary_sustainability_score INT NULL,
    average_score DECIMAL(10,2) NULL,
    weighted_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_thematic_repopulation_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_thematic_repopulation_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_thematic_cohabitation (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    local_community_involvement_score INT NULL,
    biodiversity_integration_score INT NULL,
    risk_reduction_strategy_score INT NULL,
    circular_economy_support_score INT NULL,
    multidisciplinary_sustainability_score INT NULL,
    average_score DECIMAL(10,2) NULL,
    weighted_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_thematic_cohabitation_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_thematic_cohabitation_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_thematic_community_support (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    systemic_development_score INT NULL,
    social_discrimination_contrast_score INT NULL,
    habitat_safeguard_score INT NULL,
    multistakeholder_involvement_score INT NULL,
    multidisciplinary_sustainability_score INT NULL,
    average_score DECIMAL(10,2) NULL,
    weighted_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_thematic_community_support_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_thematic_community_support_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_thematic_culture_education (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    dissemination_tools_score INT NULL,
    advocacy_legal_strengthening_score INT NULL,
    innovation_degree_score INT NULL,
    multistakeholder_involvement_score INT NULL,
    multidisciplinary_sustainability_score INT NULL,
    average_score DECIMAL(10,2) NULL,
    weighted_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_thematic_culture_education_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_thematic_culture_education_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);
