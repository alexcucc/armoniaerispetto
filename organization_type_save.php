<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_TYPE_MANAGE'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: organization_types.php');
    exit();
}

$action = $_POST['action'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$name = trim((string) filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW));

function redirectWithMessage(string $type, string $message): void
{
    $key = $type === 'error' ? 'error_message' : 'success_message';
    $_SESSION[$key] = $message;
    header('Location: organization_types.php');
    exit();
}

if (!in_array($action, ['create', 'update', 'delete'], true)) {
    redirectWithMessage('error', 'Azione non valida.');
}

if ($action !== 'delete' && $name === '') {
    redirectWithMessage('error', 'Inserisci un nome valido per la tipologia.');
}

try {
    if ($action === 'create') {
        $duplicateStmt = $pdo->prepare('SELECT COUNT(*) FROM organization_type WHERE LOWER(name) = LOWER(:name)');
        $duplicateStmt->execute([':name' => $name]);
        if ((int) $duplicateStmt->fetchColumn() > 0) {
            redirectWithMessage('error', 'Esiste già una tipologia con questo nome.');
        }

        $insertStmt = $pdo->prepare('INSERT INTO organization_type (name) VALUES (:name)');
        $insertStmt->execute([':name' => $name]);

        redirectWithMessage('success', 'Tipologia aggiunta con successo.');
    }

    if ($action === 'update') {
        if (!$id) {
            redirectWithMessage('error', 'Tipologia non valida.');
        }

        $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM organization_type WHERE id = :id');
        $existsStmt->execute([':id' => $id]);
        if ((int) $existsStmt->fetchColumn() === 0) {
            redirectWithMessage('error', 'Tipologia non trovata.');
        }

        $duplicateStmt = $pdo->prepare('SELECT COUNT(*) FROM organization_type WHERE LOWER(name) = LOWER(:name) AND id <> :id');
        $duplicateStmt->execute([':name' => $name, ':id' => $id]);
        if ((int) $duplicateStmt->fetchColumn() > 0) {
            redirectWithMessage('error', 'Esiste già una tipologia con questo nome.');
        }

        $updateStmt = $pdo->prepare('UPDATE organization_type SET name = :name WHERE id = :id');
        $updateStmt->execute([
            ':name' => $name,
            ':id' => $id,
        ]);

        redirectWithMessage('success', 'Tipologia aggiornata con successo.');
    }

    if ($action === 'delete') {
        if (!$id) {
            redirectWithMessage('error', 'Tipologia non valida.');
        }

        $usageStmt = $pdo->prepare('SELECT COUNT(*) FROM organization WHERE type_id = :id');
        $usageStmt->execute([':id' => $id]);
        if ((int) $usageStmt->fetchColumn() > 0) {
            redirectWithMessage('error', 'Non è possibile eliminare una tipologia associata ad almeno un ente.');
        }

        $deleteStmt = $pdo->prepare('DELETE FROM organization_type WHERE id = :id');
        $deleteStmt->execute([':id' => $id]);

        redirectWithMessage('success', 'Tipologia eliminata con successo.');
    }
} catch (PDOException $e) {
    error_log('Errore nella gestione delle tipologie di ente: ' . $e->getMessage());
    redirectWithMessage('error', 'Si è verificato un errore durante l\'operazione. Riprova più tardi.');
}
