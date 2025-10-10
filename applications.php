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
$organizationName = null;

if ($organizationId) {
    $orgStmt = $pdo->prepare('SELECT name FROM organization WHERE id = :id');
    $orgStmt->execute([':id' => $organizationId]);
    $organizationName = $orgStmt->fetchColumn();

    if (!$organizationName) {
        $organizationId = null;
    }
}

$whereClause = '';
$params = [];

if ($organizationId) {
    $whereClause = 'WHERE a.organization_id = :organization_id';
    $params[':organization_id'] = $organizationId;
}

$stmt = $pdo->prepare("SELECT a.id, c.title AS call_title, o.name AS organization_name, a.project_name, CONCAT(u.first_name, ' ', u.last_name) AS supervisor_name, a.status, a.application_pdf_path FROM application a LEFT JOIN call_for_proposal c ON a.call_for_proposal_id = c.id LEFT JOIN organization o ON a.organization_id = o.id LEFT JOIN supervisor s ON a.supervisor_id = s.id LEFT JOIN user u ON s.user_id = u.id $whereClause ORDER BY $sortField $sortOrder");
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <?php if ($organizationName): ?>
                        <p class="filter-info">
                            Visualizzando le domande per l'ente "<strong><?php echo htmlspecialchars($organizationName); ?></strong>".
                            <a href="applications.php">Mostra tutte le domande</a>
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
                                    'supervisor_name' => 'Relatore',
                                    'status' => 'Stato'
                                ];
                                foreach ($columns as $field => $label) {
                                    $nextOrder = ($sortField === $field && $sortOrder === 'ASC') ? 'desc' : 'asc';
                                    $icon = '';
                                    if ($sortField === $field) {
                                        $icon = $sortOrder === 'ASC' ? '▲' : '▼';
                                    }
                                    echo '<th><a href="?sort=' . $field . '&order=' . $nextOrder . '">' . $label . '<span class="sort-icon">' . $icon . '</span></a></th>';
                                }
                                ?>
                                <th>Documento</th>
                                <?php
                                ?>
                                <th>Azioni</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['call_title']); ?></td>
                                    <td><?php echo htmlspecialchars($app['organization_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['project_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['supervisor_name']); ?></td>
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
