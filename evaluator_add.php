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
    "ORDER BY u.first_name ASC, u.last_name ASC"
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
            <div class="form-group">
                <label class="form-label required" for="user_id">Utente</label>
                <select id="user_id" name="user_id" class="form-input" required>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="button-container">
                <a href="evaluators.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Aggiungi</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>