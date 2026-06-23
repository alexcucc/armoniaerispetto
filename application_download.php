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
if (!in_array($downloadType, ['application', 'budget', 'cronoprogramma'], true)) {
    http_response_code(404);
    exit();
}

$mode = strtolower((string) filter_input(INPUT_GET, 'mode', FILTER_UNSAFE_RAW));
$contentDispositionType = $mode === 'inline' ? 'inline' : 'attachment';

$columnMap = [
    'application' => 'application_pdf_path',
    'budget' => 'budget_pdf_path',
    'cronoprogramma' => 'cronoprogramma_pdf_path',
];
$column = $columnMap[$downloadType];
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
$extension = strtolower((string) pathinfo($realPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xls' => 'application/vnd.ms-excel',
    'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
];
$contentType = $mimeTypes[$extension] ?? 'application/octet-stream';

if ($downloadType === 'budget' && $extension !== 'pdf') {
    $contentDispositionType = 'attachment';
}

header('Content-Type: ' . $contentType);
header('Content-Disposition: ' . $contentDispositionType . '; filename="' . $filename . '"');
header('Content-Length: ' . filesize($realPath));

readfile($realPath);
exit();
