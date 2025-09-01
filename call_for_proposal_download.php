<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_LIST'])) {
    http_response_code(403);
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit();
}

$stmt = $pdo->prepare('SELECT pdf_path FROM call_for_proposal WHERE id = :id');
$stmt->execute([':id' => $id]);
$call = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$call) {
    http_response_code(404);
    exit();
}

$baseDir = realpath('private/documents/call_for_proposals');
$filePath = $call['pdf_path'];
$realPath = realpath($filePath);

if (!$realPath || !$baseDir || strpos($realPath, $baseDir) !== 0 || !is_file($realPath)) {
    http_response_code(404);
    exit();
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="call_for_proposal.pdf"');
header('Content-Length: ' . filesize($realPath));

readfile($realPath);
exit();
?>