<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_LIST'])) {
    header('Location: index.php');
    exit();
}

$canCreate = $rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_CREATE']);
$canUpdate = $rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_UPDATE']);
$canDelete = $rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_DELETE']);
$canSeeApplications = $rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_LIST']);

// Determine sorting parameters
$allowedSortFields = ['name', 'type', 'incorporation_year', 'location'];
$allowedSortOrders = ['asc', 'desc'];

$sortField = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortFields)
    ? $_GET['sort']
    : 'name';
$sortOrder = isset($_GET['order']) && in_array(strtolower($_GET['order']), $allowedSortOrders)
    ? strtoupper($_GET['order'])
    : 'ASC';

// Fetch all organizations with sorting
$stmt = $pdo->prepare(
    "SELECT
        id,
        name,
        type,
        incorporation_year, 
        location AS location, 
        (SELECT COUNT(*) FROM application WHERE organization_id = organization.id) AS application_count
    FROM organization ORDER BY $sortField $sortOrder"
);
$stmt->execute();
$organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);

function buildOrganizationsSortLink(string $field, string $sortField, string $sortOrder): string
{
    $nextOrder = ($sortField === $field && $sortOrder === 'ASC') ? 'desc' : 'asc';

    return '?' . http_build_query([
        'sort' => $field,
        'order' => $nextOrder,
    ]);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Enti</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Enti</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <div id="message" class="message" style="display: none;"></div>
                <div class="button-container">
                    <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
                    <?php if ($canCreate): ?>
                        <a class="page-button" href="organization_add.php">Aggiungi Ente</a>
                    <?php endif; ?>
                </div>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                        <tr>
                            <?php
                            $columns = [
                                'name' => 'Denominazione',
                                'type' => 'Tipo',
                                'incorporation_year' => 'Anno di costituzione',
                                'location' => 'Località',
                            ];
                            foreach ($columns as $field => $label) {
                                $link = buildOrganizationsSortLink($field, $sortField, $sortOrder);
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
                        <?php foreach ($organizations as $org): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($org['name']); ?></td>
                                <td><?php echo htmlspecialchars($org['type']); ?></td>
                                <td><?php echo htmlspecialchars($org['incorporation_year']); ?></td>
                                <td><?php echo htmlspecialchars($org['location']); ?></td>
                                <td>
                                    <div class="actions-cell organization-actions">
                                        <?php if ($canSeeApplications): ?>
                                            <button class="view-btn" onclick="window.location.href='applications.php?organization_id=<?php echo $org['id']; ?>'">
                                                <i class="fas fa-eye"></i> Risposte ai bandi
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($canUpdate): ?>
                                            <button class="modify-btn" onclick="window.location.href='organization_edit.php?id=<?php echo $org['id']; ?>'">
                                                <i class="fas fa-edit"></i> Modifica
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($canDelete && (int)$org['application_count'] === 0): ?>
                                            <button class="delete-btn" data-id="<?php echo $org['id']; ?>">
                                                <i class="fas fa-trash"></i> Elimina
                                            </button>
                                        <?php endif; ?>
                                    </div>
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
                if (confirm('Sei sicuro di voler eliminare questo ente?')) {
                    const orgId = this.dataset.id;
                    try {
                        const response = await fetch('organization_delete.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: orgId })
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
