<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

header('Content-Type: application/json');

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_UPDATE']
    )) {
    echo json_encode([
        'success' => false,
        'message' => 'Accesso non consentito.'
    ]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$callId = $input['id'] ?? null;
if (!is_numeric($callId)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID bando non valido'
    ]);
    exit();
}

$callId = (int) $callId;

try {
    $stmt = $pdo->prepare('SELECT status FROM call_for_proposal WHERE id = :id');
    $stmt->execute([':id' => $callId]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$call) {
        echo json_encode([
            'success' => false,
            'message' => 'Bando non trovato'
        ]);
        exit();
    }

    if (($call['status'] ?? null) === 'CLOSED') {
        echo json_encode([
            'success' => false,
            'message' => 'Il bando è già stato chiuso.'
        ]);
        exit();
    }

    $update = $pdo->prepare(
        "UPDATE call_for_proposal SET status = 'CLOSED', closed_at = NOW() WHERE id = :id"
    );
    $update->execute([':id' => $callId]);

    echo json_encode([
        'success' => true,
        'message' => 'Bando chiuso con successo.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => "Errore durante la chiusura del bando"
    ]);
}
