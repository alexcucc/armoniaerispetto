<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_CREATE'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: organization_add.php');
    exit();
}

$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
$incorporation_date = filter_input(INPUT_POST, 'incorporation_date', FILTER_SANITIZE_STRING);
$full_address = filter_input(INPUT_POST, 'full_address', FILTER_SANITIZE_STRING);

$stmt = $pdo->prepare("INSERT INTO organization (name, type, incorporation_date, full_address) VALUES (:name, :type, :incorporation_date, :full_address)");

$stmt->execute([
    ':name' => $name,
    ':type' => $type,
    ':incorporation_date' => $incorporation_date ?: null,
    ':full_address' => $full_address
]);

header('Location: organizations.php');
exit();
?>
