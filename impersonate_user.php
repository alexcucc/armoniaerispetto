<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metodo non consentito'
    ]);
    exit();
}

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);

if (!isset($_SESSION['user_id']) ||
    !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['USER_IMPERSONATE']
    )
) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Accesso non autorizzato'
    ]);
    exit();
}

$payload = json_decode(file_get_contents('php://input'), true);
$targetUserId = $payload['id'] ?? null;

if (!$targetUserId || !is_numeric($targetUserId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID utente non valido'
    ]);
    exit();
}

$targetUserId = (int) $targetUserId;
$currentUserId = $_SESSION['user_id'];

if ($targetUserId === $currentUserId) {
    echo json_encode([
        'success' => false,
        'message' => 'Sei già connesso come questo utente'
    ]);
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT id, email, first_name, last_name FROM user WHERE id = :id');
    $stmt->execute(['id' => $targetUserId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Utente non trovato'
        ]);
        exit();
    }

    $currentUserSnapshot = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'] ?? '',
        'first_name' => $_SESSION['first_name'] ?? '',
        'last_name' => $_SESSION['last_name'] ?? ''
    ];

    if (!isset($_SESSION['impersonator_user'])) {
        $_SESSION['impersonator_user'] = $currentUserSnapshot;
    }

    $_SESSION['impersonator_id'] = $_SESSION['impersonator_user']['id'];
    $_SESSION['impersonated_user'] = $targetUser;
    $_SESSION['impersonated_user_id'] = $targetUser['id'];
    $_SESSION['is_impersonating'] = true;

    $_SESSION['user_id'] = $targetUser['id'];
    $_SESSION['email'] = $targetUser['email'];
    $_SESSION['first_name'] = $targetUser['first_name'];
    $_SESSION['last_name'] = $targetUser['last_name'];
    $_SESSION['logged_in'] = true;

    echo json_encode([
        'success' => true,
        'message' => 'Stai ora agendo come ' . $targetUser['first_name'] . ' ' . $targetUser['last_name'],
        'redirect' => 'index.php?open_gestione=1'
    ]);
    exit();
} catch (PDOException $exception) {
    http_response_code(500);
    error_log('Database error during impersonation: ' . $exception->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante il cambio di utente'
    ]);
    exit();
}
