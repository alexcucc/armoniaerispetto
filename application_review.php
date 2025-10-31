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

$stmt = $pdo->prepare('SELECT a.project_name, a.project_description, c.title AS call_title, o.name AS organization_name, a.supervisor_id, s.user_id AS supervisor_user_id FROM application a JOIN call_for_proposal c ON a.call_for_proposal_id = c.id JOIN organization o ON a.organization_id = o.id JOIN supervisor s ON a.supervisor_id = s.id WHERE a.id = :id AND a.status = "SUBMITTED"');
$stmt->execute([':id' => $appId]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application || $application['supervisor_user_id'] != $_SESSION['user_id']) {
    header('Location: applications.php');
    exit();
}
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
        <h2>Convalida Risposta al Bando</h2>
        <div class="form-group">
            <p><strong>Bando:</strong> <?php echo htmlspecialchars($application['call_title']); ?></p>
            <p><strong>Ente:</strong> <?php echo htmlspecialchars($application['organization_name']); ?></p>
            <p><strong>Nome del Progetto:</strong> <?php echo htmlspecialchars($application['project_name']); ?></p>
            <p><strong>Descrizione del Progetto:</strong> <?php echo nl2br(htmlspecialchars($application['project_description'])); ?></p>
        </div>
        <form class="contact-form" action="application_review_handler.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label required">Esito</label>
                <div>
                    <label><input type="radio" name="decision" value="APPROVED" required> Approva</label>
                    <label><input type="radio" name="decision" value="REJECTED" required> Respingi</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label required" for="checklist">checklist.pdf</label>
                <input type="file" id="checklist" name="checklist" class="form-input" accept="application/pdf" required>
            </div>
            <input type="hidden" name="supervisor_id" value="<?php echo htmlspecialchars($application['supervisor_id']); ?>">
            <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($appId); ?>">
            <div class="button-container">
                <a href="applications.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Invia</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
