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
$typeId = filter_input(INPUT_POST, 'type_id', FILTER_VALIDATE_INT);
$incorporation_year = trim(filter_input(INPUT_POST, 'incorporation_year', FILTER_UNSAFE_RAW));
$location = trim(filter_input(INPUT_POST, 'location', FILTER_UNSAFE_RAW));

if (!$id || !$typeId || $incorporation_year === '' || $location === '') {
    $_SESSION['error_message'] = 'Compila i campi obbligatori.';
    $_SESSION['form_data'] = [
        'type_id' => $typeId,
        'incorporation_year' => $incorporation_year,
        'location' => $location,
    ];
    header('Location: organization_edit.php?id=' . urlencode($id));
    exit();
}

if (!preg_match('/^\d{4}$/', $incorporation_year)) {
    $_SESSION['error_message'] = 'Inserisci un anno di costituzione valido (formato: YYYY).';
    $_SESSION['form_data'] = [
        'type_id' => $typeId,
        'incorporation_year' => $incorporation_year,
        'location' => $location,
    ];
    header('Location: organization_edit.php?id=' . urlencode($id));
    exit();
}

$currentYear = (int) date('Y');
$incorporationYearNumber = (int) $incorporation_year;

if ($incorporationYearNumber < 1901 || $incorporationYearNumber > $currentYear) {
    $_SESSION['error_message'] = 'L\'anno di costituzione deve essere compreso tra 1901 e l\'anno corrente.';
    $_SESSION['form_data'] = [
        'type_id' => $typeId,
        'incorporation_year' => $incorporation_year,
        'location' => $location,
    ];
    header('Location: organization_edit.php?id=' . urlencode($id));
    exit();
}

$typeExistsStmt = $pdo->prepare('SELECT COUNT(*) FROM organization_type WHERE id = :id');
$typeExistsStmt->execute([':id' => $typeId]);
if ((int) $typeExistsStmt->fetchColumn() === 0) {
    $_SESSION['error_message'] = 'Seleziona una tipologia di ente valida.';
    $_SESSION['form_data'] = [
        'type_id' => $typeId,
        'incorporation_year' => $incorporation_year,
        'location' => $location,
    ];
    header('Location: organization_edit.php?id=' . urlencode($id));
    exit();
}

$stmt = $pdo->prepare('UPDATE organization SET type_id = :type_id, incorporation_year = :incorporation_year, location = :location WHERE id = :id');
$stmt->execute([
    ':type_id' => $typeId,
    ':incorporation_year' => $incorporation_year,
    ':location' => $location,
    ':id' => $id
]);

header('Location: organizations.php');
exit();
?>
