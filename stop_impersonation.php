<?php
session_start();

if (!isset($_SESSION['impersonator_user']) || !isset($_SESSION['impersonator_id'])) {
    header('Location: index.php');
    exit();
}

$impersonator = $_SESSION['impersonator_user'];

require_once 'db/common-db.php';

if (empty($impersonator['email']) || empty($impersonator['first_name']) || empty($impersonator['last_name'])) {
    $stmt = $pdo->prepare('SELECT id, email, first_name, last_name FROM user WHERE id = :id');
    $stmt->execute(['id' => $impersonator['id']]);
    $refreshedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($refreshedUser) {
        $impersonator = $refreshedUser;
    }
}

$_SESSION['user_id'] = $impersonator['id'];
$_SESSION['email'] = $impersonator['email'];
$_SESSION['first_name'] = $impersonator['first_name'];
$_SESSION['last_name'] = $impersonator['last_name'];
$_SESSION['logged_in'] = true;

unset($_SESSION['impersonated_user']);
unset($_SESSION['impersonated_user_id']);
unset($_SESSION['impersonator_user']);
unset($_SESSION['impersonator_id']);
unset($_SESSION['is_impersonating']);

$redirect = $_GET['redirect'] ?? 'index.php';

if (!is_string($redirect)) {
    $redirect = 'index.php';
} else {
    $redirect = trim($redirect);
    if ($redirect === '' || strpos($redirect, '://') !== false || strpos($redirect, "\n") !== false || strpos($redirect, "\r") !== false) {
        $redirect = 'index.php';
    } elseif (substr($redirect, 0, 2) === '//') {
        $redirect = 'index.php';
    }
}

header('Location: ' . $redirect);
exit();
