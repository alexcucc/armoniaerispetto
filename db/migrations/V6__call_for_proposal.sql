CREATE TABLE call_for_proposal (
    id BIGINT NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    url VARCHAR(255) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

CREATE TABLE application (
    id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    call_for_proposal_id BIGINT NOT NULL,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (call_for_proposal_id) REFERENCES call_for_proposal(id) ON DELETE CASCADE
);

CREATE TABLE evaluation (
    id BIGINT NOT NULL AUTO_INCREMENT,
    application_id BIGINT NOT NULL,
    evaluator_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (application_id) REFERENCES application(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluator_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_general (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    proposing_entity_score INT NOT NULL,
    general_project_score INT NOT NULL,
    financial_plan_score INT NOT NULL,
    qualitative_elements_score INT NOT NULL,
    thematic_criteria_score INT NOT NULL,
    overall_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_proposing_entity (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    general_information_score INT NOT NULL,
    experience_score INT NOT NULL,
    organizational_capacity_score INT NOT NULL,
    policy_score INT NOT NULL,
    budget_score INT NOT NULL,
    purpose_and_local_involvement_score INT NOT NULL,
    partnership_and_visibility_score INT NOT NULL,
    overall_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_project (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    needs_identification_and_problem_analysis_score INT NOT NULL,
    adherence_to_statuary_purposes_score INT NOT NULL,
    social_weight_score INT NOT NULL,
    objectives_score INT NOT NULL,
    expected_results_score INT NOT NULL,
    activity_score INT NOT NULL,
    local_purpose_score INT NOT NULL,
    partnership_and_relations_with_local_authorities_score INT NOT NULL,
    synergies_and_design_inefficiencies_score INT NOT NULL,
    communication_and_visibility_score INT NOT NULL,
    overall_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_financial_plan (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    completeness_and_clarity_of_budget_score INT NOT NULL,
    consistency_with_objectives_score INT NOT NULL,
    cofinancing_score INT NOT NULL,
    flexibility_score INT NOT NULL,
    overall_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_qualitative_elements (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    impact_score INT NOT NULL,
    relevance_score INT NOT NULL,
    congruity_score INT NOT NULL,
    innovation_score INT NOT NULL,
    rigor_and_scientific_validity_score INT NOT NULL,
    replicability_and_scalability_score INT NOT NULL,
    cohabitation_evidence_score INT NOT NULL,
    research_and_university_partnership_score INT NOT NULL,
    overall_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_thematic_criteria_repopulation (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    habitat_score INT NOT NULL,
    threat_mitigation_strategy_score INT NOT NULL,
    local_community_involvement_score INT NOT NULL,
    multidisciplinary_sustainability_score INT NOT NULL,
    overall_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_thematic_criteria_safeguard (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    systemic_approach_score INT NOT NULL,
    advocacy_and_legal_strengthening_score INT NOT NULL,
    habitat_safeguard_score INT NOT NULL,
    reservers_development_participation_score INT NOT NULL,
    crucial_species_activities_score INT NOT NULL,
    multistakeholder_involvement_score INT NOT NULL,
    multidisciplinary_sustainability_score INT NOT NULL,
    overall_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_thematic_criteria_cohabitation (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    risk_reduction_strategy_score INT NOT NULL,
    biodiversity_protection_and_animal_integrity_score INT NOT NULL,
    local_community_involvement_score INT NOT NULL,
    circular_economy_development_score INT NOT NULL,
    multidisciplinary_sustainability_score INT NOT NULL,
    overall_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_thematic_criteria_community_support (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    systemic_development_score INT NOT NULL,
    social_discrimination_fighting_score INT NOT NULL,
    habitat_protection_score INT NOT NULL,
    multistakeholder_involvement_score INT NOT NULL,
    multidisciplinary_sustainability_score INT NOT NULL,
    overall_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_thematic_criteria_culture_education_awareness (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    dissemination_tools_score INT NOT NULL,
    advocacy_and_legal_strengthening_score INT NOT NULL,
    innovation_score INT NOT NULL,
    multistakeholder_involvement_score INT NOT NULL,
    multidisciplinary_sustainability_score INT NOT NULL,
    overall_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);
