<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'db/common-db.php';
require_once 'default_call_for_proposal.php';

$currentUserId = (int) $_SESSION['user_id'];
$fallbackRedirect = 'profile.php';

$redirectInput = isset($_POST['redirect']) ? (string) $_POST['redirect'] : null;
$redirectPath = normalizeRedirectPath($redirectInput, $fallbackRedirect);

$callValue = null;
if (isset($_POST['call_id'])) {
    $callValue = trim((string) $_POST['call_id']);
} elseif (isset($_POST['filter_call'])) {
    $callValue = trim((string) $_POST['filter_call']);
}

$newDefaultCallId = null;
if ($callValue !== null && $callValue !== '' && strtolower($callValue) !== 'all') {
    if (!ctype_digit($callValue) || (int) $callValue <= 0) {
        $_SESSION['default_call_message'] = [
            'type' => 'error',
            'text' => 'Bando selezionato non valido.',
        ];
        header('Location: ' . $redirectPath);
        exit();
    }

    $candidateCallId = (int) $callValue;
    $callCheckStmt = $pdo->prepare('SELECT 1 FROM call_for_proposal WHERE id = :id');
    $callCheckStmt->execute([':id' => $candidateCallId]);

    if (!$callCheckStmt->fetchColumn()) {
        $_SESSION['default_call_message'] = [
            'type' => 'error',
            'text' => 'Il bando selezionato non esiste.',
        ];
        header('Location: ' . $redirectPath);
        exit();
    }

    $newDefaultCallId = $candidateCallId;
}

$updateStmt = $pdo->prepare('UPDATE user SET default_call_for_proposal_id = :default_call_id WHERE id = :user_id');
$updateStmt->bindValue(':default_call_id', $newDefaultCallId, $newDefaultCallId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
$updateStmt->bindValue(':user_id', $currentUserId, PDO::PARAM_INT);
$updateStmt->execute();

$_SESSION['default_call_message'] = [
    'type' => 'success',
    'text' => $newDefaultCallId === null
        ? 'Bando di default rimosso.'
        : 'Bando di default aggiornato con successo.',
];

header('Location: ' . $redirectPath);
exit();
