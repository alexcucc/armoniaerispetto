<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['SUPERVISOR_CREATE'])) {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare(
    "SELECT u.id, u.first_name, u.last_name, u.email " .
    "FROM user u " .
    "INNER JOIN evaluator e ON u.id = e.user_id " .
    "LEFT JOIN supervisor s ON u.id = s.user_id " .
    "WHERE s.user_id IS NULL " .
    "ORDER BY u.last_name ASC, u.first_name ASC"
);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Aggiungi Convalidatore</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Aggiungi Convalidatore</h2>
        <form class="contact-form" action="supervisor_add_handler.php" method="POST">
            <div class="button-container">
                <a href="supervisors.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Aggiungi</button>
            </div>
            <div class="form-group" style="position: relative;">
                <label class="form-label required" for="user_name">Utente</label>
                <input
                    type="text"
                    id="user_name"
                    class="form-input"
                    name="user_name"
                    list="user-options"
                    placeholder="Inizia a digitare nome o cognome"
                    aria-label="Seleziona un utente"
                    autocomplete="off"
                    required
                >
                <input type="hidden" id="user_id" name="user_id">
                <datalist id="user-options"></datalist>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
<script>
    (function() {
        const userInput = document.getElementById('user_name');
        const userIdInput = document.getElementById('user_id');
        const userOptions = document.getElementById('user-options');
        const form = document.querySelector('form.contact-form');
        const users = <?php echo json_encode($users, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

        const getUserLabel = (user) => `${user.last_name} ${user.first_name} (${user.email})`;

        const renderUserOptions = (searchValue) => {
            const query = searchValue.trim().toLowerCase();
            userOptions.innerHTML = '';

            const matchesQuery = (user) => {
                if (query.length === 0) {
                    return true;
                }

                return user.last_name.toLowerCase().startsWith(query)
                    || user.first_name.toLowerCase().startsWith(query)
                    || user.email.toLowerCase().startsWith(query);
            };

            users
                .filter(matchesQuery)
                .forEach((user) => {
                    const option = document.createElement('option');
                    option.value = getUserLabel(user);
                    userOptions.appendChild(option);
                });
        };

        const syncUserId = (value) => {
            const normalized = value.trim().toLowerCase();
            const match = users.find((user) => getUserLabel(user).toLowerCase() === normalized);
            if (match) {
                userIdInput.value = match.id;
                userInput.setCustomValidity('');
                return;
            }

            userIdInput.value = '';
        };

        form.addEventListener('submit', (event) => {
            if (!userIdInput.value) {
                event.preventDefault();
                userInput.setCustomValidity('Seleziona un utente valido dall’elenco.');
                userInput.reportValidity();
            }
        });

        userInput.addEventListener('input', (event) => {
            const value = event.target.value;
            userInput.setCustomValidity('');
            renderUserOptions(value);
            syncUserId(value);
        });

        renderUserOptions('');
    })();
</script>
</body>
</html>
