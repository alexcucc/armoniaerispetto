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
    header('Location: supervisors.php');
    exit();
}

$applicationId = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
$decision = filter_input(INPUT_POST, 'decision', FILTER_UNSAFE_RAW);

if (!$applicationId || !in_array($decision, ['approve', 'reject'], true)) {
    header('Location: supervisors.php?error=1');
    exit();
}

if (!isset($_FILES['checklist']) || $_FILES['checklist']['error'] !== UPLOAD_ERR_OK) {
    header('Location: supervisors.php?error=1');
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT a.status FROM application a JOIN supervisor s ON a.supervisor_id = s.id WHERE a.id = :id AND s.user_id = :user_id');
    $stmt->execute([
        ':id' => $applicationId,
        ':user_id' => $_SESSION['user_id']
    ]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application || $application['status'] !== 'SUBMITTED') {
        header('Location: supervisors.php?error=1');
        exit();
    }

    $extension = strtolower(pathinfo($_FILES['checklist']['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        header('Location: supervisors.php?error=1');
        exit();
    }

    $destinationDir = 'private/documents/applications/' . $applicationId;
    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }
    $destinationPath = $destinationDir . '/checklist.pdf';

    if (!move_uploaded_file($_FILES['checklist']['tmp_name'], $destinationPath)) {
        header('Location: supervisors.php?error=1');
        exit();
    }

    $newStatus = $decision === 'approve' ? 'APPROVED' : 'REJECTED';
    $updateStmt = $pdo->prepare('UPDATE application SET status = :status, checklist_path = :path WHERE id = :id');
    $updateStmt->execute([
        ':status' => $newStatus,
        ':path' => $destinationPath,
        ':id' => $applicationId
    ]);

    header('Location: supervisors.php?success=1');
    exit();
} catch (PDOException $e) {
    header('Location: supervisors.php?error=1');
    exit();
}
?>
