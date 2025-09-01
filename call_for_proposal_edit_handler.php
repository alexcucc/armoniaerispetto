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
$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
$start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
$end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);

if (!$id || !$title || !$description || !$start_date || !$end_date) {
    header('Location: call_for_proposal_edit.php?id=' . urlencode($id));
    exit();
}

$stmt = $pdo->prepare('SELECT pdf_path FROM call_for_proposal WHERE id = :id');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$existing) {
    header('Location: call_for_proposals.php');
    exit();
}

$pdfPath = $existing['pdf_path'];

if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
    if ($pdfPath && file_exists($pdfPath)) {
        unlink($pdfPath);
    }

    $uploadDir = __DIR__ . '/documents/call_for_proposals/' . $id . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $fileName = basename($_FILES['pdf']['name']);
    $targetPath = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['pdf']['tmp_name'], $targetPath)) {
        $pdfPath = 'documents/call_for_proposals/' . $id . '/' . $fileName;
    }
}

$start_date_dt = date('Y-m-d H:i:s', strtotime($start_date));
$end_date_dt = date('Y-m-d H:i:s', strtotime($end_date));

$stmt = $pdo->prepare('UPDATE call_for_proposal SET title = :title, description = :description, start_date = :start_date, end_date = :end_date, pdf_path = :pdf_path WHERE id = :id');
$stmt->execute([
    ':title' => $title,
    ':description' => $description,
    ':start_date' => $start_date_dt,
    ':end_date' => $end_date_dt,
    ':pdf_path' => $pdfPath,
    ':id' => $id
]);

header('Location: call_for_proposals.php');
exit();