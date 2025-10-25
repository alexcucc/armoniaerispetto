<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_LIST'])) {
    header('Location: index.php');
    exit();
}

// Determine sorting parameters
$allowedSortFields = ['call_title', 'organization_name', 'project_name', 'supervisor_name', 'status'];
$allowedSortOrders = ['asc', 'desc'];

$sortField = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortFields)
    ? $_GET['sort']
    : 'call_title';
$sortOrder = isset($_GET['order']) && in_array(strtolower($_GET['order']), $allowedSortOrders)
    ? strtoupper($_GET['order'])
    : 'ASC';

// Fetch all applications including supervisor full name
$organizationId = isset($_GET['organization_id']) ? (int) $_GET['organization_id'] : null;
$callId = isset($_GET['call_id']) ? (int) $_GET['call_id'] : null;
$supervisorId = isset($_GET['supervisor_id']) ? (int) $_GET['supervisor_id'] : null;
$selectedOrganizationName = null;
$selectedCallTitle = null;
$selectedSupervisorName = null;

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

$supervisorOptionsStmt = $pdo->query('SELECT s.id, CONCAT(u.first_name, " ", u.last_name) AS full_name FROM supervisor s JOIN user u ON s.user_id = u.id ORDER BY u.first_name, u.last_name');
$supervisorOptions = $supervisorOptionsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($supervisorId) {
    $supervisorStmt = $pdo->prepare('SELECT CONCAT(u.first_name, " ", u.last_name) AS full_name FROM supervisor s JOIN user u ON s.user_id = u.id WHERE s.id = :id');
    $supervisorStmt->execute([':id' => $supervisorId]);
    $selectedSupervisorName = $supervisorStmt->fetchColumn();

    if (!$selectedSupervisorName) {
        $supervisorId = null;
    }
}

$callOptionsStmt = $pdo->query('SELECT id, title FROM call_for_proposal ORDER BY title');
$callOptions = $callOptionsStmt->fetchAll(PDO::FETCH_ASSOC);

$organizationOptionsStmt = $pdo->query('SELECT id, name FROM organization ORDER BY name');
$organizationOptions = $organizationOptionsStmt->fetchAll(PDO::FETCH_ASSOC);

$whereClauses = [];
$params = [];

if ($organizationId) {
    $whereClauses[] = 'a.organization_id = :organization_id';
    $params[':organization_id'] = $organizationId;
}

if ($callId) {
    $whereClauses[] = 'a.call_for_proposal_id = :call_id';
    $params[':call_id'] = $callId;
}

if ($supervisorId) {
    $whereClauses[] = 'a.supervisor_id = :supervisor_id';
    $params[':supervisor_id'] = $supervisorId;
}

$whereClause = '';
if (!empty($whereClauses)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereClauses);
}

$stmt = $pdo->prepare("SELECT a.id, c.title AS call_title, o.name AS organization_name, a.project_name, CONCAT(u.first_name, ' ', u.last_name) AS supervisor_name, a.status, a.application_pdf_path FROM application a LEFT JOIN call_for_proposal c ON a.call_for_proposal_id = c.id LEFT JOIN organization o ON a.organization_id = o.id LEFT JOIN supervisor s ON a.supervisor_id = s.id LEFT JOIN user u ON s.user_id = u.id $whereClause ORDER BY $sortField $sortOrder");
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentFilters = [
    'call_id' => $callId ?: null,
    'organization_id' => $organizationId ?: null,
    'supervisor_id' => $supervisorId ?: null,
];

function buildApplicationsSortLink(string $field, string $sortField, string $sortOrder, array $currentFilters): string
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

