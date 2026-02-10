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
    echo json_encode(['success' => false, 'message' => 'ID risposta al bando non valido']);
    exit();
}

try {
    $pdo->beginTransaction();

    $selectStmt = $pdo->prepare('SELECT application_pdf_path, budget_pdf_path, status FROM application WHERE id = :id');
    $selectStmt->execute([':id' => $appId]);
    $application = $selectStmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Risposta al bando non trovata']);
        exit();
    }

    if (($application['status'] ?? null) !== 'SUBMITTED') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'È possibile eliminare solo le risposte in attesa']);
        exit();
    }

    $stmt = $pdo->prepare('DELETE FROM application WHERE id = :id');
    $stmt->execute([':id' => $appId]);

    if ($stmt->rowCount() > 0) {
        $pdo->commit();

        $baseDir = realpath('private/documents/applications');
        $directoriesToCheck = [];
        $paths = [
            $application['application_pdf_path'] ?? null,
            $application['budget_pdf_path'] ?? null,
        ];

        foreach ($paths as $filePath) {
            if (empty($filePath)) {
                continue;
            }

            $realPath = realpath($filePath);
            if ($realPath && $baseDir && strpos($realPath, $baseDir) === 0 && is_file($realPath)) {
                unlink($realPath);
                $directoriesToCheck[dirname($realPath)] = true;
            }
        }

        foreach (array_keys($directoriesToCheck) as $directory) {
            if (is_dir($directory)) {
                $files = glob($directory . '/*');
                if ($files !== false && count($files) === 0) {
                    rmdir($directory);
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Risposta al bando eliminata con successo']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Risposta al bando non trovata']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => "Errore durante l'eliminazione della risposta al bando"]);
}
