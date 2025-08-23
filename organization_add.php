<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_CREATE'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Aggiungi Ente</title>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Aggiungi Ente</h2>
        <form class="contact-form" action="organization_add_handler.php" method="POST">
            <div class="form-group">
                <label class="form-label required" for="name">Nome</label>
                <input type="text" id="name" name="name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label required" for="type">Tipo</label>
                <input type="text" id="type" name="type" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="incorporation_date">Data di costituzione</label>
                <input type="date" id="incorporation_date" name="incorporation_date" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="full_address">Indirizzo completo</label>
                <input type="text" id="full_address" name="full_address" class="form-input">
            </div>
            <div class="form-group">
                <button type="submit" class="page-button">Aggiungi</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
