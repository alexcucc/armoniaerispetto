<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Fetch all call for proposals
$stmt = $pdo->prepare("SELECT id, title, description, start_date, end_date, pdf_path, created_at, updated_at FROM call_for_proposal");
$stmt->execute();
$calls = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Bandi</title>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Bandi</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <div id="message" class="message" style="display:none;"></div>
                <div class="button-container">
                    <a href="javascript:history.back()" class="page-button back-button">Indietro</a>
                    <a class="page-button" href="call_for_proposal_add.php">Aggiungi Bando</a>
                </div>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                        <tr>
                            <th>Titolo</th>
                            <th>Descrizione</th>
                            <th>Inizio</th>
                            <th>Fine</th>
                            <th>Creato il</th>
                            <th>Aggiornato il</th>
                            <th>Azioni</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($calls as $cfp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cfp['title']); ?></td>
                                <td><?php echo htmlspecialchars($cfp['description']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($cfp['start_date']))); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($cfp['end_date']))); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($cfp['created_at']))); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($cfp['updated_at']))); ?></td>
                                <td>
                                    <a class="page-button" href="call_for_proposal_download.php?id=<?php echo $cfp['id']; ?>">Scarica PDF</a>
                                    <button class="modify-btn" onclick="location.href='call_for_proposal_edit.php?id=<?= $cfp['id']; ?>'"><i class="fas fa-edit"></i> Modifica</button>
                                    <button class="delete-btn" data-id="<?php echo $cfp['id']; ?>"><i class="fas fa-trash"></i> Elimina</button>
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
                if (confirm('Sei sicuro di voler eliminare questo bando?')) {
                    const id = this.dataset.id;
                    try {
                        const response = await fetch('call_for_proposal_delete.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: id })
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