<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['APPLICATION_REVIEW']
    )) {
    header('Location: index.php');
    exit();
}

// Retrieve supervisor id for the logged in user
$stmt = $pdo->prepare('SELECT id FROM supervisor WHERE user_id = :uid');
$stmt->execute(['uid' => $_SESSION['user_id']]);
$supervisorId = $stmt->fetchColumn();

if ($supervisorId) {
    $stmt = $pdo->prepare(
        'SELECT a.id, c.title AS call_title, o.name AS organization_name, a.project_name '
        . 'FROM application a '
        . 'JOIN call_for_proposal c ON a.call_for_proposal_id = c.id '
        . 'JOIN organization o ON a.organization_id = o.id '
        . 'WHERE a.status = "SUBMITTED" AND a.supervisor_id = :sid'
    );
    $stmt->execute(['sid' => $supervisorId]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $applications = [];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Domande da Revisionare</title>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Domande da Revisionare</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Bando</th>
                                <th>Ente</th>
                                <th>Nome Progetto</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="4">Nessuna domanda da revisionare.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($app['call_title']); ?></td>
                                        <td><?php echo htmlspecialchars($app['organization_name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['project_name']); ?></td>
                                        <td>
                                            <a class="page-button" href="application_review.php?application_id=<?php echo $app['id']; ?>">Revisiona</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
