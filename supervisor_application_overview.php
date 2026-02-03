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
    . "CONCAT(u.last_name, ' ', u.first_name) AS supervisor_name, a.status, a.updated_at, a.rejection_reason, "
    . "(SELECT COUNT(*) FROM evaluation e WHERE e.application_id = a.id) AS evaluation_count "
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
                <div id="message" class="message" style="display: none;"></div>
                <div class="button-container">
                    <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
                </div>
                <form class="filters-form" method="get" action="">
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
                                <th>Motivo</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="7">Nessuna risposta al bando trovata.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applications as $application): ?>
                                    <?php
                                        $statusKey = strtoupper((string) $application['status']);
                                        $statusLabel = $statusLabels[$statusKey] ?? $statusKey;
                                        $rejectionReason = trim((string) ($application['rejection_reason'] ?? ''));
                                        $evaluationCount = (int) ($application['evaluation_count'] ?? 0);
                                        $hasEvaluations = $evaluationCount > 0;
                                        $canDeleteValidation = in_array($statusKey, ['APPROVED', 'REJECTED', 'FINAL_VALIDATION'], true);
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
                                            <div class="actions-cell">
                                                <?php if ($canDeleteValidation && !$hasEvaluations): ?>
                                                    <button
                                                        type="button"
                                                        class="delete-btn delete-validation-btn"
                                                        data-id="<?php echo (int) $application['id']; ?>"
                                                    >
                                                        <i class="fas fa-trash"></i> Annulla convalida
                                                    </button>
                                                <?php elseif ($canDeleteValidation && $hasEvaluations): ?>
                                                    <span class="text-muted" title="Valutazioni già compilate">Valutazioni presenti</span>
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
        const deleteButtons = document.querySelectorAll('.delete-validation-btn');
        const messageDiv = document.getElementById('message');

        deleteButtons.forEach(button => {
            button.addEventListener('click', async function() {
                if (!confirm('Sei sicuro di voler annullare questa convalida? La risposta al bando tornerà allo stato precedente.')) {
                    return;
                }

                const appId = this.dataset.id;
                try {
                    const response = await fetch('supervisor_validation_delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: appId })
                    });

                    const data = await response.json();

                    messageDiv.textContent = data.message || 'Operazione completata.';
                    messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
                    messageDiv.style.display = 'block';

                    if (data.success) {
                        window.location.reload();
                    }
                } catch (error) {
                    messageDiv.textContent = "Si è verificato un errore durante l'annullamento della convalida.";
                    messageDiv.className = 'message error';
                    messageDiv.style.display = 'block';
                }
            });
        });
    });
</script>
</body>
</html>
