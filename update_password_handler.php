<?php
session_start();
include_once 'db/common-db.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($token) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get reset request
    $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = :token AND used = 0 LIMIT 1");
    $stmt->execute(['token' => $token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset || strtotime($reset['expires_at']) <= time()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Token non valido o scaduto']);
        exit;
    }

    // Update password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE user SET password = :password WHERE id = :user_id");
    $stmt->execute([
        'password' => $hashedPassword,
        'user_id' => $reset['user_id']
    ]);

    // Mark token as used
    $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = :token");
    $stmt->execute(['token' => $token]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Password aggiornata con successo'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore del database']);
    exit;
}