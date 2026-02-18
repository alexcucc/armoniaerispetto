<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['EVALUATOR_CREATE'])) {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare(
    "SELECT u.id, u.first_name, u.last_name, u.email " .
    "FROM user u " .
    "LEFT JOIN evaluator e ON u.id = e.user_id " .
    "WHERE e.user_id IS NULL " .
    "ORDER BY u.last_name ASC, u.first_name ASC"
);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Aggiungi Valutatore</title>
    <style>
        .autocomplete-options {
            position: fixed;
            z-index: 1200;
            display: flex;
            flex-direction: column;
            background-color: #fff;
            border: 1px solid #d0d7de;
            border-radius: 0.5em;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
            overflow-y: auto;
        }

        .autocomplete-option {
            width: 100%;
            border: 0;
            border-bottom: 1px solid #edf2f7;
            background: transparent;
            text-align: left;
            padding: 0.55rem 0.75rem;
            cursor: pointer;
            color: #1f2937;
            font: inherit;
        }

        .autocomplete-option:last-child {
            border-bottom: 0;
        }

        .autocomplete-option:hover,
        .autocomplete-option:focus-visible {
            background-color: #f1f5f9;
            outline: none;
        }

        .autocomplete-empty {
            padding: 0.55rem 0.75rem;
            color: #6b7280;
        }
    </style>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Aggiungi Valutatore</h2>
        <form class="contact-form" action="evaluator_add_handler.php" method="POST">
            <div class="button-container">
                <a href="evaluators.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Aggiungi</button>
            </div>
            <div class="form-group" style="position: relative;">
                <label class="form-label required" for="user_name">Utente</label>
                <input
                    type="text"
                    id="user_name"
                    class="form-input"
                    name="user_name"
                    placeholder="Inizia a digitare nome o cognome"
                    aria-label="Seleziona un utente"
                    aria-controls="user-options"
                    aria-expanded="false"
                    autocomplete="off"
                    autofocus
                    required
                >
                <input type="hidden" id="user_id" name="user_id">
                <div id="user-options" class="autocomplete-options" role="listbox" hidden></div>
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
        const viewportPadding = 16;
        let isDropdownOpen = false;

        const getUserLabel = (user) => `${user.last_name} ${user.first_name} (${user.email})`;
        const getUserSearchLabel = (user) => `${user.last_name} ${user.first_name}`;
        const normalize = (value) => value.trim().toLowerCase();
        const matchesQuery = (user, query) => (
            query.length === 0
            || normalize(getUserSearchLabel(user)).startsWith(query)
        );

        const positionDropdown = () => {
            const rect = userInput.getBoundingClientRect();
            const availableHeight = Math.max(window.innerHeight - rect.bottom - viewportPadding, 120);

            userOptions.style.left = `${rect.left}px`;
            userOptions.style.top = `${rect.bottom}px`;
            userOptions.style.width = `${rect.width}px`;
            userOptions.style.maxHeight = `${availableHeight}px`;
        };

        const openDropdown = () => {
            positionDropdown();
            userOptions.hidden = false;
            isDropdownOpen = true;
            userInput.setAttribute('aria-expanded', 'true');
        };

        const closeDropdown = () => {
            userOptions.hidden = true;
            isDropdownOpen = false;
            userInput.setAttribute('aria-expanded', 'false');
        };

        const renderUserOptions = (searchValue) => {
            const query = normalize(searchValue);
            userOptions.innerHTML = '';
            const filteredUsers = users.filter((user) => matchesQuery(user, query));

            if (filteredUsers.length === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'autocomplete-empty';
                emptyState.textContent = 'Nessun utente trovato';
                userOptions.appendChild(emptyState);
            } else {
                filteredUsers.forEach((user) => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'autocomplete-option';
                    option.role = 'option';
                    option.textContent = getUserLabel(user);

                    option.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                    });

                    option.addEventListener('click', () => {
                        userInput.value = getUserLabel(user);
                        userIdInput.value = user.id;
                        userInput.setCustomValidity('');
                        closeDropdown();
                    });

                    userOptions.appendChild(option);
                });
            }

            openDropdown();
        };

        const syncUserId = (value) => {
            const normalized = normalize(value);
            const match = users.find((user) => normalize(getUserLabel(user)) === normalized);
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

        userInput.addEventListener('focus', () => {
            renderUserOptions(userInput.value);
        });

        userInput.addEventListener('click', () => {
            renderUserOptions(userInput.value);
        });

        document.addEventListener('click', (event) => {
            if (event.target !== userInput && !userOptions.contains(event.target)) {
                closeDropdown();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeDropdown();
            }
        });

        window.addEventListener('resize', () => {
            if (isDropdownOpen) {
                positionDropdown();
            }
        });

        window.addEventListener('scroll', () => {
            if (isDropdownOpen) {
                positionDropdown();
            }
        }, true);
    })();
</script>
</body>
</html>
