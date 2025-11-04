<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['SUPERVISOR_LIST'])) {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare("SELECT s.id, u.first_name, u.last_name, u.email FROM supervisor s JOIN user u ON s.user_id = u.id");
$stmt->execute();
$supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Convalidatori</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Convalidatori</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <div id="message" class="message" style="display: none;"></div>
                <div class="button-container">
                    <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
                    <a class="page-button" href="supervisor_add.php">Aggiungi Convalidatore</a>
                </div>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Azioni</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($supervisors as $supervisor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($supervisor['first_name'] . ' ' . $supervisor['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($supervisor['email']); ?></td>
                                <td>
                                    <div class="actions-cell">
                                        <?php if ($rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['SUPERVISOR_DELETE'])): ?>
                                            <button class="delete-btn" data-id="<?php echo $supervisor['id']; ?>">
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
                if (confirm('Sei sicuro di voler eliminare questo convalidatore?')) {
                    const supervisorId = this.dataset.id;
                    try {
                        const response = await fetch('supervisor_delete.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: supervisorId })
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
