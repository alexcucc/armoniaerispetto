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

$start_date = DateTime::createFromFormat('Y-m-d', $start_date_input);
$end_date = DateTime::createFromFormat('Y-m-d', $end_date_input);

if (!$id || !$title || !$description || !$start_date || !$end_date) {
    header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
    exit();
}

try {
    $pdo->beginTransaction();

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

    $pdo->commit();

    header('Location: call_for_proposals.php');
    exit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
    exit();
}