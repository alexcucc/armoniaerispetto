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
// Submitted Evaluations
// ----------------------------
$stmt = $pdo->prepare("
  SELECT 
    a.id AS application_id, 
    c.title AS call_title, 
    u.organization AS ente, 
    e.created_at
  FROM evaluation e 
  JOIN application a ON e.application_id = a.id 
  JOIN call_for_proposal c ON a.call_for_proposal_id = c.id
  JOIN user u ON u.id = e.evaluator_id
  WHERE e.evaluator_id = :uid
  ORDER BY e.created_at DESC
");
$stmt->execute([':uid' => $_SESSION['user_id']]);
$submitted = $stmt->fetchAll();

// ----------------------------
// Pending Evaluations: applications that the user has not yet evaluated
// ----------------------------
$stmt = $pdo->prepare("
  SELECT 
    a.id AS application_id, 
    c.title AS call_title, 
    u.organization AS ente, 
    a.created_at
  FROM application a 
  JOIN call_for_proposal c ON a.call_for_proposal_id = c.id
  JOIN user u ON u.id = :uid
  WHERE NOT EXISTS (
    SELECT 1 FROM evaluation e 
    WHERE e.application_id = a.id 
      AND e.evaluator_id = :uid
  ) AND a.status = 'APPROVED'
  ORDER BY a.created_at DESC
");
$stmt->execute([':uid' => $_SESSION['user_id']]);
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
  <body>
    <?php include 'header.php'; ?>
    <main>
      <div style="max-width:1500px; margin:2em auto;">
        <div class="button-container">
          <a href="javascript:history.back()" class="page-button back-button">Indietro</a>
        </div>
        <h2>Valutazioni Compilate</h2>
        <?php if (count($submitted) > 0): ?>
          <table>
            <thead>
              <tr>
                <th>Bando</th>
                <th>Ente</th>
                <th>Data Compilazione</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($submitted as $row): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['call_title']); ?></td>
                  <td><?php echo htmlspecialchars($row['ente']); ?></td>
                  <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>Non hai inviato nessuna valutazione.</p>
        <?php endif; ?>

        <h2>Valutazioni da Compilare</h2>
        <?php if (count($pending) > 0): ?>
          <table>
            <thead>
              <tr>
                <th>Bando</th>
                <th>Ente</th>
                <th>Data di Invio</th>
                <th>Azione</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($pending as $row): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['call_title']); ?></td>
                  <td><?php echo htmlspecialchars($row['ente']); ?></td>
                  <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                  <td>
                    <a class="btn" href="evaluation_form.php?application_id=<?php echo $row['application_id']; ?>">Compila Valutazione</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>Non ci sono valutazioni in sospeso.</p>
        <?php endif; ?>
      </div>
    </main>
    <?php include 'footer.php'; ?>
  </body>
</html>