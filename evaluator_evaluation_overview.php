<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['EVALUATOR_MONITOR']
    )) {
    header('Location: index.php');
    exit();
}

$allowedSortFields = ['call_title', 'organization_name', 'evaluator_name', 'supervisor_name', 'status', 'updated_at'];
$allowedSortOrders = ['asc', 'desc'];

$sortFieldParam = filter_input(INPUT_GET, 'sort', FILTER_UNSAFE_RAW) ?? '';
$sortField = in_array($sortFieldParam, $allowedSortFields, true) ? $sortFieldParam : 'updated_at';

$sortOrderParam = strtolower(filter_input(INPUT_GET, 'order', FILTER_UNSAFE_RAW) ?? '');
$sortOrder = in_array($sortOrderParam, $allowedSortOrders, true) ? strtoupper($sortOrderParam) : 'DESC';

$params = [];
$filters = [];

$evaluatorId = filter_input(INPUT_GET, 'evaluator_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$organizationId = filter_input(INPUT_GET, 'organization_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$callId = filter_input(INPUT_GET, 'call_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$supervisorId = filter_input(INPUT_GET, 'supervisor_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;

if ($evaluatorId) {
    $filters[] = 'ev.id = :evaluator_id';
    $params[':evaluator_id'] = $evaluatorId;
}

if ($organizationId) {
    $filters[] = 'a.organization_id = :organization_id';
    $params[':organization_id'] = $organizationId;
}

if ($callId) {
    $filters[] = 'a.call_for_proposal_id = :call_id';
    $params[':call_id'] = $callId;
}

if ($supervisorId) {
    $filters[] = 'a.supervisor_id = :supervisor_id';
    $params[':supervisor_id'] = $supervisorId;
}

$filterClause = '';
if (!empty($filters)) {
    $filterClause = ' WHERE ' . implode(' AND ', $filters);
}

$completedFilterClause = $filterClause === ''
    ? " WHERE e.status = 'SUBMITTED'"
    : $filterClause . " AND e.status = 'SUBMITTED'";

$completedQuery = "SELECT e.id, c.title AS call_title, o.name AS organization_name, "
    . "CONCAT(u.first_name, ' ', u.last_name) AS evaluator_name, "
    . "CONCAT(su.first_name, ' ', su.last_name) AS supervisor_name, "
    . "'SUBMITTED' AS status, e.updated_at "
    . "FROM evaluation e "
    . "JOIN application a ON e.application_id = a.id "
    . "JOIN call_for_proposal c ON a.call_for_proposal_id = c.id "
    . "JOIN organization o ON a.organization_id = o.id "
    . "JOIN evaluator ev ON ev.user_id = e.evaluator_id "
    . "JOIN user u ON ev.user_id = u.id "
    . "JOIN supervisor s ON a.supervisor_id = s.id "
    . "JOIN user su ON s.user_id = su.id"
    . $completedFilterClause
    . " ORDER BY $sortField $sortOrder";

$completedStmt = $pdo->prepare($completedQuery);
$completedStmt->execute($params);
$completedEvaluations = $completedStmt->fetchAll(PDO::FETCH_ASSOC);

$pendingQuery = "SELECT a.id AS application_id, c.title AS call_title, o.name AS organization_name, "
    . "CONCAT(u.first_name, ' ', u.last_name) AS evaluator_name, "
    . "CONCAT(su.first_name, ' ', su.last_name) AS supervisor_name, "
    . "COALESCE(e.status, 'NOT_STARTED') AS status, a.updated_at "
    . "FROM evaluator ev "
    . "JOIN user u ON ev.user_id = u.id "
    . "JOIN application a ON a.status = 'FINAL_VALIDATION' "
    . "JOIN call_for_proposal c ON a.call_for_proposal_id = c.id "
    . "JOIN organization o ON a.organization_id = o.id "
    . "JOIN supervisor s ON a.supervisor_id = s.id "
    . "JOIN user su ON s.user_id = su.id "
    . "LEFT JOIN evaluation e ON e.application_id = a.id AND e.evaluator_id = ev.user_id"
    . $filterClause
    . ($filterClause === '' ? ' WHERE' : ' AND')
    . " (e.id IS NULL OR e.status = 'DRAFT')"
    . " ORDER BY $sortField $sortOrder";

$pendingStmt = $pdo->prepare($pendingQuery);
$pendingStmt->execute($params);
$pendingEvaluations = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

$evaluatorsStmt = $pdo->query(
    "SELECT ev.id, CONCAT(u.first_name, ' ', u.last_name) AS full_name "
    . "FROM evaluator ev "
    . "JOIN user u ON ev.user_id = u.id "
    . "ORDER BY u.first_name, u.last_name"
);
$evaluators = $evaluatorsStmt->fetchAll(PDO::FETCH_ASSOC);

$organizationsStmt = $pdo->query(
    "SELECT id, name FROM organization ORDER BY name"
);
$organizations = $organizationsStmt->fetchAll(PDO::FETCH_ASSOC);

$callsStmt = $pdo->query(
    "SELECT id, title FROM call_for_proposal ORDER BY title"
);
$calls = $callsStmt->fetchAll(PDO::FETCH_ASSOC);

$supervisorsStmt = $pdo->query(
    "SELECT s.id, CONCAT(u.first_name, ' ', u.last_name) AS full_name "
    . "FROM supervisor s "
    . "JOIN user u ON s.user_id = u.id "
    . "ORDER BY u.first_name, u.last_name"
);
$supervisors = $supervisorsStmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'SUBMITTED' => 'Valutata',
    'DRAFT' => 'Valutazione in bozza',
    'NOT_STARTED' => 'In attesa di valutazione',
];

$currentFilters = [
    'evaluator_id' => $evaluatorId,
    'organization_id' => $organizationId,
    'call_id' => $callId,
    'supervisor_id' => $supervisorId,
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
    <title>Monitoraggio valutatori</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Monitoraggio delle valutazioni</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <div class="button-container">
                    <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
                </div>
                <form class="filters-form" method="get" action="">
                    <div class="form-group">
                        <label class="form-label" for="evaluator_id">Valutatore</label>
                        <select id="evaluator_id" name="evaluator_id" class="form-input">
                            <option value="">Tutti</option>
                            <?php foreach ($evaluators as $evaluator): ?>
                                <option value="<?php echo htmlspecialchars($evaluator['id']); ?>" <?php echo ($evaluatorId === (int) $evaluator['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($evaluator['full_name']); ?>
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
                    <div class="filters-actions">
                        <button type="submit" class="page-button">Applica filtri</button>
                        <a href="evaluator_evaluation_overview.php" class="page-button secondary-button">Reset</a>
                    </div>
                </form>
                <div class="tab-container">
                    <div class="tab-buttons" role="tablist" aria-label="Filtra valutazioni per stato">
                        <button
                            type="button"
                            class="tab-button active"
                            role="tab"
                            id="evaluations-completed-tab"
                            aria-controls="evaluations-completed"
                            aria-selected="true"
                        >
                            Valutazioni completate
                        </button>
                        <button
                            type="button"
                            class="tab-button"
                            role="tab"
                            id="evaluations-pending-tab"
                            aria-controls="evaluations-pending"
                            aria-selected="false"
                        >
                            Valutazioni da compilare
                        </button>
                    </div>
                    <div class="tab-panels">
                        <section
                            id="evaluations-completed"
                            class="tab-panel active users-table-container"
                            role="tabpanel"
                            aria-labelledby="evaluations-completed-tab">
                            <table class="users-table">
                                <thead>
                                <tr>
                                    <?php
                                    $columns = [
                                        'call_title' => 'Bando',
                                        'organization_name' => 'Ente',
                                        'evaluator_name' => 'Valutatore',
                                        'supervisor_name' => 'Convalidatore',
                                        'status' => 'Stato',
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
                                <?php if (empty($completedEvaluations)): ?>
                                    <tr>
                                        <td colspan="6">Nessuna valutazione trovata.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($completedEvaluations as $evaluation): ?>
                                        <?php $formattedDate = $evaluation['updated_at'] ? date('d/m/Y H:i', strtotime($evaluation['updated_at'])) : '-'; ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($evaluation['call_title']); ?></td>
                                            <td><?php echo htmlspecialchars($evaluation['organization_name']); ?></td>
                                            <td><?php echo htmlspecialchars($evaluation['evaluator_name']); ?></td>
                                            <td><?php echo htmlspecialchars($evaluation['supervisor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($statusLabels[$evaluation['status']] ?? $evaluation['status']); ?></td>
                                            <td><?php echo htmlspecialchars($formattedDate); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </section>
                        <section
                            id="evaluations-pending"
                            class="tab-panel users-table-container"
                            role="tabpanel"
                            aria-labelledby="evaluations-pending-tab"
                            hidden>
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
                                <?php if (empty($pendingEvaluations)): ?>
                                    <tr>
                                        <td colspan="6">Nessuna valutazione in sospeso per i criteri selezionati.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pendingEvaluations as $evaluation): ?>
                                        <?php $formattedDate = $evaluation['updated_at'] ? date('d/m/Y H:i', strtotime($evaluation['updated_at'])) : '-'; ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($evaluation['call_title']); ?></td>
                                            <td><?php echo htmlspecialchars($evaluation['organization_name']); ?></td>
                                            <td><?php echo htmlspecialchars($evaluation['evaluator_name']); ?></td>
                                            <td><?php echo htmlspecialchars($evaluation['supervisor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($statusLabels[$evaluation['status']] ?? $evaluation['status']); ?></td>
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
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
