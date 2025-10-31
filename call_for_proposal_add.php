<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_CREATE'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Aggiungi Bando</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Aggiungi Bando</h2>
        <form class="contact-form" action="call_for_proposal_add_handler.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label required" for="title">Titolo</label>
                <input type="text" id="title" name="title" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label required" for="description">Descrizione</label>
                <textarea id="description" name="description" class="form-input" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label required" for="pdf">PDF del Bando</label>
                <input type="file" id="pdf" name="pdf" class="form-input" accept="application/pdf" required>
            </div>
            <div class="form-group">
                <label class="form-label required" for="start_date">Data Inizio</label>
                <input type="date" id="start_date" name="start_date" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label required" for="end_date">Data Fine</label>
                <input type="date" id="end_date" name="end_date" class="form-input" required>
            </div>
            <div class="button-container">
                <a href="call_for_proposals.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Aggiungi</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>