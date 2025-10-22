<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['USER_LIST'])) {
    header('Location: login.php');
    exit();
}

$canImpersonate = $rolePermissionManager->userHasPermission(
    $_SESSION['user_id'],
    RolePermissionManager::$PERMISSIONS['USER_IMPERSONATE']
);

// Get all users from database
$stmt = $pdo->prepare("SELECT id, email, first_name, last_name, phone, organization, created_at, email_verified FROM user");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php';?>
    <title>Utenti - Admin</title>
</head>
<body>
    <?php include 'header.php';?>
    <main>
        <div class="hero">
            <div class="title">
                <h1>Utenti</h1>
            </div>
            <div class="content-container">
                <div class = "content">
                    <div id="message" class="message" style="display: none;"></div>
                    <div class="button-container">
                        <a href="javascript:history.back()" class="page-button back-button">Indietro</a>
                    </div>
                    <div class="users-table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Nome</th>
                                    <th>Cognome</th>
                                    <th>Telefono</th>
                                    <th>Organizzazione</th>
                                    <th>Email Verificata</th>
                                    <th>Data Registrazione</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($user['organization']); ?></td>
                                    <td>
                                        <span class="verification-status <?php echo $user['email_verified'] ? 'verified' : 'unverified'; ?>">
                                            <?php echo $user['email_verified'] ? 'Verificata' : 'Non Verificata'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td class="actions-cell">
                                        <?php if ($canImpersonate): ?>
                                            <button
                                                class="impersonate-btn"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                                            >
                                                <i class="fas fa-user-secret"></i> Assumi ruolo
                                            </button>
                                        <?php endif; ?>
                                        <button class="delete-btn" data-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-trash"></i> Elimina
                                        </button>
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
    <?php include 'footer.php';?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const impersonateButtons = document.querySelectorAll('.impersonate-btn');
            const messageDiv = document.getElementById('message');

            deleteButtons.forEach(button => {
                button.addEventListener('click', async function() {
                    if (confirm('Sei sicuro di voler eliminare questo utente?')) {
                        const userId = this.dataset.id;
                        try {
                            const response = await fetch('user_delete.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ id: userId })
                            });

                            const data = await response.json();
                            
                            messageDiv.textContent = data.message;
                            messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
                            messageDiv.style.display = 'block';

                            if (data.success) {
                                // Remove the row from the table
                                this.closest('tr').remove();
                            }
                        } catch (error) {
                            messageDiv.textContent = 'Si è verificato un errore durante l\'eliminazione.';
                            messageDiv.className = 'message error';
                            messageDiv.style.display = 'block';
                        }
                    }
                });
            });

            impersonateButtons.forEach(button => {
                button.addEventListener('click', async function() {
                    const userId = this.dataset.id;
                    const userName = this.dataset.name;

                    if (!confirm('Vuoi agire come ' + userName + '?')) {
                        return;
                    }

                    try {
                        const response = await fetch('impersonate_user.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: userId })
                        });

                        const data = await response.json();

                        messageDiv.textContent = data.message;
                        messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
                        messageDiv.style.display = 'block';

                        if (data.success && data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } catch (error) {
                        messageDiv.textContent = 'Si è verificato un errore durante il cambio di utente.';
                        messageDiv.className = 'message error';
                        messageDiv.style.display = 'block';
                    }
                });
            });
        });
    </script>
</body>
</html>