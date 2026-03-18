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

$action = strtolower($_POST['action'] ?? 'save');
if (!in_array($action, ['save', 'submit', 'submit_force'], true)) {
    sendResponseAndExit($isAjaxRequest, false, 'Azione non valida.');
}

$rolePermissionManager = new RolePermissionManager($pdo);
$currentUserId = (int) $_SESSION['user_id'];
$canCreateEvaluation = $rolePermissionManager->userHasPermission($currentUserId, RolePermissionManager::$PERMISSIONS['EVALUATION_CREATE']);
$canMonitorEvaluators = $rolePermissionManager->userHasPermission($currentUserId, RolePermissionManager::$PERMISSIONS['EVALUATOR_MONITOR']);
$isForcedWeightedOnlyAction = $action === 'submit_force';
if ($isForcedWeightedOnlyAction) {
    if (!$canCreateEvaluation && !$canMonitorEvaluators) {
        sendResponseAndExit($isAjaxRequest, false, 'Accesso non consentito.');
    }
} elseif (!$canCreateEvaluation) {
    sendResponseAndExit($isAjaxRequest, false, 'Accesso non consentito.');
}

$adminCheckStmt = $pdo->prepare(
    "SELECT 1 FROM user_role ur JOIN role r ON r.id = ur.role_id WHERE ur.user_id = :user_id AND r.name = 'Admin' LIMIT 1"
);
$adminCheckStmt->execute([':user_id' => $currentUserId]);
$isAdminUser = (bool) $adminCheckStmt->fetchColumn();

$applicationId = $_POST['application_id'] ?? null;

if ($applicationId === null) {
    sendResponseAndExit($isAjaxRequest, false, 'Dati della valutazione mancanti.');
}

if (!ctype_digit((string) $applicationId)) {
    sendResponseAndExit($isAjaxRequest, false, 'Identificativo risposta al bando non valido.');
}

$applicationId = (int) $applicationId;

$isSubmitAction = in_array($action, ['submit', 'submit_force'], true);
$validateSectionsForSubmit = $isSubmitAction && !$isForcedWeightedOnlyAction;
$redirectTargetParam = $_POST['redirect_target'] ?? '';
$redirectTarget = in_array($redirectTargetParam, ['evaluations', 'overview'], true) ? $redirectTargetParam : '';

$evaluatorId = $currentUserId;
if ($isForcedWeightedOnlyAction) {
    $targetEvaluatorId = $_POST['evaluator_id'] ?? null;
    if ($targetEvaluatorId === null || !ctype_digit((string) $targetEvaluatorId)) {
        sendResponseAndExit($isAjaxRequest, false, 'Valutatore selezionato non valido.');
    }

    $evaluatorId = (int) $targetEvaluatorId;
}

$forcedActionRedirect = 'evaluations.php';
if ($isForcedWeightedOnlyAction) {
    $isSelfForce = $evaluatorId === $currentUserId;
    if (!$isAdminUser && !$isSelfForce) {
        sendResponseAndExit($isAjaxRequest, false, 'Puoi inviare una valutazione forzata solo per una tua valutazione non ancora compilata.');
    }

    if ($redirectTarget === 'overview') {
        $forcedActionRedirect = 'evaluator_evaluation_overview.php';
    } elseif ($redirectTarget === 'evaluations') {
        $forcedActionRedirect = 'evaluations.php';
    } else {
        $forcedActionRedirect = $isSelfForce ? 'evaluations.php' : 'evaluator_evaluation_overview.php';
    }
}

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
    'SELECT id, status, forced_weighted_total_score '
    . 'FROM evaluation WHERE application_id = :application_id AND evaluator_id = :evaluator_id LIMIT 1'
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

$forcedWeightedTotalScore = null;
$forcedWeightedTotalRaw = $_POST['forced_weighted_total_score'] ?? null;
$maxForcedWeightedTotalScore = 2090.0;

