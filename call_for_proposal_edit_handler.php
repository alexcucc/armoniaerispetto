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
    header('Location: call_for_proposals.php');
    exit();
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$title = trim(filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
$description = trim(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW));
$start_date_input = trim(filter_input(INPUT_POST, 'start_date', FILTER_UNSAFE_RAW));
$end_date_input = trim(filter_input(INPUT_POST, 'end_date', FILTER_UNSAFE_RAW));
$rawEvaluatorUserIds = $_POST['evaluator_user_ids'] ?? [];

$_SESSION['call_for_proposal_form_evaluator_ids'] = $rawEvaluatorUserIds;

$start_date = DateTime::createFromFormat('Y-m-d', $start_date_input);
$end_date = DateTime::createFromFormat('Y-m-d', $end_date_input);

if (!$id || !$title || !$description || !$start_date || !$end_date) {
    $_SESSION['call_for_proposal_form_error'] = 'Compila tutti i campi obbligatori.';
    header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
    exit();
}

if (!is_array($rawEvaluatorUserIds)) {
    $_SESSION['call_for_proposal_form_error'] = 'Seleziona almeno un valutatore abilitato.';
    header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
    exit();
}

$evaluatorUserIds = [];
foreach ($rawEvaluatorUserIds as $rawEvaluatorUserId) {
    if (!ctype_digit((string) $rawEvaluatorUserId)) {
        $_SESSION['call_for_proposal_form_error'] = 'Valutatori selezionati non validi.';
        header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
        exit();
    }
    $evaluatorUserIds[] = (int) $rawEvaluatorUserId;
}
$evaluatorUserIds = array_values(array_unique($evaluatorUserIds));
if ($evaluatorUserIds === []) {
    $_SESSION['call_for_proposal_form_error'] = 'Seleziona almeno un valutatore abilitato.';
    header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
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
        header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
        exit();
    }

    $stmt = $pdo->prepare('SELECT pdf_path FROM call_for_proposal WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        $pdo->rollBack();
        header('Location: call_for_proposals.php');
        exit();
    }

    $pdfPath = $existing['pdf_path'];

    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $pdf_tmp_path = $_FILES['pdf']['tmp_name'];
        $pdf_name = $_FILES['pdf']['name'];
        $pdf_extension = strtolower(pathinfo($pdf_name, PATHINFO_EXTENSION));
        if ($pdf_extension !== 'pdf') {
            $pdo->rollBack();
            $_SESSION['call_for_proposal_form_error'] = 'Il file del bando deve essere in formato PDF.';
            header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
            exit();
        }

        $destination_dir = 'private/documents/call_for_proposals/' . $id;
        if (!is_dir($destination_dir)) {
            mkdir($destination_dir, 0755, true);
        }
        $destination_path = $destination_dir . '/call_for_proposal.pdf';

        if ($pdfPath && file_exists($pdfPath)) {
            unlink($pdfPath);
        }

        if (!move_uploaded_file($pdf_tmp_path, $destination_path)) {
            $pdo->rollBack();
            $_SESSION['call_for_proposal_form_error'] = 'Errore durante il caricamento del PDF.';
            header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
            exit();
        }

        $pdfPath = $destination_path;
    }
    $start_date_dt = $start_date->format('Y-m-d 00:00:00');
    $end_date_dt = $end_date->format('Y-m-d 00:00:00');

    $stmt = $pdo->prepare('UPDATE call_for_proposal SET title = :title, description = :description, start_date = :start_date, end_date = :end_date, pdf_path = :pdf_path WHERE id = :id');
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':start_date' => $start_date_dt,
        ':end_date' => $end_date_dt,
        ':pdf_path' => $pdfPath,
        ':id' => $id
    ]);

    $existingAssignmentsStmt = $pdo->prepare(
        'SELECT evaluator_user_id FROM call_for_proposal_evaluator WHERE call_for_proposal_id = :call_for_proposal_id'
    );
    $existingAssignmentsStmt->execute([':call_for_proposal_id' => $id]);
    $existingEvaluatorIds = array_map('intval', $existingAssignmentsStmt->fetchAll(PDO::FETCH_COLUMN));

    $evaluatorIdsToRemove = array_values(array_diff($existingEvaluatorIds, $evaluatorUserIds));
    if ($evaluatorIdsToRemove !== []) {
        $placeholders = implode(',', array_fill(0, count($evaluatorIdsToRemove), '?'));
        $submittedOrRevisedStmt = $pdo->prepare(
            "SELECT DISTINCT e.evaluator_id "
            . "FROM evaluation e "
            . "JOIN application a ON a.id = e.application_id "
            . "WHERE a.call_for_proposal_id = ? "
            . "AND e.status IN ('SUBMITTED', 'REVISED') "
            . "AND e.evaluator_id IN (" . $placeholders . ')'
        );
        $submittedOrRevisedStmt->execute(array_merge([$id], $evaluatorIdsToRemove));
        $lockedEvaluatorIds = array_map('intval', $submittedOrRevisedStmt->fetchAll(PDO::FETCH_COLUMN));
        if ($lockedEvaluatorIds !== []) {
            $pdo->rollBack();
            $_SESSION['call_for_proposal_form_error'] =
                'Non puoi rimuovere valutatori che hanno già inviato una valutazione per questo bando.';
            header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
            exit();
        }
    }

    $deleteAssignmentsStmt = $pdo->prepare('DELETE FROM call_for_proposal_evaluator WHERE call_for_proposal_id = :call_for_proposal_id');
    $deleteAssignmentsStmt->execute([':call_for_proposal_id' => $id]);

    $insertAssignmentStmt = $pdo->prepare(
        'INSERT INTO call_for_proposal_evaluator (call_for_proposal_id, evaluator_user_id) '
        . 'VALUES (:call_for_proposal_id, :evaluator_user_id)'
    );
    foreach ($evaluatorUserIds as $evaluatorUserId) {
        $insertAssignmentStmt->execute([
            ':call_for_proposal_id' => $id,
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
    $_SESSION['call_for_proposal_form_error'] = 'Errore durante la modifica del bando.';
    header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
    exit();
}
