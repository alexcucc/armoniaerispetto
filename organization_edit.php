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

$stmt = $pdo->prepare('SELECT id, name, type_id, incorporation_year, location FROM organization WHERE id = :id');
$stmt->execute([':id' => $id]);
$org = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$org) {
    header('Location: organizations.php');
    exit();
}

$errorMessage = $_SESSION['error_message'] ?? null;
$formData = $_SESSION['form_data'] ?? [];

unset($_SESSION['error_message'], $_SESSION['form_data']);

$typeValue = $formData['type_id'] ?? $org['type_id'];
$locationValue = $formData['location'] ?? $org['location'];

$incorporationFormValue = $formData['incorporation_year'] ?? null;
if ($incorporationFormValue !== null && $incorporationFormValue !== '') {
    $incorporationYearValue = $incorporationFormValue;
} else {
$incorporationYearValue = $org['incorporation_year'] ?? '';
}

$typesStmt = $pdo->query('SELECT id, name FROM organization_type ORDER BY name');
$organizationTypes = $typesStmt->fetchAll(PDO::FETCH_ASSOC);
$hasOrganizationTypes = !empty($organizationTypes);
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
        <?php if (!empty($errorMessage)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        <form class="contact-form" action="organization_edit_handler.php" method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($org['id']); ?>">
            <div class="form-group">
                <label class="form-label" for="name">Denominazione</label>
                <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($org['name']); ?>" readonly>
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
                    <small class="form-help-text">Aggiungi almeno una tipologia di ente prima di modificare un ente.</small>
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
                <button type="submit" class="page-button" <?php echo $hasOrganizationTypes ? '' : 'disabled'; ?>>Aggiorna</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
