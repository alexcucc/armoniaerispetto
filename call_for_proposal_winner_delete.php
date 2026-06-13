<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';
require_once 'call_for_proposal_winner_utils.php';

$rolePermissionManager = new RolePermissionManager($pdo);
if (
    !isset($_SESSION['user_id'])
    || !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_UPDATE']
    )
) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: call_for_proposals.php');
    exit();
}

$callId = filter_input(INPUT_POST, 'call_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$winnerId = filter_input(INPUT_POST, 'winner_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$callId || !$winnerId) {
    header('Location: call_for_proposals.php');
    exit();
}

$callStmt = $pdo->prepare('SELECT id, status FROM call_for_proposal WHERE id = :id');
$callStmt->execute([':id' => $callId]);
$call = $callStmt->fetch(PDO::FETCH_ASSOC);

if (!$call) {
    header('Location: call_for_proposals.php');
    exit();
}

if (($call['status'] ?? '') !== 'CLOSED') {
    $_SESSION['call_for_proposal_winner_message'] = [
        'type' => 'error',
        'text' => 'Puoi configurare i vincitori solo per i bandi chiusi.',
    ];
    header('Location: call_for_proposals.php');
    exit();
}

try {
    $winnerStmt = $pdo->prepare(
        'SELECT id FROM call_for_proposal_winner WHERE id = :winner_id AND call_for_proposal_id = :call_id'
    );
    $winnerStmt->execute([
        ':winner_id' => $winnerId,
        ':call_id' => $callId,
    ]);
    $winner = $winnerStmt->fetch(PDO::FETCH_ASSOC);

    if (!$winner) {
        $_SESSION['call_for_proposal_winner_message'] = [
            'type' => 'error',
            'text' => 'Vincitore non trovato.',
        ];
        header('Location: call_for_proposal_winners_manage.php?id=' . urlencode((string) $callId));
        exit();
    }

    $deleteStmt = $pdo->prepare('DELETE FROM call_for_proposal_winner WHERE id = :winner_id');
    $deleteStmt->execute([':winner_id' => $winnerId]);

    deleteDirectoryRecursively(getCallForProposalWinnerDirectory($callId, $winnerId));
    $_SESSION['call_for_proposal_winner_message'] = [
        'type' => 'success',
        'text' => 'Vincitore eliminato con successo.',
    ];
} catch (PDOException $exception) {
    $_SESSION['call_for_proposal_winner_message'] = [
        'type' => 'error',
        'text' => 'Errore durante l’eliminazione del vincitore.',
    ];
}

header('Location: call_for_proposal_winners_manage.php?id=' . urlencode((string) $callId));
exit();
?>
