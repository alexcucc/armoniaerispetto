<?php
session_start();
header('Content-Type: application/json');

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);

if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['SUPERVISOR_DELETE'])) {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$supervisorId = $data['id'] ?? null;

if (!$supervisorId) {
    echo json_encode(['success' => false, 'message' => 'ID relatore non valido']);
    exit();
}

try {
    $pdo->beginTransaction();

    $userStmt = $pdo->prepare("SELECT user_id FROM supervisor WHERE id = ?");
    $userStmt->execute([$supervisorId]);
    $userId = $userStmt->fetchColumn();

    if (!$userId) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Relatore non trovato']);
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM supervisor WHERE id = ?");
    $stmt->execute([$supervisorId]);

    $roleStmt = $pdo->prepare(
        "DELETE FROM user_role WHERE user_id = ? AND role_id = (SELECT id FROM role WHERE name = 'Supervisor')"
    );
    $roleStmt->execute([$userId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Relatore eliminato con successo']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => "Errore durante l'eliminazione del relatore"]);
}
