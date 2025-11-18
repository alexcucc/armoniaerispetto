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

$name = trim(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW));
$type = trim(filter_input(INPUT_POST, 'type', FILTER_UNSAFE_RAW));
$incorporation_year = trim(filter_input(INPUT_POST, 'incorporation_year', FILTER_UNSAFE_RAW));
$location = trim(filter_input(INPUT_POST, 'location', FILTER_UNSAFE_RAW));

$formData = [
    'name' => $name,
    'type' => $type,
    'incorporation_year' => $incorporation_year,
    'location' => $location,
];

if ($name === '' || $type === '') {
    $_SESSION['error_message'] = 'Compila i campi obbligatori.';
    $_SESSION['form_data'] = $formData;
    header('Location: organization_add.php');
    exit();
}

if ($incorporation_year === '') {
    $incorporation_year = null;
}

if ($location === '') {
    $location = null;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM organization WHERE LOWER(name) = LOWER(:name)');
$stmt->execute([':name' => $name]);
$duplicateCount = (int) $stmt->fetchColumn();

if ($duplicateCount > 0) {
    $_SESSION['error_message'] = 'Esiste già un ente con la stessa denominazione.';
    $_SESSION['form_data'] = $formData;
    header('Location: organization_add.php');
    exit();
}

$stmt = $pdo->prepare("INSERT INTO organization (name, type, incorporation_year, location) VALUES (:name, :type, :incorporation_year, :location)");

$stmt->execute([
    ':name' => $name,
    ':type' => $type,
    ':incorporation_year' => $incorporation_year,
    ':location' => $location
]);

header('Location: organizations.php');
exit();
?>
