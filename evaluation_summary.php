<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include_once 'db/common-db.php';
include_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);
if ($rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['EVALUATION_CREATE']) === false) {
    header("Location: index.php");
    exit;
}

$applicationId = filter_input(INPUT_GET, 'application_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$applicationId) {
    $_SESSION['evaluation_error'] = 'Valutazione non trovata.';
    header('Location: evaluations.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT e.id AS evaluation_id, e.status AS evaluation_status, e.updated_at, "
    . "c.title AS call_title, COALESCE(o.name, 'Soggetto proponente') AS organization_name, "
    . "eg.proposing_entity_score, eg.general_project_score, eg.financial_plan_score, "
    . "eg.qualitative_elements_score, eg.thematic_criteria_score, eg.overall_score, "
    . "etr.overall_score AS thematic_repopulation_score, "
    . "ets.overall_score AS thematic_safeguard_score, "
    . "etc_hab.overall_score AS thematic_cohabitation_score, "
    . "etc_support.overall_score AS thematic_community_support_score, "
    . "etc_culture.overall_score AS thematic_culture_education_score "
    . "FROM evaluation e "
    . "JOIN application a ON e.application_id = a.id "
    . "JOIN call_for_proposal c ON a.call_for_proposal_id = c.id "
    . "LEFT JOIN organization o ON a.organization_id = o.id "
    . "LEFT JOIN evaluation_general eg ON eg.evaluation_id = e.id "
    . "LEFT JOIN evaluation_thematic_criteria_repopulation etr ON etr.evaluation_id = e.id "
    . "LEFT JOIN evaluation_thematic_criteria_safeguard ets ON ets.evaluation_id = e.id "
    . "LEFT JOIN evaluation_thematic_criteria_cohabitation etc_hab ON etc_hab.evaluation_id = e.id "
    . "LEFT JOIN evaluation_thematic_criteria_community_support etc_support ON etc_support.evaluation_id = e.id "
    . "LEFT JOIN evaluation_thematic_criteria_culture_education_awareness etc_culture ON etc_culture.evaluation_id = e.id "
    . "WHERE e.application_id = :application_id AND e.evaluator_id = :evaluator_id "
    . "LIMIT 1"
);
$stmt->execute([
    ':application_id' => $applicationId,
    ':evaluator_id' => $_SESSION['user_id'],
]);
$evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evaluation) {
    $_SESSION['evaluation_error'] = 'Valutazione non trovata.';
    header('Location: evaluations.php');
    exit;
}

$sumNullableScores = static function (array $scores): ?float {
    $filteredScores = array_filter($scores, static fn ($score) => $score !== null);
    if ($filteredScores === []) {
        return null;
    }

    return array_sum($filteredScores);
};

