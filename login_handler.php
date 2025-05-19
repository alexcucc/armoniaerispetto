<?php
session_start();

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden'
    ]);
    exit;
}

$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($login) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

include_once 'db/common-db.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :login LIMIT 1");
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];

        echo json_encode([
            'success' => true,
            'redirect' => 'benvenuto.php'
        ]);
        exit;
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Credenziali non valide'
        ]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
    exit;
}
?>