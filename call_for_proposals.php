<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$canList = $rolePermissionManager->userHasPermission(
    $_SESSION['user_id'],
    RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_LIST']
);

if (!$canList) {
    header('Location: index.php');
    exit();
}

$canCreate = $rolePermissionManager->userHasPermission(
    $_SESSION['user_id'],
    RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_CREATE']
);
$canUpdate = $rolePermissionManager->userHasPermission(
    $_SESSION['user_id'],
    RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_UPDATE']
);
$canDelete = $rolePermissionManager->userHasPermission(
    $_SESSION['user_id'],
    RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_DELETE']
);
$canViewResults = $rolePermissionManager->userHasPermission(
    $_SESSION['user_id'],
    RolePermissionManager::$PERMISSIONS['EVALUATION_VIEW']
);

// Fetch all call for proposals with associated application counts
$stmt = $pdo->prepare(
    "SELECT cfp.id,
            cfp.title,
            cfp.description,
            cfp.start_date,
            cfp.end_date,
            cfp.pdf_path,
            cfp.created_at,
            cfp.updated_at,
            (
                SELECT COUNT(*)
                FROM application a
                WHERE a.call_for_proposal_id = cfp.id
            ) AS application_count
     FROM call_for_proposal cfp"
);
$stmt->execute();
$calls = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Bandi</title>
</head>
<body class="management-page">
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
                    <?php if ($canCreate): ?>
                        <a class="page-button" href="call_for_proposal_add.php">Aggiungi Bando</a>
                    <?php endif; ?>
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
                                        <div class="actions-cell">
                                            <?php if ($canViewResults): ?>
                                                <a class="page-button" href="call_for_proposal_results.php?id=<?php echo $cfp['id']; ?>">Graduatoria</a>
                                            <?php endif; ?>
                                            <a class="page-button" href="call_for_proposal_download.php?id=<?php echo $cfp['id']; ?>">Scarica PDF</a>
                                            <?php if ($canUpdate): ?>
                                                <button class="modify-btn" onclick="location.href='call_for_proposal_edit.php?id=<?php echo $cfp['id']; ?>'"><i class="fas fa-edit"></i> Modifica</button>
                                            <?php endif; ?>
                                            <?php if ($canDelete && (int) $cfp['application_count'] === 0): ?>
                                                <button class="delete-btn" data-id="<?php echo $cfp['id']; ?>"><i class="fas fa-trash"></i> Elimina</button>
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