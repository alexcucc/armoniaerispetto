UPDATE evaluation
SET model_version = 'v5'
WHERE model_version = 'v4';

DROP TABLE IF EXISTS evaluation_v4_thematic_repopulation;
DROP TABLE IF EXISTS evaluation_v4_thematic_safeguard;
DROP TABLE IF EXISTS evaluation_v4_thematic_cohabitation;
DROP TABLE IF EXISTS evaluation_v4_thematic_community_support;
DROP TABLE IF EXISTS evaluation_v4_thematic_culture_education;

CREATE TABLE evaluation_v4_thematic_safeguard (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    prevention_operational_tactical_score INT NULL,
    prevention_operational_tactical_notes TEXT NULL,
    legal_contrast_score INT NULL,
    legal_contrast_notes TEXT NULL,
    liberation_actions_score INT NULL,
    liberation_actions_notes TEXT NULL,
    shelter_remedy_score INT NULL,
    shelter_remedy_notes TEXT NULL,
    recovery_remedy_score INT NULL,
    recovery_remedy_notes TEXT NULL,
    protection_remedy_score INT NULL,
    protection_remedy_notes TEXT NULL,
    veterinary_rehabilitation_score INT NULL,
    veterinary_rehabilitation_notes TEXT NULL,
    relocation_remedy_score INT NULL,
    relocation_remedy_notes TEXT NULL,
    weighted_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_thematic_safeguard_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_thematic_safeguard_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_thematic_conservation_species_habitat (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    habitat_protection_score INT NULL,
    habitat_protection_notes TEXT NULL,
    reserves_oases_coparticipation_score INT NULL,
    reserves_oases_coparticipation_notes TEXT NULL,
    repopulation_score INT NULL,
    repopulation_notes TEXT NULL,
    reintroductions_score INT NULL,
    reintroductions_notes TEXT NULL,
    monitoring_research_census_score INT NULL,
    monitoring_research_census_notes TEXT NULL,
    rare_species_conservation_score INT NULL,
    rare_species_conservation_notes TEXT NULL,
    weighted_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_eval_v4_thematic_conservation_species_habitat_eval_id (evaluation_id),
    CONSTRAINT fk_eval_v4_thematic_conservation_species_habitat_eval FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_thematic_anthropic_threat_reduction (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    anti_poaching_illegal_traffic_score INT NULL,
    anti_poaching_illegal_traffic_notes TEXT NULL,
    infrastructure_impact_mitigation_score INT NULL,
    infrastructure_impact_mitigation_notes TEXT NULL,
    roadkill_prevention_score INT NULL,
    roadkill_prevention_notes TEXT NULL,
    electrocution_protection_score INT NULL,
    electrocution_protection_notes TEXT NULL,
    accidental_capture_prevention_score INT NULL,
    accidental_capture_prevention_notes TEXT NULL,
    pollution_fight_score INT NULL,
    pollution_fight_notes TEXT NULL,
    weighted_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_eval_v4_thematic_anthropic_threat_reduction_eval_id (evaluation_id),
    CONSTRAINT fk_eval_v4_thematic_anthropic_threat_reduction_eval FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_thematic_cohabitation (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    local_community_mediation_score INT NULL,
    local_community_mediation_notes TEXT NULL,
    biodiversity_integration_human_activities_score INT NULL,
    biodiversity_integration_human_activities_notes TEXT NULL,
    human_animal_conflict_risk_reduction_score INT NULL,
    human_animal_conflict_risk_reduction_notes TEXT NULL,
    circular_economy_local_support_score INT NULL,
    circular_economy_local_support_notes TEXT NULL,
    multidisciplinary_overall_strength_score INT NULL,
    multidisciplinary_overall_strength_notes TEXT NULL,
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
    systemic_capacity_building_score INT NULL,
    systemic_capacity_building_notes TEXT NULL,
    social_discrimination_contrast_score INT NULL,
    social_discrimination_contrast_notes TEXT NULL,
    multistakeholder_context_attention_score INT NULL,
    multistakeholder_context_attention_notes TEXT NULL,
    territorial_networks_partnerships_score INT NULL,
    territorial_networks_partnerships_notes TEXT NULL,
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
    education_and_culture_score INT NULL,
    education_and_culture_notes TEXT NULL,
    advocacy_governance_politics_score INT NULL,
    advocacy_governance_politics_notes TEXT NULL,
    scientific_research_score INT NULL,
    scientific_research_notes TEXT NULL,
    involvement_breadth_score INT NULL,
    involvement_breadth_notes TEXT NULL,
    weighted_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_thematic_culture_education_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_thematic_culture_education_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);

CREATE TABLE evaluation_v4_thematic_weight_depth (
    id BIGINT NOT NULL AUTO_INCREMENT,
    evaluation_id BIGINT NOT NULL,
    scale_score INT NULL,
    scale_notes TEXT NULL,
    depth_score INT NULL,
    depth_notes TEXT NULL,
    duration_score INT NULL,
    duration_notes TEXT NULL,
    weighted_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluation_v4_thematic_weight_depth_evaluation_id (evaluation_id),
    CONSTRAINT fk_evaluation_v4_thematic_weight_depth_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluation(id) ON DELETE CASCADE
);
