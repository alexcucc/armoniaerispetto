<?php
session_start();

$callId = filter_input(INPUT_GET, 'call_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$winnerId = filter_input(INPUT_GET, 'winner_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$callId) {
    header('Location: call_for_proposals.php');
    exit();
}

$location = 'call_for_proposal_winners_manage.php?id=' . urlencode((string) $callId);
if ($winnerId) {
    $location .= '#winner-form-' . urlencode((string) $winnerId);
} else {
    $location .= '#new-winner-form';
}

header('Location: ' . $location);
exit();
?>
