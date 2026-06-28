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
    $_SESSION['evaluation_error'] = 'Valutazione non trovata.';
    header('Location: evaluations.php');
    exit;
}

$stmt = $pdo->prepare('SELECT model_version FROM evaluation WHERE application_id = :application_id AND evaluator_id = :evaluator_id LIMIT 1');
$stmt->execute([
    ':application_id' => $applicationId,
    ':evaluator_id' => (int) $_SESSION['user_id'],
]);
$modelVersion = $stmt->fetchColumn();
if ($modelVersion === false || $modelVersion === null || $modelVersion === '') {
    $modelVersion = evaluationGetCurrentModelVersion();
}

if (evaluationIsLegacyModel((string) $modelVersion)) {
    include 'evaluation_summary_legacy.php';
    return;
}

include 'evaluation_summary_v4.php';
