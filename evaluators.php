<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['EVALUATOR_LIST'])) {
    header('Location: index.php');
    exit();
}

$canImpersonate = $rolePermissionManager->userHasPermission(
    $_SESSION['user_id'],
    RolePermissionManager::$PERMISSIONS['USER_IMPERSONATE']
);

$allowedSortFields = ['name', 'email'];
$allowedSortOrders = ['asc', 'desc'];

$sortFieldParam = strtolower(filter_input(INPUT_GET, 'sort', FILTER_UNSAFE_RAW) ?? '');
$sortField = in_array($sortFieldParam, $allowedSortFields, true) ? $sortFieldParam : 'name';

$sortOrderParam = strtolower(filter_input(INPUT_GET, 'order', FILTER_UNSAFE_RAW) ?? '');
$sortOrder = in_array($sortOrderParam, $allowedSortOrders, true) ? strtoupper($sortOrderParam) : 'ASC';

switch ($sortField) {
    case 'email':
        $orderByClause = "u.email $sortOrder";
        break;
    case 'name':
    default:
        $orderByClause = "u.last_name $sortOrder, u.first_name $sortOrder";
        break;
}

$stmt = $pdo->prepare(
    "SELECT e.id, u.id AS user_id, u.first_name, u.last_name, u.email, "
    . "EXISTS (SELECT 1 FROM supervisor s WHERE s.user_id = u.id) AS is_supervisor "
    . "FROM evaluator e "
    . "JOIN user u ON e.user_id = u.id "
    . "ORDER BY $orderByClause"
);
$stmt->execute();
$evaluators = $stmt->fetchAll(PDO::FETCH_ASSOC);

function buildEvaluatorsSortLink(string $field, string $sortField, string $sortOrder): string
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
    <title>Valutatori</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="hero">
        <div class="title">
            <h1>Valutatori</h1>
        </div>
        <div class="content-container">
            <div class="content">
                <div id="message" class="message" style="display: none;"></div>
                <div class="button-container">
                    <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
                    <a class="page-button" href="evaluator_add.php">Aggiungi Valutatore</a>
                </div>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                        <tr>
                            <?php
                            $columns = [
                                'name' => 'Nome',
                                'email' => 'Email',
                            ];
                            foreach ($columns as $field => $label) {
                                $link = buildEvaluatorsSortLink($field, $sortField, $sortOrder);
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
                        <?php foreach ($evaluators as $evaluator): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($evaluator['last_name'] . ' ' . $evaluator['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($evaluator['email']); ?></td>
                                <td>
                                    <div class="actions-cell role-actions">
                                        <?php if ($canImpersonate): ?>
                                            <button
                                                class="impersonate-btn"
                                                data-id="<?php echo $evaluator['user_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($evaluator['last_name'] . ' ' . $evaluator['first_name']); ?>"
                                            >
                                                <i class="fas fa-user-secret"></i> Assumi ruolo
                                            </button>
                                        <?php endif; ?>
                                        <?php if (
                                            $rolePermissionManager->userHasPermission(
                                                $_SESSION['user_id'],
                                                RolePermissionManager::$PERMISSIONS['EVALUATOR_DELETE']
                                            )
                                            && !(int) $evaluator['is_supervisor']
                                        ): ?>
                                            <button class="delete-btn" data-id="<?php echo $evaluator['id']; ?>">
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
        const impersonateButtons = document.querySelectorAll('.impersonate-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', async function() {
                if (confirm('Sei sicuro di voler eliminare questo valutatore?')) {
                    const evalId = this.dataset.id;
                    try {
                        const response = await fetch('evaluator_delete.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: evalId })
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
