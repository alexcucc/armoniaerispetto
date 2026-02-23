<?php
session_start();

$isAjaxRequest = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

/**
 * Sends a consistent response to the client and terminates the script.
 */
function sendResponseAndExit(bool $isAjax, bool $success, ?string $message = null, ?string $redirect = null): void
{
    if (!$isAjax && $success && $message !== null) {
        $_SESSION['evaluation_success'] = $message;
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        $payload = ['success' => $success];
        if ($message !== null) {
            $payload['message'] = $message;
        }
        if ($redirect !== null) {
            $payload['redirect'] = $redirect;
        }
        echo json_encode($payload);
        exit;
    }

    if ($success && $redirect !== null) {
        header('Location: ' . $redirect);
        exit;
    }

    if ($message !== null) {
        $_SESSION['evaluation_error'] = $message;
    }
    header('Location: evaluations.php');
    exit;
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponseAndExit($isAjaxRequest, false, 'Utente non autenticato.');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponseAndExit($isAjaxRequest, false, 'Metodo non consentito.');
    exit;
}

include_once 'db/common-db.php';
include_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);
if (!$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['EVALUATION_CREATE'])) {
    sendResponseAndExit($isAjaxRequest, false, 'Accesso non consentito.');
}

$applicationId = $_POST['application_id'] ?? null;
$evaluatorId   = $_POST['evaluator_id'] ?? null;

if ($applicationId === null || $evaluatorId === null) {
    sendResponseAndExit($isAjaxRequest, false, 'Dati della valutazione mancanti.');
}

if (!ctype_digit((string) $applicationId) || !ctype_digit((string) $evaluatorId)) {
    sendResponseAndExit($isAjaxRequest, false, 'Identificativi non validi.');
}

$applicationId = (int) $applicationId;
$evaluatorId   = (int) $evaluatorId;

$action = strtolower($_POST['action'] ?? 'save');
if (!in_array($action, ['save', 'submit'], true)) {
    sendResponseAndExit($isAjaxRequest, false, 'Azione non valida.');
}
$isSubmitAction = $action === 'submit';

$applicationStatusStmt = $pdo->prepare(
    'SELECT a.status, c.status AS call_status '
    . 'FROM application a '
    . 'JOIN call_for_proposal c ON a.call_for_proposal_id = c.id '
    . 'WHERE a.id = :application_id'
);
$applicationStatusStmt->execute([':application_id' => $applicationId]);
$applicationStatus = $applicationStatusStmt->fetch(PDO::FETCH_ASSOC);
if ($applicationStatus === false) {
    sendResponseAndExit($isAjaxRequest, false, 'Risposta al bando non trovata.');
}
if (($applicationStatus['call_status'] ?? null) === 'CLOSED') {
    sendResponseAndExit($isAjaxRequest, false, 'Il bando è chiuso e non è più possibile valutare.');
}
if (($applicationStatus['status'] ?? '') !== 'FINAL_VALIDATION') {
    sendResponseAndExit($isAjaxRequest, false, 'È possibile valutare solo le risposte in stato "Convalida in definitiva".');
}

$providedEvaluationId = null;
if (isset($_POST['evaluation_id'])) {
    $evaluationIdParam = $_POST['evaluation_id'];
    if (!ctype_digit((string) $evaluationIdParam)) {
        sendResponseAndExit($isAjaxRequest, false, 'Identificativo valutazione non valido.');
    }
    $providedEvaluationId = (int) $evaluationIdParam;
}

$existingEvaluationStmt = $pdo->prepare(
    'SELECT id, status FROM evaluation WHERE application_id = :application_id AND evaluator_id = :evaluator_id LIMIT 1'
);
$existingEvaluationStmt->execute([
    ':application_id' => $applicationId,
    ':evaluator_id'   => $evaluatorId,
]);
$existingEvaluation = $existingEvaluationStmt->fetch(PDO::FETCH_ASSOC);

if ($existingEvaluation === false) {
    $existingEvaluation = null;
}

