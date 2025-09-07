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
$projectName = trim(filter_input(INPUT_POST, 'project_name', FILTER_SANITIZE_STRING));
$projectDescription = trim(filter_input(INPUT_POST, 'project_description', FILTER_SANITIZE_STRING));

if (!$callId || !$organizationId || !$supervisorId || !$projectName || !$projectDescription) {
    header('Location: applications.php');
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $insertStmt = $pdo->prepare('INSERT INTO application (call_for_proposal_id, organization_id, supervisor_id, project_name, project_description, status) VALUES (:call_id, :org_id, :sup_id, :name, :description, "submitted")');
    $insertStmt->execute([
        'call_id' => $callId,
        'org_id' => $organizationId,
        'sup_id' => $supervisorId,
        'name' => $projectName,
        'description' => $projectDescription
    ]);
} catch (PDOException $e) {
    // Handle errors if necessary
}

header('Location: applications.php');
exit();