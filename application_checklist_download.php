<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission(
    $_SESSION['user_id'],
    RolePermissionManager::$PERMISSIONS['EVALUATION_CREATE']
)) {
    http_response_code(403);
    exit();
}

$applicationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$applicationId) {
    http_response_code(404);
    exit();
}

$stmt = $pdo->prepare('SELECT checklist_path FROM application WHERE id = :id');
$stmt->execute([':id' => $applicationId]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$application || empty($application['checklist_path'])) {
    http_response_code(404);
    exit();
}

$baseDir = realpath('private/documents/applications');
$realPath = realpath($application['checklist_path']);

if (!$realPath || !$baseDir || strpos($realPath, $baseDir) !== 0 || !is_file($realPath)) {
    http_response_code(404);
    exit();
}

$filename = basename($realPath);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($realPath));

readfile($realPath);
exit();
