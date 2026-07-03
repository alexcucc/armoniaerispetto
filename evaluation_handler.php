<?php
session_start();

require_once 'evaluation_models.php';

$isAjaxRequest = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

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

function buildIncompleteSectionsMessage(string $baseMessage, array $sectionLabels): string
{
    if ($sectionLabels === []) {
        return $baseMessage;
    }

    return $baseMessage . "\nSezioni non completate:\n- " . implode("\n- ", $sectionLabels);
}

function validateLegacySections(array $postData, bool $validateForSubmit, bool $isAjaxRequest): array
{
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
            'fields' => ['overall_score'],
            'single_overall' => true,
        ],
        'thematic_safeguard' => [
            'label' => 'Criteri tematici - Tutela',
            'table'  => 'evaluation_thematic_criteria_safeguard',
            'fields' => ['overall_score'],
            'single_overall' => true,
        ],
        'thematic_cohabitation' => [
            'label' => 'Criteri tematici - Convivenza',
            'table'  => 'evaluation_thematic_criteria_cohabitation',
            'fields' => ['overall_score'],
            'single_overall' => true,
        ],
        'thematic_community_support' => [
            'label' => 'Criteri tematici - Supporto alla comunita',
            'table'  => 'evaluation_thematic_criteria_community_support',
            'fields' => ['overall_score'],
            'single_overall' => true,
        ],
        'thematic_culture_education' => [
            'label' => 'Criteri tematici - Cultura ed educazione',
            'table'  => 'evaluation_thematic_criteria_culture_education_awareness',
            'fields' => ['overall_score'],
            'single_overall' => true,
        ],
    ];

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
        $rawSection = $postData[$sectionKey] ?? null;
        if (!is_array($rawSection)) {
            if ($validateForSubmit) {
                $sectionIsIncomplete = true;
            }
            $rawSection = [];
        }

        $scores = [];
        foreach ($definition['fields'] as $fieldName) {
            if (!array_key_exists($fieldName, $rawSection)) {
                if ($validateForSubmit) {
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
                if ($validateForSubmit) {
                    $sectionIsIncomplete = true;
                }
                $scores[$fieldName] = null;
                continue;
            }

            $rawValueString = (string) $rawValue;
            if (!preg_match('/^(0|[1-9][0-9]*)$/', $rawValueString)) {
                if ($validateForSubmit) {
                    sendResponseAndExit($isAjaxRequest, false, 'Valori non validi presenti nella sezione "' . $definition['label'] . '".');
                }
                $scores[$fieldName] = null;
                continue;
            }

            $score = (int) $rawValueString;
            if ($score < 0 || $score > 10) {
                if ($validateForSubmit) {
                    sendResponseAndExit($isAjaxRequest, false, 'I punteggi devono essere compresi tra 0 e 10 per la sezione "' . $definition['label'] . '".');
                }
                $scores[$fieldName] = null;
                continue;
            }

            $scores[$fieldName] = $score;
        }

        if ($validateForSubmit && $sectionIsIncomplete) {
            $incompleteSections[$sectionKey] = $definition['label'];
        }

        $sectionScores[$sectionKey] = [
            'scores' => $scores,
            'overall' => $sumNullable($scores),
            'has_scores' => array_filter($scores, static fn ($value) => $value !== null) !== [],
        ];
    }

    return [
        'definitions' => $sectionDefinitions,
        'scores' => $sectionScores,
        'incomplete_labels' => array_values($incompleteSections),
    ];
}

