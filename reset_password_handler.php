<?php
session_start();
include_once 'db/common-db.php';
include_once 'mail/config/mail.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email non valida']);
    exit;
}

try {
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM user WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Se l\'email esiste nel nostro sistema, riceverai un link per reimpostare la password.']);
        exit;
    }

    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store reset token
    $stmt = $pdo->prepare("INSERT INTO password_reset (user_id, token, expires_at) VALUES (:user_id, :token, :expires)");
    $stmt->execute([
        'user_id' => $user['id'],
        'token' => $token,
        'expires' => $expires
    ]);

    // Send reset email
    $resetLink = "$url_prefix/new_password.php?token=" . $token;
    $to = $email;
    $subject = "Reset Password - Fondazione Armonia e Rispetto";
    $message = "Per favore clicca sul seguente link per reimpostare la tua password:\n\n$resetLink\n\nQuesto link scadrà tra un'ora.";
    $headers = "From: noreply@armoniaerispetto.it";

    mail($to, $subject, $message, $headers);

    echo json_encode([
        'success' => true,
        'message' => 'Se l\'email esiste nel nostro sistema, riceverai un link per reimpostare la password.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore del database']);
    exit;
}