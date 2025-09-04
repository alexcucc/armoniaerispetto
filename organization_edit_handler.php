<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['ORGANIZATION_UPDATE'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: organizations.php');
    exit();
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
$incorporation_year = filter_input(INPUT_POST, 'incorporation_year', FILTER_SANITIZE_STRING);
$location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);

if (!$id || !$name || !$type) {
    header('Location: organization_edit.php?id=' . urlencode($id));
    exit();
}

$stmt = $pdo->prepare('UPDATE organization SET name = :name, type = :type, incorporation_year = :incorporation_year, location = :location WHERE id = :id');
$stmt->execute([
    ':name' => $name,
    ':type' => $type,
    ':incorporation_year' => $incorporation_year,
    ':location' => $location,
    ':id' => $id
]);

header('Location: organizations.php');
exit();
?>