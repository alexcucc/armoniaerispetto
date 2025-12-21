<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_TYPE_MANAGE'])) {
    header('Location: index.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: organization_types.php');
    exit();
}

$typeStmt = $pdo->prepare('SELECT id, name FROM organization_type WHERE id = :id');
$typeStmt->execute([':id' => $id]);
$organizationType = $typeStmt->fetch(PDO::FETCH_ASSOC);

if (!$organizationType) {
    header('Location: organization_types.php');
    exit();
}

$errorMessage = $_SESSION['error_message'] ?? null;
$formData = $_SESSION['form_data'] ?? [];

unset($_SESSION['error_message'], $_SESSION['form_data']);

$nameValue = $formData['name'] ?? $organizationType['name'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Modifica Tipologia di Ente</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Modifica Tipologia di Ente</h2>
        <?php if (!empty($errorMessage)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        <form class="contact-form" action="organization_type_save.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?php echo (int) $organizationType['id']; ?>">
            <div class="form-group">
                <label class="form-label required" for="name">Nome</label>
                <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($nameValue); ?>" required maxlength="255" autofocus>
            </div>
            <div class="button-container">
                <a href="organization_types.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Aggiorna</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
