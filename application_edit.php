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

$stmt = $pdo->prepare('SELECT call_for_proposal_id, organization_id, supervisor_id, project_name, project_description, application_pdf_path FROM application WHERE id = :id');
$stmt->execute([':id' => $appId]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$application) {
    header('Location: applications.php');
    exit();
}

$stmtCalls = $pdo->prepare('SELECT id, title FROM call_for_proposal');
$stmtCalls->execute();
$availableCalls = $stmtCalls->fetchAll(PDO::FETCH_ASSOC);

$orgStmt = $pdo->prepare('SELECT id, name FROM organization ORDER BY name');
$orgStmt->execute();
$organizations = $orgStmt->fetchAll(PDO::FETCH_ASSOC);

$supStmt = $pdo->prepare('SELECT s.id, u.first_name, u.last_name FROM supervisor s JOIN user u ON s.user_id = u.id ORDER BY u.first_name, u.last_name');
$supStmt->execute();
$supervisors = $supStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Modifica Risposta al Bando</title>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Modifica Risposta al Bando</h2>
        <form class="contact-form" action="application_edit_handler.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($appId); ?>">
            <div class="form-group">
                <label class="form-label required" for="call_id">Bando</label>
                <select id="call_id" name="call_id" class="form-input" required>
                    <?php foreach ($availableCalls as $call): ?>
                    <option value="<?php echo $call['id']; ?>" <?php if ($call['id'] == $application['call_for_proposal_id']) echo 'selected'; ?>><?php echo htmlspecialchars($call['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required" for="organization_id">Ente</label>
                <select id="organization_id" name="organization_id" class="form-input" required>
                    <?php foreach ($organizations as $org): ?>
                    <option value="<?php echo $org['id']; ?>" <?php if ($org['id'] == $application['organization_id']) echo 'selected'; ?>><?php echo htmlspecialchars($org['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required" for="supervisor_id">Relatore</label>
                <select id="supervisor_id" name="supervisor_id" class="form-input" required>
                    <?php foreach ($supervisors as $sup): ?>
                    <option value="<?php echo $sup['id']; ?>" <?php if ($sup['id'] == $application['supervisor_id']) echo 'selected'; ?>><?php echo htmlspecialchars($sup['first_name'] . ' ' . $sup['last_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required" for="project_name">Nome del Progetto</label>
                <input type="text" id="project_name" name="project_name" class="form-input" value="<?php echo htmlspecialchars($application['project_name']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required" for="project_description">Descrizione del Progetto</label>
                <textarea id="project_description" name="project_description" class="form-input" required><?php echo htmlspecialchars($application['project_description']); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="current_application_pdf">PDF attuale della domanda</label>
                <?php if (!empty($application['application_pdf_path'])): ?>
                <p id="current_application_pdf">
                    <a href="application_download.php?id=<?php echo htmlspecialchars($appId); ?>" target="_blank" rel="noopener">Scarica il PDF attuale</a>
                </p>
                <?php else: ?>
                <p id="current_application_pdf">Nessun PDF caricato.</p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="application_pdf">Sostituisci PDF della domanda</label>
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
