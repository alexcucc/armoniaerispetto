<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_TYPE_MANAGE'])) {
    header('Location: index.php');
    exit();
}

$errorMessage = $_SESSION['error_message'] ?? null;
$successMessage = $_SESSION['success_message'] ?? null;

unset($_SESSION['error_message'], $_SESSION['success_message']);

$typesStmt = $pdo->query('SELECT id, name, created_at, updated_at FROM organization_type ORDER BY name');
$organizationTypes = $typesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Tipologie di Ente</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Tipologie di Ente</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <?php if (!empty($errorMessage)): ?>
                    <div class="message error">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($successMessage)): ?>
                    <div class="message success">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <div class="button-container" style="margin-bottom: 16px;">
                    <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
                    <a class="page-button" href="organization_type_add.php">Aggiungi tipologia</a>
                </div>

                <div class="users-table-container">
                    <h2>Tipologie esistenti</h2>
                    <?php if (empty($organizationTypes)): ?>
                        <p>Non sono ancora state create tipologie di ente.</p>
                    <?php else: ?>
                        <table class="users-table">
                            <thead>
                            <tr>
                                <th scope="col">Nome</th>
                                <th scope="col">Azioni</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($organizationTypes as $type): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($type['name']); ?></td>
                                    <td>
                                        <div class="actions-cell role-actions">
                                            <a class="page-button" href="organization_type_edit.php?id=<?php echo (int) $type['id']; ?>" style="padding: 10px 16px;">Modifica</a>
                                            <form class="inline-form delete-type-form" action="organization_type_save.php" method="POST" style="display:inline-block;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int) $type['id']; ?>">
                                                <button type="submit" class="delete-btn" style="margin-left: 8px;">
                                                    <i class="fas fa-trash"></i> Elimina
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.delete-type-form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const confirmed = confirm('Eliminare questa tipologia? Sarà possibile eliminarla solo se non associata ad alcun ente.');
                if (!confirmed) {
                    event.preventDefault();
                }
            });
        });
    });
</script>
</body>
</html>