$resetUrl = 'applications.php?' . http_build_query([
    'sort' => $sortField,
    'order' => strtolower($sortOrder),
]);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Risposte ai bandi</title>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Risposte ai bandi</h1>
        </div>
        <div class="content-container">
                <div class="content">
                    <div id="message" class="message" style="display: none;"></div>
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
                        <div class="form-group">
                            <label class="form-label" for="supervisor_id">Convalidatore</label>
                            <select id="supervisor_id" name="supervisor_id" class="form-input">
                                <option value="">Tutti i convalidatori</option>
                                <?php foreach ($supervisorOptions as $supervisorOption): ?>
                                    <option value="<?php echo (int) $supervisorOption['id']; ?>" <?php echo $supervisorId === (int) $supervisorOption['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supervisorOption['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortField); ?>">
                        <input type="hidden" name="order" value="<?php echo htmlspecialchars(strtolower($sortOrder)); ?>">
                        <div class="filters-actions">
                            <button type="submit" class="page-button">Applica filtri</button>
                            <a href="<?php echo htmlspecialchars($resetUrl); ?>" class="page-button secondary-button">Reset</a>
                        </div>
                    </form>
                    <?php if ($selectedCallTitle || $selectedOrganizationName || $selectedSupervisorName): ?>
                        <p class="filter-info">
                            Visualizzando le domande
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
                            <?php if ($selectedSupervisorName): ?>
                                <?php if ($selectedCallTitle || $selectedOrganizationName): ?>
                                    e
                                <?php else: ?>
                                    per
                                <?php endif; ?>
                                il convalidatore "<strong><?php echo htmlspecialchars($selectedSupervisorName); ?></strong>"
                            <?php endif; ?>.
                            <a href="<?php echo htmlspecialchars('applications.php?sort=' . urlencode($sortField) . '&order=' . urlencode(strtolower($sortOrder))); ?>">Mostra tutte le domande</a>
                        </p>
                    <?php endif; ?>
                    <div class="button-container">
                        <a href="javascript:history.back()" class="page-button back-button">Indietro</a>
                    <a href="application_submit.php" class="page-button">Carica risposta al bando</a>
                </div>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <?php
                                $columns = [
                                    'call_title' => 'Bando',
                                    'organization_name' => 'Ente',
                                    'project_name' => 'Nome Progetto',
                                    'supervisor_name' => 'Convalidatore',
                                    'status' => 'Stato'
                                ];
                                foreach ($columns as $field => $label) {
                                    $link = buildApplicationsSortLink($field, $sortField, $sortOrder, $currentFilters);
                                    $icon = '';
                                    if ($sortField === $field) {
                                        $icon = $sortOrder === 'ASC' ? '▲' : '▼';
                                    }
                                    echo '<th><a href="' . htmlspecialchars($link) . '">' . $label . '<span class="sort-icon">' . $icon . '</span></a></th>';
                                }
                                ?>
                                <th>Documento</th>
                                <?php
                                ?>
                                <th>Azioni</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="7">Nessuna domanda trovata.</td>
                                </tr>
                            <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['call_title']); ?></td>
                                    <td><?php echo htmlspecialchars($app['organization_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['project_name']); ?></td>
                                    <?php $supervisorName = trim((string) ($app['supervisor_name'] ?? '')); ?>
                                    <td><?php echo htmlspecialchars($supervisorName !== '' ? $supervisorName : 'Non assegnato'); ?></td>
                                    <td><?php echo htmlspecialchars($app['status']); ?></td>
                                    <td>
                                        <?php if (!empty($app['application_pdf_path'])): ?>
                                        <button class="download-btn" onclick="window.location.href='application_download.php?id=<?php echo $app['id']; ?>'">
                                            <i class="fas fa-file-download"></i> Scarica
                                        </button>
                                        <?php else: ?>
                                            <span class="text-muted">Non disponibile</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_UPDATE'])): ?>
                                        <button class="modify-btn" onclick="window.location.href='application_edit.php?id=<?php echo $app['id']; ?>'">
                                            <i class="fas fa-edit"></i> Modifica
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_DELETE'])): ?>
                                        <button class="delete-btn" data-id="<?php echo $app['id']; ?>">
                                            <i class="fas fa-trash"></i> Elimina
                                        </button>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const messageDiv = document.getElementById('message');

        deleteButtons.forEach(button => {
            button.addEventListener('click', async function() {
                if (confirm('Sei sicuro di voler eliminare questa risposta al bando?')) {
                    const appId = this.dataset.id;
                    try {
                        const response = await fetch('application_delete.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: appId })
                        });

                        const data = await response.json();

                        messageDiv.textContent = data.message;
                        messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
                        messageDiv.style.display = 'block';

                        if (data.success) {
                            this.closest('tr').remove();
                        }
                    } catch (error) {
                        messageDiv.textContent = "Si è verificato un errore durante l'eliminazione.";
                        messageDiv.className = 'message error';
                        messageDiv.style.display = 'block';
                    }
                }
            });
        });
    });
</script>
</body>
</html>
