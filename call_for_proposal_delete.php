<?php
session_start();
header('Content-Type: application/json');

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);

if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_DELETE']
    )
) {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$callForProposalId = $data['id'] ?? ($_POST['id'] ?? null);

if (!$callForProposalId) {
    echo json_encode(['success' => false, 'message' => 'ID bando non valido']);
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT pdf_path FROM call_for_proposal WHERE id = ?');
    $stmt->execute([$callForProposalId]);
    $pdfPath = $stmt->fetchColumn();

    if ($pdfPath === false) {
        echo json_encode(['success' => false, 'message' => 'Bando non trovato']);
        exit();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM application WHERE call_for_proposal_id = ?');
    $stmt->execute([$callForProposalId]);
    $applicationCount = (int) $stmt->fetchColumn();

    if ($applicationCount > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Impossibile eliminare il bando perché sono presenti risposte al bando associate.'
        ]);
        exit();
    }

    if ($pdfPath && file_exists($pdfPath)) {
        unlink($pdfPath);
        @rmdir(dirname($pdfPath));
    }

    $stmt = $pdo->prepare('DELETE FROM call_for_proposal WHERE id = ?');
    $stmt->execute([$callForProposalId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Bando eliminato con successo']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bando non trovato']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Errore durante l'eliminazione del bando"]);
}