if ($isForcedWeightedOnlyAction) {
    $evaluatorExistsStmt = $pdo->prepare('SELECT 1 FROM evaluator WHERE user_id = :evaluator_id LIMIT 1');
    $evaluatorExistsStmt->execute([':evaluator_id' => $evaluatorId]);
    $evaluatorExists = (bool) $evaluatorExistsStmt->fetchColumn();
    if (!$evaluatorExists) {
        sendResponseAndExit($isAjaxRequest, false, 'Il valutatore selezionato non è valido.');
    }

    $existingForcedWeightedTotalScore = null;
    if ($existingEvaluation !== null && is_numeric($existingEvaluation['forced_weighted_total_score'] ?? null)) {
        $existingForcedWeightedTotalScore = (float) $existingEvaluation['forced_weighted_total_score'];
    }

    if ($existingEvaluation !== null && $existingForcedWeightedTotalScore === null) {
        sendResponseAndExit($isAjaxRequest, false, 'Esiste già una valutazione standard per il bando e il valutatore selezionato.');
    }

    if (is_string($forcedWeightedTotalRaw)) {
        $forcedWeightedTotalRaw = trim($forcedWeightedTotalRaw);
    }

    if ($forcedWeightedTotalRaw === null || $forcedWeightedTotalRaw === '') {
        sendResponseAndExit($isAjaxRequest, false, 'Inserisci il voto totale pesato da caricare.');
    }

    $forcedWeightedTotalString = (string) $forcedWeightedTotalRaw;
    if (!preg_match('/^(?:0|[1-9][0-9]*)(?:[.,][0-9]{1,2})?$/', $forcedWeightedTotalString)) {
        sendResponseAndExit($isAjaxRequest, false, 'Il voto totale pesato non è valido.');
    }

    $normalizedForcedWeightedTotal = str_replace(',', '.', $forcedWeightedTotalString);
    $forcedWeightedTotalScore = (float) $normalizedForcedWeightedTotal;
    if ($forcedWeightedTotalScore < 0 || $forcedWeightedTotalScore > $maxForcedWeightedTotalScore) {
        sendResponseAndExit($isAjaxRequest, false, 'Il voto totale pesato deve essere compreso tra 0 e 2090.');
    }

    $totalEvaluatorsStmt = $pdo->query('SELECT COUNT(*) FROM evaluator');
    $totalEvaluators = (int) $totalEvaluatorsStmt->fetchColumn();

    $completedEvaluationsStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM evaluation WHERE application_id = :application_id AND status IN ('SUBMITTED', 'REVISED')"
    );
    $completedEvaluationsStmt->execute([':application_id' => $applicationId]);
    $completedEvaluations = (int) $completedEvaluationsStmt->fetchColumn();

    if ($existingEvaluation === null && ($totalEvaluators <= 0 || $completedEvaluations >= $totalEvaluators)) {
        sendResponseAndExit(
            $isAjaxRequest,
            false,
            'Valutazione forzata non consentita: per questa domanda risultano già presenti tutte le valutazioni complete.'
        );
    }
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
            'overall_score',
        ],
        'single_overall' => true,
    ],
    'thematic_safeguard' => [
        'label' => 'Criteri tematici - Tutela',
        'table'  => 'evaluation_thematic_criteria_safeguard',
        'fields' => [
            'overall_score',
        ],
        'single_overall' => true,
    ],
    'thematic_cohabitation' => [
        'label' => 'Criteri tematici - Convivenza',
        'table'  => 'evaluation_thematic_criteria_cohabitation',
        'fields' => [
            'overall_score',
        ],
        'single_overall' => true,
    ],
    'thematic_community_support' => [
        'label' => 'Criteri tematici - Supporto alla comunità',
        'table'  => 'evaluation_thematic_criteria_community_support',
        'fields' => [
            'overall_score',
        ],
        'single_overall' => true,
    ],
    'thematic_culture_education' => [
        'label' => 'Criteri tematici - Cultura ed educazione',
        'table'  => 'evaluation_thematic_criteria_culture_education_awareness',
        'fields' => [
            'overall_score',
        ],
        'single_overall' => true,
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
        if ($validateSectionsForSubmit) {
            $sectionIsIncomplete = true;
        }

        $rawSection = [];
    }

    $scores = [];
    foreach ($definition['fields'] as $fieldName) {
        if (!array_key_exists($fieldName, $rawSection)) {
            if ($validateSectionsForSubmit) {
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
            if ($validateSectionsForSubmit) {
                $sectionIsIncomplete = true;
            }

            $scores[$fieldName] = null;
            continue;
        }

        $rawValueString = (string) $rawValue;
        if (!preg_match('/^(0|[1-9][0-9]*)$/', $rawValueString)) {
            if ($validateSectionsForSubmit) {
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
            if ($validateSectionsForSubmit) {
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

    if ($validateSectionsForSubmit && $sectionIsIncomplete) {
        $incompleteSections[$sectionKey] = $definition['label'];
    }

    $sectionScores[$sectionKey] = [
        'scores'     => $scores,
        'overall'    => $sumNullable($scores),
        'has_scores' => array_filter($scores, static fn ($value) => $value !== null) !== [],
    ];
}

if ($validateSectionsForSubmit && $incompleteSections !== []) {
    sendResponseAndExit(
        $isAjaxRequest,
        false,
        $buildIncompleteSectionsMessage($incompleteMessage, array_values($incompleteSections))
    );
}

try {
    // Use transaction for consistency
    $pdo->beginTransaction();

    if ($isForcedWeightedOnlyAction) {
        $statusToApply = 'REVISED';
    } elseif ($existingEvaluation !== null) {
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
            'INSERT INTO evaluation (application_id, evaluator_id, status, forced_weighted_total_score) '
            . 'VALUES (:application_id, :evaluator_id, :status, :forced_weighted_total_score)'
        );
        $insertEvaluationStmt->execute([
            ':application_id' => $applicationId,
            ':evaluator_id'   => $evaluatorId,
            ':status'         => $statusToApply,
            ':forced_weighted_total_score' => $isForcedWeightedOnlyAction ? $forcedWeightedTotalScore : null,
        ]);
        $evaluation_id = (int) $pdo->lastInsertId();
    }

    if (!$isForcedWeightedOnlyAction) {
        foreach ($sectionDefinitions as $sectionKey => $definition) {
            $hasSectionScores = $sectionScores[$sectionKey]['has_scores'];

            if (!$isSubmitAction && !$hasSectionScores) {
                continue;
            }

            if (!empty($definition['single_overall'])) {
                $stmt = $pdo->prepare(
                    sprintf(
                        'INSERT INTO %s (evaluation_id, overall_score) VALUES (:evaluation_id, :overall_score)',
                        $definition['table']
                    )
                );
                $stmt->execute([
                    ':evaluation_id' => $evaluation_id,
                    ':overall_score' => $sectionScores[$sectionKey]['overall'],
                ]);
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
    } else {
        $clearGeneralStmt = $pdo->prepare('DELETE FROM evaluation_general WHERE evaluation_id = :evaluation_id');
        $clearGeneralStmt->execute([':evaluation_id' => $evaluation_id]);
    }

    $statusUpdateStmt = $pdo->prepare(
        'UPDATE evaluation SET status = :status, forced_weighted_total_score = :forced_weighted_total_score WHERE id = :id'
    );
    $statusUpdateStmt->execute([
        ':status' => $statusToApply,
        ':forced_weighted_total_score' => $isForcedWeightedOnlyAction ? $forcedWeightedTotalScore : null,
        ':id'     => $evaluation_id,
    ]);

    $pdo->commit();

    if ($isForcedWeightedOnlyAction) {
        $successMessage = 'Valutazione forzata revisionata con successo.';
    } elseif ($statusToApply === 'REVISED') {
        $successMessage = 'Valutazione revisionata con successo.';
    } elseif ($isSubmitAction) {
        $successMessage = 'Valutazione inviata con successo.';
    } else {
        $successMessage = 'Valutazione salvata come bozza.';
    }
    $successRedirect = $isForcedWeightedOnlyAction ? $forcedActionRedirect : 'evaluations.php';
    sendResponseAndExit($isAjaxRequest, true, $successMessage, $successRedirect);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendResponseAndExit($isAjaxRequest, false, 'Errore nell\'inserimento: ' . $e->getMessage());
}
?>
