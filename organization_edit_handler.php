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
$name = trim(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW));
$type = trim(filter_input(INPUT_POST, 'type', FILTER_UNSAFE_RAW));
$incorporation_year = trim(filter_input(INPUT_POST, 'incorporation_year', FILTER_UNSAFE_RAW));
$location = trim(filter_input(INPUT_POST, 'location', FILTER_UNSAFE_RAW));

if (!$id || !$name || !$type) {
    header('Location: organization_edit.php?id=' . urlencode($id));
    exit();
}

if ($incorporation_year === '') {
    $incorporation_year = null;
}

if ($location === '') {
    $location = null;
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