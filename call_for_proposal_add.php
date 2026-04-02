<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_CREATE'])) {
    header('Location: index.php');
    exit();
}

$errorMessage = $_SESSION['call_for_proposal_form_error'] ?? null;
unset($_SESSION['call_for_proposal_form_error']);

$selectedEvaluatorIds = $_SESSION['call_for_proposal_form_evaluator_ids'] ?? [];
unset($_SESSION['call_for_proposal_form_evaluator_ids']);
$selectedEvaluatorIds = array_values(array_unique(array_map('intval', is_array($selectedEvaluatorIds) ? $selectedEvaluatorIds : [])));

$evaluatorsStmt = $pdo->query(
    "SELECT ev.user_id, CONCAT(u.last_name, ' ', u.first_name) AS full_name "
    . "FROM evaluator ev "
    . "JOIN user u ON u.id = ev.user_id "
    . "ORDER BY u.last_name, u.first_name"
);
$evaluators = $evaluatorsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Aggiungi Bando</title>
</head>
<body class="management-page management-page--scroll">
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Aggiungi Bando</h2>
        <?php if ($errorMessage !== null): ?>
            <div class="flash-message" style="margin-bottom:1.5rem;padding:1rem;border-radius:8px;background-color:#fdecea;color:#611a15;">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        <form class="contact-form" action="call_for_proposal_add_handler.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label required" for="title">Titolo</label>
                <input type="text" id="title" name="title" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label required" for="description">Descrizione</label>
                <textarea id="description" name="description" class="form-input" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label required" for="pdf">PDF del Bando</label>
                <input type="file" id="pdf" name="pdf" class="form-input" accept="application/pdf" required>
            </div>
            <div class="form-group">
                <label class="form-label required" for="start_date">Data Inizio</label>
                <input type="date" id="start_date" name="start_date" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label required" for="end_date">Data Fine</label>
                <input type="date" id="end_date" name="end_date" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label required">Valutatori abilitati</label>
                <?php if (empty($evaluators)): ?>
                    <p class="text-muted">Nessun valutatore disponibile. Aggiungi almeno un valutatore prima di creare il bando.</p>
                <?php else: ?>
                    <div class="evaluator-selector" data-evaluator-selector>
                        <div class="evaluator-selector__toolbar">
                            <input
                                type="text"
                                class="form-input evaluator-selector__search"
                                placeholder="Cerca valutatore..."
                                data-role="search"
                                aria-label="Cerca valutatore"
                            >
                            <div class="evaluator-selector__actions">
                                <button type="button" class="page-button secondary-button evaluator-selector__action" data-role="select-visible">Seleziona visibili</button>
                                <button type="button" class="page-button secondary-button evaluator-selector__action" data-role="clear-visible">Deseleziona visibili</button>
                            </div>
                            <p class="form-note evaluator-selector__stats">
                                Mostrati: <span data-role="visible-count">0</span> - Selezionati: <span data-role="selected-count">0</span>
                            </p>
                        </div>
                        <div class="evaluator-selector__grid">
                            <?php foreach ($evaluators as $evaluator): ?>
                                <?php $evaluatorUserId = (int) $evaluator['user_id']; ?>
                                <label class="evaluator-option" data-role="option">
                                    <input
                                        type="checkbox"
                                        name="evaluator_user_ids[]"
                                        value="<?php echo htmlspecialchars((string) $evaluatorUserId); ?>"
                                        class="evaluator-option__checkbox"
                                        <?php echo in_array($evaluatorUserId, $selectedEvaluatorIds, true) ? 'checked' : ''; ?>
                                    >
                                    <span class="evaluator-option__name"><?php echo htmlspecialchars($evaluator['full_name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="button-container">
                <a href="call_for_proposals.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Aggiungi</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectors = document.querySelectorAll('[data-evaluator-selector]');

        selectors.forEach(selector => {
            const searchInput = selector.querySelector('[data-role="search"]');
            const optionElements = Array.from(selector.querySelectorAll('[data-role="option"]'));
            const visibleCountElement = selector.querySelector('[data-role="visible-count"]');
            const selectedCountElement = selector.querySelector('[data-role="selected-count"]');
            const selectVisibleButton = selector.querySelector('[data-role="select-visible"]');
            const clearVisibleButton = selector.querySelector('[data-role="clear-visible"]');

            const getVisibleOptions = () => optionElements.filter(option => !option.hidden);

            const refreshState = () => {
                const visibleOptions = getVisibleOptions();
                const selectedCount = optionElements.filter(option => {
                    const checkbox = option.querySelector('.evaluator-option__checkbox');
                    return checkbox && checkbox.checked;
                }).length;

                optionElements.forEach(option => {
                    const checkbox = option.querySelector('.evaluator-option__checkbox');
                    option.classList.toggle('is-selected', Boolean(checkbox && checkbox.checked));
                });

                if (visibleCountElement) {
                    visibleCountElement.textContent = String(visibleOptions.length);
                }
                if (selectedCountElement) {
                    selectedCountElement.textContent = String(selectedCount);
                }
            };

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const term = searchInput.value.trim().toLowerCase();
                    optionElements.forEach(option => {
                        const optionText = (option.textContent || '').toLowerCase();
                        option.hidden = term !== '' && !optionText.includes(term);
                    });
                    refreshState();
                });
            }

            optionElements.forEach(option => {
                const checkbox = option.querySelector('.evaluator-option__checkbox');
                if (checkbox) {
                    checkbox.addEventListener('change', refreshState);
                }
            });

            if (selectVisibleButton) {
                selectVisibleButton.addEventListener('click', () => {
                    getVisibleOptions().forEach(option => {
                        const checkbox = option.querySelector('.evaluator-option__checkbox');
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                    refreshState();
                });
            }

            if (clearVisibleButton) {
                clearVisibleButton.addEventListener('click', () => {
                    getVisibleOptions().forEach(option => {
                        const checkbox = option.querySelector('.evaluator-option__checkbox');
                        if (checkbox) {
                            checkbox.checked = false;
                        }
                    });
                    refreshState();
                });
            }

            refreshState();
        });
    });
</script>
</body>
</html>
