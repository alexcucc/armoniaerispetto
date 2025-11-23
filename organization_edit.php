<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_UPDATE'])) {
    header('Location: index.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: organizations.php');
    exit();
}

$stmt = $pdo->prepare('SELECT id, name, type, incorporation_year, location FROM organization WHERE id = :id');
$stmt->execute([':id' => $id]);
$org = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$org) {
    header('Location: organizations.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Modifica Ente</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Modifica Ente</h2>
        <form class="contact-form" action="organization_edit_handler.php" method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($org['id']); ?>">
            <div class="form-group">
                <label class="form-label" for="name">Denominazione</label>
                <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($org['name']); ?>" readonly>
            </div>
            <div class="form-group">
                <label class="form-label required" for="type">Tipo</label>
                <input type="text" id="type" name="type" class="form-input" required value="<?php echo htmlspecialchars($org['type']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="incorporation_year">Anno di costituzione</label>
                <input type="number" id="incorporation_year" name="incorporation_year" class="form-input" value="<?php echo htmlspecialchars($org['incorporation_year']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="location">Località</label>
                <input type="text" id="location" name="location" class="form-input" value="<?php echo htmlspecialchars($org['location']); ?>">
            </div>
            <div class="button-container">
                <a href="organizations.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Aggiorna</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>