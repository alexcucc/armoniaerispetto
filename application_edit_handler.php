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
$callId = filter_input(INPUT_POST, 'call_id', FILTER_VALIDATE_INT);
$organizationId = filter_input(INPUT_POST, 'organization_id', FILTER_VALIDATE_INT);
$supervisorId = filter_input(INPUT_POST, 'supervisor_id', FILTER_VALIDATE_INT);
$projectName = trim(filter_input(INPUT_POST, 'project_name', FILTER_UNSAFE_RAW));
$projectDescription = trim(filter_input(INPUT_POST, 'project_description', FILTER_UNSAFE_RAW));

if (!$id || !$callId || !$organizationId || !$supervisorId || !$projectName || !$projectDescription) {
    header('Location: application_edit.php?id=' . urlencode($id));
    exit();
}

$userId = $_SESSION['user_id'];

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

    $query = 'UPDATE application SET call_for_proposal_id = :call_id, organization_id = :org_id, supervisor_id = :sup_id, project_name = :name, project_description = :description';
    $params = [
        ':call_id' => $callId,
        ':org_id' => $organizationId,
        ':sup_id' => $supervisorId,
        ':name' => $projectName,
        ':description' => $projectDescription,
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
