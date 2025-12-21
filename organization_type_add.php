<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_TYPE_MANAGE'])) {
    header('Location: index.php');
    exit();
}

$errorMessage = $_SESSION['error_message'] ?? null;
$formData = $_SESSION['form_data'] ?? [];

unset($_SESSION['error_message'], $_SESSION['form_data']);

$nameValue = $formData['name'] ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Aggiungi Tipologia di Ente</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Aggiungi Tipologia di Ente</h2>
        <?php if (!empty($errorMessage)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        <form class="contact-form" action="organization_type_save.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label class="form-label required" for="name">Nome</label>
                <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($nameValue); ?>" required maxlength="255" autofocus>
            </div>
            <div class="button-container">
                <a href="organization_types.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Aggiungi</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