function validateV4Sections(array $postData, bool $validateForSubmit, bool $isAjaxRequest): array
{
    $sections = evaluationGetV4EnabledSections();
    $data = evaluationV4CreateEmptyData();
    $incompleteSections = [];

    foreach ($sections as $sectionKey => $sectionDefinition) {
        $rawSection = $postData[$sectionKey] ?? [];
        if (!is_array($rawSection)) {
            $rawSection = [];
        }

        $rawCriterionNotes = $postData[$sectionKey . '_criterion_notes'] ?? [];
        if (!is_array($rawCriterionNotes)) {
            $rawCriterionNotes = [];
        }

        $sectionScores = [];
        $sectionCriterionNotes = [];
        $hasAnyScore = false;
        $sectionIsIncomplete = false;
        $sectionIsThematic = ($sectionDefinition['type'] ?? '') === 'thematic';

        foreach ($sectionDefinition['criteria'] as $fieldName => $criterionDefinition) {
            $sectionCriterionNotes[$fieldName] = trim((string) ($rawCriterionNotes[$fieldName] ?? ''));

            $rawValue = $rawSection[$fieldName] ?? null;
            if (is_string($rawValue)) {
                $rawValue = trim($rawValue);
            }

            if ($rawValue === '' || $rawValue === null) {
                if ($validateForSubmit && !$sectionIsThematic) {
                    $sectionIsIncomplete = true;
                }
                $sectionScores[$fieldName] = null;
                continue;
            }

            if (!preg_match('/^-?(0|[1-9][0-9]*)$/', (string) $rawValue)) {
                sendResponseAndExit($isAjaxRequest, false, 'Valore non valido per "' . $criterionDefinition['label'] . '".');
            }

            $bounds = evaluationGetV4FieldBounds($criterionDefinition);
            $score = (int) $rawValue;
            if ($score < $bounds['min'] || $score > $bounds['max']) {
                sendResponseAndExit(
                    $isAjaxRequest,
                    false,
                    'Il punteggio per "' . $criterionDefinition['label'] . '" deve essere compreso tra ' . $bounds['min'] . ' e ' . $bounds['max'] . '.'
                );
            }

            $sectionScores[$fieldName] = $score;
            $hasAnyScore = true;
        }

        if ($validateForSubmit) {
            if (!$sectionIsThematic && $sectionIsIncomplete) {
                $incompleteSections[] = $sectionDefinition['label'];
            }
        }

        $data[$sectionKey]['scores'] = $sectionScores;
        $data[$sectionKey]['criterion_notes'] = $sectionCriterionNotes;
    }

    return [
        'data' => $data,
        'totals' => evaluationV4CalculateTotals($data),
        'incomplete_labels' => $incompleteSections,
    ];
}

