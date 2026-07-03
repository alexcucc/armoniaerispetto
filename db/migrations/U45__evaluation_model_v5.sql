UPDATE evaluation
SET model_version = 'v4'
WHERE model_version = 'v5';

DROP TABLE IF EXISTS evaluation_v4_thematic_conservation_species_habitat;
DROP TABLE IF EXISTS evaluation_v4_thematic_anthropic_threat_reduction;
DROP TABLE IF EXISTS evaluation_v4_thematic_weight_depth;
DROP TABLE IF EXISTS evaluation_v4_thematic_safeguard;
DROP TABLE IF EXISTS evaluation_v4_thematic_cohabitation;
DROP TABLE IF EXISTS evaluation_v4_thematic_community_support;
DROP TABLE IF EXISTS evaluation_v4_thematic_culture_education;

CREATE TABLE evaluation_v4_thematic_safeguard (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    habitat_safeguard_score INT NULL,
    habitat_safeguard_notes TEXT NULL,
    prevention_score INT NULL,
    prevention_notes TEXT NULL,
    legal_contrast_score INT NULL,
    legal_contrast_notes TEXT NULL,
    liberation_actions_score INT NULL,
    liberation_actions_notes TEXT NULL,
    shelter_remedy_score INT NULL,
    shelter_remedy_notes TEXT NULL,
    protection_remedy_score INT NULL,
    protection_remedy_notes TEXT NULL,
    veterinary_rehabilitation_score INT NULL,
    veterinary_rehabilitation_notes TEXT NULL,
    relocation_remedy_score INT NULL,
    relocation_remedy_notes TEXT NULL,
    species_focus_score INT NULL,
    species_focus_notes TEXT NULL,
    facility_coparticipation_score INT NULL,
    facility_coparticipation_notes TEXT NULL,
    multidisciplinary_sustainability_score INT NULL,
    multidisciplinary_sustainability_notes TEXT NULL,
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
    intervention_habitat_notes TEXT NULL,
    threat_mitigation_strategy_score INT NULL,
    threat_mitigation_strategy_notes TEXT NULL,
    local_community_involvement_score INT NULL,
    local_community_involvement_notes TEXT NULL,
    multidisciplinary_sustainability_score INT NULL,
    multidisciplinary_sustainability_notes TEXT NULL,
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
    local_community_involvement_notes TEXT NULL,
    biodiversity_integration_score INT NULL,
    biodiversity_integration_notes TEXT NULL,
    risk_reduction_strategy_score INT NULL,
    risk_reduction_strategy_notes TEXT NULL,
    circular_economy_support_score INT NULL,
    circular_economy_support_notes TEXT NULL,
    multidisciplinary_sustainability_score INT NULL,
    multidisciplinary_sustainability_notes TEXT NULL,
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
    systemic_development_notes TEXT NULL,
    social_discrimination_contrast_score INT NULL,
    social_discrimination_contrast_notes TEXT NULL,
    habitat_safeguard_score INT NULL,
    habitat_safeguard_notes TEXT NULL,
    multistakeholder_involvement_score INT NULL,
    multistakeholder_involvement_notes TEXT NULL,
    multidisciplinary_sustainability_score INT NULL,
    multidisciplinary_sustainability_notes TEXT NULL,
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
    dissemination_tools_notes TEXT NULL,
    advocacy_legal_strengthening_score INT NULL,
    advocacy_legal_strengthening_notes TEXT NULL,
    innovation_degree_score INT NULL,
    innovation_degree_notes TEXT NULL,
    multistakeholder_involvement_score INT NULL,
    multistakeholder_involvement_notes TEXT NULL,
    multidisciplinary_sustainability_score INT NULL,
    multidisciplinary_sustainability_notes TEXT NULL,
    average_score DECIMAL(10,2) NULL,
    weighted_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_thematic_culture_education_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_thematic_culture_education_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);
