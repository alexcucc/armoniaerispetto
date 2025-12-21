<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_UPDATE'])) {
    header('Location: index.php');
    exit();
}

$appId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$appId) {
    header('Location: applications.php');
    exit();
}

$stmt = $pdo->prepare(
    'SELECT a.call_for_proposal_id, a.organization_id, a.supervisor_id, a.project_name, '
    . 'a.application_pdf_path, a.checklist_path, '
    . 'c.title AS call_title, o.name AS organization_name '
    . 'FROM application a '
    . 'JOIN call_for_proposal c ON a.call_for_proposal_id = c.id '
    . 'JOIN organization o ON a.organization_id = o.id '
    . 'WHERE a.id = :id'
);
$stmt->execute([':id' => $appId]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$application) {
    header('Location: applications.php');
    exit();
}

$supStmt = $pdo->prepare('SELECT s.id, u.first_name, u.last_name FROM supervisor s JOIN user u ON s.user_id = u.id ORDER BY u.first_name, u.last_name');
$supStmt->execute();
$supervisors = $supStmt->fetchAll(PDO::FETCH_ASSOC);

$currentSupervisorName = null;
foreach ($supervisors as $sup) {
    if ((int) $sup['id'] === (int) $application['supervisor_id']) {
        $currentSupervisorName = $sup['first_name'] . ' ' . $sup['last_name'];
        break;
    }
}

if ($currentSupervisorName === null) {
    $currentSupervisorName = 'Convalidatore #' . (string) $application['supervisor_id'];
}

$canChangeSupervisor = empty($application['checklist_path']);

$currentPdfName = null;
if (!empty($application['application_pdf_path'])) {
    $currentPdfName = basename($application['application_pdf_path']);
}

$errorMessage = $_SESSION['error_message'] ?? null;
$formData = $_SESSION['form_data'] ?? [];

unset($_SESSION['error_message'], $_SESSION['form_data']);

if (!empty($formData)) {
    if ($canChangeSupervisor && isset($formData['supervisor_id'])) {
        $application['supervisor_id'] = (int) $formData['supervisor_id'];
    }
    if (isset($formData['project_name'])) {
        $application['project_name'] = $formData['project_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Modifica Risposta al Bando</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Modifica Risposta al Bando</h2>
        <?php if (!empty($errorMessage)): ?>
        <div class="message error">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
        <?php endif; ?>
        <form class="contact-form" action="application_edit_handler.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($appId); ?>">
            <div class="form-group">
                <label class="form-label required" for="call_id">Bando</label>
                <select id="call_id" class="form-input" disabled>
                    <option selected><?php echo htmlspecialchars($application['call_title']); ?></option>
                </select>
                <input type="hidden" name="call_id" value="<?php echo htmlspecialchars($application['call_for_proposal_id']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label required" for="organization_id">Ente</label>
                <select id="organization_id" class="form-input" disabled>
                    <option selected><?php echo htmlspecialchars($application['organization_name']); ?></option>
                </select>
                <input type="hidden" name="organization_id" value="<?php echo htmlspecialchars($application['organization_id']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label required" for="supervisor_id">Convalidatore</label>
                <?php if ($canChangeSupervisor): ?>
                <select id="supervisor_id" name="supervisor_id" class="form-input" required>
                    <?php foreach ($supervisors as $sup): ?>
                    <option value="<?php echo $sup['id']; ?>" <?php if ($sup['id'] == $application['supervisor_id']) echo 'selected'; ?>><?php echo htmlspecialchars($sup['first_name'] . ' ' . $sup['last_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <select id="supervisor_id" class="form-input" disabled>
                    <option selected><?php echo htmlspecialchars($currentSupervisorName); ?></option>
                </select>
                <input type="hidden" name="supervisor_id" value="<?php echo htmlspecialchars($application['supervisor_id']); ?>">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label required" for="project_name">Nome del Progetto</label>
                <input type="text" id="project_name" name="project_name" class="form-input" value="<?php echo htmlspecialchars($application['project_name']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="current_application_pdf">PDF attuale della risposta al bando</label>
                <?php if (!empty($application['application_pdf_path'])): ?>
                <p id="current_application_pdf">
                    <a href="application_download.php?id=<?php echo htmlspecialchars($appId); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($currentPdfName ?? 'Scarica il PDF attuale'); ?></a>
                </p>
                <?php else: ?>
                <p id="current_application_pdf">Nessun PDF caricato.</p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="application_pdf">Sostituisci PDF della risposta al bando</label>
                <input type="file" id="application_pdf" name="application_pdf" class="form-input" accept="application/pdf">
                <small>Carica un nuovo file solo se desideri sostituire il PDF attuale.</small>
            </div>
            <div class="button-container">
                <a href="applications.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Salva</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
