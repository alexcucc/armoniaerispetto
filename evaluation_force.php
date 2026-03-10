<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
$currentUserId = (int) $_SESSION['user_id'];
if ($rolePermissionManager->userHasPermission($currentUserId, RolePermissionManager::$PERMISSIONS['EVALUATION_CREATE']) === false) {
    header('Location: index.php');
    exit;
}

$adminCheckStmt = $pdo->prepare(
    "SELECT 1 FROM user_role ur JOIN role r ON r.id = ur.role_id WHERE ur.user_id = :user_id AND r.name = 'Admin' LIMIT 1"
);
$adminCheckStmt->execute([':user_id' => $currentUserId]);
$isAdminUser = (bool) $adminCheckStmt->fetchColumn();
if (!$isAdminUser) {
    $_SESSION['evaluation_error'] = 'Solo un Admin può forzare il voto finale.';
    header('Location: evaluations.php');
    exit;
}

$applicationId = filter_input(INPUT_GET, 'application_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$applicationId) {
    $_SESSION['evaluation_error'] = 'Risposta al bando non valida.';
    header('Location: evaluations.php');
    exit;
}
$selectedEvaluatorId = filter_input(INPUT_GET, 'evaluator_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$selectedEvaluatorId) {
    $_SESSION['evaluation_error'] = 'Valutatore selezionato non valido.';
    header('Location: evaluator_evaluation_overview.php');
    exit;
}

$applicationStmt = $pdo->prepare(
    "SELECT a.id, a.status, c.status AS call_status, c.title AS call_title, COALESCE(o.name, 'Soggetto proponente') AS organization_name "
    . 'FROM application a '
    . 'JOIN call_for_proposal c ON c.id = a.call_for_proposal_id '
    . 'LEFT JOIN organization o ON o.id = a.organization_id '
    . 'WHERE a.id = :application_id '
    . 'LIMIT 1'
);
$applicationStmt->execute([':application_id' => $applicationId]);
$applicationInfo = $applicationStmt->fetch(PDO::FETCH_ASSOC);
if (!$applicationInfo) {
    $_SESSION['evaluation_error'] = 'Risposta al bando non trovata.';
    header('Location: evaluations.php');
    exit;
}

if (($applicationInfo['call_status'] ?? null) === 'CLOSED') {
    $_SESSION['evaluation_error'] = 'Il bando è chiuso e non è più possibile valutare.';
    header('Location: evaluations.php');
    exit;
}
if (($applicationInfo['status'] ?? '') !== 'FINAL_VALIDATION') {
    $_SESSION['evaluation_error'] = 'È possibile forzare il voto solo per risposte in stato "Convalida in definitiva".';
    header('Location: evaluations.php');
    exit;
}

$selectedEvaluatorStmt = $pdo->prepare(
    "SELECT ev.user_id, CONCAT(u.last_name, ' ', u.first_name) AS evaluator_name "
    . 'FROM evaluator ev '
    . 'JOIN user u ON u.id = ev.user_id '
    . 'WHERE ev.user_id = :evaluator_id '
    . 'LIMIT 1'
);
$selectedEvaluatorStmt->execute([':evaluator_id' => $selectedEvaluatorId]);
$selectedEvaluator = $selectedEvaluatorStmt->fetch(PDO::FETCH_ASSOC);
if (!$selectedEvaluator) {
    $_SESSION['evaluation_error'] = 'Il valutatore selezionato non esiste.';
    header('Location: evaluator_evaluation_overview.php');
    exit;
}

$existingEvaluationStmt = $pdo->prepare(
    'SELECT id FROM evaluation WHERE application_id = :application_id AND evaluator_id = :evaluator_id LIMIT 1'
);
$existingEvaluationStmt->execute([
    ':application_id' => $applicationId,
    ':evaluator_id' => $selectedEvaluatorId,
]);
$existingEvaluation = $existingEvaluationStmt->fetch(PDO::FETCH_ASSOC);
if ($existingEvaluation) {
    $_SESSION['evaluation_error'] = 'Esiste già una valutazione per il bando e il valutatore selezionato.';
    header('Location: evaluator_evaluation_overview.php');
    exit;
}

$totalEvaluatorsStmt = $pdo->query('SELECT COUNT(*) FROM evaluator');
$totalEvaluators = (int) $totalEvaluatorsStmt->fetchColumn();
$completedEvaluationsStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM evaluation WHERE application_id = :application_id AND status IN ('SUBMITTED', 'REVISED')"
);
$completedEvaluationsStmt->execute([':application_id' => $applicationId]);
$completedEvaluations = (int) $completedEvaluationsStmt->fetchColumn();
if ($totalEvaluators <= 0 || $completedEvaluations >= $totalEvaluators) {
    $_SESSION['evaluation_error'] = 'Non è possibile forzare il voto: le valutazioni risultano già complete.';
    header('Location: evaluator_evaluation_overview.php');
    exit;
}

$maxForcedWeightedTotalScore = 2090;
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php'; ?>
    <title>Forza Voto Finale</title>
    <link rel="stylesheet" href="styles.css">
  </head>
  <body class="management-page">
    <?php include 'header.php'; ?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Forza voto finale (Admin)</h1>
        </div>
        <div class="content-container">
          <div class="content">
            <div class="button-container">
              <a href="evaluator_evaluation_overview.php" class="page-button back-button">Indietro</a>
            </div>

            <div class="evaluation-summary">
              <p><strong>Bando:</strong> <?php echo htmlspecialchars($applicationInfo['call_title']); ?></p>
              <p><strong>Ente:</strong> <?php echo htmlspecialchars($applicationInfo['organization_name']); ?></p>
              <p><strong>Valutatore:</strong> <?php echo htmlspecialchars($selectedEvaluator['evaluator_name']); ?></p>
              <p><strong>Valutazioni complete:</strong> <?php echo htmlspecialchars((string) $completedEvaluations); ?>/<?php echo htmlspecialchars((string) $totalEvaluators); ?></p>

              <form class="contact-form" action="evaluation_handler.php" method="post">
                <input type="hidden" name="application_id" value="<?php echo (int) $applicationId; ?>">
                <input type="hidden" name="evaluator_id" value="<?php echo (int) $selectedEvaluatorId; ?>">

                <div class="form-group">
                  <label class="form-label required" for="forced_weighted_total_score">Voto totale pesato</label>
                  <input
                    class="form-input"
                    type="number"
                    id="forced_weighted_total_score"
                    name="forced_weighted_total_score"
                    min="0"
                    max="<?php echo (int) $maxForcedWeightedTotalScore; ?>"
                    step="0.01"
                    required
                  >
                  <small class="form-text">Inserisci solo il punteggio totale pesato (0-<?php echo (int) $maxForcedWeightedTotalScore; ?>).</small>
                </div>

                <div class="button-container">
                  <button class="page-button" type="submit" name="action" value="submit_force">Invia voto finale</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php'; ?>
  </body>
</html>
