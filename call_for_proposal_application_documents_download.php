<?php
session_start();

require_once 'db/common-db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit();
}

$stmt = $pdo->prepare('SELECT id FROM call_for_proposal WHERE id = :id');
$stmt->execute([':id' => $id]);
$call = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$call) {
    http_response_code(404);
    exit();
}

$baseDir = realpath('private/documents/call_for_proposals');
$expectedPath = 'private/documents/call_for_proposals/' . $id . '/application_documents.zip';
$realPath = realpath($expectedPath);

if (!$realPath || !$baseDir || strpos($realPath, $baseDir) !== 0 || !is_file($realPath)) {
    http_response_code(404);
    exit();
}

$filename = 'documenti_presentazione_bando_' . $id . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($realPath));

readfile($realPath);
exit();