$loadSectionScores = static function (PDO $pdo, int $evaluationId, string $table, array $fields): ?array {
    if ($fields === []) {
        return null;
    }

    $columns = implode(', ', $fields);
    $stmt = $pdo->prepare("SELECT {$columns} FROM {$table} WHERE evaluation_id = :evaluation_id LIMIT 1");
    $stmt->execute([':evaluation_id' => $evaluationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
};

$nonThematicSectionDefinitions = [
    'proposing_entity' => [
        'label' => 'Soggetto proponente',
        'table' => 'evaluation_proposing_entity',
        'weights' => [
            'general_information_score' => 2,
            'experience_score' => 4,
            'organizational_capacity_score' => 4,
            'policy_score' => 4,
            'budget_score' => 3,
            'purpose_and_local_involvement_score' => 4,
            'partnership_and_visibility_score' => 4,
        ],
    ],
    'project' => [
        'label' => 'Progetto',
        'table' => 'evaluation_project',
        'weights' => [
            'needs_identification_and_problem_analysis_score' => 3,
            'adherence_to_statuary_purposes_score' => 3,
            'social_weight_score' => 2,
            'objectives_score' => 2,
            'expected_results_score' => 2,
            'activity_score' => 3,
            'local_purpose_score' => 2,
            'partnership_and_relations_with_local_authorities_score' => 2,
            'synergies_and_design_inefficiencies_score' => 3,
            'communication_and_visibility_score' => 3,
        ],
    ],
    'financial_plan' => [
        'label' => 'Piano finanziario',
        'table' => 'evaluation_financial_plan',
        'weights' => [
            'completeness_and_clarity_of_budget_score' => 3,
            'consistency_with_objectives_score' => 3,
            'cofinancing_score' => 2,
            'flexibility_score' => 2,
        ],
    ],
    'qualitative_elements' => [
        'label' => 'Elementi qualitativi',
        'table' => 'evaluation_qualitative_elements',
        'weights' => [
            'impact_score' => 5,
            'relevance_score' => 6,
            'congruity_score' => 4,
            'innovation_score' => 3,
            'rigor_and_scientific_validity_score' => 6,
            'replicability_and_scalability_score' => 4,
            'cohabitation_evidence_score' => 6,
            'research_and_university_partnership_score' => 6,
        ],
    ],
];

$thematicSectionDefinitions = [
    'thematic_repopulation' => [
        'label' => 'Ripopolamento',
        'overall_key' => 'thematic_repopulation_score',
        'section_weight' => 35,
    ],
    'thematic_safeguard' => [
        'label' => 'Tutela',
        'overall_key' => 'thematic_safeguard_score',
        'section_weight' => 35,
    ],
    'thematic_cohabitation' => [
        'label' => 'Convivenza',
        'overall_key' => 'thematic_cohabitation_score',
        'section_weight' => 20,
    ],
    'thematic_community_support' => [
        'label' => 'Supporto alla comunità',
        'overall_key' => 'thematic_community_support_score',
        'section_weight' => 9,
    ],
    'thematic_culture_education' => [
        'label' => 'Cultura ed educazione',
        'overall_key' => 'thematic_culture_education_score',
        'section_weight' => 10,
    ],
];

$evaluationId = (int) $evaluation['evaluation_id'];
$thematicDisplayMax = 70;
$sectionMaxScores = [];
$weightedNonThematicScores = [];

foreach ($nonThematicSectionDefinitions as $sectionKey => $definition) {
    $weights = $definition['weights'];
    $sectionMaxScores[$sectionKey] = array_sum($weights);

    $sectionData = $loadSectionScores(
        $pdo,
        $evaluationId,
        $definition['table'],
        array_keys($weights)
    );

    if ($sectionData === null) {
        $weightedNonThematicScores[$sectionKey] = null;
        continue;
    }

    $weightedScore = 0.0;
    $hasAtLeastOneScore = false;
    foreach ($weights as $fieldName => $weight) {
        $rawValue = $sectionData[$fieldName] ?? null;
        if (!is_numeric($rawValue)) {
            continue;
        }

        $hasAtLeastOneScore = true;
        $weightedScore += (((float) $rawValue) * $weight) / 10;
    }

    $weightedNonThematicScores[$sectionKey] = $hasAtLeastOneScore ? $weightedScore : null;
}

$thematicMaxScores = [];
$weightedThematicScores = [];
$thematicWeightTotal = array_sum(array_map(
    static fn (array $definition): int => (int) $definition['section_weight'],
    $thematicSectionDefinitions
));
foreach ($thematicSectionDefinitions as $sectionKey => $definition) {
    $rawSectionWeight = (float) $definition['section_weight'];
    $scaledSectionMax = $thematicWeightTotal > 0
        ? ($rawSectionWeight / $thematicWeightTotal) * $thematicDisplayMax
        : 0.0;
    $thematicMaxScores[$sectionKey] = $scaledSectionMax;

    $rawScore = $evaluation[$definition['overall_key']] ?? null;
    if (!is_numeric($rawScore)) {
        $weightedThematicScores[$sectionKey] = null;
        continue;
    }

    $normalizedScore = max(0.0, min(10.0, (float) $rawScore));
    $weightedThematicScores[$sectionKey] = ($normalizedScore / 10) * $scaledSectionMax;
}

$thematicDisplayScore = $sumNullableScores(array_values($weightedThematicScores));
$maxOverall = array_sum($sectionMaxScores) + $thematicDisplayMax;
$overallDisplayScore = $sumNullableScores(array_merge(array_values($weightedNonThematicScores), [$thematicDisplayScore]));

$mainSectionRows = [];
foreach ($nonThematicSectionDefinitions as $sectionKey => $definition) {
    $mainSectionRows[] = [
        'label' => $definition['label'],
        'score' => $weightedNonThematicScores[$sectionKey] ?? null,
        'max' => $sectionMaxScores[$sectionKey],
    ];
}
$mainSectionRows[] = [
    'label' => 'Criteri tematici',
    'score' => $thematicDisplayScore,
    'max' => $thematicDisplayMax,
    'is_thematic' => true,
];

$thematicSectionRows = [];
foreach ($thematicSectionDefinitions as $sectionKey => $definition) {
    $thematicSectionRows[] = [
        'label' => $definition['label'],
        'score' => $weightedThematicScores[$sectionKey] ?? null,
        'max' => $thematicMaxScores[$sectionKey],
    ];
}

function formatScore($value): string
{
    if ($value === null) {
        return 'Non disponibile';
    }

    if (is_numeric($value)) {
        $numeric = (float) $value;
        if (floor($numeric) === $numeric) {
            return (string) ((int) $numeric);
        }
        return number_format($numeric, 2, ',', '.');
    }

    return 'Non disponibile';
}
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php'; ?>
    <title>Sintesi Valutazione</title>
    <link rel="stylesheet" href="styles.css">
  </head>
  <body class="management-page management-page--scroll evaluation-summary-page">
    <?php include 'header.php'; ?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Sintesi Valutazione</h1>
        </div>
        <div class="content-container">
          <div class="content">
            <div class="button-container">
              <a href="evaluations.php" class="page-button back-button">Indietro</a>
            </div>

            <div class="evaluation-summary">
              <div class="summary-context" aria-label="Dati principali">
                <p class="summary-context__item">
                  <span class="summary-context__label">Bando:</span>
                  <span class="summary-context__value"><?php echo htmlspecialchars($evaluation['call_title']); ?></span>
                </p>
                <p class="summary-context__item">
                  <span class="summary-context__label">Ente:</span>
                  <span class="summary-context__value"><?php echo htmlspecialchars($evaluation['organization_name']); ?></span>
                </p>
              </div>

              <section class="summary-card summary-card--primary">
                <div class="summary-table-wrap">
                  <table class="summary-table">
                    <thead>
                      <tr>
                        <th>Sezione</th>
                        <th>Valutazione complessiva</th>
                        <th>Massimo</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($mainSectionRows as $row): ?>
                        <tr class="<?php echo !empty($row['is_thematic']) ? 'summary-section-row summary-section-row--thematic' : 'summary-section-row'; ?>">
                          <td class="summary-section"><?php echo htmlspecialchars($row['label']); ?></td>
                          <td><?php echo htmlspecialchars(formatScore($row['score'])); ?></td>
                          <td class="summary-max"><?php echo htmlspecialchars(formatScore($row['max'])); ?></td>
                        </tr>
                        <?php if (!empty($row['is_thematic'])): ?>
                          <?php $thematicLastIndex = count($thematicSectionRows) - 1; ?>
                          <?php foreach ($thematicSectionRows as $index => $subRow): ?>
                            <tr class="summary-subsection-row<?php echo $index === 0 ? ' summary-subsection-row--first' : ''; ?><?php echo $index === $thematicLastIndex ? ' summary-subsection-row--last' : ''; ?>">
                              <td class="summary-subsection">
                                <span class="summary-subsection__branch" aria-hidden="true"></span>
                                <span class="summary-subsection__label"><?php echo htmlspecialchars($subRow['label']); ?></span>
                              </td>
                              <td><?php echo htmlspecialchars(formatScore($subRow['score'])); ?></td>
                              <td class="summary-max"><?php echo htmlspecialchars(formatScore($subRow['max'])); ?></td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      <?php endforeach; ?>
                      <tr class="summary-total">
                        <td>Totale</td>
                        <td><?php echo htmlspecialchars(formatScore($overallDisplayScore)); ?></td>
                        <td class="summary-max"><?php echo htmlspecialchars(formatScore($maxOverall)); ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php'; ?>
  </body>
</html>
