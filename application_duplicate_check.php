<?php
session_start();

header('Content-Type: application/json');

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_CREATE'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Operazione non autorizzata.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito.']);
    exit();
}

$callId = filter_input(INPUT_GET, 'call_id', FILTER_VALIDATE_INT);
$organizationId = filter_input(INPUT_GET, 'organization_id', FILTER_VALIDATE_INT);

if (!$callId || !$organizationId) {
    http_response_code(400);
    echo json_encode(['error' => 'Parametri non validi.']);
    exit();
}

$duplicateCheckStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM application WHERE call_for_proposal_id = :call_id AND organization_id = :org_id'
);
$duplicateCheckStmt->execute([
    ':call_id' => $callId,
    ':org_id' => $organizationId,
]);

$exists = $duplicateCheckStmt->fetchColumn() > 0;

echo json_encode([
    'exists' => $exists,
    'message' => $exists ? 'Esiste già una risposta al bando per questo ente.' : null,
]);
