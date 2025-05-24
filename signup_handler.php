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

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO user (first_name, last_name, email, password, phone) VALUES (:first_name, :last_name, :email, :password, :phone)");
    $stmt->execute([
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email'      => $email,
        'password'   => $hashed_password,
        'phone'      => $phone
    ]);

    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;

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