if ($existingEvaluation !== null) {
    if ($providedEvaluationId !== null && $providedEvaluationId !== (int) $existingEvaluation['id']) {
        sendResponseAndExit($isAjaxRequest, false, 'Identificativo valutazione non valido.');
    }
} elseif ($providedEvaluationId !== null) {
    sendResponseAndExit($isAjaxRequest, false, 'Identificativo valutazione non valido.');
}

// Section definitions reused from the evaluation form to validate and persist scores consistently
$sectionDefinitions = [
    'proposing_entity' => [
        'label' => 'Soggetto proponente',
        'table'  => 'evaluation_proposing_entity',
        'fields' => [
            'general_information_score',
            'experience_score',
            'organizational_capacity_score',
            'policy_score',
            'budget_score',
            'purpose_and_local_involvement_score',
            'partnership_and_visibility_score',
        ],
    ],
    'project' => [
        'label' => 'Progetto',
        'table'  => 'evaluation_project',
        'fields' => [
            'needs_identification_and_problem_analysis_score',
            'adherence_to_statuary_purposes_score',
            'social_weight_score',
            'objectives_score',
            'expected_results_score',
            'activity_score',
            'local_purpose_score',
            'partnership_and_relations_with_local_authorities_score',
            'synergies_and_design_inefficiencies_score',
            'communication_and_visibility_score',
        ],
    ],
    'financial_plan' => [
        'label' => 'Piano finanziario',
        'table'  => 'evaluation_financial_plan',
        'fields' => [
            'completeness_and_clarity_of_budget_score',
            'consistency_with_objectives_score',
            'cofinancing_score',
            'flexibility_score',
        ],
    ],
    'qualitative_elements' => [
        'label' => 'Elementi qualitativi',
        'table'  => 'evaluation_qualitative_elements',
        'fields' => [
            'impact_score',
            'relevance_score',
            'congruity_score',
            'innovation_score',
            'rigor_and_scientific_validity_score',
            'replicability_and_scalability_score',
            'cohabitation_evidence_score',
            'research_and_university_partnership_score',
        ],
    ],
    'thematic_repopulation' => [
        'label' => 'Criteri tematici - Ripopolamento',
        'table'  => 'evaluation_thematic_criteria_repopulation',
        'fields' => [
            'habitat_score',
            'threat_mitigation_strategy_score',
            'local_community_involvement_score',
            'multidisciplinary_sustainability_score',
        ],
    ],
    'thematic_safeguard' => [
        'label' => 'Criteri tematici - Tutela',
        'table'  => 'evaluation_thematic_criteria_safeguard',
        'fields' => [
            'systemic_approach_score',
            'advocacy_and_legal_strengthening_score',
            'habitat_safeguard_score',
            'reservers_development_participation_score',
            'crucial_species_activities_score',
            'multistakeholder_involvement_score',
            'multidisciplinary_sustainability_score',
        ],
    ],
    'thematic_cohabitation' => [
        'label' => 'Criteri tematici - Convivenza',
        'table'  => 'evaluation_thematic_criteria_cohabitation',
        'fields' => [
            'risk_reduction_strategy_score',
            'biodiversity_protection_and_animal_integrity_score',
            'local_community_involvement_score',
            'circular_economy_development_score',
            'multidisciplinary_sustainability_score',
        ],
    ],
    'thematic_community_support' => [
        'label' => 'Criteri tematici - Supporto alla comunità',
        'table'  => 'evaluation_thematic_criteria_community_support',
        'fields' => [
            'systemic_development_score',
            'social_discrimination_fighting_score',
            'habitat_protection_score',
            'multistakeholder_involvement_score',
            'multidisciplinary_sustainability_score',
        ],
    ],
    'thematic_culture_education' => [
        'label' => 'Criteri tematici - Cultura ed educazione',
        'table'  => 'evaluation_thematic_criteria_culture_education_awareness',
        'fields' => [
            'dissemination_tools_score',
            'advocacy_and_legal_strengthening_score',
            'innovation_score',
            'multistakeholder_involvement_score',
            'multidisciplinary_sustainability_score',
        ],
    ],
];

