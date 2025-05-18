<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(403);
    exit('Forbidden');
}

$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($login) || empty($password)) {
    http_response_code(400);
    exit('Missing required fields');
}

include_once 'db/common-db.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :login OR username = :login LIMIT 1");
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        header("Location: benvenuto.php");
        exit;
    } else {
        http_response_code(401);
        exit('Credenziali non valide');
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    exit('Database error');
}
?>