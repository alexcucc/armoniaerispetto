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

$downloadType = strtolower((string) ($_GET['type'] ?? 'application'));
if (!in_array($downloadType, ['application', 'budget'], true)) {
    http_response_code(404);
    exit();
}

$column = $downloadType === 'budget' ? 'budget_pdf_path' : 'application_pdf_path';
$stmt = $pdo->prepare("SELECT $column AS file_path FROM application WHERE id = :id");
$stmt->execute([':id' => $id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$application || empty($application['file_path'])) {
    http_response_code(404);
    exit();
}

$baseDir = realpath('private/documents/applications');
$filePath = $application['file_path'];
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
