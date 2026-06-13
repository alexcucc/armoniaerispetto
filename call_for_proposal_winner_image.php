<?php
require_once 'db/common-db.php';
require_once 'call_for_proposal_winner_utils.php';

$imageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$imageId) {
    http_response_code(404);
    exit();
}

$imageStmt = $pdo->prepare(
    'SELECT i.image_path '
    . 'FROM call_for_proposal_winner_image i '
    . 'JOIN call_for_proposal_winner w ON w.id = i.winner_id '
    . 'JOIN call_for_proposal c ON c.id = w.call_for_proposal_id '
    . 'WHERE i.id = :image_id AND c.status = "CLOSED"'
);
$imageStmt->execute([':image_id' => $imageId]);
$imagePath = $imageStmt->fetchColumn();

if (!$imagePath || !is_file($imagePath)) {
    http_response_code(404);
    exit();
}

$mimeType = detectImageContentType($imagePath);
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) filesize($imagePath));
header('Content-Disposition: inline; filename="' . basename($imagePath) . '"');
readfile($imagePath);
exit();
?>
