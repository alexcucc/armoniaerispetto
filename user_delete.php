<?php
session_start();
header('Content-Type: application/json');

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['USER_DELETE'])) {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit();
}

// Get and decode JSON data
$data = json_decode(file_get_contents('php://input'), true);
$userId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'ID utente non valido']);
    exit();
}

try {
    $evaluatorStmt = $pdo->prepare("SELECT 1 FROM evaluator WHERE user_id = ? LIMIT 1");
    $evaluatorStmt->execute([$userId]);

    if ($evaluatorStmt->fetchColumn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Non è possibile eliminare l\'utente perché è un valutatore'
        ]);
        exit();
    }

    $supervisorStmt = $pdo->prepare("SELECT 1 FROM supervisor WHERE user_id = ? LIMIT 1");
    $supervisorStmt->execute([$userId]);

    if ($supervisorStmt->fetchColumn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Non è possibile eliminare l\'utente perché è un convalidatore'
        ]);
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Utente eliminato con successo']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'utente']);
}
