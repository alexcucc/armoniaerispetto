<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once 'db/common-db.php';

// Get all users from database
$stmt = $pdo->prepare("SELECT id, email, first_name, last_name, phone, organization, created_at, email_verified FROM user");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php';?>
    <title>Gestione Utenti - Admin</title>
</head>
<body>
    <?php include 'header.php';?>
    <main>
        <div class="hero">
            <div class="title">
                <h1>Gestione Utenti</h1>
            </div>
            <div class="content-container">
                <div class = "content">
                    <div id="message" class="message" style="display: none;"></div>
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
                                    <td>
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
        });
    </script>
</body>
</html>