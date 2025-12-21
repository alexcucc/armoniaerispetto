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
$typeId = filter_input(INPUT_POST, 'type_id', FILTER_VALIDATE_INT);
$incorporation_year = trim(filter_input(INPUT_POST, 'incorporation_year', FILTER_UNSAFE_RAW));
$location = trim(filter_input(INPUT_POST, 'location', FILTER_UNSAFE_RAW));

$formData = [
    'name' => $name,
    'type_id' => $typeId,
    'incorporation_year' => $incorporation_year,
    'location' => $location,
];

if ($name === '' || !$typeId || $incorporation_year === '' || $location === '') {
    $_SESSION['error_message'] = 'Compila i campi obbligatori.';
    $_SESSION['form_data'] = $formData;
    header('Location: organization_add.php');
    exit();
}

if (!preg_match('/^\d{4}$/', $incorporation_year)) {
    $_SESSION['error_message'] = 'Inserisci un anno di costituzione valido (formato: YYYY).';
    $_SESSION['form_data'] = $formData;
    header('Location: organization_add.php');
    exit();
}

$currentYear = (int) date('Y');
$incorporationYearNumber = (int) $incorporation_year;

if ($incorporationYearNumber < 1901 || $incorporationYearNumber > $currentYear) {
    $_SESSION['error_message'] = 'L\'anno di costituzione deve essere compreso tra 1901 e l\'anno corrente.';
    $_SESSION['form_data'] = $formData;
    header('Location: organization_add.php');
    exit();
}

$typeExistsStmt = $pdo->prepare('SELECT COUNT(*) FROM organization_type WHERE id = :id');
$typeExistsStmt->execute([':id' => $typeId]);
if ((int) $typeExistsStmt->fetchColumn() === 0) {
    $_SESSION['error_message'] = 'Seleziona una tipologia di ente valida.';
    $_SESSION['form_data'] = $formData;
    header('Location: organization_add.php');
    exit();
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

$stmt = $pdo->prepare("INSERT INTO organization (name, type_id, incorporation_year, location) VALUES (:name, :type_id, :incorporation_year, :location)");

$stmt->execute([
    ':name' => $name,
    ':type_id' => $typeId,
    ':incorporation_year' => $incorporation_year,
    ':location' => $location
]);

header('Location: organizations.php');
exit();
?>
