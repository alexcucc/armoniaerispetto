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
                <label class="form-label required" for="user-search">Utente</label>
                <input
                    type="text"
                    id="user-search"
                    class="form-input"
                    name="user-search"
                    placeholder="Filtra per nome o cognome"
                    aria-label="Filtra e seleziona utenti per nome o cognome"
                    autocomplete="off"
                    required
                >
                <button type="button" id="user-dropdown-toggle" class="autocomplete-toggle" aria-label="Mostra tutti gli utenti disponibili">▼</button>
                <input type="hidden" id="user_id" name="user_id">
                <div id="user-suggestions" class="autocomplete-list" role="listbox"></div>
                <datalist id="available-users">
                    <?php foreach ($users as $user): ?>
                        <?php $displayName = htmlspecialchars($user['last_name'] . ' ' . $user['first_name'] . ' (' . $user['email'] . ')'); ?>
                        <option data-user-id="<?php echo $user['id']; ?>" value="<?php echo $displayName; ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
<script>
    (function() {
        const userInput = document.getElementById('user-search');
        const userIdInput = document.getElementById('user_id');
        const options = Array.from(document.querySelectorAll('#available-users option'));
        const form = document.querySelector('form.contact-form');
        const suggestionBox = document.getElementById('user-suggestions');
        const toggleButton = document.getElementById('user-dropdown-toggle');
        const users = options.map((option) => ({
            id: option.dataset.userId,
            label: option.value,
        }));

        const ensureStyles = () => {
            if (document.getElementById('autocomplete-styles')) return;
            const style = document.createElement('style');
            style.id = 'autocomplete-styles';
            style.textContent = `
                .autocomplete-list {
                    border: 1px solid #ccc;
                    border-top: none;
                    max-height: 200px;
                    overflow-y: auto;
                    background: #fff;
                    position: absolute;
                    width: 100%;
                    z-index: 2;
                    display: none;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    left: 0;
                    top: calc(100% - 1px);
                }
                .autocomplete-item {
                    padding: 8px 10px;
                    cursor: pointer;
                }
                .autocomplete-item:hover,
                .autocomplete-item:focus {
                    background-color: #f0f0f0;
                }
                .autocomplete-toggle {
                    position: absolute;
                    right: 8px;
                    top: 34px;
                    background: transparent;
                    border: none;
                    cursor: pointer;
                    font-size: 0.9rem;
                    padding: 4px;
                    color: #555;
                }
                .form-group .form-input {
                    padding-right: 32px;
                }
            `;
            document.head.appendChild(style);
        };

        let showAllOnEmpty = false;

        const selectUser = (user) => {
            userInput.value = user.label;
            userIdInput.value = user.id;
            userInput.setCustomValidity('');
            suggestionBox.style.display = 'none';
            showAllOnEmpty = false;
        };

        const renderSuggestions = (forceShowAll = false) => {
            const query = userInput.value.trim().toLowerCase();
            const shouldShowAll = forceShowAll || showAllOnEmpty;
            const filtered = query === ''
                ? (shouldShowAll ? users : [])
                : users.filter((user) => user.label.toLowerCase().includes(query));

            suggestionBox.innerHTML = '';

            filtered.forEach((user) => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.textContent = user.label;
                item.tabIndex = 0;
                item.setAttribute('role', 'option');
                item.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    selectUser(user);
                });
                suggestionBox.appendChild(item);
            });

            suggestionBox.style.display = filtered.length ? 'block' : 'none';
        };

        form.addEventListener('submit', (event) => {
            if (!userIdInput.value) {
                event.preventDefault();
                userInput.reportValidity();
            }
        });

        userInput.addEventListener('input', () => {
            userIdInput.value = '';
            userInput.setCustomValidity('Seleziona un utente dalla lista.');
            showAllOnEmpty = false;
            renderSuggestions();
        });

        userInput.addEventListener('focus', () => renderSuggestions());

        userInput.addEventListener('blur', () => {
            setTimeout(() => {
                suggestionBox.style.display = 'none';
                showAllOnEmpty = false;
            }, 150);
        });

        toggleButton.addEventListener('click', () => {
            const isOpen = suggestionBox.style.display === 'block';
            if (isOpen) {
                suggestionBox.style.display = 'none';
                showAllOnEmpty = false;
                return;
            }

            showAllOnEmpty = true;
            renderSuggestions(true);
            userInput.focus();
        });

        ensureStyles();
    })();
</script>
</body>
</html>