if (!isset($_SESSION['user_id'])) {
    sendResponseAndExit($isAjaxRequest, false, 'Utente non autenticato.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponseAndExit($isAjaxRequest, false, 'Metodo non consentito.');
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
    'SELECT a.status, c.status AS call_status, c.id AS call_for_proposal_id '
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
    sendResponseAndExit($isAjaxRequest, false, 'Il bando e chiuso e non e piu possibile valutare.');
}
if (($applicationStatus['status'] ?? '') !== 'FINAL_VALIDATION') {
    sendResponseAndExit($isAjaxRequest, false, 'E possibile valutare solo le risposte in stato "Convalida in definitiva".');
}

$callForProposalId = (int) ($applicationStatus['call_for_proposal_id'] ?? 0);
$evaluatorAssignmentStmt = $pdo->prepare(
    'SELECT 1 FROM call_for_proposal_evaluator WHERE call_for_proposal_id = :call_for_proposal_id AND evaluator_user_id = :evaluator_user_id LIMIT 1'
);
$evaluatorAssignmentStmt->execute([
    ':call_for_proposal_id' => $callForProposalId,
    ':evaluator_user_id' => $evaluatorId,
]);
if (!(bool) $evaluatorAssignmentStmt->fetchColumn()) {
    sendResponseAndExit($isAjaxRequest, false, 'Il valutatore selezionato non e abilitato per il bando della domanda.');
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
    'SELECT id, status, forced_weighted_total_score, model_version FROM evaluation WHERE application_id = :application_id AND evaluator_id = :evaluator_id LIMIT 1'
);
$existingEvaluationStmt->execute([
    ':application_id' => $applicationId,
    ':evaluator_id' => $evaluatorId,
]);
$existingEvaluation = $existingEvaluationStmt->fetch(PDO::FETCH_ASSOC) ?: null;

if ($existingEvaluation !== null) {
    if ($providedEvaluationId !== null && $providedEvaluationId !== (int) $existingEvaluation['id']) {
        sendResponseAndExit($isAjaxRequest, false, 'Identificativo valutazione non valido.');
    }
} elseif ($providedEvaluationId !== null) {
    sendResponseAndExit($isAjaxRequest, false, 'Identificativo valutazione non valido.');
}

$modelVersion = $existingEvaluation !== null
    ? strtolower((string) ($existingEvaluation['model_version'] ?? 'legacy'))
    : evaluationGetCurrentModelVersion();
$isLegacyModel = evaluationIsLegacyModel($modelVersion);

$forcedWeightedTotalScore = null;
$forcedWeightedTotalRaw = $_POST['forced_weighted_total_score'] ?? null;
$maxForcedWeightedTotalScore = evaluationGetForcedWeightedMaxScoreForModel($modelVersion);

if ($isForcedWeightedOnlyAction) {
    $evaluatorExistsStmt = $pdo->prepare('SELECT 1 FROM evaluator WHERE user_id = :evaluator_id LIMIT 1');
    $evaluatorExistsStmt->execute([':evaluator_id' => $evaluatorId]);
    if (!(bool) $evaluatorExistsStmt->fetchColumn()) {
        sendResponseAndExit($isAjaxRequest, false, 'Il valutatore selezionato non e valido.');
    }

    $existingForcedWeightedTotalScore = null;
    if ($existingEvaluation !== null && is_numeric($existingEvaluation['forced_weighted_total_score'] ?? null)) {
        $existingForcedWeightedTotalScore = (float) $existingEvaluation['forced_weighted_total_score'];
    }
    if ($existingEvaluation !== null && $existingForcedWeightedTotalScore === null) {
        sendResponseAndExit($isAjaxRequest, false, 'Esiste gia una valutazione standard per il bando e il valutatore selezionato.');
    }

    if (is_string($forcedWeightedTotalRaw)) {
        $forcedWeightedTotalRaw = trim($forcedWeightedTotalRaw);
    }
    if ($forcedWeightedTotalRaw === null || $forcedWeightedTotalRaw === '') {
        sendResponseAndExit($isAjaxRequest, false, 'Inserisci il voto totale pesato da caricare.');
    }
    if (!preg_match('/^(?:0|[1-9][0-9]*)(?:[.,][0-9]{1,2})?$/', (string) $forcedWeightedTotalRaw)) {
        sendResponseAndExit($isAjaxRequest, false, 'Il voto totale pesato non e valido.');
    }

    $forcedWeightedTotalScore = (float) str_replace(',', '.', (string) $forcedWeightedTotalRaw);
    if ($forcedWeightedTotalScore < 0 || $forcedWeightedTotalScore > $maxForcedWeightedTotalScore) {
        sendResponseAndExit(
            $isAjaxRequest,
            false,
            'Il voto totale pesato deve essere compreso tra 0 e ' . evaluationV4FormatScore($maxForcedWeightedTotalScore) . '.'
        );
    }

    $totalEvaluatorsStmt = $pdo->prepare('SELECT COUNT(*) FROM call_for_proposal_evaluator WHERE call_for_proposal_id = :call_for_proposal_id');
    $totalEvaluatorsStmt->execute([':call_for_proposal_id' => $callForProposalId]);
    $totalEvaluators = (int) $totalEvaluatorsStmt->fetchColumn();

    $completedEvaluationsStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM evaluation e JOIN call_for_proposal_evaluator cfe ON cfe.call_for_proposal_id = :call_for_proposal_id AND cfe.evaluator_user_id = e.evaluator_id WHERE e.application_id = :application_id AND e.status IN ('SUBMITTED', 'REVISED')"
    );
    $completedEvaluationsStmt->execute([
        ':application_id' => $applicationId,
        ':call_for_proposal_id' => $callForProposalId,
    ]);
    $completedEvaluations = (int) $completedEvaluationsStmt->fetchColumn();

    if ($existingEvaluation === null && ($totalEvaluators <= 0 || $completedEvaluations >= $totalEvaluators)) {
        sendResponseAndExit($isAjaxRequest, false, 'Valutazione forzata non consentita: per questa domanda risultano gia presenti tutte le valutazioni complete.');
    }
}

$legacyValidation = null;
$v4Validation = null;
if (!$isForcedWeightedOnlyAction) {
    if ($isLegacyModel) {
        $legacyValidation = validateLegacySections($_POST, $validateSectionsForSubmit, $isAjaxRequest);
    } else {
        $v4Validation = validateV4Sections($_POST, $validateSectionsForSubmit, $isAjaxRequest);
    }
}

$incompleteMessage = 'valutazione incompleta: valutazione non inviabile';
if ($validateSectionsForSubmit) {
    $incompleteLabels = [];
    if ($legacyValidation !== null) {
        $incompleteLabels = $legacyValidation['incomplete_labels'];
    } elseif ($v4Validation !== null) {
        $incompleteLabels = $v4Validation['incomplete_labels'];
    }

    if ($incompleteLabels !== []) {
        sendResponseAndExit($isAjaxRequest, false, buildIncompleteSectionsMessage($incompleteMessage, $incompleteLabels));
    }
}

