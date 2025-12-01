<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['SUPERVISOR_MONITOR']
    )) {
    header('Location: index.php');
    exit();
}

$allowedSortFields = ['call_title', 'organization_name', 'supervisor_name', 'status', 'updated_at'];
$allowedSortOrders = ['asc', 'desc'];

$sortFieldParam = filter_input(INPUT_GET, 'sort', FILTER_UNSAFE_RAW) ?? '';
$sortField = in_array($sortFieldParam, $allowedSortFields, true) ? $sortFieldParam : 'updated_at';

$sortOrderParam = strtolower(filter_input(INPUT_GET, 'order', FILTER_UNSAFE_RAW) ?? '');
$sortOrder = in_array($sortOrderParam, $allowedSortOrders, true) ? strtoupper($sortOrderParam) : 'DESC';

$filters = [];
$params = [];

$supervisorId = filter_input(INPUT_GET, 'supervisor_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$organizationId = filter_input(INPUT_GET, 'organization_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$callId = filter_input(INPUT_GET, 'call_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$statusFilterParam = strtoupper(trim((string) (filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW) ?? '')));
$allowedStatuses = ['SUBMITTED', 'APPROVED', 'REJECTED', 'FINAL_VALIDATION'];
$statusFilter = in_array($statusFilterParam, $allowedStatuses, true) ? $statusFilterParam : '';

if ($supervisorId) {
    $filters[] = 'a.supervisor_id = :supervisor_id';
    $params[':supervisor_id'] = $supervisorId;
}

if ($organizationId) {
    $filters[] = 'a.organization_id = :organization_id';
    $params[':organization_id'] = $organizationId;
}

if ($callId) {
    $filters[] = 'a.call_for_proposal_id = :call_id';
    $params[':call_id'] = $callId;
}

if ($statusFilter !== '') {
    $filters[] = 'a.status = :status_filter';
    $params[':status_filter'] = $statusFilter;
}

$whereClause = '';
if (!empty($filters)) {
    $whereClause = ' WHERE ' . implode(' AND ', $filters);
}

$applicationsQuery = "SELECT a.id, c.title AS call_title, o.name AS organization_name, "
    . "CONCAT(u.last_name, ' ', u.first_name) AS supervisor_name, a.status, a.updated_at, a.rejection_reason "
    . "FROM application a "
    . "JOIN call_for_proposal c ON a.call_for_proposal_id = c.id "
    . "JOIN organization o ON a.organization_id = o.id "
    . "JOIN supervisor s ON a.supervisor_id = s.id "
    . "JOIN user u ON s.user_id = u.id"
    . $whereClause
    . " ORDER BY $sortField $sortOrder";

$applicationsStmt = $pdo->prepare($applicationsQuery);
$applicationsStmt->execute($params);
$applications = $applicationsStmt->fetchAll(PDO::FETCH_ASSOC);

$supervisorsStmt = $pdo->query(
    "SELECT s.id, CONCAT(u.last_name, ' ', u.first_name) AS full_name " .
    "FROM supervisor s " .
    "JOIN user u ON s.user_id = u.id " .
    "ORDER BY u.last_name, u.first_name"
);
$supervisors = $supervisorsStmt->fetchAll(PDO::FETCH_ASSOC);

$organizationsStmt = $pdo->query(
    "SELECT id, name FROM organization ORDER BY name"
);
$organizations = $organizationsStmt->fetchAll(PDO::FETCH_ASSOC);

$callsStmt = $pdo->query(
    "SELECT id, title FROM call_for_proposal ORDER BY title"
);
$calls = $callsStmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'SUBMITTED' => 'In attesa di convalida',
    'APPROVED' => 'Convalidata',
    'REJECTED' => 'Respinta',
    'FINAL_VALIDATION' => 'Convalida in definitiva',
];
$statusOptions = ['' => 'Tutti gli stati'];
foreach ($allowedStatuses as $statusKey) {
    $statusOptions[$statusKey] = $statusLabels[$statusKey] ?? $statusKey;
}

$currentFilters = [
    'supervisor_id' => $supervisorId,
    'organization_id' => $organizationId,
    'call_id' => $callId,
    'status' => $statusFilter,
];

function buildSortLink(string $field, string $sortField, string $sortOrder, array $currentFilters): string
{
    $nextOrder = ($sortField === $field && $sortOrder === 'ASC') ? 'desc' : 'asc';
    $query = array_filter(
        array_merge($currentFilters, [
            'sort' => $field,
            'order' => $nextOrder,
        ]),
        static function ($value) {
            return $value !== null && $value !== '';
        }
    );

    return '?' . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Monitoraggio risposte ai bandi dei convalidatori</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Monitoraggio risposte ai bandi dei convalidatori</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <div class="button-container">
                    <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
                </div>
                <form class="filters-form" method="get" action="">
                    <div class="form-group">
                        <label class="form-label" for="supervisor_id">Convalidatore</label>
                        <select id="supervisor_id" name="supervisor_id" class="form-input">
                            <option value="">Tutti</option>
                            <?php foreach ($supervisors as $supervisor): ?>
                                <option value="<?php echo htmlspecialchars($supervisor['id']); ?>" <?php echo ($supervisorId === (int) $supervisor['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($supervisor['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="organization_id">Ente</label>
                        <select id="organization_id" name="organization_id" class="form-input">
                            <option value="">Tutti</option>
                            <?php foreach ($organizations as $organization): ?>
                                <option value="<?php echo htmlspecialchars($organization['id']); ?>" <?php echo ($organizationId === (int) $organization['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($organization['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="call_id">Bando</label>
                        <select id="call_id" name="call_id" class="form-input">
                            <option value="">Tutti</option>
                            <?php foreach ($calls as $call): ?>
                                <option value="<?php echo htmlspecialchars($call['id']); ?>" <?php echo ($callId === (int) $call['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($call['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="status">Stato</label>
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
                        <a href="supervisor_application_overview.php" class="page-button secondary-button">Reset</a>
                    </div>
                </form>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <?php
                                $columns = [
                                    'call_title' => 'Bando',
                                    'organization_name' => 'Ente',
                                    'supervisor_name' => 'Convalidatore',
                                    'status' => 'Stato',
                                    'updated_at' => 'Ultimo aggiornamento',
                                ];
                                foreach ($columns as $field => $label) {
                                    $link = buildSortLink($field, $sortField, $sortOrder, $currentFilters);
                                    $icon = '';
                                    if ($sortField === $field) {
                                        $icon = $sortOrder === 'ASC' ? '▲' : '▼';
                                    }
                                    echo '<th><a href="' . htmlspecialchars($link) . '">' . $label . '<span class="sort-icon">' . $icon . '</span></a></th>';
                                }
                                ?>
                                <th>Motivazione</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="6">Nessuna risposta al bando trovata.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applications as $application): ?>
                                    <?php
                                        $statusKey = strtoupper((string) $application['status']);
                                        $statusLabel = $statusLabels[$statusKey] ?? $statusKey;
                                        $rejectionReason = trim((string) ($application['rejection_reason'] ?? ''));
                                        $formattedDate = $application['updated_at']
                                            ? date('d/m/Y H:i', strtotime($application['updated_at']))
                                            : '-';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($application['call_title']); ?></td>
                                        <td><?php echo htmlspecialchars($application['organization_name']); ?></td>
                                        <td><?php echo htmlspecialchars($application['supervisor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($statusLabel); ?></td>
                                        <td><?php echo htmlspecialchars($formattedDate); ?></td>
                                        <td>
                                            <?php if ($statusKey === 'REJECTED'): ?>
                                                <?php if ($rejectionReason !== ''): ?>
                                                    <?php echo nl2br(htmlspecialchars($rejectionReason)); ?>
                                                <?php else: ?>
                                                    <span class="text-warning">Motivazione mancante</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
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
