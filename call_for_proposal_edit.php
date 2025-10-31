<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_CREATE'])) {
    header('Location: index.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: call_for_proposals.php');
    exit();
}

$stmt = $pdo->prepare('SELECT * FROM call_for_proposal WHERE id = :id');
$stmt->execute([':id' => $id]);
$proposal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proposal) {
    header('Location: call_for_proposals.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Modifica Bando</title>
</head>
<body class="management-page">
<?php include 'header.php'; ?>
<main>
    <div class="contact-form-container">
        <h2>Modifica Bando</h2>
        <form class="contact-form" action="call_for_proposal_edit_handler.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($proposal['id']); ?>">
            <div class="form-group">
                <label class="form-label required" for="title">Titolo</label>
                <input type="text" id="title" name="title" class="form-input" required value="<?php echo htmlspecialchars($proposal['title']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label required" for="description">Descrizione</label>
                <textarea id="description" name="description" class="form-input" required><?php echo htmlspecialchars($proposal['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label required" for="start_date">Data inizio</label>
                <input type="date" id="start_date" name="start_date" class="form-input" required value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($proposal['start_date']))); ?>">
            </div>
            <div class="form-group">
                <label class="form-label required" for="end_date">Data fine</label>
                <input type="date" id="end_date" name="end_date" class="form-input" required value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($proposal['end_date']))); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="pdf">PDF (opzionale)</label>
                <input type="file" id="pdf" name="pdf" class="form-input" accept="application/pdf">
            </div>
            <div class="button-container">
                <a href="call_for_proposals.php" class="page-button" style="background-color: #007bff;">Indietro</a>
                <button type="submit" class="page-button">Aggiorna</button>
            </div>
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>