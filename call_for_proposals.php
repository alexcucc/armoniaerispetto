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
// Fetch all call for proposals with associated application counts
$stmt = $pdo->prepare(
    "SELECT cfp.id,
            cfp.title,
            cfp.description,
            cfp.start_date,
            cfp.end_date,
            cfp.pdf_path,
            cfp.status,
            cfp.closed_at,
            cfp.created_at,
            cfp.updated_at,
            (
                SELECT GROUP_CONCAT(CONCAT(u.last_name, ' ', u.first_name) ORDER BY u.last_name, u.first_name SEPARATOR ', ')
                FROM call_for_proposal_evaluator cfe
                JOIN user u ON u.id = cfe.evaluator_user_id
                WHERE cfe.call_for_proposal_id = cfp.id
            ) AS assigned_evaluators,
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
                    <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
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
                                <th>Stato</th>
                                <th>Valutatori abilitati</th>
                                <th>Creato il</th>
                                <th>Aggiornato il</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calls as $cfp): ?>
                                <?php
                                    $assignedEvaluatorsRaw = trim((string) ($cfp['assigned_evaluators'] ?? ''));
                                    $assignedEvaluators = $assignedEvaluatorsRaw !== ''
                                        ? array_values(array_filter(array_map('trim', explode(',', $assignedEvaluatorsRaw)), static fn ($name) => $name !== ''))
                                        : [];
                                    $assignedEvaluatorCount = count($assignedEvaluators);
                                    $previewEvaluators = array_slice($assignedEvaluators, 0, 3);
                                    $extraEvaluators = array_slice($assignedEvaluators, 3);
                                    $extraCount = count($extraEvaluators);
                                    $toggleTargetId = 'evaluator-extra-' . (int) $cfp['id'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cfp['title']); ?></td>
                                    <td><?php echo htmlspecialchars($cfp['description']); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($cfp['start_date']))); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($cfp['end_date']))); ?></td>
            <td class="status-tag <?php echo $cfp['status'] === 'CLOSED' ? 'status-closed' : 'status-open'; ?>">
                <?php echo $cfp['status'] === 'CLOSED' ? 'Chiuso' : 'Aperto'; ?>
            </td>
                                    <td>
                                        <div class="evaluator-assignment-cell">
                                            <span class="evaluator-assignment-count">
                                                <?php echo htmlspecialchars((string) $assignedEvaluatorCount); ?> valutatori
                                            </span>
                                            <?php if ($assignedEvaluatorCount === 0): ?>
                                                <div class="evaluator-badges">
                                                    <span class="evaluator-badge evaluator-badge--empty">Nessuno</span>
                                                </div>
                                            <?php else: ?>
                                                <div class="evaluator-badges">
                                                    <?php foreach ($previewEvaluators as $evaluatorName): ?>
                                                        <span class="evaluator-badge"><?php echo htmlspecialchars($evaluatorName); ?></span>
                                                    <?php endforeach; ?>
                                                    <?php if ($extraCount > 0): ?>
                                                        <button
                                                            type="button"
                                                            class="evaluator-badge evaluator-badge--more js-evaluator-toggle"
                                                            data-target-id="<?php echo htmlspecialchars($toggleTargetId); ?>"
                                                            aria-expanded="false"
                                                        >
                                                            +<?php echo htmlspecialchars((string) $extraCount); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($extraCount > 0): ?>
                                                    <div class="evaluator-badges evaluator-badges--extra" id="<?php echo htmlspecialchars($toggleTargetId); ?>" hidden>
                                                        <?php foreach ($extraEvaluators as $evaluatorName): ?>
                                                            <span class="evaluator-badge"><?php echo htmlspecialchars($evaluatorName); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($cfp['created_at']))); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($cfp['updated_at']))); ?></td>
                                    <td>
                                        <div class="actions-cell role-actions">
                                            <a class="page-button" href="call_for_proposal_results.php?id=<?php echo $cfp['id']; ?>">Graduatoria</a>
                                            <a class="page-button" href="call_for_proposal_download.php?id=<?php echo $cfp['id']; ?>">Scarica PDF</a>
                                            <?php if ($canUpdate): ?>
                                                <button class="modify-btn" onclick="location.href='call_for_proposal_edit.php?id=<?php echo $cfp['id']; ?>'"><i class="fas fa-edit"></i> Modifica</button>
                                                <?php if ($cfp['status'] === 'OPEN'): ?>
                                                    <button class="close-btn" data-id="<?php echo $cfp['id']; ?>">
                                                        <i class="fas fa-ban"></i> Chiudi
                                                    </button>
                                                <?php else: ?>
                                                    <button class="page-button reopen-btn" data-id="<?php echo $cfp['id']; ?>">
                                                        <i class="fas fa-undo"></i> Riapri
                                                    </button>
                                                <?php endif; ?>
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
        const closeButtons = document.querySelectorAll('.close-btn');
        const reopenButtons = document.querySelectorAll('.reopen-btn');
        const evaluatorToggleButtons = document.querySelectorAll('.js-evaluator-toggle');
        const messageDiv = document.getElementById('message');

        const showMessage = (data) => {
            messageDiv.textContent = data.message;
            messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
            messageDiv.style.display = 'block';
        };

        const attachReopenHandler = (button) => {
            button.addEventListener('click', async function() {
                if (confirm('Vuoi riaprire questo bando?')) {
                    const id = this.dataset.id;
                    try {
                        const response = await fetch('call_for_proposal_reopen.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id })
                        });

                        const data = await response.json();
                        showMessage(data);

                        if (data.success) {
                            const row = this.closest('tr');
                            const statusCell = row.querySelector('.status-tag');
                            statusCell.textContent = 'Aperto';
                            statusCell.classList.remove('status-closed');
                            statusCell.classList.add('status-open');

                            const actionsCell = this.closest('.actions-cell');
                            this.remove();

                            const closeButton = document.createElement('button');
                            closeButton.className = 'close-btn';
                            closeButton.dataset.id = id;
                            closeButton.innerHTML = '<i class="fas fa-ban"></i> Chiudi';
                            actionsCell.appendChild(closeButton);
                            attachCloseHandler(closeButton);
                        }
                    } catch (error) {
                        showMessage({ success: false, message: "Si è verificato un errore durante la riapertura." });
                    }
                }
            });
        };

        const attachCloseHandler = (button) => {
            button.addEventListener('click', async function() {
                if (confirm('Sei sicuro di voler chiudere questo bando? Potrai riaprirlo in seguito.')) {
                    const id = this.dataset.id;
                    try {
                        const response = await fetch('call_for_proposal_close.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id })
                        });

                        const data = await response.json();
                        showMessage(data);

                        if (data.success) {
                            const row = this.closest('tr');
                            const statusCell = row.querySelector('.status-tag');
                            statusCell.textContent = 'Chiuso';
                            statusCell.classList.remove('status-open');
                            statusCell.classList.add('status-closed');

                            const actionsCell = this.closest('.actions-cell');
                            this.remove();

                            const reopenButton = document.createElement('button');
                            reopenButton.className = 'page-button reopen-btn';
                            reopenButton.dataset.id = id;
                            reopenButton.innerHTML = '<i class="fas fa-undo"></i> Riapri';
                            actionsCell.appendChild(reopenButton);
                            attachReopenHandler(reopenButton);
                        }
                    } catch (error) {
                        showMessage({ success: false, message: "Si è verificato un errore durante la chiusura." });
                    }
                }
            });
        };

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

                        showMessage(data);

                        if (data.success) {
                            this.closest('tr').remove();
                        }
                    } catch (error) {
                        showMessage({ success: false, message: "Si è verificato un errore durante l'eliminazione." });
                    }
                }
            });
        });

        closeButtons.forEach(button => attachCloseHandler(button));
        reopenButtons.forEach(button => attachReopenHandler(button));
        evaluatorToggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.dataset.targetId;
                if (!targetId) {
                    return;
                }
                const target = document.getElementById(targetId);
                if (!target) {
                    return;
                }

                const isCurrentlyExpanded = this.getAttribute('aria-expanded') === 'true';
                const shouldExpand = !isCurrentlyExpanded;
                this.setAttribute('aria-expanded', shouldExpand ? 'true' : 'false');
                target.hidden = !shouldExpand;
                this.textContent = shouldExpand ? 'Riduci' : ('+' + target.children.length);
            });
        });
    });
</script>
</body>
</html>
