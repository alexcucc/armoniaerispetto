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

$organizationId = isset($_GET['organization_id']) ? (int) $_GET['organization_id'] : null;
$callId = isset($_GET['call_id']) ? (int) $_GET['call_id'] : null;
$selectedOrganizationName = null;
$selectedCallTitle = null;

if ($organizationId) {
    $orgStmt = $pdo->prepare('SELECT name FROM organization WHERE id = :id');
    $orgStmt->execute([':id' => $organizationId]);
    $selectedOrganizationName = $orgStmt->fetchColumn();

    if (!$selectedOrganizationName) {
        $organizationId = null;
    }
}

if ($callId) {
    $callStmt = $pdo->prepare('SELECT title FROM call_for_proposal WHERE id = :id');
    $callStmt->execute([':id' => $callId]);
    $selectedCallTitle = $callStmt->fetchColumn();

    if (!$selectedCallTitle) {
        $callId = null;
    }
}

$callOptionsStmt = $pdo->query('SELECT id, title FROM call_for_proposal ORDER BY title');
$callOptions = $callOptionsStmt->fetchAll(PDO::FETCH_ASSOC);

$organizationOptionsStmt = $pdo->query('SELECT id, name FROM organization ORDER BY name');
$organizationOptions = $organizationOptionsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($supervisorId) {
    $baseWhere = ['a.supervisor_id = :sid'];
    $baseParams = [':sid' => $supervisorId];

    if ($organizationId) {
        $baseWhere[] = 'a.organization_id = :organization_id';
        $baseParams[':organization_id'] = $organizationId;
    }

    if ($callId) {
        $baseWhere[] = 'a.call_for_proposal_id = :call_id';
        $baseParams[':call_id'] = $callId;
    }

    $pendingWhere = array_merge(['a.status = :pending_status'], $baseWhere);
    $pendingSql = 'SELECT a.id, c.title AS call_title, o.name AS organization_name, a.project_name, a.status '
        . 'FROM application a '
        . 'JOIN call_for_proposal c ON a.call_for_proposal_id = c.id '
        . 'JOIN organization o ON a.organization_id = o.id '
        . 'WHERE ' . implode(' AND ', $pendingWhere)
        . ' ORDER BY c.title, o.name, a.project_name';
    $pendingStmt = $pdo->prepare($pendingSql);
    $pendingParams = $baseParams;
    $pendingParams[':pending_status'] = 'SUBMITTED';
    $pendingStmt->execute($pendingParams);
    $pendingApplications = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

    $reviewedWhere = array_merge(['a.status IN ("APPROVED", "REJECTED")'], $baseWhere);
    $reviewedSql = 'SELECT a.id, c.title AS call_title, o.name AS organization_name, a.project_name, a.status '
        . 'FROM application a '
        . 'JOIN call_for_proposal c ON a.call_for_proposal_id = c.id '
        . 'JOIN organization o ON a.organization_id = o.id '
        . 'WHERE ' . implode(' AND ', $reviewedWhere)
        . ' ORDER BY a.updated_at DESC';
    $reviewedStmt = $pdo->prepare($reviewedSql);
    $reviewedStmt->execute($baseParams);
    $reviewedApplications = $reviewedStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $pendingApplications = [];
    $reviewedApplications = [];
}

$filtersApplied = $selectedCallTitle || $selectedOrganizationName;
$resetUrl = 'supervisor_applications.php';

$statusLabels = [
    'SUBMITTED' => 'In attesa',
    'APPROVED' => 'Approvata',
    'REJECTED' => 'Respinta'
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Risposte ai bandi da convalidare</title>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Risposte ai bandi da convalidare</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <form method="get" class="filters-form">
                    <div class="form-group">
                        <label class="form-label" for="call_id">Bando</label>
                        <select id="call_id" name="call_id" class="form-input">
                            <option value="">Tutti i bandi</option>
                            <?php foreach ($callOptions as $callOption): ?>
                                <option value="<?php echo (int) $callOption['id']; ?>" <?php echo $callId === (int) $callOption['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($callOption['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="organization_id">Ente</label>
                        <select id="organization_id" name="organization_id" class="form-input">
                            <option value="">Tutti gli enti</option>
                            <?php foreach ($organizationOptions as $organizationOption): ?>
                                <option value="<?php echo (int) $organizationOption['id']; ?>" <?php echo $organizationId === (int) $organizationOption['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($organizationOption['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filters-actions">
                        <button type="submit" class="page-button">Applica filtri</button>
                        <a href="<?php echo htmlspecialchars($resetUrl); ?>" class="page-button secondary-button">Reset</a>
                    </div>
                </form>
                <?php if ($filtersApplied): ?>
                    <p class="filter-info">
                        Visualizzando le risposte ai bandi
                        <?php if ($selectedCallTitle): ?>
                            per il bando "<strong><?php echo htmlspecialchars($selectedCallTitle); ?></strong>"
                        <?php endif; ?>
                        <?php if ($selectedOrganizationName): ?>
                            <?php if ($selectedCallTitle): ?>
                                e
                            <?php else: ?>
                                per
                            <?php endif; ?>
                            l'ente "<strong><?php echo htmlspecialchars($selectedOrganizationName); ?></strong>"
                        <?php endif; ?>.
                        <a href="<?php echo htmlspecialchars($resetUrl); ?>">Mostra tutte le risposte ai bandi</a>
                    </p>
                <?php endif; ?>
                <div class="users-table-container">
                    <h2>Risposte ai bandi in attesa di convalida</h2>
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
                            <?php if (empty($pendingApplications)): ?>
                                <tr>
                                    <td colspan="4">Nessuna risposta al bando da convalidare.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingApplications as $app): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($app['call_title']); ?></td>
                                        <td><?php echo htmlspecialchars($app['organization_name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['project_name']); ?></td>
                                        <td>
                                            <a class="page-button" href="application_review.php?application_id=<?php echo $app['id']; ?>">Convalida</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="users-table-container" style="margin-top: 2rem;">
                    <h2>Risposte ai bandi già convalidate</h2>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Bando</th>
                                <th>Ente</th>
                                <th>Nome Progetto</th>
                                <th>Esito</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reviewedApplications)): ?>
                                <tr>
                                    <td colspan="4">Nessuna risposta al bando convalidata.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reviewedApplications as $app): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($app['call_title']); ?></td>
                                        <td><?php echo htmlspecialchars($app['organization_name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['project_name']); ?></td>
                                        <td><?php echo htmlspecialchars($statusLabels[$app['status']] ?? $app['status']); ?></td>
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
