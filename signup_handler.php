<?php
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(403);
    exit('Forbidden');
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
    http_response_code(400);
    exit('Missing required fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Invalid email format');
}

include_once 'db/common-db.php';

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE email = :email OR username = :username");
    $stmt->execute(['email' => $email, 'username' => $username]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409);
        exit('Email o username già in uso');
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user record
    $stmt = $pdo->prepare("INSERT INTO user (firstname, lastname, email, username, password, phone) VALUES (:firstname, :lastname, :email, :username, :password, :phone)");
    $stmt->execute([
        'firstname' => $first_name,
        'lastname'  => $last_name,
        'email'     => $email,
        'username'  => $username,
        'password'  => $hashed_password,
        'phone'     => $phone
    ]);

    // Redirect to the login page after successful registration
    header("Location: login.php");
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    exit('Database error');
}
?>