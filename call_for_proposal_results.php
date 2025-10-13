<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);

if (
    !isset($_SESSION['user_id'])
    || !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['EVALUATION_VIEW']
    )
) {
    header('Location: index.php');
    exit();
}

$callId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$callId) {
    header('Location: call_for_proposals.php');
    exit();
}

$callStmt = $pdo->prepare('SELECT id, title, description FROM call_for_proposal WHERE id = :id');
$callStmt->execute([':id' => $callId]);
$call = $callStmt->fetch(PDO::FETCH_ASSOC);

if (!$call) {
    header('Location: call_for_proposals.php');
    exit();
}

$totalEvaluatorsStmt = $pdo->query('SELECT COUNT(*) FROM evaluator');
$totalEvaluators = (int) $totalEvaluatorsStmt->fetchColumn();

$organizations = [];
if ($totalEvaluators > 0) {
    $organizationsStmt = $pdo->prepare(
        'SELECT
            o.id AS organization_id,
            o.name AS organization_name,
            COUNT(DISTINCT a.id) AS applications_count,
            COUNT(e.id) AS total_evaluations,
            COALESCE(SUM(eg.overall_score), 0) AS total_score
        FROM application a
        JOIN organization o ON a.organization_id = o.id
        LEFT JOIN evaluation e ON e.application_id = a.id
        LEFT JOIN evaluation_general eg ON eg.evaluation_id = e.id
        WHERE a.call_for_proposal_id = :call_id
        GROUP BY o.id, o.name
        HAVING COUNT(e.id) = COUNT(DISTINCT a.id) * :total_evaluators
        ORDER BY total_score DESC, organization_name ASC'
    );

    $organizationsStmt->execute([
        ':call_id' => $callId,
        ':total_evaluators' => $totalEvaluators,
    ]);
    $organizations = $organizationsStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Graduatoria - <?php echo htmlspecialchars($call['title']); ?></title>
</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <div class="hero">
            <div class="title">
                <h1>Graduatoria - <?php echo htmlspecialchars($call['title']); ?></h1>
            </div>

            <div class="content-container">
                <div class="content">
                    <div class="button-container">
                        <a href="javascript:history.back()" class="page-button back-button">Indietro</a>
                        <a class="page-button" href="call_for_proposals.php">Torna ai bandi</a>
                    </div>

                    <?php if ($totalEvaluators === 0): ?>
                        <p>Non ci sono valutatori registrati. Aggiungi almeno un valutatore per visualizzare i risultati.</p>
                    <?php elseif (empty($organizations)): ?>
                        <p>Non sono ancora disponibili risultati per questo bando. Verifica che tutte le valutazioni siano state completate.</p>
                    <?php else: ?>
                        <div class="users-table-container">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>Posizione</th>
                                        <th>Ente</th>
                                        <th>Domande presentate</th>
                                        <th>Valutazioni ricevute</th>
                                        <th>Punteggio totale</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $position = 1; ?>
                                    <?php foreach ($organizations as $organization): ?>
                                        <tr>
                                            <td><?php echo $position++; ?></td>
                                            <td><?php echo htmlspecialchars($organization['organization_name']); ?></td>
                                            <td><?php echo htmlspecialchars((string) $organization['applications_count']); ?></td>
                                            <td><?php echo htmlspecialchars((string) $organization['total_evaluations']); ?></td>
                                            <td><?php echo htmlspecialchars(number_format((float) $organization['total_score'], 0, ',', '.')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
