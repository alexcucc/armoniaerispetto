<?php
session_start();
header('Content-Type: application/json');

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);

if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['APPLICATION_DELETE'])) {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$appId = $data['id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$appId) {
    echo json_encode(['success' => false, 'message' => 'ID domanda non valido']);
    exit();
}

try {
    $stmt = $pdo->prepare('DELETE FROM application WHERE id = :id');
    $stmt->execute([':id' => $appId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Domanda eliminata con successo']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Domanda non trovata']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Errore durante l'eliminazione della domanda"]);
}
