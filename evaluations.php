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

// ----------------------------
// Filters
// ----------------------------
$selectedCall = isset($_GET['filter_call']) ? trim($_GET['filter_call']) : '';
if ($selectedCall !== '' && !ctype_digit($selectedCall)) {
    $selectedCall = '';
}
$selectedEnte = isset($_GET['filter_ente']) ? trim($_GET['filter_ente']) : '';
$selectedEnteLength = function_exists('mb_strlen') ? mb_strlen($selectedEnte) : strlen($selectedEnte);
if ($selectedEnte !== '' && $selectedEnteLength > 255) {
    $selectedEnte = '';
}

// ----------------------------
// Filter options
// ----------------------------
$callOptions = [];
$enteOptions = [];

$stmt = $pdo->prepare("
  SELECT DISTINCT
    c.id AS call_id,
    c.title AS call_title,
    COALESCE(o.name, 'Soggetto proponente') AS ente
  FROM evaluation e
  JOIN application a ON e.application_id = a.id
  JOIN call_for_proposal c ON a.call_for_proposal_id = c.id
  LEFT JOIN organization o ON a.organization_id = o.id
  WHERE e.evaluator_id = :uid
");
$stmt->execute([':uid' => $_SESSION['user_id']]);
foreach ($stmt->fetchAll() as $row) {
    $callOptions[$row['call_id']] = $row['call_title'];
    $enteOptions[$row['ente']] = true;
}

$stmt = $pdo->prepare("
  SELECT DISTINCT
    c.id AS call_id,
    c.title AS call_title,
    COALESCE(o.name, 'Soggetto proponente') AS ente
  FROM application a
  JOIN call_for_proposal c ON a.call_for_proposal_id = c.id
  LEFT JOIN organization o ON a.organization_id = o.id
  WHERE NOT EXISTS (
    SELECT 1 FROM evaluation e
    WHERE e.application_id = a.id
      AND e.evaluator_id = :uid
  )
    AND a.status = 'APPROVED'
");
$stmt->execute([':uid' => $_SESSION['user_id']]);
foreach ($stmt->fetchAll() as $row) {
    $callOptions[$row['call_id']] = $row['call_title'];
    $enteOptions[$row['ente']] = true;
}

asort($callOptions, SORT_NATURAL | SORT_FLAG_CASE);
$enteOptions = array_keys($enteOptions);
sort($enteOptions, SORT_NATURAL | SORT_FLAG_CASE);

$selectedCallTitle = '';
if ($selectedCall !== '') {
    $callKey = (int) $selectedCall;
    if (isset($callOptions[$callKey])) {
        $selectedCallTitle = $callOptions[$callKey];
    } elseif (isset($callOptions[$selectedCall])) {
        $selectedCallTitle = $callOptions[$selectedCall];
    }
}
$filtersApplied = ($selectedCall !== '' || $selectedEnte !== '');

// ----------------------------
// Submitted Evaluations
// ----------------------------
$submittedQuery = "
  SELECT
    a.id AS application_id,
    c.id AS call_id,
    c.title AS call_title,
    COALESCE(o.name, 'Soggetto proponente') AS ente,
    e.created_at,
    a.checklist_path
  FROM evaluation e
  JOIN application a ON e.application_id = a.id
  JOIN call_for_proposal c ON a.call_for_proposal_id = c.id
  LEFT JOIN organization o ON a.organization_id = o.id
  WHERE e.evaluator_id = :uid
";
$submittedParams = [':uid' => $_SESSION['user_id']];
if ($selectedCall !== '') {
    $submittedQuery .= " AND c.id = :call_filter";
    $submittedParams[':call_filter'] = (int) $selectedCall;
}
if ($selectedEnte !== '') {
    $submittedQuery .= " AND COALESCE(o.name, 'Soggetto proponente') = :ente_filter";
    $submittedParams[':ente_filter'] = $selectedEnte;
}
$submittedQuery .= " ORDER BY e.created_at DESC";
$stmt = $pdo->prepare($submittedQuery);
$stmt->execute($submittedParams);
$submitted = $stmt->fetchAll();

// ----------------------------
// Pending Evaluations: applications that the user has not yet evaluated
// ----------------------------
$pendingQuery = "
  SELECT
    a.id AS application_id,
    c.id AS call_id,
    c.title AS call_title,
    COALESCE(o.name, 'Soggetto proponente') AS ente,
    a.created_at,
    a.checklist_path
  FROM application a
  JOIN call_for_proposal c ON a.call_for_proposal_id = c.id
  LEFT JOIN organization o ON a.organization_id = o.id
  WHERE NOT EXISTS (
    SELECT 1 FROM evaluation e
    WHERE e.application_id = a.id
      AND e.evaluator_id = :uid
  ) AND a.status = 'APPROVED'
";
$pendingParams = [':uid' => $_SESSION['user_id']];
if ($selectedCall !== '') {
    $pendingQuery .= " AND c.id = :call_filter";
    $pendingParams[':call_filter'] = (int) $selectedCall;
}
if ($selectedEnte !== '') {
    $pendingQuery .= " AND COALESCE(o.name, 'Soggetto proponente') = :ente_filter";
    $pendingParams[':ente_filter'] = $selectedEnte;
}
$pendingQuery .= " ORDER BY a.created_at DESC";
$stmt = $pdo->prepare($pendingQuery);
$stmt->execute($pendingParams);
$pending = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php'; ?>
    <title>Le mie Valutazioni</title>
    <!-- Make sure styles.css is linked. If not already in common-head.php, add: -->
    <link rel="stylesheet" href="styles.css">
  </head>
  <body class="management-page">
    <?php include 'header.php'; ?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Le mie Valutazioni</h1>
        </div>
        <div class="content-container">
          <div class="content">

            <div class="button-container">
              <a href="javascript:history.back()" class="page-button back-button">Indietro</a>
            </div>

            <form method="get" class="filters-form">
              <div class="form-group">
                <label class="form-label" for="filter_call">Bando</label>
                <select name="filter_call" id="filter_call" class="form-input">
                  <option value="">Tutti i bandi</option>
                  <?php foreach ($callOptions as $callId => $callTitle): ?>
                    <option value="<?php echo htmlspecialchars((string) $callId); ?>" <?php echo ((string) $callId === $selectedCall) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($callTitle); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" for="filter_ente">Ente</label>
                <select name="filter_ente" id="filter_ente" class="form-input">
                  <option value="">Tutti gli enti</option>
                  <?php foreach ($enteOptions as $enteOption): ?>
                    <option value="<?php echo htmlspecialchars($enteOption); ?>" <?php echo ($enteOption === $selectedEnte) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($enteOption); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="filters-actions">
                <button type="submit" class="page-button">Applica filtri</button>
                <a class="page-button secondary-button" href="evaluations.php">Reset</a>
              </div>
            </form>

            <?php if ($filtersApplied): ?>
              <p class="filter-info">
                Visualizzando le valutazioni
                <?php if ($selectedCallTitle !== ''): ?>
                  per il bando "<strong><?php echo htmlspecialchars($selectedCallTitle); ?></strong>"
                <?php elseif ($selectedCall !== ''): ?>
                  per il bando selezionato
                <?php endif; ?>
                <?php if ($selectedEnte !== ''): ?>
                  <?php if ($selectedCall !== ''): ?>
                    e
                  <?php else: ?>
                    per
                  <?php endif; ?>
                  l'ente "<strong><?php echo htmlspecialchars($selectedEnte); ?></strong>"
                <?php endif; ?>.
                <a href="evaluations.php">Mostra tutte le valutazioni</a>
              </p>
            <?php endif; ?>

            <section class="users-table-container">
              <h2>Valutazioni Compilate</h2>
              <table class="users-table">
                <thead>
                  <tr>
                    <th>Bando</th>
                    <th>Ente</th>
                    <th>Data Compilazione</th>
                    <th>Checklist</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($submitted) > 0): ?>
                    <?php foreach($submitted as $row): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($row['call_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['ente']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                          <?php if (!empty($row['checklist_path'])): ?>
                            <a
                              class="page-button secondary-button"
                              href="application_checklist_download.php?id=<?php echo $row['application_id']; ?>"
                              target="_blank"
                              rel="noopener noreferrer"
                            >Apri Checklist</a>
                          <?php else: ?>
                            <span>Non disponibile</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="4">Non hai inviato nessuna valutazione.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </section>

            <section class="users-table-container" style="margin-top: 2rem;">
              <h2>Valutazioni da Compilare</h2>
              <table class="users-table">
                <thead>
                  <tr>
                    <th>Bando</th>
                    <th>Ente</th>
                    <th>Data di Invio</th>
                    <th>Checklist</th>
                    <th>Azione</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($pending) > 0): ?>
                    <?php foreach($pending as $row): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($row['call_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['ente']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                          <?php if (!empty($row['checklist_path'])): ?>
                            <a
                              class="page-button secondary-button"
                              href="application_checklist_download.php?id=<?php echo $row['application_id']; ?>"
                              target="_blank"
                              rel="noopener noreferrer"
                            >Apri Checklist</a>
                          <?php else: ?>
                            <span>Non disponibile</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <a class="page-button" href="evaluation_form.php?application_id=<?php echo $row['application_id']; ?>">Compila Valutazione</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="5">Non ci sono valutazioni in sospeso.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </section>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php'; ?>
  </body>
</html>