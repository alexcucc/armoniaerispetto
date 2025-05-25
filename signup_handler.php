<?php

include_once 'mail/config/mail.php';

session_start();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden'
    ]);
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$password_verify = trim($_POST['password_verify'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($password_verify) || empty($phone)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Compilare tutti i campi richiesti.'
    ]);
    exit;
}

// Check if passwords match
if ($password !== $password_verify) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Le password non corrispondono.'
    ]);
    exit;
}

// Add password validation: must be at least 6 characters long
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'La password deve contenere almeno 6 caratteri.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Formato della mail non valido.'
    ]);
    exit;
}

include_once 'db/common-db.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Email già in uso.'
        ]);
        exit;
    }

    // Generate verification token
    $verification_token = bin2hex(random_bytes(32));
    $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

        // Insert user first
    $stmt = $pdo->prepare("INSERT INTO user (first_name, last_name, email, password, phone, email_verified) 
                          VALUES (:first_name, :last_name, :email, :password, :phone, 0)");
    $stmt->execute([
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email'      => $email,
        'password'   => $hashed_password,
        'phone'      => $phone
    ]);

    $user_id = $pdo->lastInsertId();

    // Create verification token
    $verification_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $stmt = $pdo->prepare("INSERT INTO email_verification_token (user_id, token, expires_at) 
                          VALUES (:user_id, :token, :expires_at)");
    $stmt->execute([
        'user_id' => $user_id,
        'token' => $verification_token,
        'expires_at' => $expires_at
    ]);

    $pdo->commit();

    // Send verification email
    $verification_link = "$url_prefix/verify_email.php?token=" . $verification_token;
    $to = $email;
    $subject = "Verifica il tuo indirizzo email";
    $message = "Ciao $first_name,\n\n";
    $message .= "Grazie per esserti registrato. Per completare la registrazione, clicca sul seguente link:\n\n";
    $message .= $verification_link . "\n\n";
    $message .= "Il link scadrà tra 24 ore.\n\n";
    $message .= "Cordiali saluti,\nFondazione Armonia e Rispetto";
    $headers = "From: noreply@armoniaerispetto.it";

    mail($to, $subject, $message, $headers);

    echo json_encode([
        'success' => true,
        'message' => 'Registrazione completata. Controlla la tua email per verificare il tuo account.',
        'redirect' => 'verification_pending.php'
    ]);
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Errore di database durante la registrazione.'
    ]);
    exit;
}
?>