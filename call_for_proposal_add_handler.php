<?php
session_start();

require_once 'db/common-db.php';
include_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_CREATE'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: call_for_proposal_add.php');
    exit();
}

$_SESSION['call_for_proposal_form_evaluator_ids'] = $_POST['evaluator_user_ids'] ?? [];

$title = trim(filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
$description = trim(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW));
$start_date_input = trim(filter_input(INPUT_POST, 'start_date', FILTER_UNSAFE_RAW));
$end_date_input = trim(filter_input(INPUT_POST, 'end_date', FILTER_UNSAFE_RAW));
$rawEvaluatorUserIds = $_POST['evaluator_user_ids'] ?? [];

$start_date = DateTime::createFromFormat('Y-m-d', $start_date_input);
$end_date = DateTime::createFromFormat('Y-m-d', $end_date_input);

$pdf_uploaded = isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK;
$zip_uploaded = isset($_FILES['application_documents_zip']) && $_FILES['application_documents_zip']['error'] === UPLOAD_ERR_OK;
if (!$title || !$description || !$start_date || !$end_date || !$pdf_uploaded || !$zip_uploaded) {
    $_SESSION['call_for_proposal_form_error'] = 'Compila tutti i campi obbligatori.';
    header('Location: call_for_proposal_add.php');
    exit();
}

$pdf_tmp_path = $_FILES['pdf']['tmp_name'];
$pdf_name = $_FILES['pdf']['name'];
$pdf_extension = strtolower(pathinfo($pdf_name, PATHINFO_EXTENSION));
if ($pdf_extension !== 'pdf') {
    $_SESSION['call_for_proposal_form_error'] = 'Il file del bando deve essere in formato PDF.';
    header('Location: call_for_proposal_add.php');
    exit();
}

$zip_tmp_path = $_FILES['application_documents_zip']['tmp_name'];
$zip_name = $_FILES['application_documents_zip']['name'];
$zip_extension = strtolower(pathinfo($zip_name, PATHINFO_EXTENSION));
if ($zip_extension !== 'zip') {
    $_SESSION['call_for_proposal_form_error'] = 'I documenti della presentazione domanda devono essere in formato ZIP.';
    header('Location: call_for_proposal_add.php');
    exit();
}

if (!is_array($rawEvaluatorUserIds)) {
    $_SESSION['call_for_proposal_form_error'] = 'Seleziona almeno un valutatore abilitato.';
    header('Location: call_for_proposal_add.php');
    exit();
}

$evaluatorUserIds = [];
foreach ($rawEvaluatorUserIds as $rawEvaluatorUserId) {
    if (!ctype_digit((string) $rawEvaluatorUserId)) {
        $_SESSION['call_for_proposal_form_error'] = 'Valutatori selezionati non validi.';
        header('Location: call_for_proposal_add.php');
        exit();
    }
    $evaluatorUserIds[] = (int) $rawEvaluatorUserId;
}
$evaluatorUserIds = array_values(array_unique($evaluatorUserIds));
if ($evaluatorUserIds === []) {
    $_SESSION['call_for_proposal_form_error'] = 'Seleziona almeno un valutatore abilitato.';
    header('Location: call_for_proposal_add.php');
    exit();
}

try {
    $pdo->beginTransaction();

    $validEvaluatorsStmt = $pdo->prepare(
        'SELECT user_id FROM evaluator WHERE user_id IN ('
        . implode(',', array_fill(0, count($evaluatorUserIds), '?'))
        . ')'
    );
    $validEvaluatorsStmt->execute($evaluatorUserIds);
    $validEvaluatorIds = array_map('intval', $validEvaluatorsStmt->fetchAll(PDO::FETCH_COLUMN));
    if (count($validEvaluatorIds) !== count($evaluatorUserIds)) {
        $pdo->rollBack();
        $_SESSION['call_for_proposal_form_error'] = 'Valutatori selezionati non validi.';
        header('Location: call_for_proposal_add.php');
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO call_for_proposal (title, description, pdf_path, start_date, end_date) VALUES (:title, :description, '', :start_date, :end_date)");
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':start_date' => $start_date->format('Y-m-d 00:00:00'),
        ':end_date' => $end_date->format('Y-m-d 00:00:00')
    ]);
    $call_for_proposal_id = $pdo->lastInsertId();

    $destination_dir = 'private/documents/call_for_proposals/' . $call_for_proposal_id;
    if (!is_dir($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }
    $destination_path = $destination_dir . '/call_for_proposal.pdf';

    if (!move_uploaded_file($pdf_tmp_path, $destination_path)) {
        $pdo->rollBack();
        header('Location: call_for_proposal_add.php');
        exit();
    }

    $zip_destination_path = $destination_dir . '/application_documents.zip';
    if (!move_uploaded_file($zip_tmp_path, $zip_destination_path)) {
        $pdo->rollBack();
        $_SESSION['call_for_proposal_form_error'] = 'Errore durante il caricamento dello ZIP dei documenti.';
        header('Location: call_for_proposal_add.php');
        exit();
    }

    $stmt = $pdo->prepare("UPDATE call_for_proposal SET pdf_path = :pdf_path WHERE id = :id");
    $stmt->execute([
        ':pdf_path' => $destination_path,
        ':id' => $call_for_proposal_id
    ]);

    $insertAssignmentStmt = $pdo->prepare(
        'INSERT INTO call_for_proposal_evaluator (call_for_proposal_id, evaluator_user_id) '
        . 'VALUES (:call_for_proposal_id, :evaluator_user_id)'
    );
    foreach ($evaluatorUserIds as $evaluatorUserId) {
        $insertAssignmentStmt->execute([
            ':call_for_proposal_id' => $call_for_proposal_id,
            ':evaluator_user_id' => $evaluatorUserId,
        ]);
    }

    $pdo->commit();
    unset($_SESSION['call_for_proposal_form_evaluator_ids'], $_SESSION['call_for_proposal_form_error']);

    header('Location: call_for_proposals.php');
    exit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['call_for_proposal_form_error'] = 'Errore durante la creazione del bando.';
    header('Location: call_for_proposal_add.php');
    exit();
}
