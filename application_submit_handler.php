<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_CREATE'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: applications.php');
    exit();
}

$callId = filter_input(INPUT_POST, 'call_id', FILTER_VALIDATE_INT);
$organizationId = filter_input(INPUT_POST, 'organization_id', FILTER_VALIDATE_INT);
$supervisorId = filter_input(INPUT_POST, 'supervisor_id', FILTER_VALIDATE_INT);
$projectName = trim(filter_input(INPUT_POST, 'project_name', FILTER_UNSAFE_RAW));

if (!$callId || !$organizationId || !$supervisorId || !$projectName) {
    header('Location: applications.php');
    exit();
}

$callStatusStmt = $pdo->prepare('SELECT status FROM call_for_proposal WHERE id = :call_id');
$callStatusStmt->execute([':call_id' => $callId]);
$callStatus = $callStatusStmt->fetchColumn();

if ($callStatus === false || $callStatus === 'CLOSED') {
    $_SESSION['error_message'] = 'Il bando selezionato è chiuso e non accetta nuove risposte.';
    $_SESSION['form_data'] = [
        'call_id' => $callId,
        'organization_id' => $organizationId,
        'supervisor_id' => $supervisorId,
        'project_name' => $projectName,
    ];
    header('Location: application_submit.php');
    exit();
}

$duplicateCheckStmt = $pdo->prepare('SELECT COUNT(*) FROM application WHERE call_for_proposal_id = :call_id AND organization_id = :org_id');
$duplicateCheckStmt->execute([
    ':call_id' => $callId,
    ':org_id' => $organizationId
]);

if ($duplicateCheckStmt->fetchColumn() > 0) {
    $_SESSION['error_message'] = 'Esiste già una risposta al bando per questo ente.';
    $_SESSION['form_data'] = [
        'call_id' => $callId,
        'organization_id' => $organizationId,
        'supervisor_id' => $supervisorId,
        'project_name' => $projectName,
    ];
    header('Location: application_submit.php?call_id=' . urlencode($callId));
    exit();
}

$pdfUploaded = isset($_FILES['application_pdf']) && $_FILES['application_pdf']['error'] !== UPLOAD_ERR_NO_FILE;

if (!$pdfUploaded) {
    header('Location: applications.php');
    exit();
}

if ($_FILES['application_pdf']['error'] !== UPLOAD_ERR_OK) {
    header('Location: applications.php');
    exit();
}

$pdfTmpPath = $_FILES['application_pdf']['tmp_name'];
$pdfName = $_FILES['application_pdf']['name'];
$pdfExtension = strtolower(pathinfo($pdfName, PATHINFO_EXTENSION));

if ($pdfExtension !== 'pdf') {
    header('Location: applications.php');
    exit();
}

try {
    $pdo->beginTransaction();
    $insertStmt = $pdo->prepare('INSERT INTO application (call_for_proposal_id, organization_id, supervisor_id, project_name, status) VALUES (:call_id, :org_id, :sup_id, :name, "SUBMITTED")');
    $insertStmt->execute([
        'call_id' => $callId,
        'org_id' => $organizationId,
        'sup_id' => $supervisorId,
        'name' => $projectName
    ]);
$applicationId = $pdo->lastInsertId();

    $destinationDir = 'private/documents/applications/' . $applicationId;
    if (!is_dir($destinationDir)) {
        if (!mkdir($destinationDir, 0755, true)) {
            throw new RuntimeException('Unable to create application directory');
        }
    }

    $destinationPath = $destinationDir . '/domanda.pdf';

    if (!move_uploaded_file($pdfTmpPath, $destinationPath)) {
        throw new RuntimeException('Unable to move uploaded file');
    }

    $updateStmt = $pdo->prepare('UPDATE application SET application_pdf_path = :pdf_path WHERE id = :id');
    $updateStmt->execute([
        ':pdf_path' => $destinationPath,
        ':id' => $applicationId
    ]);

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: applications.php');
    exit();
}

header('Location: applications.php');
exit();
