<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_CREATE'])) {
    header('Location: index.php');
    exit();
}

$errorMessage = $_SESSION['error_message'] ?? null;
$formData = $_SESSION['form_data'] ?? [];

unset($_SESSION['error_message'], $_SESSION['form_data']);

$nameValue = $formData['name'] ?? '';
$typeValue = $formData['type_id'] ?? '';
$incorporationYearValue = $formData['incorporation_year'] ?? '';
$locationValue = $formData['location'] ?? '';

$typesStmt = $pdo->query('SELECT id, name FROM organization_type ORDER BY name');
$organizationTypes = $typesStmt->fetchAll(PDO::FETCH_ASSOC);
$hasOrganizationTypes = !empty($organizationTypes);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Aggiungi Ente</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Aggiungi Ente</h2>
        <?php if (!empty($errorMessage)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        <form class="contact-form" action="organization_add_handler.php" method="POST">
            <div class="form-group">
                <label class="form-label required" for="name">Denominazione</label>
                <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($nameValue); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label required" for="type_id">Tipo</label>
                <select id="type_id" name="type_id" class="form-input" required <?php echo $hasOrganizationTypes ? '' : 'disabled'; ?>>
                    <option value="">Seleziona una tipologia</option>
                    <?php foreach ($organizationTypes as $type): ?>
                        <option value="<?php echo (int) $type['id']; ?>" <?php echo ((string) $typeValue === (string) $type['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$hasOrganizationTypes): ?>
                    <small class="form-help-text">Aggiungi almeno una tipologia di ente prima di creare un nuovo ente.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label required" for="incorporation_year">Anno di costituzione</label>
                <input type="number" id="incorporation_year" name="incorporation_year" class="form-input" value="<?php echo htmlspecialchars($incorporationYearValue); ?>" min="1901" max="<?php echo date('Y'); ?>" step="1" required>
            </div>
            <div class="form-group">
                <label class="form-label required" for="location">Località</label>
                <input type="text" id="location" name="location" class="form-input" value="<?php echo htmlspecialchars($locationValue); ?>" required>
            </div>
            <div class="button-container">
                <a href="organizations.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button" <?php echo $hasOrganizationTypes ? '' : 'disabled'; ?>>Aggiungi</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
