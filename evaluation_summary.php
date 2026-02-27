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

$sectionMaxScores = [
    'proposing_entity' => 7 * 10,
    'project' => 10 * 10,
    'financial_plan' => 4 * 10,
    'qualitative_elements' => 8 * 10,
];

$thematicMaxScores = [
    'thematic_repopulation' => 4 * 10,
    'thematic_safeguard' => 7 * 10,
    'thematic_cohabitation' => 5 * 10,
    'thematic_community_support' => 5 * 10,
    'thematic_culture_education' => 5 * 10,
];

$thematicRawMax = array_sum($thematicMaxScores);
$thematicDisplayMax = 70;
$maxOverall = array_sum($sectionMaxScores) + $thematicDisplayMax;

$thematicDisplayScore = null;
$thematicRawScore = is_numeric($evaluation['thematic_criteria_score']) ? (float) $evaluation['thematic_criteria_score'] : null;
if ($thematicRawScore !== null && $thematicRawMax > 0) {
    $thematicDisplayScore = ($thematicRawScore / $thematicRawMax) * $thematicDisplayMax;
    $thematicDisplayScore = max(0, min($thematicDisplayMax, $thematicDisplayScore));
}

$nonThematicScores = [
    is_numeric($evaluation['proposing_entity_score']) ? (float) $evaluation['proposing_entity_score'] : null,
    is_numeric($evaluation['general_project_score']) ? (float) $evaluation['general_project_score'] : null,
    is_numeric($evaluation['financial_plan_score']) ? (float) $evaluation['financial_plan_score'] : null,
    is_numeric($evaluation['qualitative_elements_score']) ? (float) $evaluation['qualitative_elements_score'] : null,
];
$filteredNonThematicScores = array_filter($nonThematicScores, static fn ($value) => $value !== null);
$nonThematicOverallScore = $filteredNonThematicScores === [] ? null : array_sum($filteredNonThematicScores);

$overallDisplayScore = null;
$overallDisplayParts = array_filter(
    [$nonThematicOverallScore, $thematicDisplayScore],
    static fn ($value) => $value !== null
);
if ($overallDisplayParts !== []) {
    $overallDisplayScore = array_sum($overallDisplayParts);
}

$mainSectionRows = [
    [
        'label' => 'Soggetto proponente',
        'score' => $evaluation['proposing_entity_score'],
        'max' => $sectionMaxScores['proposing_entity'],
    ],
    [
        'label' => 'Progetto',
        'score' => $evaluation['general_project_score'],
        'max' => $sectionMaxScores['project'],
    ],
    [
        'label' => 'Piano finanziario',
        'score' => $evaluation['financial_plan_score'],
        'max' => $sectionMaxScores['financial_plan'],
    ],
    [
        'label' => 'Elementi qualitativi',
        'score' => $evaluation['qualitative_elements_score'],
        'max' => $sectionMaxScores['qualitative_elements'],
    ],
    [
        'label' => 'Criteri tematici',
        'score' => $thematicDisplayScore,
        'max' => $thematicDisplayMax,
        'is_thematic' => true,
    ],
];

$thematicSectionRows = [
    [
        'label' => 'Ripopolamento',
        'score' => $evaluation['thematic_repopulation_score'],
        'max' => $thematicMaxScores['thematic_repopulation'],
    ],
    [
        'label' => 'Tutela',
        'score' => $evaluation['thematic_safeguard_score'],
        'max' => $thematicMaxScores['thematic_safeguard'],
    ],
    [
        'label' => 'Convivenza',
        'score' => $evaluation['thematic_cohabitation_score'],
        'max' => $thematicMaxScores['thematic_cohabitation'],
    ],
    [
        'label' => 'Supporto alla comunità',
        'score' => $evaluation['thematic_community_support_score'],
        'max' => $thematicMaxScores['thematic_community_support'],
    ],
    [
        'label' => 'Cultura ed educazione',
        'score' => $evaluation['thematic_culture_education_score'],
        'max' => $thematicMaxScores['thematic_culture_education'],
    ],
];

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
