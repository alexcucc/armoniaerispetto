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

                <div class="contact-form-container">
                    <h2>Aggiungi tipologia</h2>
                    <form class="contact-form" action="organization_type_save.php" method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="form-group">
                            <label class="form-label required" for="name">Nome</label>
                            <input type="text" id="name" name="name" class="form-input" required maxlength="255">
                        </div>
                        <div class="button-container">
                            <a href="index.php?open_gestione=1" class="page-button" style="background-color: #007bff;">Indietro</a>
                            <button type="submit" class="page-button">Aggiungi</button>
                        </div>
                    </form>
                </div>

                <div class="users-table-container">
                    <h2 style="margin-top: 32px;">Tipologie esistenti</h2>
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
                                        <div class="actions-cell organization-actions">
                                            <form class="inline-form" action="organization_type_save.php" method="POST" style="display:inline-flex; align-items: center; gap: 8px;">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id" value="<?php echo (int) $type['id']; ?>">
                                                <label class="sr-only" for="name-<?php echo (int) $type['id']; ?>">Nome tipologia</label>
                                                <input
                                                    type="text"
                                                    id="name-<?php echo (int) $type['id']; ?>"
                                                    name="name"
                                                    class="form-input"
                                                    value="<?php echo htmlspecialchars($type['name']); ?>"
                                                    required
                                                    maxlength="255"
                                                    style="width: 240px;"
                                                >
                                                <button type="submit" class="page-button" style="padding: 10px 16px;">Salva</button>
                                            </form>
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
