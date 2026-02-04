<?php
session_start();
header('Content-Type: application/json');

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
$userId = $_SESSION['user_id'] ?? null;
$isMonitor = false;
$isSupervisor = false;

if ($userId !== null) {
    $isMonitor = $rolePermissionManager->userHasPermission(
        $userId,
        RolePermissionManager::$PERMISSIONS['SUPERVISOR_MONITOR']
    );
    $isSupervisor = $rolePermissionManager->userHasPermission(
        $userId,
        RolePermissionManager::$PERMISSIONS['APPLICATION_REVIEW']
    );
}

if (!$userId || (!$isMonitor && !$isSupervisor)) {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$applicationId = $data['id'] ?? null;

if ($applicationId === null || !ctype_digit((string) $applicationId)) {
    echo json_encode(['success' => false, 'message' => 'ID risposta al bando non valido.']);
    exit();
}

$applicationId = (int) $applicationId;

try {
    $pdo->beginTransaction();

    if ($isMonitor) {
        $applicationStmt = $pdo->prepare(
            'SELECT status, checklist_path FROM application WHERE id = :id FOR UPDATE'
        );
        $applicationStmt->execute([':id' => $applicationId]);
    } else {
        $applicationStmt = $pdo->prepare(
            'SELECT a.status, a.checklist_path FROM application a '
            . 'JOIN supervisor s ON a.supervisor_id = s.id '
            . 'WHERE a.id = :id AND s.user_id = :user_id FOR UPDATE'
        );
        $applicationStmt->execute([
            ':id' => $applicationId,
            ':user_id' => $userId
        ]);
    }
    $application = $applicationStmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Risposta al bando non trovata.']);
        exit();
    }

    $status = strtoupper((string) ($application['status'] ?? ''));
    if (!$isMonitor && $status === 'FINAL_VALIDATION') {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Impossibile annullare la convalida: è già definitiva.'
        ]);
        exit();
    }

    $allowedStatuses = $isMonitor
        ? ['APPROVED', 'REJECTED', 'FINAL_VALIDATION']
        : ['APPROVED', 'REJECTED'];

    if (!in_array($status, $allowedStatuses, true)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Non è presente una convalida da annullare.']);
        exit();
    }

    $evaluationStmt = $pdo->prepare('SELECT COUNT(*) FROM evaluation WHERE application_id = :id');
    $evaluationStmt->execute([':id' => $applicationId]);
    $evaluationCount = (int) $evaluationStmt->fetchColumn();

    if ($evaluationCount > 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Impossibile annullare la convalida: sono presenti valutazioni compilate.'
        ]);
        exit();
    }

    $previousStatus = 'SUBMITTED';
    $clearChecklist = true;

    $updateSql = 'UPDATE application SET status = :status, rejection_reason = NULL';
    if ($clearChecklist) {
        $updateSql .= ', checklist_path = NULL';
    }
    $updateSql .= ' WHERE id = :id';

    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ':status' => $previousStatus,
        ':id' => $applicationId,
    ]);

    $pdo->commit();

    if ($clearChecklist) {
        $checklistPath = $application['checklist_path'] ?? null;
        if (!empty($checklistPath)) {
            $realPath = realpath($checklistPath);
            $baseDir = realpath('private/documents/applications/' . $applicationId);
            if ($realPath && $baseDir && strpos($realPath, $baseDir) === 0 && is_file($realPath)) {
                unlink($realPath);
                $files = glob($baseDir . '/*');
                if ($files !== false && count($files) === 0) {
                    rmdir($baseDir);
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Convalida annullata con successo. La risposta al bando è stata riportata allo stato precedente.'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => "Errore durante l'annullamento della convalida."
    ]);
}
?>
