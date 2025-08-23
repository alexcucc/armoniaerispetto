<?php
session_start();
header('Content-Type: application/json');

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);

if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_DELETE'])) {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$organizationId = $data['id'] ?? null;

if (!$organizationId) {
    echo json_encode(['success' => false, 'message' => 'ID ente non valido']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM organization WHERE id = ?");
    $stmt->execute([$organizationId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Ente eliminato con successo']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ente non trovato']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Errore durante l'eliminazione dell'ente"]);
}