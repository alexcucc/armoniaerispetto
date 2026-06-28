<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'evaluation_models.php';
include_once 'db/common-db.php';

$applicationId = filter_input(INPUT_GET, 'application_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$applicationId) {
    exit('Error: application_id not set.');
}

$currentUserId = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT model_version FROM evaluation WHERE application_id = :application_id AND evaluator_id = :evaluator_id LIMIT 1');
$stmt->execute([
    ':application_id' => $applicationId,
    ':evaluator_id' => $currentUserId,
]);
$modelVersion = $stmt->fetchColumn();
if ($modelVersion === false || $modelVersion === null || $modelVersion === '') {
    $modelVersion = evaluationGetCurrentModelVersion();
}

if (evaluationIsLegacyModel((string) $modelVersion)) {
    include 'evaluation_form_legacy.php';
    return;
}

define('EVALUATION_FORM_DISPATCH', true);
include 'evaluation_form_v4.php';

