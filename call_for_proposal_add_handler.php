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

$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
$start_date_input = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
$end_date_input = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);

$start_date = DateTime::createFromFormat('Y-m-d\\TH:i', $start_date_input);
$end_date = DateTime::createFromFormat('Y-m-d\\TH:i', $end_date_input);

$pdf_uploaded = isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK;
if (!$title || !$description || !$start_date || !$end_date || !$pdf_uploaded) {
    header('Location: call_for_proposal_add.php');
    exit();
}

$pdf_tmp_path = $_FILES['pdf']['tmp_name'];
$pdf_name = $_FILES['pdf']['name'];
$pdf_extension = strtolower(pathinfo($pdf_name, PATHINFO_EXTENSION));
if ($pdf_extension !== 'pdf') {
    header('Location: call_for_proposal_add.php');
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO call_for_proposal (title, description, pdf_path, start_date, end_date) VALUES (:title, :description, '', :start_date, :end_date)");
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':start_date' => $start_date->format('Y-m-d H:i:s'),
        ':end_date' => $end_date->format('Y-m-d H:i:s')
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

    $stmt = $pdo->prepare("UPDATE call_for_proposal SET pdf_path = :pdf_path WHERE id = :id");
    $stmt->execute([
        ':pdf_path' => $destination_path,
        ':id' => $call_for_proposal_id
    ]);

    $pdo->commit();

    header('Location: call_for_proposals.php');
    exit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: call_for_proposal_add.php');
    exit();
}