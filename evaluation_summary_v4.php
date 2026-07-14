<?php
if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'evaluation_models.php';
include_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if ($rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['EVALUATION_CREATE']) === false) {
    header('Location: index.php');
    exit;
}

$applicationId = filter_input(INPUT_GET, 'application_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$applicationId) {
    $_SESSION['evaluation_error'] = 'Valutazione non trovata.';
    header('Location: evaluations.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT e.id AS evaluation_id, e.status AS evaluation_status, e.updated_at, e.forced_weighted_total_score, e.model_version, '
    . 'c.title AS call_title, COALESCE(o.name, \'' . 'Soggetto proponente' . '\') AS organization_name, '
    . 'a.application_pdf_path, a.budget_pdf_path, a.cronoprogramma_pdf_path, a.checklist_path, '
    . 'g.proposing_entity_score, g.project_score, g.financial_plan_score, g.qualitative_elements_score, g.thematic_criteria_score, g.overall_score '
    . 'FROM evaluation e '
    . 'JOIN application a ON e.application_id = a.id '
    . 'JOIN call_for_proposal c ON a.call_for_proposal_id = c.id '
    . 'LEFT JOIN organization o ON a.organization_id = o.id '
    . 'LEFT JOIN evaluation_v4_general g ON g.evaluation_id = e.id '
    . 'WHERE e.application_id = :application_id AND e.evaluator_id = :evaluator_id LIMIT 1'
);
$stmt->execute([
    ':application_id' => $applicationId,
    ':evaluator_id' => (int) $_SESSION['user_id'],
]);
$evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$evaluation) {
    $_SESSION['evaluation_error'] = 'Valutazione non trovata.';
    header('Location: evaluations.php');
    exit;
}
if (evaluationIsLegacyModel($evaluation['model_version'] ?? null)) {
    include 'evaluation_summary_legacy.php';
    return;
}

$modelVersion = $evaluation['model_version'] ?? evaluationGetCurrentModelVersion();
$definition = evaluationGetV4Definition($modelVersion);
$sections = evaluationGetV4EnabledSections($modelVersion);
$isBudgetPdf = !empty($evaluation['budget_pdf_path']) && strtolower((string) pathinfo((string) $evaluation['budget_pdf_path'], PATHINFO_EXTENSION)) === 'pdf';
$evaluationId = (int) $evaluation['evaluation_id'];
$sectionData = evaluationV4LoadData($pdo, $evaluationId, $modelVersion);
$totals = evaluationV4CalculateTotals($sectionData, $modelVersion);
$forcedWeightedTotalScore = is_numeric($evaluation['forced_weighted_total_score'] ?? null) ? (float) $evaluation['forced_weighted_total_score'] : null;

