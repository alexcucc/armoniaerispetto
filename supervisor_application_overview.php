<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['APPLICATION_LIST']
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

$filterClause = '';
if (!empty($filters)) {
    $filterClause = ' AND ' . implode(' AND ', $filters);
}

function fetchApplicationsByStatus(PDO $pdo, array $params, string $filterClause, array $statuses, string $sortField, string $sortOrder): array
{
    $statusPlaceholders = [];
    $statusParams = [];
    foreach ($statuses as $index => $status) {
        $placeholder = ':status_' . $index;
        $statusPlaceholders[] = $placeholder;
        $statusParams[$placeholder] = $status;
    }

    $query = "SELECT a.id, c.title AS call_title, o.name AS organization_name, "
        . "CONCAT(u.first_name, ' ', u.last_name) AS supervisor_name, a.status, a.updated_at "
        . "FROM application a "
        . "JOIN call_for_proposal c ON a.call_for_proposal_id = c.id "
        . "JOIN organization o ON a.organization_id = o.id "
        . "JOIN supervisor s ON a.supervisor_id = s.id "
        . "JOIN user u ON s.user_id = u.id "
        . "WHERE a.status IN (" . implode(', ', $statusPlaceholders) . ")"
        . $filterClause
        . " ORDER BY $sortField $sortOrder";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($params, $statusParams));

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$compiledApplications = fetchApplicationsByStatus(
    $pdo,
    $params,
    $filterClause,
    ['APPROVED', 'REJECTED'],
    $sortField,
    $sortOrder
);

$pendingApplications = fetchApplicationsByStatus(
    $pdo,
    $params,
    $filterClause,
    ['SUBMITTED'],
    $sortField,
    $sortOrder
);

$supervisorsStmt = $pdo->query(
    "SELECT s.id, CONCAT(u.first_name, ' ', u.last_name) AS full_name " .
    "FROM supervisor s " .
    "JOIN user u ON s.user_id = u.id " .
    "ORDER BY u.first_name, u.last_name"
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
    'SUBMITTED' => 'In attesa di revisione',
    'APPROVED' => 'Approvata',
    'REJECTED' => 'Respinta',
];

$currentFilters = [
    'supervisor_id' => $supervisorId,
    'organization_id' => $organizationId,
    'call_id' => $callId,
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
    <title>Monitoraggio domande dei relatori</title>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Monitoraggio domande dei relatori</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <div class="button-container">
                    <a href="javascript:history.back()" class="page-button back-button">Indietro</a>
                </div>
                <form class="filters-form" method="get" action="">
                    <div class="form-group">
                        <label class="form-label" for="supervisor_id">Relatore</label>
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
                    <div class="filters-actions">
                        <button type="submit" class="page-button">Applica filtri</button>
                        <a href="supervisor_application_overview.php" class="page-button secondary-button">Reset</a>
                    </div>
                </form>
                <section class="users-table-container">
                    <h2>Domande già compilate dai relatori</h2>
                    <table class="users-table">
                        <thead>
                        <tr>
                            <?php
                            $columns = [
                                'call_title' => 'Bando',
                                'organization_name' => 'Ente',
                                'supervisor_name' => 'Relatore',
                                'status' => 'Esito',
                                'updated_at' => 'Ultimo aggiornamento'
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
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($compiledApplications)): ?>
                            <tr>
                                <td colspan="5">Nessuna domanda trovata.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($compiledApplications as $application): ?>
                                <?php $formattedDate = $application['updated_at'] ? date('d/m/Y H:i', strtotime($application['updated_at'])) : '-'; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($application['call_title']); ?></td>
                                    <td><?php echo htmlspecialchars($application['organization_name']); ?></td>
                                    <td><?php echo htmlspecialchars($application['supervisor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($statusLabels[$application['status']] ?? $application['status']); ?></td>
                                    <td><?php echo htmlspecialchars($formattedDate); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </section>

                <section class="users-table-container">
                    <h2>Domande ancora da compilare</h2>
                    <table class="users-table">
                        <thead>
                        <tr>
                            <?php
                            foreach ($columns as $field => $label) {
                                $link = buildSortLink($field, $sortField, $sortOrder, $currentFilters);
                                $icon = '';
                                if ($sortField === $field) {
                                    $icon = $sortOrder === 'ASC' ? '▲' : '▼';
                                }
                                echo '<th><a href="' . htmlspecialchars($link) . '">' . $label . '<span class="sort-icon">' . $icon . '</span></a></th>';
                            }
                            ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($pendingApplications)): ?>
                            <tr>
                                <td colspan="5">Nessuna domanda in attesa per i criteri selezionati.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingApplications as $application): ?>
                                <?php $formattedDate = $application['updated_at'] ? date('d/m/Y H:i', strtotime($application['updated_at'])) : '-'; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($application['call_title']); ?></td>
                                    <td><?php echo htmlspecialchars($application['organization_name']); ?></td>
                                    <td><?php echo htmlspecialchars($application['supervisor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($statusLabels[$application['status']] ?? $application['status']); ?></td>
                                    <td><?php echo htmlspecialchars($formattedDate); ?></td>
                                </tr>
                            <?php endforeach; ?>
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
