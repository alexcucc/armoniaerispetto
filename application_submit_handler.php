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

function validateUploadedPdf(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Invalid uploaded file');
    }

    $tmpPath = $file['tmp_name'] ?? '';
    $originalName = basename((string) ($file['name'] ?? ''));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($tmpPath === '' || $originalName === '' || $extension !== 'pdf') {
        throw new RuntimeException('Invalid PDF file');
    }

    return [
        'tmp_path' => $tmpPath,
        'original_name' => $originalName
    ];
}

function buildUploadDestinationPath(string $destinationDir, string $label, string $originalName): string
{
    $fileNameWithoutExtension = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) pathinfo($originalName, PATHINFO_FILENAME));
    if ($fileNameWithoutExtension === null || $fileNameWithoutExtension === '') {
        $fileNameWithoutExtension = 'documento';
    }

    return $destinationDir . '/' . $label . '_' . $fileNameWithoutExtension . '.pdf';
}

$applicationPdfUploaded = isset($_FILES['application_pdf']) && $_FILES['application_pdf']['error'] !== UPLOAD_ERR_NO_FILE;
$budgetPdfUploaded = isset($_FILES['budget_pdf']) && $_FILES['budget_pdf']['error'] !== UPLOAD_ERR_NO_FILE;

if (!$applicationPdfUploaded || !$budgetPdfUploaded) {
    header('Location: applications.php');
    exit();
}

if ($_FILES['application_pdf']['error'] !== UPLOAD_ERR_OK || $_FILES['budget_pdf']['error'] !== UPLOAD_ERR_OK) {
    header('Location: applications.php');
    exit();
}

try {
    $applicationPdf = validateUploadedPdf($_FILES['application_pdf']);
    $budgetPdf = validateUploadedPdf($_FILES['budget_pdf']);
} catch (RuntimeException $e) {
    header('Location: applications.php');
    exit();
}

$movedFiles = [];
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

    $applicationPdfPath = buildUploadDestinationPath($destinationDir, 'risposta', $applicationPdf['original_name']);
    $budgetPdfPath = buildUploadDestinationPath($destinationDir, 'budget', $budgetPdf['original_name']);

    if (!move_uploaded_file($applicationPdf['tmp_path'], $applicationPdfPath)) {
        throw new RuntimeException('Unable to move uploaded application PDF');
    }
    $movedFiles[] = $applicationPdfPath;

    if (!move_uploaded_file($budgetPdf['tmp_path'], $budgetPdfPath)) {
        throw new RuntimeException('Unable to move uploaded budget PDF');
    }
    $movedFiles[] = $budgetPdfPath;

    $updateStmt = $pdo->prepare('UPDATE application SET application_pdf_path = :application_pdf_path, budget_pdf_path = :budget_pdf_path WHERE id = :id');
    $updateStmt->execute([
        ':application_pdf_path' => $applicationPdfPath,
        ':budget_pdf_path' => $budgetPdfPath,
        ':id' => $applicationId
    ]);

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    foreach ($movedFiles as $movedFile) {
        if (is_file($movedFile)) {
            unlink($movedFile);
        }
    }
    header('Location: applications.php');
    exit();
}

header('Location: applications.php');
exit();
