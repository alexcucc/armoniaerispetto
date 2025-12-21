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
                <label class="form-label required" for="user-search">Utente</label>
                <select
                    id="user-search"
                    class="form-input"
                    name="user_id"
                    aria-label="Seleziona un utente"
                    autofocus
                    required
                >
                    <option value="" disabled selected hidden>Seleziona un utente</option>
                    <?php foreach ($users as $user): ?>
                        <?php $displayName = htmlspecialchars($user['last_name'] . ' ' . $user['first_name'] . ' (' . $user['email'] . ')'); ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo $displayName; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
<script>
    (function() {
        const select = document.getElementById('user-search');
        const options = Array.from(select.options).filter((option) => option.value);

        select.addEventListener('keydown', (event) => {
            if (event.key.length !== 1) return;

            const typed = event.key.toLowerCase();
            const match = options.find((option) => option.text.toLowerCase().startsWith(typed));

            if (match) {
                event.preventDefault();
                select.value = match.value;
            }
        });
    })();
</script>
</body>
</html>
