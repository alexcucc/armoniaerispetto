<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_LIST'])) {
    http_response_code(403);
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit();
}

$stmt = $pdo->prepare('SELECT application_pdf_path FROM application WHERE id = :id');
$stmt->execute([':id' => $id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$application || empty($application['application_pdf_path'])) {
    http_response_code(404);
    exit();
}

$baseDir = realpath('private/documents/applications');
$filePath = $application['application_pdf_path'];
$realPath = realpath($filePath);

if (!$realPath || !$baseDir || strpos($realPath, $baseDir) !== 0 || !is_file($realPath)) {
    http_response_code(404);
    exit();
}

$filename = basename($realPath);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($realPath));

readfile($realPath);
exit();
