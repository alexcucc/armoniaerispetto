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
    ? " WHERE e.status IN ('SUBMITTED', 'REVISED')"
    : $filterClause . " AND e.status IN ('SUBMITTED', 'REVISED')";

$completedQuery = "SELECT e.id AS evaluation_id, c.title AS call_title, o.name AS organization_name, "
    . "CONCAT(u.last_name, ' ', u.first_name) AS evaluator_name, "
    . "CONCAT(su.last_name, ' ', su.first_name) AS supervisor_name, "
    . "e.status AS status, COALESCE(e.updated_at, a.updated_at) AS updated_at "
    . "FROM evaluation e "
    . "JOIN application a ON e.application_id = a.id "
    . "JOIN call_for_proposal c ON a.call_for_proposal_id = c.id "
    . "JOIN organization o ON a.organization_id = o.id "
    . "JOIN evaluator ev ON ev.user_id = e.evaluator_id "
    . "JOIN user u ON ev.user_id = u.id "
    . "JOIN supervisor s ON a.supervisor_id = s.id "
    . "JOIN user su ON s.user_id = su.id"
    . $completedFilterClause;

$pendingQuery = "SELECT e.id AS evaluation_id, c.title AS call_title, o.name AS organization_name, "
    . "CONCAT(u.last_name, ' ', u.first_name) AS evaluator_name, "
    . "CONCAT(su.last_name, ' ', su.first_name) AS supervisor_name, "
    . "COALESCE(e.status, 'NOT_STARTED') AS status, COALESCE(e.updated_at, a.updated_at) AS updated_at "
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
    . " (e.id IS NULL OR e.status = 'DRAFT')";

$evaluationsQuery = "(" . $completedQuery . ") UNION ALL (" . $pendingQuery . ") ORDER BY $sortField $sortOrder";

$evaluationsStmt = $pdo->prepare($evaluationsQuery);
$evaluationsStmt->execute($params);
$evaluations = $evaluationsStmt->fetchAll(PDO::FETCH_ASSOC);

$evaluatorsStmt = $pdo->query(
    "SELECT ev.id, CONCAT(u.last_name, ' ', u.first_name) AS full_name "
    . "FROM evaluator ev "
    . "JOIN user u ON ev.user_id = u.id "
    . "ORDER BY u.last_name, u.first_name"
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
    "SELECT s.id, CONCAT(u.last_name, ' ', u.first_name) AS full_name "
    . "FROM supervisor s "
    . "JOIN user u ON s.user_id = u.id "
    . "ORDER BY u.last_name, u.first_name"
);
$supervisors = $supervisorsStmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'SUBMITTED' => 'Valutata',
    'REVISED' => 'Revisionata',
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
                <div id="message" class="message" style="display: none;"></div>
                <div class="button-container">
                    <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
                </div>
                <form class="filters-form" method="get" action="">
                    <div class="form-group">
                        <select id="evaluator_id" name="evaluator_id" class="form-input">
                            <option value="">Tutti i valutatori</option>
                            <?php foreach ($evaluators as $evaluator): ?>
                                <option value="<?php echo htmlspecialchars($evaluator['id']); ?>" <?php echo ($evaluatorId === (int) $evaluator['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($evaluator['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <select id="organization_id" name="organization_id" class="form-input">
                            <option value="">Tutti gli enti</option>
                            <?php foreach ($organizations as $organization): ?>
                                <option value="<?php echo htmlspecialchars($organization['id']); ?>" <?php echo ($organizationId === (int) $organization['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($organization['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <select id="call_id" name="call_id" class="form-input">
                            <option value="">Tutti i bandi</option>
                            <?php foreach ($calls as $call): ?>
                                <option value="<?php echo htmlspecialchars($call['id']); ?>" <?php echo ($callId === (int) $call['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($call['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <select id="supervisor_id" name="supervisor_id" class="form-input">
                            <option value="">Tutti i convalidatori</option>
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
                <div class="users-table-container">
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
                                    $isActive = $sortField === $field;
                                    $ariaSort = $isActive
                                        ? (strtoupper($sortOrder) === 'ASC' ? 'ascending' : 'descending')
                                        : 'none';

                                    echo '<th'
                                        . ' scope="col"'
                                        . ' class="sortable"'
                                        . ' data-sort-url="' . htmlspecialchars($link) . '"'
                                        . ' aria-sort="' . $ariaSort . '"'
                                        . ' tabindex="0"'
                                        . '>'
                                        . '<span class="sortable-header">'
                                        . htmlspecialchars($label)
                                        . '<span class="sort-indicator" aria-hidden="true"></span>'
                                        . '</span>'
                                        . '</th>';
                                }
                                ?>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($evaluations)): ?>
                                <tr>
                                    <td colspan="7">Nessuna valutazione trovata.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($evaluations as $evaluation): ?>
                                    <?php
                                        $formattedDate = $evaluation['updated_at'] ? date('d/m/Y H:i', strtotime($evaluation['updated_at'])) : '-';
                                        $evaluationId = isset($evaluation['evaluation_id']) ? (int) $evaluation['evaluation_id'] : 0;
                                        $canDelete = $evaluationId > 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($evaluation['call_title']); ?></td>
                                        <td><?php echo htmlspecialchars($evaluation['organization_name']); ?></td>
                                        <td><?php echo htmlspecialchars($evaluation['evaluator_name']); ?></td>
                                        <td><?php echo htmlspecialchars($evaluation['supervisor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($statusLabels[$evaluation['status']] ?? $evaluation['status']); ?></td>
                                        <td><?php echo htmlspecialchars($formattedDate); ?></td>
                                        <td>
                                            <div class="actions-cell">
                                                <?php if ($canDelete): ?>
                                                    <button
                                                        type="button"
                                                        class="delete-btn delete-evaluation-btn"
                                                        data-id="<?php echo $evaluationId; ?>"
                                                    >
                                                        <i class="fas fa-trash"></i> Elimina
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-evaluation-btn');
        const messageDiv = document.getElementById('message');

        deleteButtons.forEach(button => {
            button.addEventListener('click', async function() {
                if (!confirm('Sei sicuro di voler eliminare questa valutazione?')) {
                    return;
                }

                const evaluationId = this.dataset.id;
                try {
                    const response = await fetch('evaluation_delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: evaluationId })
                    });

                    const data = await response.json();

                    messageDiv.textContent = data.message || 'Operazione completata.';
                    messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
                    messageDiv.style.display = 'block';

                    if (data.success) {
                        window.location.reload();
                    }
                } catch (error) {
                    messageDiv.textContent = "Si è verificato un errore durante l'eliminazione.";
                    messageDiv.className = 'message error';
                    messageDiv.style.display = 'block';
                }
            });
        });
    });
</script>
</body>
</html>