$incompleteMessage = 'valutazione incompleta: valutazione non inviabile';
$buildIncompleteSectionsMessage = static function (string $baseMessage, array $sectionLabels): string {
    if ($sectionLabels === []) {
        return $baseMessage;
    }

    return $baseMessage . "\nSezioni non completate:\n- " . implode("\n- ", $sectionLabels);
};

$sumNullable = static function (array $values): ?int {
    $filtered = array_filter($values, static fn ($value) => $value !== null);
    if ($filtered === []) {
        return null;
    }

    return array_sum($filtered);
};

$sectionScores = [];
$incompleteSections = [];
foreach ($sectionDefinitions as $sectionKey => $definition) {
    $sectionIsIncomplete = false;
    $rawSection = $_POST[$sectionKey] ?? null;
    if (!is_array($rawSection)) {
        if ($isSubmitAction) {
            $sectionIsIncomplete = true;
        }

        $rawSection = [];
    }

    $scores = [];
    foreach ($definition['fields'] as $fieldName) {
        if (!array_key_exists($fieldName, $rawSection)) {
            if ($isSubmitAction) {
                $sectionIsIncomplete = true;
            }

            $scores[$fieldName] = null;
            continue;
        }

        $rawValue = $rawSection[$fieldName];
        if (is_string($rawValue)) {
            $rawValue = trim($rawValue);
        }

        if ($rawValue === '' || $rawValue === null) {
            if ($isSubmitAction) {
                $sectionIsIncomplete = true;
            }

            $scores[$fieldName] = null;
            continue;
        }

        $rawValueString = (string) $rawValue;
        if (!preg_match('/^(0|[1-9][0-9]*)$/', $rawValueString)) {
            if ($isSubmitAction) {
                sendResponseAndExit(
                    $isAjaxRequest,
                    false,
                    'Valori non validi presenti nella sezione "' . $definition['label'] . '".'
                );
            }

            $scores[$fieldName] = null;
            continue;
        }

        $score = (int) $rawValueString;
        if ($score < 0 || $score > 10) {
            if ($isSubmitAction) {
                sendResponseAndExit(
                    $isAjaxRequest,
                    false,
                    'I punteggi devono essere compresi tra 0 e 10 per la sezione "' . $definition['label'] . '".'
                );
            }

            $scores[$fieldName] = null;
            continue;
        }

        $scores[$fieldName] = $score;
    }

    if ($isSubmitAction && $sectionIsIncomplete) {
        $incompleteSections[$sectionKey] = $definition['label'];
    }

    $sectionScores[$sectionKey] = [
        'scores'     => $scores,
        'overall'    => $sumNullable($scores),
        'has_scores' => array_filter($scores, static fn ($value) => $value !== null) !== [],
    ];
}

if ($isSubmitAction && $incompleteSections !== []) {
    sendResponseAndExit(
        $isAjaxRequest,
        false,
        $buildIncompleteSectionsMessage($incompleteMessage, array_values($incompleteSections))
    );
}

