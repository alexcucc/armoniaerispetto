<?php
session_start();
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito.']);
    exit;
}

include_once 'db/common-db.php';
include_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);
if (!$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['EVALUATION_CREATE'])) {
    echo json_encode(['success' => false, 'message' => 'Accesso non consentito.']);
    exit;
}

try {
    // Use transaction for consistency
    $pdo->beginTransaction();

    // Insert into evaluation table
    $stmt = $pdo->prepare("INSERT INTO evaluation (application_id, evaluator_id) VALUES (:application_id, :evaluator_id)");
    $stmt->execute([
        ':application_id' => $_POST['application_id'],
        ':evaluator_id'   => $_POST['evaluator_id']
    ]);
    $evaluation_id = $pdo->lastInsertId();

    // Get data for each section from POST
    $pe   = $_POST['proposing_entity'];
    $proj = $_POST['project'];
    $fp   = $_POST['financial_plan'];
    $qe   = $_POST['qualitative_elements'];
    $tr   = $_POST['thematic_repopulation'];
    $ts   = $_POST['thematic_safeguard'];
    $tc   = $_POST['thematic_cohabitation'];
    $tcom = $_POST['thematic_community_support'];
    $tce  = $_POST['thematic_culture_education'];

    // Compute overall scores per section as the sum of the individual scores

    // Evaluation Proposing Entity overall_score:
    $peOverall = floatval($pe['general_information_score'])
               + floatval($pe['experience_score'])
               + floatval($pe['organizational_capacity_score'])
               + floatval($pe['policy_score'])
               + floatval($pe['budget_score'])
               + floatval($pe['purpose_and_local_involvement_score'])
               + floatval($pe['partnership_and_visibility_score']);

    // Evaluation Project overall_score:
    $projOverall = floatval($proj['needs_identification_and_problem_analysis_score'])
                 + floatval($proj['adherence_to_statuary_purposes_score'])
                 + floatval($proj['social_weight_score'])
                 + floatval($proj['objectives_score'])
                 + floatval($proj['expected_results_score'])
                 + floatval($proj['activity_score'])
                 + floatval($proj['local_purpose_score'])
                 + floatval($proj['partnership_and_relations_with_local_authorities_score'])
                 + floatval($proj['synergies_and_design_inefficiencies_score'])
                 + floatval($proj['communication_and_visibility_score']);

    // Evaluation Financial Plan overall_score:
    $fpOverall = floatval($fp['completeness_and_clarity_of_budget_score'])
               + floatval($fp['consistency_with_objectives_score'])
               + floatval($fp['cofinancing_score'])
               + floatval($fp['flexibility_score']);

    // Evaluation Qualitative Elements overall_score:
    $qeOverall = floatval($qe['impact_score'])
               + floatval($qe['relevance_score'])
               + floatval($qe['congruity_score'])
               + floatval($qe['innovation_score'])
               + floatval($qe['rigor_and_scientific_validity_score'])
               + floatval($qe['replicability_and_scalability_score'])
               + floatval($qe['cohabitation_evidence_score'])
               + floatval($qe['research_and_university_partnership_score']);

    // Evaluation Thematic Criteria - Repopulation overall_score:
    $trOverall = floatval($tr['habitat_score'])
               + floatval($tr['threat_mitigation_strategy_score'])
               + floatval($tr['local_community_involvement_score'])
               + floatval($tr['multidisciplinary_sustainability_score']);

    // Evaluation Thematic Criteria - Safeguard overall_score:
    $tsOverall = floatval($ts['systemic_approach_score'])
               + floatval($ts['advocacy_and_legal_strengthening_score'])
               + floatval($ts['habitat_safeguard_score'])
               + floatval($ts['reservers_development_participation_score'])
               + floatval($ts['crucial_species_activities_score'])
               + floatval($ts['multistakeholder_involvement_score'])
               + floatval($ts['multidisciplinary_sustainability_score']);

    // Evaluation Thematic Criteria - Cohabitation overall_score:
    $tcOverall = floatval($tc['risk_reduction_strategy_score'])
               + floatval($tc['biodiversity_protection_and_animal_integrity_score'])
               + floatval($tc['local_community_involvement_score'])
               + floatval($tc['circular_economy_development_score'])
               + floatval($tc['multidisciplinary_sustainability_score']);

    // Evaluation Thematic Criteria - Community Support overall_score:
    $tcomOverall = floatval($tcom['systemic_development_score'])
                + floatval($tcom['social_discrimination_fighting_score'])
                + floatval($tcom['habitat_protection_score'])
                + floatval($tcom['multistakeholder_involvement_score'])
                + floatval($tcom['multidisciplinary_sustainability_score']);

    // Evaluation Thematic Criteria - Culture and Education overall_score:
    $tceOverall = floatval($tce['dissemination_tools_score'])
               + floatval($tce['advocacy_and_legal_strengthening_score'])
               + floatval($tce['innovation_score'])
               + floatval($tce['multistakeholder_involvement_score'])
               + floatval($tce['multidisciplinary_sustainability_score']);

    // Insert into evaluation_proposing_entity with computed overall_score
    $stmt = $pdo->prepare("INSERT INTO evaluation_proposing_entity (evaluation_id, general_information_score, experience_score, organizational_capacity_score, policy_score, budget_score, purpose_and_local_involvement_score, partnership_and_visibility_score, overall_score) VALUES (:evaluation_id, :general_information_score, :experience_score, :organizational_capacity_score, :policy_score, :budget_score, :purpose_and_local_involvement_score, :partnership_and_visibility_score, :overall_score)");
    $stmt->execute([
        ':evaluation_id'                     => $evaluation_id,
        ':general_information_score'         => $pe['general_information_score'],
        ':experience_score'                  => $pe['experience_score'],
        ':organizational_capacity_score'     => $pe['organizational_capacity_score'],
        ':policy_score'                      => $pe['policy_score'],
        ':budget_score'                      => $pe['budget_score'],
        ':purpose_and_local_involvement_score'=> $pe['purpose_and_local_involvement_score'],
        ':partnership_and_visibility_score'  => $pe['partnership_and_visibility_score'],
        ':overall_score'                     => $peOverall
    ]);

    // Insert into evaluation_project with computed overall_score
    $stmt = $pdo->prepare("INSERT INTO evaluation_project (evaluation_id, needs_identification_and_problem_analysis_score, adherence_to_statuary_purposes_score, social_weight_score, objectives_score, expected_results_score, activity_score, local_purpose_score, partnership_and_relations_with_local_authorities_score, synergies_and_design_inefficiencies_score, communication_and_visibility_score, overall_score) VALUES (:evaluation_id, :needs_identification_and_problem_analysis_score, :adherence_to_statuary_purposes_score, :social_weight_score, :objectives_score, :expected_results_score, :activity_score, :local_purpose_score, :partnership_and_relations_with_local_authorities_score, :synergies_and_design_inefficiencies_score, :communication_and_visibility_score, :overall_score)");
    $stmt->execute([
        ':evaluation_id' => $evaluation_id,
        ':needs_identification_and_problem_analysis_score' => $proj['needs_identification_and_problem_analysis_score'],
        ':adherence_to_statuary_purposes_score'             => $proj['adherence_to_statuary_purposes_score'],
        ':social_weight_score'                              => $proj['social_weight_score'],
        ':objectives_score'                                 => $proj['objectives_score'],
        ':expected_results_score'                           => $proj['expected_results_score'],
        ':activity_score'                                   => $proj['activity_score'],
        ':local_purpose_score'                              => $proj['local_purpose_score'],
        ':partnership_and_relations_with_local_authorities_score' => $proj['partnership_and_relations_with_local_authorities_score'],
        ':synergies_and_design_inefficiencies_score'        => $proj['synergies_and_design_inefficiencies_score'],
        ':communication_and_visibility_score'             => $proj['communication_and_visibility_score'],
        ':overall_score'                                    => $projOverall
    ]);

    // Insert into evaluation_financial_plan with computed overall_score
    $stmt = $pdo->prepare("INSERT INTO evaluation_financial_plan (evaluation_id, completeness_and_clarity_of_budget_score, consistency_with_objectives_score, cofinancing_score, flexibility_score, overall_score) VALUES (:evaluation_id, :completeness_and_clarity_of_budget_score, :consistency_with_objectives_score, :cofinancing_score, :flexibility_score, :overall_score)");
    $stmt->execute([
        ':evaluation_id' => $evaluation_id,
        ':completeness_and_clarity_of_budget_score' => $fp['completeness_and_clarity_of_budget_score'],
        ':consistency_with_objectives_score'        => $fp['consistency_with_objectives_score'],
        ':cofinancing_score'                        => $fp['cofinancing_score'],
        ':flexibility_score'                        => $fp['flexibility_score'],
        ':overall_score'                            => $fpOverall
    ]);

    // Insert into evaluation_qualitative_elements with computed overall_score
    $stmt = $pdo->prepare("INSERT INTO evaluation_qualitative_elements (evaluation_id, impact_score, relevance_score, congruity_score, innovation_score, rigor_and_scientific_validity_score, replicability_and_scalability_score, cohabitation_evidence_score, research_and_university_partnership_score, overall_score) VALUES (:evaluation_id, :impact_score, :relevance_score, :congruity_score, :innovation_score, :rigor_and_scientific_validity_score, :replicability_and_scalability_score, :cohabitation_evidence_score, :research_and_university_partnership_score, :overall_score)");
    $stmt->execute([
        ':evaluation_id' => $evaluation_id,
        ':impact_score'  => $qe['impact_score'],
        ':relevance_score' => $qe['relevance_score'],
        ':congruity_score'  => $qe['congruity_score'],
        ':innovation_score' => $qe['innovation_score'],
        ':rigor_and_scientific_validity_score' => $qe['rigor_and_scientific_validity_score'],
        ':replicability_and_scalability_score'  => $qe['replicability_and_scalability_score'],
        ':cohabitation_evidence_score' => $qe['cohabitation_evidence_score'],
        ':research_and_university_partnership_score' => $qe['research_and_university_partnership_score'],
        ':overall_score' => $qeOverall
    ]);

    // Insert into evaluation_thematic_criteria_repopulation with computed overall_score
    $stmt = $pdo->prepare("INSERT INTO evaluation_thematic_criteria_repopulation (evaluation_id, habitat_score, threat_mitigation_strategy_score, local_community_involvement_score, multidisciplinary_sustainability_score, overall_score) VALUES (:evaluation_id, :habitat_score, :threat_mitigation_strategy_score, :local_community_involvement_score, :multidisciplinary_sustainability_score, :overall_score)");
    $stmt->execute([
        ':evaluation_id' => $evaluation_id,
        ':habitat_score' => $tr['habitat_score'],
        ':threat_mitigation_strategy_score' => $tr['threat_mitigation_strategy_score'],
        ':local_community_involvement_score' => $tr['local_community_involvement_score'],
        ':multidisciplinary_sustainability_score' => $tr['multidisciplinary_sustainability_score'],
        ':overall_score' => $trOverall
    ]);

    $stmt = $pdo->prepare("INSERT INTO evaluation_thematic_criteria_safeguard (evaluation_id, systemic_approach_score, advocacy_and_legal_strengthening_score, habitat_safeguard_score, reservers_development_participation_score, crucial_species_activities_score, multistakeholder_involvement_score, multidisciplinary_sustainability_score, overall_score) VALUES (:evaluation_id, :systemic_approach_score, :advocacy_and_legal_strengthening_score, :habitat_safeguard_score, :reservers_development_participation_score, :crucial_species_activities_score, :multistakeholder_involvement_score, :multidisciplinary_sustainability_score, :overall_score)");
    $stmt->execute([
        ':evaluation_id'                          => $evaluation_id,
        ':systemic_approach_score'                => $ts['systemic_approach_score'],
        ':advocacy_and_legal_strengthening_score' => $ts['advocacy_and_legal_strengthening_score'],
        ':habitat_safeguard_score'                => $ts['habitat_safeguard_score'],
        ':reservers_development_participation_score' => $ts['reservers_development_participation_score'],
        ':crucial_species_activities_score'       => $ts['crucial_species_activities_score'],
        ':multistakeholder_involvement_score'      => $ts['multistakeholder_involvement_score'],
        ':multidisciplinary_sustainability_score' => $ts['multidisciplinary_sustainability_score'],
        ':overall_score'                          => $tsOverall
    ]);

    // Insert into evaluation_thematic_criteria_cohabitation with computed overall_score
    $stmt = $pdo->prepare("INSERT INTO evaluation_thematic_criteria_cohabitation (evaluation_id, risk_reduction_strategy_score, biodiversity_protection_and_animal_integrity_score, local_community_involvement_score, circular_economy_development_score, multidisciplinary_sustainability_score, overall_score) VALUES (:evaluation_id, :risk_reduction_strategy_score, :biodiversity_protection_and_animal_integrity_score, :local_community_involvement_score, :circular_economy_development_score, :multidisciplinary_sustainability_score, :overall_score)");
    $stmt->execute([
        ':evaluation_id'                               => $evaluation_id,
        ':risk_reduction_strategy_score'               => $tc['risk_reduction_strategy_score'],
        ':biodiversity_protection_and_animal_integrity_score' => $tc['biodiversity_protection_and_animal_integrity_score'],
        ':local_community_involvement_score'           => $tc['local_community_involvement_score'],
        ':circular_economy_development_score'          => $tc['circular_economy_development_score'],
        ':multidisciplinary_sustainability_score'      => $tc['multidisciplinary_sustainability_score'],
        ':overall_score'                               => $tcOverall
    ]);

    // Insert into evaluation_thematic_criteria_community_support with computed overall_score
    $stmt = $pdo->prepare("INSERT INTO evaluation_thematic_criteria_community_support (evaluation_id, systemic_development_score, social_discrimination_fighting_score, habitat_protection_score, multistakeholder_involvement_score, multidisciplinary_sustainability_score, overall_score) VALUES (:evaluation_id, :systemic_development_score, :social_discrimination_fighting_score, :habitat_protection_score, :multistakeholder_involvement_score, :multidisciplinary_sustainability_score, :overall_score)");
    $stmt->execute([
        ':evaluation_id'                          => $evaluation_id,
        ':systemic_development_score'             => $tcom['systemic_development_score'],
        ':social_discrimination_fighting_score'   => $tcom['social_discrimination_fighting_score'],
        ':habitat_protection_score'               => $tcom['habitat_protection_score'],
        ':multistakeholder_involvement_score'      => $tcom['multistakeholder_involvement_score'],
        ':multidisciplinary_sustainability_score' => $tcom['multidisciplinary_sustainability_score'],
        ':overall_score'                          => $tcomOverall
    ]);

    // Insert into evaluation_thematic_criteria_culture_education_awareness with computed overall_score
    $stmt = $pdo->prepare("INSERT INTO evaluation_thematic_criteria_culture_education_awareness (evaluation_id, dissemination_tools_score, advocacy_and_legal_strengthening_score, innovation_score, multistakeholder_involvement_score, multidisciplinary_sustainability_score, overall_score) VALUES (:evaluation_id, :dissemination_tools_score, :advocacy_and_legal_strengthening_score, :innovation_score, :multistakeholder_involvement_score, :multidisciplinary_sustainability_score, :overall_score)");
    $stmt->execute([
        ':evaluation_id'                          => $evaluation_id,
        ':dissemination_tools_score'              => $tce['dissemination_tools_score'],
        ':advocacy_and_legal_strengthening_score' => $tce['advocacy_and_legal_strengthening_score'],
        ':innovation_score'                       => $tce['innovation_score'],
        ':multistakeholder_involvement_score'      => $tce['multistakeholder_involvement_score'],
        ':multidisciplinary_sustainability_score' => $tce['multidisciplinary_sustainability_score'],
        ':overall_score'                          => $tceOverall
    ]);

    // Compute evaluation_general values using the computed section overall scores
    $thematicOverall = $trOverall + $tsOverall + $tcOverall + $tcomOverall + $tceOverall;
    $generalOverall  = $peOverall + $projOverall + $fpOverall + $qeOverall + $thematicOverall;

    $generalData = [
        'proposing_entity_score'     => $peOverall,
        'general_project_score'      => $projOverall,
        'financial_plan_score'       => $fpOverall,
        'qualitative_elements_score' => $qeOverall,
        'thematic_criteria_score'    => $thematicOverall,
        'overall_score'              => $generalOverall
    ];

    $stmt = $pdo->prepare("INSERT INTO evaluation_general (evaluation_id, proposing_entity_score, general_project_score, financial_plan_score, qualitative_elements_score, thematic_criteria_score, overall_score) VALUES (:evaluation_id, :proposing_entity_score, :general_project_score, :financial_plan_score, :qualitative_elements_score, :thematic_criteria_score, :overall_score)");
    $stmt->execute(array_merge([':evaluation_id' => $evaluation_id], $generalData));

    $pdo->commit();

    echo json_encode(['success' => true, 'redirect' => 'my_evaluations.php']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Errore nell\'inserimento: ' . $e->getMessage()]);
}
?>