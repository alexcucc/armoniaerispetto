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
$statusFilter = isset($_GET['status']) ? strtoupper(trim($_GET['status'])) : '';
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

$applications = [];
$filtersApplied = $selectedCallTitle || $selectedOrganizationName || $statusFilter !== '';
$resetUrl = 'supervisor_applications.php';

$statusLabels = [
    'SUBMITTED' => 'In attesa',
    'APPROVED' => 'Convalidata',
    'REJECTED' => 'Respinta',
    'FINAL_VALIDATION' => 'Convalida in definitiva',
];
$allowedStatuses = array_keys($statusLabels);
if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}
$statusOptions = ['' => 'Tutti gli stati'];
foreach ($allowedStatuses as $statusKey) {
    $statusOptions[$statusKey] = $statusLabels[$statusKey] ?? $statusKey;
}

if ($supervisorId) {
    $where = ['a.supervisor_id = :sid'];
    $params = [':sid' => $supervisorId];

    if ($organizationId) {
        $where[] = 'a.organization_id = :organization_id';
        $params[':organization_id'] = $organizationId;
        $filtersApplied = true;
    }

    if ($callId) {
        $where[] = 'a.call_for_proposal_id = :call_id';
        $params[':call_id'] = $callId;
        $filtersApplied = true;
    }

    if ($statusFilter !== '') {
        $where[] = 'a.status = :status_filter';
        $params[':status_filter'] = $statusFilter;
        $filtersApplied = true;
    } else {
        $where[] = 'a.status IN ("SUBMITTED", "APPROVED", "REJECTED", "FINAL_VALIDATION")';
    }

    $sql = 'SELECT a.id, c.title AS call_title, o.name AS organization_name, a.project_name, a.status, a.rejection_reason, c.status AS call_status '
        . 'FROM application a '
        . 'JOIN call_for_proposal c ON a.call_for_proposal_id = c.id '
        . 'JOIN organization o ON a.organization_id = o.id '
        . 'WHERE ' . implode(' AND ', $where)
        . ' ORDER BY a.updated_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Convalida risposte ai bandi</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Convalida risposte ai bandi</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <div class="button-container">
                    <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
                </div>
                <form method="get" class="filters-form">
                    <div class="form-group">
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
                        <select id="organization_id" name="organization_id" class="form-input">
                            <option value="">Tutti gli enti</option>
                            <?php foreach ($organizationOptions as $organizationOption): ?>
                                <option value="<?php echo (int) $organizationOption['id']; ?>" <?php echo $organizationId === (int) $organizationOption['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($organizationOption['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <select id="status" name="status" class="form-input">
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $statusFilter === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
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
                        <?php endif; ?>
                        <?php if ($statusFilter !== ''): ?>
                            <?php if ($selectedCallTitle || $selectedOrganizationName): ?>
                                con lo stato
                            <?php else: ?>
                                nello stato
                            <?php endif; ?>
                            "<strong><?php echo htmlspecialchars($statusLabels[$statusFilter] ?? $statusFilter); ?></strong>"
                        <?php endif; ?>.
                        <a href="<?php echo htmlspecialchars($resetUrl); ?>">Mostra tutte le risposte ai bandi</a>
                    </p>
                <?php endif; ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Bando</th>
                                <th>Ente</th>
                                <th>Nome Progetto</th>
                                <th>Stato</th>
                                <th>Motivo</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="6">Nessuna risposta al bando trovata.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applications as $app): ?>
                                    <?php
                                        $statusKey = strtoupper((string) $app['status']);
                                        $statusLabel = $statusLabels[$statusKey] ?? $statusKey;
                                        $rejectionReason = trim((string) ($app['rejection_reason'] ?? ''));
                                        $isRejected = $statusKey === 'REJECTED';
                                        $isFinal = $statusKey === 'FINAL_VALIDATION';
                                        $isClosed = ($app['call_status'] ?? null) === 'CLOSED';
                                        $canEdit = !$isFinal && !$isClosed;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($app['call_title']); ?></td>
                                        <td><?php echo htmlspecialchars($app['organization_name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['project_name']); ?></td>
                                        <td><?php echo htmlspecialchars($statusLabel); ?></td>
                                        <td>
                                            <?php if ($isRejected): ?>
                                                <?php if ($rejectionReason !== ''): ?>
                                                    <button
                                                        type="button"
                                                        class="icon-button motivation-icon motivation-viewer"
                                                        data-reason="<?php echo htmlspecialchars($rejectionReason, ENT_QUOTES); ?>"
                                                        aria-label="Visualizza motivazione"
                                                    >
                                                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button
                                                        type="button"
                                                        class="icon-button motivation-icon motivation-missing"
                                                        title="Motivazione mancante"
                                                        aria-label="Motivazione mancante"
                                                    >
                                                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <td>
                                            <div class="actions-cell role-actions">
                                                <?php if ($isClosed): ?>
                                                    <span class="text-muted">Bando chiuso</span>
                                                <?php elseif ($canEdit): ?>
                                                    <a class="page-button<?php echo $statusKey === 'SUBMITTED' ? '' : ' secondary-button'; ?>" href="application_review.php?application_id=<?php echo $app['id']; ?>">
                                                        <?php echo $statusKey === 'SUBMITTED' ? 'Convalida' : 'Modifica'; ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
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
