<?php
session_start();
header('Content-Type: application/json');

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);

if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['EVALUATOR_DELETE'])) {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$evaluatorId = $data['id'] ?? null;

if (!$evaluatorId) {
    echo json_encode(['success' => false, 'message' => 'ID valutatore non valido']);
    exit();
}

try {
    $pdo->beginTransaction();

    $userStmt = $pdo->prepare("SELECT user_id FROM evaluator WHERE id = ?");
    $userStmt->execute([$evaluatorId]);
    $userId = $userStmt->fetchColumn();

    if (!$userId) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Valutatore non trovato']);
        exit();
    }

    $supervisorStmt = $pdo->prepare("SELECT id FROM supervisor WHERE user_id = ?");
    $supervisorStmt->execute([$userId]);

    if ($supervisorStmt->fetchColumn()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Non è possibile eliminare il valutatore perché è anche un convalidatore']);
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM evaluator WHERE id = ?");
    $stmt->execute([$evaluatorId]);

    $roleStmt = $pdo->prepare(
        "DELETE FROM user_role WHERE user_id = ? AND role_id = (SELECT id FROM role WHERE name = 'Evaluator')"
    );
    $roleStmt->execute([$userId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Valutatore eliminato con successo']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => "Errore durante l'eliminazione del valutatore"]);
}