try {
    // Use transaction for consistency
    $pdo->beginTransaction();

    if ($existingEvaluation !== null) {
        $existingStatus = strtoupper((string) ($existingEvaluation['status'] ?? ''));
        $isPreviouslySent = in_array($existingStatus, ['SUBMITTED', 'REVISED'], true);
        $statusToApply = $isPreviouslySent ? 'REVISED' : ($isSubmitAction ? 'SUBMITTED' : 'DRAFT');
    } else {
        $statusToApply = $isSubmitAction ? 'SUBMITTED' : 'DRAFT';
    }

    if ($existingEvaluation !== null) {
        $evaluation_id = (int) $existingEvaluation['id'];

        $tablesToReset = [
            'evaluation_general',
            'evaluation_proposing_entity',
            'evaluation_project',
            'evaluation_financial_plan',
            'evaluation_qualitative_elements',
            'evaluation_thematic_criteria_repopulation',
            'evaluation_thematic_criteria_safeguard',
            'evaluation_thematic_criteria_cohabitation',
            'evaluation_thematic_criteria_community_support',
            'evaluation_thematic_criteria_culture_education_awareness',
        ];

        foreach ($tablesToReset as $tableName) {
            $deleteStmt = $pdo->prepare("DELETE FROM {$tableName} WHERE evaluation_id = :evaluation_id");
            $deleteStmt->execute([':evaluation_id' => $evaluation_id]);
        }
    } else {
        $insertEvaluationStmt = $pdo->prepare(
            'INSERT INTO evaluation (application_id, evaluator_id, status) VALUES (:application_id, :evaluator_id, :status)'
        );
        $insertEvaluationStmt->execute([
            ':application_id' => $applicationId,
            ':evaluator_id'   => $evaluatorId,
            ':status'         => $statusToApply,
        ]);
        $evaluation_id = (int) $pdo->lastInsertId();
    }

    foreach ($sectionDefinitions as $sectionKey => $definition) {
        $hasSectionScores = $sectionScores[$sectionKey]['has_scores'];

        if (!$isSubmitAction && !$hasSectionScores) {
            continue;
        }

        $columnList = implode(', ', $definition['fields']);
        $placeholders = ':' . implode(', :', $definition['fields']);
        $sql = sprintf(
            'INSERT INTO %s (evaluation_id, %s, overall_score) VALUES (:evaluation_id, %s, :overall_score)',
            $definition['table'],
            $columnList,
            $placeholders
        );

        $stmt = $pdo->prepare($sql);
        $params = [
            ':evaluation_id' => $evaluation_id,
            ':overall_score' => $sectionScores[$sectionKey]['overall'],
        ];

        foreach ($sectionScores[$sectionKey]['scores'] as $fieldName => $score) {
            $params[':' . $fieldName] = $score;
        }

        $stmt->execute($params);
    }

    $thematicOverall = $sumNullable([
        $sectionScores['thematic_repopulation']['overall'],
        $sectionScores['thematic_safeguard']['overall'],
        $sectionScores['thematic_cohabitation']['overall'],
        $sectionScores['thematic_community_support']['overall'],
        $sectionScores['thematic_culture_education']['overall'],
    ]);

    $generalOverall  = $sumNullable([
        $sectionScores['proposing_entity']['overall'],
        $sectionScores['project']['overall'],
        $sectionScores['financial_plan']['overall'],
        $sectionScores['qualitative_elements']['overall'],
        $thematicOverall,
    ]);

    if ($isSubmitAction || $generalOverall !== null) {
        $stmt = $pdo->prepare(
            'INSERT INTO evaluation_general '
            . '(evaluation_id, proposing_entity_score, general_project_score, financial_plan_score, qualitative_elements_score, '
            . 'thematic_criteria_score, overall_score) '
            . 'VALUES (:evaluation_id, :proposing_entity_score, :general_project_score, :financial_plan_score, '
            . ':qualitative_elements_score, :thematic_criteria_score, :overall_score)'
        );

        $stmt->execute([
            ':evaluation_id'              => $evaluation_id,
            ':proposing_entity_score'     => $sectionScores['proposing_entity']['overall'],
            ':general_project_score'      => $sectionScores['project']['overall'],
            ':financial_plan_score'       => $sectionScores['financial_plan']['overall'],
            ':qualitative_elements_score' => $sectionScores['qualitative_elements']['overall'],
            ':thematic_criteria_score'    => $thematicOverall,
            ':overall_score'              => $generalOverall,
        ]);
    }

    $statusUpdateStmt = $pdo->prepare('UPDATE evaluation SET status = :status WHERE id = :id');
    $statusUpdateStmt->execute([
        ':status' => $statusToApply,
        ':id'     => $evaluation_id,
    ]);

    $pdo->commit();

    if ($statusToApply === 'REVISED') {
        $successMessage = 'Valutazione revisionata con successo.';
    } elseif ($isSubmitAction) {
        $successMessage = 'Valutazione inviata con successo.';
    } else {
        $successMessage = 'Valutazione salvata come bozza.';
    }
    sendResponseAndExit($isAjaxRequest, true, $successMessage, 'evaluations.php');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendResponseAndExit($isAjaxRequest, false, 'Errore nell\'inserimento: ' . $e->getMessage());
}
?>
