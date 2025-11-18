<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_REVIEW'])) {
    header('Location: index.php');
    exit();
}

$appId = filter_input(INPUT_GET, 'application_id', FILTER_VALIDATE_INT);
if (!$appId) {
    header('Location: applications.php');
    exit();
}

$stmt = $pdo->prepare(
    'SELECT a.project_name, a.status, a.checklist_path, '
    . 'c.title AS call_title, o.name AS organization_name, a.supervisor_id, '
    . 's.user_id AS supervisor_user_id '
    . 'FROM application a '
    . 'JOIN call_for_proposal c ON a.call_for_proposal_id = c.id '
    . 'JOIN organization o ON a.organization_id = o.id '
    . 'JOIN supervisor s ON a.supervisor_id = s.id '
    . 'WHERE a.id = :id'
);
$stmt->execute([':id' => $appId]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application || $application['supervisor_user_id'] != $_SESSION['user_id']) {
    header('Location: applications.php');
    exit();
}

$editableStatuses = ['SUBMITTED', 'APPROVED', 'REJECTED'];
if (!in_array($application['status'], $editableStatuses, true)) {
    header('Location: applications.php');
    exit();
}

$isEditing = $application['status'] !== 'SUBMITTED';
$currentDecision = $isEditing ? $application['status'] : null;
$hasExistingChecklist = !empty($application['checklist_path']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Convalida Risposta al Bando</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2><?php echo $isEditing ? 'Modifica convalida' : 'Convalida Risposta al Bando'; ?></h2>
        <div class="form-group">
            <p><strong>Bando:</strong> <?php echo htmlspecialchars($application['call_title']); ?></p>
            <p><strong>Ente:</strong> <?php echo htmlspecialchars($application['organization_name']); ?></p>
            <p><strong>Nome del Progetto:</strong> <?php echo htmlspecialchars($application['project_name']); ?></p>
        </div>
        <form class="contact-form" action="application_review_handler.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label required">Esito</label>
                <div>
                    <label><input type="radio" name="decision" value="APPROVED" <?php echo $currentDecision === 'APPROVED' ? 'checked' : ''; ?> required> Approva</label>
                    <label><input type="radio" name="decision" value="REJECTED" <?php echo $currentDecision === 'REJECTED' ? 'checked' : ''; ?> required> Respingi</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label<?php echo $isEditing ? '' : ' required'; ?>" for="checklist">checklist.pdf</label>
                <input
                    type="file"
                    id="checklist"
                    name="checklist"
                    class="form-input"
                    accept="application/pdf"
                    <?php echo $isEditing && $hasExistingChecklist ? '' : 'required'; ?>
                >
                <?php if ($isEditing && $hasExistingChecklist): ?>
                    <p class="form-help">Lascia vuoto per mantenere il file esistente.</p>
                    <p class="form-help">
                        File attuale:
                        <a href="application_checklist_download.php?id=<?php echo $appId; ?>">Scarica checklist</a>
                    </p>
                <?php endif; ?>
            </div>
            <input type="hidden" name="supervisor_id" value="<?php echo htmlspecialchars($application['supervisor_id']); ?>">
            <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($appId); ?>">
            <div class="button-container">
                <a href="supervisor_applications.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Invia</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
