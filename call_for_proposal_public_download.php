<?php
require_once 'db/common-db.php';

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

$pdfPath = trim((string) ($call['pdf_path'] ?? ''));
if ($pdfPath === '') {
    http_response_code(404);
    exit();
}

$baseDir = realpath('private/documents/call_for_proposals');
$realPath = realpath($pdfPath);

if (!$baseDir || !$realPath || !is_file($realPath)) {
    http_response_code(404);
    exit();
}

$normalizedBaseDir = rtrim(str_replace('\\', '/', $baseDir), '/');
$normalizedRealPath = str_replace('\\', '/', $realPath);
if (strpos($normalizedRealPath, $normalizedBaseDir . '/') !== 0) {
    http_response_code(404);
    exit();
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="call_for_proposal.pdf"');
header('Content-Length: ' . filesize($realPath));

readfile($realPath);
exit();
?>
