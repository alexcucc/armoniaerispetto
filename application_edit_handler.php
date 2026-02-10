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

$applicationStmt = $pdo->prepare('SELECT call_for_proposal_id, organization_id, supervisor_id, checklist_path, application_pdf_path, budget_pdf_path FROM application WHERE id = :id');
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

function buildUploadDestinationPath(string $destinationDir, string $label, string $originalName, array $reservedPaths = []): string
{
    $fileNameWithoutExtension = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) pathinfo($originalName, PATHINFO_FILENAME));
    if ($fileNameWithoutExtension === null || $fileNameWithoutExtension === '') {
        $fileNameWithoutExtension = 'documento';
    }

    $candidatePath = $destinationDir . '/' . $label . '_' . $fileNameWithoutExtension . '.pdf';
    $counter = 1;
    while (in_array($candidatePath, $reservedPaths, true) || file_exists($candidatePath)) {
        $candidatePath = $destinationDir . '/' . $label . '_' . $fileNameWithoutExtension . '_' . $counter . '.pdf';
        $counter++;
    }

    return $candidatePath;
}

$pdfUploaded = isset($_FILES['application_pdf']) && $_FILES['application_pdf']['error'] !== UPLOAD_ERR_NO_FILE;
$budgetPdfUploaded = isset($_FILES['budget_pdf']) && $_FILES['budget_pdf']['error'] !== UPLOAD_ERR_NO_FILE;

if (
    ($pdfUploaded && $_FILES['application_pdf']['error'] !== UPLOAD_ERR_OK) ||
    ($budgetPdfUploaded && $_FILES['budget_pdf']['error'] !== UPLOAD_ERR_OK)
) {
    header('Location: application_edit.php?id=' . urlencode($id));
    exit();
}

$destinationDir = 'private/documents/applications/' . $id;
$destinationPath = null;
$budgetDestinationPath = null;
$pdfTmpPath = null;
$budgetPdfTmpPath = null;
$existingPdfPath = $existingApplication['application_pdf_path'] ?? null;
$existingBudgetPdfPath = $existingApplication['budget_pdf_path'] ?? null;
$reservedPaths = [];

if ($pdfUploaded) {
    try {
        $applicationPdf = validateUploadedPdf($_FILES['application_pdf']);
    } catch (RuntimeException $e) {
        header('Location: application_edit.php?id=' . urlencode($id));
        exit();
    }

    $pdfTmpPath = $applicationPdf['tmp_path'];
    $destinationPath = buildUploadDestinationPath($destinationDir, 'risposta', $applicationPdf['original_name'], $reservedPaths);
    $reservedPaths[] = $destinationPath;
}

if ($budgetPdfUploaded) {
    try {
        $budgetPdf = validateUploadedPdf($_FILES['budget_pdf']);
    } catch (RuntimeException $e) {
        header('Location: application_edit.php?id=' . urlencode($id));
        exit();
    }

    $budgetPdfTmpPath = $budgetPdf['tmp_path'];
    $budgetDestinationPath = buildUploadDestinationPath($destinationDir, 'budget', $budgetPdf['original_name'], $reservedPaths);
    $reservedPaths[] = $budgetDestinationPath;
}

$movedFiles = [];
try {
    $pdo->beginTransaction();

    if ($pdfUploaded || $budgetPdfUploaded) {
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true)) {
                throw new RuntimeException('Unable to create application directory');
            }
        }
    }

    if ($pdfUploaded || $budgetPdfUploaded) {
        $destinationBasePath = realpath($destinationDir);
        if (!$destinationBasePath) {
            throw new RuntimeException('Unable to resolve application directory');
        }
    }

    if ($pdfUploaded) {
        if (!move_uploaded_file($pdfTmpPath, $destinationPath)) {
            throw new RuntimeException('Unable to move uploaded application PDF');
        }
        $movedFiles[] = $destinationPath;
    }

    if ($budgetPdfUploaded) {
        if (!move_uploaded_file($budgetPdfTmpPath, $budgetDestinationPath)) {
            throw new RuntimeException('Unable to move uploaded budget PDF');
        }
        $movedFiles[] = $budgetDestinationPath;
    }

    if ($pdfUploaded && !empty($existingPdfPath)) {
        $existingPdfRealPath = realpath($existingPdfPath);
        if ($existingPdfRealPath && strpos($existingPdfRealPath, $destinationBasePath) === 0 && is_file($existingPdfRealPath)) {
            if (!unlink($existingPdfRealPath)) {
                throw new RuntimeException('Unable to replace existing application PDF');
            }
        }
    }

    if ($budgetPdfUploaded && !empty($existingBudgetPdfPath)) {
        $existingBudgetPdfRealPath = realpath($existingBudgetPdfPath);
        if ($existingBudgetPdfRealPath && strpos($existingBudgetPdfRealPath, $destinationBasePath) === 0 && is_file($existingBudgetPdfRealPath)) {
            if (!unlink($existingBudgetPdfRealPath)) {
                throw new RuntimeException('Unable to replace existing budget PDF');
            }
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
    if ($budgetPdfUploaded) {
        $query .= ', budget_pdf_path = :budget_pdf_path';
        $params[':budget_pdf_path'] = $budgetDestinationPath;
    }

    $query .= ' WHERE id = :id';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

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
    header('Location: application_edit.php?id=' . urlencode($id));
    exit();
}

header('Location: applications.php');
exit();