$sectionTotals = [
    'proposing_entity' => $evaluation['proposing_entity_score'] ?? ($totals['sections']['proposing_entity']['weighted_score'] ?? null),
    'project' => $evaluation['project_score'] ?? ($totals['sections']['project']['weighted_score'] ?? null),
    'financial_plan' => $evaluation['financial_plan_score'] ?? ($totals['sections']['financial_plan']['weighted_score'] ?? null),
    'qualitative_elements' => $evaluation['qualitative_elements_score'] ?? ($totals['sections']['qualitative_elements']['weighted_score'] ?? null),
    'thematic_total' => $evaluation['thematic_criteria_score'] ?? $totals['thematic_total'],
    'overall_total' => $forcedWeightedTotalScore ?? ($evaluation['overall_score'] ?? $totals['overall_total']),
];
$hasSectionDetails = false;
foreach ($sections as $sectionKey => $sectionDefinition) {
    if (($totals['sections'][$sectionKey]['has_scores'] ?? false) === true) {
        $hasSectionDetails = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <?php include 'common-head.php'; ?>
  <title>Sintesi valutazione</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .summary-v4-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; box-shadow: 0 8px 24px rgba(15,23,42,.06); }
    .summary-v4-card + .summary-v4-card { margin-top: 1rem; }
    .summary-v4-grid { display: grid; gap: 1rem; }
    .summary-v4-totals { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .75rem; }
    .summary-v4-total { padding: .85rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
    .summary-v4-total strong { display: block; font-size: 1.2rem; margin-top: .2rem; }
    .summary-v4-table { width: 100%; border-collapse: collapse; }
    .summary-v4-table th, .summary-v4-table td { padding: .6rem .5rem; border-bottom: 1px solid #eef2f7; text-align: left; vertical-align: top; }
    .summary-v4-table th { font-size: .82rem; text-transform: uppercase; color: #64748b; }
    .summary-v4-meta { display: grid; gap: .35rem; margin-bottom: 1rem; }
    .summary-v4-note-block { margin-top: .85rem; padding-top: .85rem; border-top: 1px solid #eef2f7; }
    .summary-v4-note-block p { white-space: pre-wrap; }
  </style>
</head>
<body class="management-page management-page--scroll evaluation-summary-page">
<?php include 'header.php'; ?>
<main>
  <div class="hero">
    <div class="title"><h1>Sintesi valutazione</h1></div>
    <div class="content-container">
      <div class="content summary-v4-grid">
        <div class="button-container">
          <a href="evaluations.php" class="page-button back-button">Indietro</a>
        </div>

        <section class="summary-v4-card">
          <div class="summary-v4-meta">
            <p><strong>Bando:</strong> <?php echo htmlspecialchars($evaluation['call_title']); ?></p>
            <p><strong>Ente:</strong> <?php echo htmlspecialchars($evaluation['organization_name']); ?></p>
            <?php if ($forcedWeightedTotalScore !== null): ?>
              <p><strong>Modalità:</strong> voto finale forzato</p>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label">Documenti della risposta</label>
            <div class="actions-cell document-actions">
              <?php if (!empty($evaluation['application_pdf_path'])): ?>
                <div class="document-action-group">
                  <span class="document-action-label">Risposta</span>
                  <a class="page-button secondary-button page-button--icon" href="application_download.php?id=<?php echo $applicationId; ?>&type=application&mode=inline" target="_blank" rel="noopener noreferrer"><i class="fas fa-eye" aria-hidden="true"></i></a>
                  <a class="page-button secondary-button page-button--icon" href="application_download.php?id=<?php echo $applicationId; ?>&type=application"><i class="fas fa-download" aria-hidden="true"></i></a>
                </div>
              <?php endif; ?>
              <?php if (!empty($evaluation['budget_pdf_path'])): ?>
                <div class="document-action-group">
                  <span class="document-action-label">Budget</span>
                  <?php if ($isBudgetPdf): ?>
                    <a class="page-button secondary-button page-button--icon" href="application_download.php?id=<?php echo $applicationId; ?>&type=budget&mode=inline" target="_blank" rel="noopener noreferrer"><i class="fas fa-eye" aria-hidden="true"></i></a>
                  <?php endif; ?>
                  <a class="page-button secondary-button page-button--icon" href="application_download.php?id=<?php echo $applicationId; ?>&type=budget"><i class="fas fa-download" aria-hidden="true"></i></a>
                </div>
              <?php endif; ?>
              <?php if (!empty($evaluation['cronoprogramma_pdf_path'])): ?>
                <div class="document-action-group">
                  <span class="document-action-label">Cronoprogr.</span>
                  <a class="page-button secondary-button page-button--icon" href="application_download.php?id=<?php echo $applicationId; ?>&type=cronoprogramma&mode=inline" target="_blank" rel="noopener noreferrer"><i class="fas fa-eye" aria-hidden="true"></i></a>
                  <a class="page-button secondary-button page-button--icon" href="application_download.php?id=<?php echo $applicationId; ?>&type=cronoprogramma"><i class="fas fa-download" aria-hidden="true"></i></a>
                </div>
              <?php endif; ?>
              <?php if (!empty($evaluation['checklist_path'])): ?>
                <div class="document-action-group">
                  <span class="document-action-label">Checklist</span>
                  <a class="page-button secondary-button page-button--icon" href="application_checklist_download.php?id=<?php echo $applicationId; ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-eye" aria-hidden="true"></i></a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <section class="summary-v4-card">
          <div class="summary-v4-totals">
            <div class="summary-v4-total"><span>Soggetto proponente</span><strong><?php echo htmlspecialchars(evaluationV4FormatScore($sectionTotals['proposing_entity'])); ?> / 30</strong></div>
            <div class="summary-v4-total"><span>Progetto</span><strong><?php echo htmlspecialchars(evaluationV4FormatScore($sectionTotals['project'])); ?> / 30</strong></div>
            <div class="summary-v4-total"><span>Piano finanziario</span><strong><?php echo htmlspecialchars(evaluationV4FormatScore($sectionTotals['financial_plan'])); ?> / 15</strong></div>
            <div class="summary-v4-total"><span>Elementi qualitativi</span><strong><?php echo htmlspecialchars(evaluationV4FormatScore($sectionTotals['qualitative_elements'])); ?> / 25</strong></div>
            <div class="summary-v4-total"><span>Criteri tematici</span><strong><?php echo htmlspecialchars(evaluationV4FormatScore($sectionTotals['thematic_total'])); ?> / <?php echo htmlspecialchars((string) ($definition['thematic_display_max_score'] ?? 100)); ?></strong></div>
            <div class="summary-v4-total"><span>Totale generale</span><strong><?php echo htmlspecialchars(evaluationV4FormatScore($sectionTotals['overall_total'])); ?> / <?php echo htmlspecialchars((string) ($definition['max_total_score'] ?? 200)); ?></strong></div>
          </div>
        </section>

        <?php if ($forcedWeightedTotalScore !== null && !$hasSectionDetails): ?>
          <section class="summary-v4-card">
            <p>Questa valutazione contiene solo un voto finale forzato. Il dettaglio per criterio non è disponibile.</p>
          </section>
        <?php endif; ?>

        <?php foreach ($sections as $sectionKey => $sectionDefinition): ?>
          <?php $sectionResult = $totals['sections'][$sectionKey] ?? null; ?>
          <?php if ($sectionResult === null || ($sectionResult['has_scores'] ?? false) === false) { continue; } ?>
          <section class="summary-v4-card">
            <h2><?php echo htmlspecialchars($sectionDefinition['label']); ?></h2>
            <table class="summary-v4-table">
              <thead>
                <tr>
                  <th>Criterio</th>
                  <th>Peso</th>
                  <th>Voto</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($sectionDefinition['criteria'] as $fieldName => $criterionDefinition): ?>
                  <?php $criterionNote = trim((string) ($sectionData[$sectionKey]['criterion_notes'][$fieldName] ?? '')); ?>
                  <tr>
                    <td><?php echo htmlspecialchars($criterionDefinition['label']); ?><?php if ($criterionNote !== ''): ?><div style="margin-top:.35rem;white-space:pre-wrap;"><strong>Nota:</strong><br><?php echo nl2br(htmlspecialchars($criterionNote)); ?></div><?php endif; ?></td>
                    <td><?php echo htmlspecialchars((string) $criterionDefinition['weight']); ?></td>
                    <td><?php echo htmlspecialchars(evaluationV4FormatScore($sectionData[$sectionKey]['scores'][$fieldName] ?? null)); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <p><strong>Totale sezione:</strong> <?php echo htmlspecialchars(evaluationV4FormatScore($sectionResult['weighted_score'] ?? null)); ?> / <?php echo htmlspecialchars((string) $sectionDefinition['max']); ?></p>

          </section>
        <?php endforeach; ?>

      </div>
    </div>
  </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>






