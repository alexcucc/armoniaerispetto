<?php
session_start();
header('Content-Type: application/json');

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['EVALUATION_DELETE']
    )) {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$evaluationId = $data['id'] ?? null;

if ($evaluationId === null || !ctype_digit((string) $evaluationId)) {
    echo json_encode(['success' => false, 'message' => 'ID valutazione non valido']);
    exit();
}

$evaluationId = (int) $evaluationId;

try {
    $pdo->beginTransaction();

    $selectStmt = $pdo->prepare(
        'SELECT e.id, c.status AS call_status '
        . 'FROM evaluation e '
        . 'JOIN application a ON a.id = e.application_id '
        . 'JOIN call_for_proposal c ON c.id = a.call_for_proposal_id '
        . 'WHERE e.id = :id '
        . 'LIMIT 1'
    );
    $selectStmt->execute([':id' => $evaluationId]);
    $evaluation = $selectStmt->fetch(PDO::FETCH_ASSOC);

    if (!$evaluation) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Valutazione non trovata']);
        exit();
    }

    if (($evaluation['call_status'] ?? null) === 'CLOSED') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => "Il bando è chiuso e non è possibile eliminare la valutazione."]);
        exit();
    }

    $deleteStmt = $pdo->prepare('DELETE FROM evaluation WHERE id = :id');
    $deleteStmt->execute([':id' => $evaluationId]);

    if ($deleteStmt->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Valutazione non trovata']);
        exit();
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Valutazione eliminata con successo']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => "Errore durante l'eliminazione della valutazione"]);
}
?>
