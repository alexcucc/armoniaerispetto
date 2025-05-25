<?php
session_start();
include_once 'db/common-db.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('Token mancante');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT t.*, u.* 
        FROM email_verification_token t
        JOIN user u ON t.user_id = u.id
        WHERE t.token = :token 
        AND t.expires_at > NOW() 
        AND t.used_at IS NULL 
        AND u.email_verified = 0 
        LIMIT 1
    ");
    $stmt->execute(['token' => $token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception('Token non valido o scaduto');
    }

    // Mark email as verified
    $stmt = $pdo->prepare("UPDATE user SET email_verified = 1 WHERE id = :id");
    $stmt->execute(['id' => $result['user_id']]);

    // Mark token as used
    $stmt = $pdo->prepare("UPDATE email_verification_token SET used_at = NOW() WHERE token = :token");
    $stmt->execute(['token' => $token]);

    $pdo->commit();

    // Login user and redirect
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $result['user_id'];
    $_SESSION['email'] = $result['email'];
    $_SESSION['first_name'] = $result['first_name'];
    $_SESSION['last_name'] = $result['last_name'];

    header('Location: benvenuto.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Verification error: ' . $e->getMessage());
    die('Errore durante la verifica dell\'email');
}
?>