<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['EVALUATOR_CREATE'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: evaluator_add.php');
    exit();
}

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$user_id) {
    header('Location: evaluator_add.php');
    exit();
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("INSERT INTO evaluator (user_id) VALUES (:user_id)");
    $stmt->execute([':user_id' => $user_id]);

    $roleStmt = $pdo->prepare(
        "INSERT INTO user_role (user_id, role_id)
         VALUES (:user_id, (SELECT id FROM role WHERE name = 'Evaluator'))"
    );
    $roleStmt->execute([':user_id' => $user_id]);

    $pdo->commit();
    header('Location: evaluators.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: evaluator_add.php');
    exit();
}