try {
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
        $evaluationId = (int) $existingEvaluation['id'];

        $legacyTablesToReset = [
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
        $v4TablesToReset = [
            'evaluation_v4_general',
            'evaluation_v4_proposing_entity',
            'evaluation_v4_project',
            'evaluation_v4_financial_plan',
            'evaluation_v4_qualitative_elements',
            'evaluation_v4_thematic_safeguard',
            'evaluation_v4_thematic_conservation_species_habitat',
            'evaluation_v4_thematic_anthropic_threat_reduction',
            'evaluation_v4_thematic_cohabitation',
            'evaluation_v4_thematic_community_support',
            'evaluation_v4_thematic_culture_education',
            'evaluation_v4_thematic_weight_depth',
        ];

        foreach (array_merge($legacyTablesToReset, $v4TablesToReset) as $tableName) {
            $deleteStmt = $pdo->prepare("DELETE FROM {$tableName} WHERE evaluation_id = :evaluation_id");
            $deleteStmt->execute([':evaluation_id' => $evaluationId]);
        }
    } else {
        $insertEvaluationStmt = $pdo->prepare(
            'INSERT INTO evaluation (application_id, evaluator_id, status, forced_weighted_total_score, model_version) VALUES (:application_id, :evaluator_id, :status, :forced_weighted_total_score, :model_version)'
        );
        $insertEvaluationStmt->execute([
            ':application_id' => $applicationId,
            ':evaluator_id' => $evaluatorId,
            ':status' => $statusToApply,
            ':forced_weighted_total_score' => $isForcedWeightedOnlyAction ? $forcedWeightedTotalScore : null,
            ':model_version' => $modelVersion,
        ]);
        $evaluationId = (int) $pdo->lastInsertId();
    }

    if (!$isForcedWeightedOnlyAction) {
        if ($isLegacyModel) {
            $sectionDefinitions = $legacyValidation['definitions'];
            $sectionScores = $legacyValidation['scores'];

            foreach ($sectionDefinitions as $sectionKey => $definition) {
                $hasSectionScores = $sectionScores[$sectionKey]['has_scores'];
                if (!$isSubmitAction && !$hasSectionScores) {
                    continue;
                }

                if (!empty($definition['single_overall'])) {
                    $stmt = $pdo->prepare(sprintf('INSERT INTO %s (evaluation_id, overall_score) VALUES (:evaluation_id, :overall_score)', $definition['table']));
                    $stmt->execute([
                        ':evaluation_id' => $evaluationId,
                        ':overall_score' => $sectionScores[$sectionKey]['overall'],
                    ]);
                    continue;
                }

                $columnList = implode(', ', $definition['fields']);
                $placeholders = ':' . implode(', :', $definition['fields']);
                $stmt = $pdo->prepare(sprintf('INSERT INTO %s (evaluation_id, %s, overall_score) VALUES (:evaluation_id, %s, :overall_score)', $definition['table'], $columnList, $placeholders));
                $params = [
                    ':evaluation_id' => $evaluationId,
                    ':overall_score' => $sectionScores[$sectionKey]['overall'],
                ];
                foreach ($sectionScores[$sectionKey]['scores'] as $fieldName => $score) {
                    $params[':' . $fieldName] = $score;
                }
                $stmt->execute($params);
            }

            $thematicOverall = null;
            $legacyThematicValues = [
                $sectionScores['thematic_repopulation']['overall'],
                $sectionScores['thematic_safeguard']['overall'],
                $sectionScores['thematic_cohabitation']['overall'],
                $sectionScores['thematic_community_support']['overall'],
                $sectionScores['thematic_culture_education']['overall'],
            ];
            $filteredLegacyThematics = array_filter($legacyThematicValues, static fn ($value) => $value !== null);
            if ($filteredLegacyThematics !== []) {
                $thematicOverall = array_sum($filteredLegacyThematics);
            }

            $legacyOverallParts = [
                $sectionScores['proposing_entity']['overall'],
                $sectionScores['project']['overall'],
                $sectionScores['financial_plan']['overall'],
                $sectionScores['qualitative_elements']['overall'],
                $thematicOverall,
            ];
            $filteredLegacyOverall = array_filter($legacyOverallParts, static fn ($value) => $value !== null);
            $generalOverall = $filteredLegacyOverall !== [] ? array_sum($filteredLegacyOverall) : null;

            if ($isSubmitAction || $generalOverall !== null) {
                $stmt = $pdo->prepare(
                    'INSERT INTO evaluation_general (evaluation_id, proposing_entity_score, general_project_score, financial_plan_score, qualitative_elements_score, thematic_criteria_score, overall_score) VALUES (:evaluation_id, :proposing_entity_score, :general_project_score, :financial_plan_score, :qualitative_elements_score, :thematic_criteria_score, :overall_score)'
                );
                $stmt->execute([
                    ':evaluation_id' => $evaluationId,
                    ':proposing_entity_score' => $sectionScores['proposing_entity']['overall'],
                    ':general_project_score' => $sectionScores['project']['overall'],
                    ':financial_plan_score' => $sectionScores['financial_plan']['overall'],
                    ':qualitative_elements_score' => $sectionScores['qualitative_elements']['overall'],
                    ':thematic_criteria_score' => $thematicOverall,
                    ':overall_score' => $generalOverall,
                ]);
            }
        } else {
            $sections = evaluationGetV4EnabledSections();
            $data = $v4Validation['data'];
            $totals = $v4Validation['totals'];

            foreach ($sections as $sectionKey => $sectionDefinition) {
                $criteria = $sectionDefinition['criteria'];
                $fields = array_keys($criteria);
                $scoreValues = $data[$sectionKey]['scores'];
                $criterionNotes = $data[$sectionKey]['criterion_notes'] ?? [];
                $sectionTotals = $totals['sections'][$sectionKey] ?? [];
                $hasAnyScore = array_filter($scoreValues, static fn ($value) => $value !== null) !== [];
                $hasAnyCriterionNote = array_filter($criterionNotes, static fn ($value) => trim((string) $value) !== '') !== [];
                $hasAnyContent = $hasAnyScore || $hasAnyCriterionNote;
                if (!$isSubmitAction && !$hasAnyContent) {
                    continue;
                }

                $params = [':evaluation_id' => $evaluationId];
                $columns = ['evaluation_id'];
                $placeholders = [':evaluation_id'];
                foreach ($fields as $fieldName) {
                    $noteColumn = evaluationV4GetCriterionNoteColumn($fieldName);
                    $columns[] = $fieldName;
                    $columns[] = $noteColumn;
                    $placeholders[] = ':' . $fieldName;
                    $placeholders[] = ':' . $noteColumn;
                    $params[':' . $fieldName] = $scoreValues[$fieldName];
                    $params[':' . $noteColumn] = $criterionNotes[$fieldName] ?? '';
                }

                if (($sectionDefinition['type'] ?? '') === 'thematic') {
                    $columns[] = 'weighted_score';
                    $placeholders[] = ':weighted_score';
                    $params[':weighted_score'] = $sectionTotals['weighted_score'] ?? null;
                } else {
                    $columns[] = 'overall_score';
                    $placeholders[] = ':overall_score';
                    $params[':overall_score'] = $sectionTotals['weighted_score'] ?? null;
                }

                $stmt = $pdo->prepare(
                    sprintf(
                        'INSERT INTO %s (%s) VALUES (%s)',
                        $sectionDefinition['table'],
                        implode(', ', $columns),
                        implode(', ', $placeholders)
                    )
                );
                $stmt->execute($params);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO evaluation_v4_general (evaluation_id, proposing_entity_score, project_score, financial_plan_score, qualitative_elements_score, thematic_criteria_score, overall_score) VALUES (:evaluation_id, :proposing_entity_score, :project_score, :financial_plan_score, :qualitative_elements_score, :thematic_criteria_score, :overall_score)'
            );
            $stmt->execute([
                ':evaluation_id' => $evaluationId,
                ':proposing_entity_score' => $totals['sections']['proposing_entity']['weighted_score'] ?? null,
                ':project_score' => $totals['sections']['project']['weighted_score'] ?? null,
                ':financial_plan_score' => $totals['sections']['financial_plan']['weighted_score'] ?? null,
                ':qualitative_elements_score' => $totals['sections']['qualitative_elements']['weighted_score'] ?? null,
                ':thematic_criteria_score' => $totals['thematic_total'],
                ':overall_score' => $totals['overall_total'],
            ]);
        }
    }

    $statusUpdateStmt = $pdo->prepare('UPDATE evaluation SET status = :status, forced_weighted_total_score = :forced_weighted_total_score, model_version = :model_version WHERE id = :id');
    $statusUpdateStmt->execute([
        ':status' => $statusToApply,
        ':forced_weighted_total_score' => $isForcedWeightedOnlyAction ? $forcedWeightedTotalScore : null,
        ':model_version' => $modelVersion,
        ':id' => $evaluationId,
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





