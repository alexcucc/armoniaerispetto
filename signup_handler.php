<?php

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
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Compilare tutti i campi richiesti.'
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
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = :username OR email = :email LIMIT 1");
    $stmt->execute(['username' => $username, 'email' => $email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Username o email già in uso.'
        ]);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO user (firstname, lastname, email, username, password, phone) VALUES (:firstname, :lastname, :email, :username, :password, :phone)");
    $stmt->execute([
        'firstname' => $first_name,
        'lastname'  => $last_name,
        'email'     => $email,
        'username'  => $username,
        'password'  => $hashed_password,
        'phone'     => $phone
    ]);

    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['username'] = $username;

    echo json_encode([
        'success' => true,
        'redirect' => 'benvenuto.php'
    ]);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Errore di database durante la registrazione.'
    ]);
    exit;
}
?>