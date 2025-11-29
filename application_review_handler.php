<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['APPLICATION_REVIEW']
    )) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: supervisor_applications.php');
    exit();
}

$applicationId = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
$decision = filter_input(INPUT_POST, 'decision', FILTER_UNSAFE_RAW);
$rejectionReason = trim((string) ($_POST['rejection_reason'] ?? ''));

if (!$applicationId || !in_array($decision, ['APPROVED', 'REJECTED', 'FINAL_VALIDATION'], true)) {
    header('Location: supervisor_applications.php?error=1');
    exit();
}

if ($decision === 'REJECTED' && $rejectionReason === '') {
    header('Location: supervisor_applications.php?error=1');
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT a.status, a.checklist_path FROM application a JOIN supervisor s ON a.supervisor_id = s.id WHERE a.id = :id AND s.user_id = :user_id');
    $stmt->execute([
        ':id' => $applicationId,
        ':user_id' => $_SESSION['user_id']
    ]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application || !in_array($application['status'], ['SUBMITTED', 'APPROVED', 'REJECTED'], true)) {
        header('Location: supervisor_applications.php?error=1');
        exit();
    }

    if ($decision === 'FINAL_VALIDATION' && $application['status'] !== 'APPROVED') {
        header('Location: supervisor_applications.php?error=1');
        exit();
    }

    $existingChecklist = $application['checklist_path'] ?? null;
    $fileError = $_FILES['checklist']['error'] ?? UPLOAD_ERR_NO_FILE;
    $hasNewChecklist = $fileError === UPLOAD_ERR_OK;
    $checklistRequired = $application['status'] === 'SUBMITTED' || empty($existingChecklist);

    if ($checklistRequired && !$hasNewChecklist) {
        header('Location: supervisor_applications.php?error=1');
        exit();
    }

    $destinationPath = $existingChecklist;

    if ($hasNewChecklist) {
        $extension = strtolower(pathinfo($_FILES['checklist']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            header('Location: supervisor_applications.php?error=1');
            exit();
        }

        $destinationDir = 'private/documents/applications/' . $applicationId;
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }
        $destinationPath = $destinationDir . '/checklist.pdf';

        if ($existingChecklist && file_exists($existingChecklist)) {
            unlink($existingChecklist);
        }

        if (!move_uploaded_file($_FILES['checklist']['tmp_name'], $destinationPath)) {
            header('Location: supervisor_applications.php?error=1');
            exit();
        }
    } elseif ($fileError !== UPLOAD_ERR_NO_FILE) {
        header('Location: supervisor_applications.php?error=1');
        exit();
    }

    $newStatus = 'REJECTED';
    if ($decision === 'APPROVED' || $decision === 'FINAL_VALIDATION') {
        $newStatus = $decision === 'FINAL_VALIDATION' ? 'FINAL_VALIDATION' : 'APPROVED';
    }

    $rejectionReasonToSave = $decision === 'REJECTED' ? $rejectionReason : null;

    $updateStmt = $pdo->prepare(
        'UPDATE application SET status = :status, checklist_path = :path, rejection_reason = :reason WHERE id = :id'
    );
    $updateStmt->execute([
        ':status' => $newStatus,
        ':path' => $destinationPath,
        ':reason' => $rejectionReasonToSave,
        ':id' => $applicationId
    ]);

    header('Location: supervisor_applications.php?success=1');
    exit();
} catch (PDOException $e) {
    header('Location: supervisor_applications.php?error=1');
    exit();
}
?>
