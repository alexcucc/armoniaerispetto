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
$projectName = trim(filter_input(INPUT_POST, 'project_name', FILTER_SANITIZE_STRING));
$projectDescription = trim(filter_input(INPUT_POST, 'project_description', FILTER_SANITIZE_STRING));

if (!$id || !$callId || !$organizationId || !$supervisorId || !$projectName || !$projectDescription) {
    header('Location: application_edit.php?id=' . urlencode($id));
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare('UPDATE application SET call_for_proposal_id = :call_id, organization_id = :org_id, supervisor_id = :sup_id, project_name = :name, project_description = :description WHERE id = :id');
$stmt->execute([
    ':call_id' => $callId,
    ':org_id' => $organizationId,
    ':sup_id' => $supervisorId,
    ':name' => $projectName,
    ':description' => $projectDescription,
    ':id' => $id
]);

header('Location: applications.php');
exit();
