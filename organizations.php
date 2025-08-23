<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_LIST'])) {
    header('Location: index.php');
    exit();
}

// Fetch all organizations
$stmt = $pdo->prepare("SELECT id, name, type, incorporation_date, full_address, created_at, updated_at FROM organization");
$stmt->execute();
$organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Enti</title>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Enti</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <div id="message" class="message" style="display: none;"></div>
                <a class="page-button" href="organization_add.php" style="margin-bottom: 20px; display: inline-block;">Aggiungi Ente</a>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Data di costituzione</th>
                            <th>Indirizzo completo</th>
                            <th>Creato il</th>
                            <th>Aggiornato il</th>
                            <th>Azioni</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($organizations as $org): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($org['name']); ?></td>
                                <td><?php echo htmlspecialchars($org['type']); ?></td>
                                <td><?php echo $org['incorporation_date'] ? htmlspecialchars(date('d/m/Y', strtotime($org['incorporation_date']))) : ''; ?></td>
                                <td><?php echo htmlspecialchars($org['full_address']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($org['created_at']))); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($org['updated_at']))); ?></td>
                                <td>
                                    <?php if ($rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_DELETE'])): ?>
                                    <button class="delete-btn" data-id="<?php echo $org['id']; ?>">
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
