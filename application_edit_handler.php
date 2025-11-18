<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_UPDATE'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: applications.php');
    exit();
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$supervisorIdInput = filter_input(INPUT_POST, 'supervisor_id', FILTER_VALIDATE_INT);
$projectName = trim(filter_input(INPUT_POST, 'project_name', FILTER_UNSAFE_RAW));

if (!$id || !$supervisorIdInput || !$projectName) {
    header('Location: application_edit.php?id=' . urlencode((string) $id));
    exit();
}

$applicationStmt = $pdo->prepare('SELECT call_for_proposal_id, organization_id, supervisor_id, checklist_path FROM application WHERE id = :id');
$applicationStmt->execute([':id' => $id]);
$existingApplication = $applicationStmt->fetch(PDO::FETCH_ASSOC);

if (!$existingApplication) {
    header('Location: applications.php');
    exit();
}

$callId = (int) $existingApplication['call_for_proposal_id'];
$organizationId = (int) $existingApplication['organization_id'];
$existingSupervisorId = (int) $existingApplication['supervisor_id'];
$canChangeSupervisor = empty($existingApplication['checklist_path']);
$supervisorId = (int) $supervisorIdInput;

if (!$canChangeSupervisor && $supervisorId !== $existingSupervisorId) {
    $_SESSION['error_message'] = 'Non è possibile modificare il convalidatore dopo il caricamento della convalida.';
    $_SESSION['form_data'] = [
        'project_name' => $projectName
    ];
    header('Location: application_edit.php?id=' . urlencode((string) $id));
    exit();
}

if (!$canChangeSupervisor) {
    $supervisorId = $existingSupervisorId;
}

$duplicateCheckStmt = $pdo->prepare('SELECT COUNT(*) FROM application WHERE call_for_proposal_id = :call_id AND organization_id = :org_id AND id <> :id');
$duplicateCheckStmt->execute([
    ':call_id' => $callId,
    ':org_id' => $organizationId,
    ':id' => $id
]);

if ($duplicateCheckStmt->fetchColumn() > 0) {
    $_SESSION['error_message'] = 'Esiste già una risposta al bando per questo ente.';
    $_SESSION['form_data'] = [
        'supervisor_id' => $supervisorId,
        'project_name' => $projectName
    ];
    header('Location: application_edit.php?id=' . urlencode($id));
    exit();
}

$pdfUploaded = isset($_FILES['application_pdf']) && $_FILES['application_pdf']['error'] !== UPLOAD_ERR_NO_FILE;

if ($pdfUploaded && $_FILES['application_pdf']['error'] !== UPLOAD_ERR_OK) {
    header('Location: application_edit.php?id=' . urlencode($id));
    exit();
}

$destinationPath = null;
$pdfTmpPath = null;
if ($pdfUploaded) {
    $pdfTmpPath = $_FILES['application_pdf']['tmp_name'];
    $pdfName = $_FILES['application_pdf']['name'];
    $pdfExtension = strtolower(pathinfo($pdfName, PATHINFO_EXTENSION));

    if ($pdfExtension !== 'pdf') {
        header('Location: application_edit.php?id=' . urlencode($id));
        exit();
    }

    $destinationDir = 'private/documents/applications/' . $id;
    $destinationPath = $destinationDir . '/domanda.pdf';
}

try {
    $pdo->beginTransaction();

    if ($pdfUploaded) {
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true)) {
                throw new RuntimeException('Unable to create application directory');
            }
        }

        if (file_exists($destinationPath) && !unlink($destinationPath)) {
            throw new RuntimeException('Unable to replace existing PDF');
        }

        if (!move_uploaded_file($pdfTmpPath, $destinationPath)) {
            throw new RuntimeException('Unable to move uploaded file');
        }
    }

    $query = 'UPDATE application SET call_for_proposal_id = :call_id, organization_id = :org_id, supervisor_id = :sup_id, project_name = :name';
    $params = [
        ':call_id' => $callId,
        ':org_id' => $organizationId,
        ':sup_id' => $supervisorId,
        ':name' => $projectName,
        ':id' => $id
    ];

    if ($pdfUploaded) {
        $query .= ', application_pdf_path = :pdf_path';
        $params[':pdf_path'] = $destinationPath;
    }

    $query .= ' WHERE id = :id';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: application_edit.php?id=' . urlencode($id));
    exit();
}

header('Location: applications.php');
exit();
