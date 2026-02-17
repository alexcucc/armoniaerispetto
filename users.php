<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['USER_LIST'])) {
    header('Location: login.php');
    exit();
}

// Get all users from database
$stmt = $pdo->prepare(
    "SELECT u.id, u.email, u.first_name, u.last_name, u.phone, u.organization, u.created_at, u.email_verified, "
    . "EXISTS (SELECT 1 FROM evaluator e WHERE e.user_id = u.id) AS is_evaluator, "
    . "EXISTS (SELECT 1 FROM supervisor s WHERE s.user_id = u.id) AS is_supervisor "
    . "FROM user u"
);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php';?>
    <title>Utenti - Admin</title>
</head>
<body class="management-page">
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
                        <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
                    </div>
                    <div class="users-filters" aria-label="Filtri utenti">
                        <div class="filter-group">
                            <input type="text" id="filter-email" data-filter-key="email" placeholder="Cerca email" />
                        </div>
                        <div class="filter-group">
                            <input type="text" id="filter-first-name" data-filter-key="firstName" placeholder="Cerca nome" />
                        </div>
                        <div class="filter-group">
                            <input type="text" id="filter-last-name" data-filter-key="lastName" placeholder="Cerca cognome" />
                        </div>
                        <div class="filter-group">
                            <input type="text" id="filter-phone" data-filter-key="phone" placeholder="Cerca telefono" />
                        </div>
                        <div class="filter-group">
                            <input type="text" id="filter-organization" data-filter-key="organization" placeholder="Cerca organizzazione" />
                        </div>
                        <div class="filter-group">
                            <select id="filter-email-verified" data-filter-key="emailVerified">
                                <option value="">Tutte</option>
                                <option value="1">Verificata</option>
                                <option value="0">Non verificata</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="button" id="clear-filters" class="page-button secondary">Pulisci filtri</button>
                        </div>
                    </div>
                    <div class="users-table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th scope="col" class="sortable" data-sort-key="email" aria-sort="none">Email <span class="sort-indicator" aria-hidden="true"></span></th>
                                    <th scope="col" class="sortable" data-sort-key="firstName" aria-sort="none">Nome <span class="sort-indicator" aria-hidden="true"></span></th>
                                    <th scope="col" class="sortable" data-sort-key="lastName" aria-sort="none">Cognome <span class="sort-indicator" aria-hidden="true"></span></th>
                                    <th scope="col" class="sortable" data-sort-key="phone" aria-sort="none">Telefono <span class="sort-indicator" aria-hidden="true"></span></th>
                                    <th scope="col" class="sortable" data-sort-key="organization" aria-sort="none">Organizzazione <span class="sort-indicator" aria-hidden="true"></span></th>
                                    <th scope="col" class="sortable" data-sort-key="emailVerified" aria-sort="none">Email Verificata <span class="sort-indicator" aria-hidden="true"></span></th>
                                    <th scope="col" class="sortable" data-sort-key="createdAtTimestamp" aria-sort="none">Data Registrazione <span class="sort-indicator" aria-hidden="true"></span></th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr
                                    data-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-first-name="<?php echo htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-last-name="<?php echo htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-phone="<?php echo htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-organization="<?php echo htmlspecialchars($user['organization'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-email-verified="<?php echo $user['email_verified'] ? '1' : '0'; ?>"
                                    data-created-at-timestamp="<?php echo htmlspecialchars((string) strtotime($user['created_at']), ENT_QUOTES, 'UTF-8'); ?>"
                                >
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
                                        <div class="actions-cell role-actions">
                                            <?php
                                                $isEvaluator = (int) ($user['is_evaluator'] ?? 0) === 1;
                                                $isSupervisor = (int) ($user['is_supervisor'] ?? 0) === 1;
                                                $isProtectedRole = $isEvaluator || $isSupervisor;
                                                $cannotDeleteTitle = "Impossibile eliminare: l'utente ";

                                                if ($isEvaluator && $isSupervisor) {
                                                    $cannotDeleteTitle .= 'è un valutatore e un convalidatore';
                                                } elseif ($isEvaluator) {
                                                    $cannotDeleteTitle .= 'è un valutatore';
                                                } elseif ($isSupervisor) {
                                                    $cannotDeleteTitle .= 'è un convalidatore';
                                                }
                                            ?>
                                            <button
                                                class="delete-btn"
                                                data-id="<?php echo $user['id']; ?>"
                                                <?php if ($isProtectedRole): ?>
                                                    disabled
                                                    title="<?php echo htmlspecialchars($cannotDeleteTitle, ENT_QUOTES, 'UTF-8'); ?>"
                                                <?php endif; ?>
                                            >
                                                <i class="fas fa-trash"></i> Elimina
                                            </button>
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
    <?php include 'footer.php';?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const messageDiv = document.getElementById('message');
            const usersTable = document.querySelector('.users-table');
            const tableHeaders = document.querySelectorAll('.users-table th.sortable');
            const filterInputs = document.querySelectorAll('.users-filters [data-filter-key]');
            const clearFiltersButton = document.getElementById('clear-filters');

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

            const compareValues = (valueA, valueB, key) => {
                if (key === 'emailVerified' || key === 'createdAtTimestamp') {
                    return Number(valueA) - Number(valueB);
                }

                const normalizedA = (valueA || '').toString().toLowerCase();
                const normalizedB = (valueB || '').toString().toLowerCase();
                return normalizedA.localeCompare(normalizedB, 'it', { sensitivity: 'base' });
            };

            const updateSortIndicators = (activeHeader, direction) => {
                tableHeaders.forEach(header => {
                    if (header === activeHeader) {
                        header.setAttribute('aria-sort', direction);
                    } else {
                        header.setAttribute('aria-sort', 'none');
                    }
                });
            };

            const sortTable = (key, direction) => {
                if (!usersTable) {
                    return;
                }

                const tbody = usersTable.querySelector('tbody');
                const rows = Array.from(tbody.rows);

                rows.sort((rowA, rowB) => {
                    const valueA = rowA.dataset[key] || '';
                    const valueB = rowB.dataset[key] || '';
                    const comparison = compareValues(valueA, valueB, key);
                    return direction === 'ascending' ? comparison : -comparison;
                });

                rows.forEach(row => tbody.appendChild(row));
            };

            let currentSort = { key: null, direction: 'ascending' };

            tableHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    const key = header.dataset.sortKey;

                    if (!key) {
                        return;
                    }

                    const newDirection = currentSort.key === key && currentSort.direction === 'ascending'
                        ? 'descending'
                        : 'ascending';

                    currentSort = { key, direction: newDirection };
                    sortTable(key, newDirection);
                    updateSortIndicators(header, newDirection);
                });
            });

            const applyFilters = () => {
                if (!usersTable) {
                    return;
                }

                const tbody = usersTable.querySelector('tbody');
                const rows = Array.from(tbody.rows);

                const filters = Array.from(filterInputs).reduce((accumulator, input) => {
                    accumulator[input.dataset.filterKey] = input.value;
                    return accumulator;
                }, {});

                rows.forEach(row => {
                    let isVisible = true;

                    const textFilters = ['email', 'firstName', 'lastName', 'phone', 'organization'];

                    textFilters.forEach(filterKey => {
                        if (!isVisible) {
                            return;
                        }

                        const filterValue = (filters[filterKey] || '').toString().trim().toLowerCase();

                        if (filterValue) {
                            const rowValue = (row.dataset[filterKey] || '').toString().toLowerCase();

                            if (!rowValue.includes(filterValue)) {
                                isVisible = false;
                            }
                        }
                    });

                    if (!isVisible) {
                        row.style.display = 'none';
                        return;
                    }

                    const emailVerifiedFilter = filters.emailVerified;

                    if (emailVerifiedFilter !== undefined && emailVerifiedFilter !== '') {
                        if (row.dataset.emailVerified !== emailVerifiedFilter) {
                            row.style.display = 'none';
                            return;
                        }
                    }

                    row.style.display = '';
                });
            };

            filterInputs.forEach(input => {
                const eventName = input.tagName === 'SELECT' || input.type === 'date' ? 'change' : 'input';
                input.addEventListener(eventName, applyFilters);
            });

            if (clearFiltersButton) {
                clearFiltersButton.addEventListener('click', () => {
                    filterInputs.forEach(input => {
                        if (input.tagName === 'SELECT') {
                            input.selectedIndex = 0;
                        } else {
                            input.value = '';
                        }
                    });

                    applyFilters();
                });
            }

            applyFilters();
        });
    </script>
</body>
</html>
