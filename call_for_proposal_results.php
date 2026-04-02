<?php
session_start();

require_once 'db/common-db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$callId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$callId) {
    header('Location: call_for_proposals.php');
    exit();
}

$callStmt = $pdo->prepare('SELECT id, title, description FROM call_for_proposal WHERE id = :id');
$callStmt->execute([':id' => $callId]);
$call = $callStmt->fetch(PDO::FETCH_ASSOC);

if (!$call) {
    header('Location: call_for_proposals.php');
    exit();
}

$totalEvaluatorsStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM call_for_proposal_evaluator WHERE call_for_proposal_id = :call_id'
);
$totalEvaluatorsStmt->execute([':call_id' => $callId]);
$totalEvaluators = (int) $totalEvaluatorsStmt->fetchColumn();

$evaluationScoreSubquery = '
    SELECT
        ev.id AS evaluation_id,
        CASE
            WHEN ev.forced_weighted_total_score IS NOT NULL THEN ev.forced_weighted_total_score
            ELSE COALESCE(efp.weighted_score, 0)
                + COALESCE(ep.weighted_score, 0)
                + COALESCE(epe.weighted_score, 0)
                + COALESCE(eq.weighted_score, 0)
                + COALESCE(etc_cohab.weighted_score, 0)
                + COALESCE(etc_community.weighted_score, 0)
                + COALESCE(etc_culture.weighted_score, 0)
                + COALESCE(etc_repopulation.weighted_score, 0)
                + COALESCE(etc_safeguard.weighted_score, 0)
        END AS total_overall_score
    FROM evaluation ev
    LEFT JOIN (
        SELECT evaluation_id,
            SUM(
                COALESCE(completeness_and_clarity_of_budget_score, 0) * 3
                + COALESCE(consistency_with_objectives_score, 0) * 3
                + COALESCE(cofinancing_score, 0) * 2
                + COALESCE(flexibility_score, 0) * 2
            ) AS weighted_score
        FROM evaluation_financial_plan
        GROUP BY evaluation_id
    ) efp ON efp.evaluation_id = ev.id
    LEFT JOIN (
        SELECT evaluation_id,
            SUM(
                COALESCE(needs_identification_and_problem_analysis_score, 0) * 3
                + COALESCE(adherence_to_statuary_purposes_score, 0) * 3
                + COALESCE(social_weight_score, 0) * 2
                + COALESCE(objectives_score, 0) * 2
                + COALESCE(expected_results_score, 0) * 2
                + COALESCE(activity_score, 0) * 3
                + COALESCE(local_purpose_score, 0) * 2
                + COALESCE(partnership_and_relations_with_local_authorities_score, 0) * 2
                + COALESCE(synergies_and_design_inefficiencies_score, 0) * 3
                + COALESCE(communication_and_visibility_score, 0) * 3
            ) AS weighted_score
        FROM evaluation_project
        GROUP BY evaluation_id
    ) ep ON ep.evaluation_id = ev.id
    LEFT JOIN (
        SELECT evaluation_id,
            SUM(
                COALESCE(general_information_score, 0) * 2
                + COALESCE(experience_score, 0) * 4
                + COALESCE(organizational_capacity_score, 0) * 4
                + COALESCE(policy_score, 0) * 4
                + COALESCE(budget_score, 0) * 3
                + COALESCE(purpose_and_local_involvement_score, 0) * 4
                + COALESCE(partnership_and_visibility_score, 0) * 4
            ) AS weighted_score
        FROM evaluation_proposing_entity
        GROUP BY evaluation_id
    ) epe ON epe.evaluation_id = ev.id
    LEFT JOIN (
        SELECT evaluation_id,
            SUM(
                COALESCE(impact_score, 0) * 5
                + COALESCE(relevance_score, 0) * 6
                + COALESCE(congruity_score, 0) * 4
                + COALESCE(innovation_score, 0) * 3
                + COALESCE(rigor_and_scientific_validity_score, 0) * 6
                + COALESCE(replicability_and_scalability_score, 0) * 4
                + COALESCE(cohabitation_evidence_score, 0) * 6
                + COALESCE(research_and_university_partnership_score, 0) * 6
            ) AS weighted_score
        FROM evaluation_qualitative_elements
        GROUP BY evaluation_id
    ) eq ON eq.evaluation_id = ev.id
    LEFT JOIN (
        SELECT evaluation_id,
            SUM(COALESCE(overall_score, 0) * 20) AS weighted_score
        FROM evaluation_thematic_criteria_cohabitation
        GROUP BY evaluation_id
    ) etc_cohab ON etc_cohab.evaluation_id = ev.id
    LEFT JOIN (
        SELECT evaluation_id,
            SUM(COALESCE(overall_score, 0) * 9) AS weighted_score
        FROM evaluation_thematic_criteria_community_support
        GROUP BY evaluation_id
    ) etc_community ON etc_community.evaluation_id = ev.id
    LEFT JOIN (
        SELECT evaluation_id,
            SUM(COALESCE(overall_score, 0) * 10) AS weighted_score
        FROM evaluation_thematic_criteria_culture_education_awareness
        GROUP BY evaluation_id
    ) etc_culture ON etc_culture.evaluation_id = ev.id
    LEFT JOIN (
        SELECT evaluation_id,
            SUM(COALESCE(overall_score, 0) * 35) AS weighted_score
        FROM evaluation_thematic_criteria_repopulation
        GROUP BY evaluation_id
    ) etc_repopulation ON etc_repopulation.evaluation_id = ev.id
    LEFT JOIN (
        SELECT evaluation_id,
            SUM(COALESCE(overall_score, 0) * 35) AS weighted_score
        FROM evaluation_thematic_criteria_safeguard
        GROUP BY evaluation_id
    ) etc_safeguard ON etc_safeguard.evaluation_id = ev.id
    WHERE ev.status IN (\'SUBMITTED\', \'REVISED\')
';

$organizations = [];
$organizationEvaluatorDetails = [];
if ($totalEvaluators > 0) {
    $organizationsStmt = $pdo->prepare(
        'SELECT
            o.id AS organization_id,
            o.name AS organization_name,
            COUNT(e.id) AS total_evaluations,
            SUM(CASE WHEN e.forced_weighted_total_score IS NOT NULL THEN 1 ELSE 0 END) AS forced_evaluations,
            COALESCE(SUM(es.total_overall_score), 0) AS total_score,
            COALESCE(
                COALESCE(SUM(es.total_overall_score), 0) / NULLIF(COUNT(DISTINCT e.evaluator_id), 0),
                0
            ) AS average_score
        FROM application a
        JOIN organization o ON a.organization_id = o.id
        LEFT JOIN evaluation e
            ON e.application_id = a.id
            AND e.status IN (\'SUBMITTED\', \'REVISED\')
            AND EXISTS (
                SELECT 1
                FROM call_for_proposal_evaluator cfe
                WHERE cfe.call_for_proposal_id = a.call_for_proposal_id
                  AND cfe.evaluator_user_id = e.evaluator_id
            )
        LEFT JOIN (' . $evaluationScoreSubquery . ') es ON es.evaluation_id = e.id
        WHERE a.call_for_proposal_id = :call_id
        GROUP BY o.id, o.name
        HAVING COUNT(e.id) = COUNT(DISTINCT a.id) * :total_evaluators
        ORDER BY average_score DESC, organization_name ASC'
    );

    $organizationsStmt->execute([
        ':call_id' => $callId,
        ':total_evaluators' => $totalEvaluators,
    ]);
    $organizations = $organizationsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($organizations)) {
        $organizationIds = array_map(
            static fn (array $organization): int => (int) $organization['organization_id'],
            $organizations
        );
        $organizationIdLookup = array_fill_keys($organizationIds, true);

        $organizationEvaluatorDetailsStmt = $pdo->prepare(
            'SELECT
                o.id AS organization_id,
                e.evaluator_id,
                CONCAT(u.last_name, \' \', u.first_name) AS evaluator_name,
                COALESCE(SUM(es.total_overall_score), 0) AS evaluator_total_score
            FROM application a
            JOIN organization o ON a.organization_id = o.id
            JOIN evaluation e
                ON e.application_id = a.id
                AND e.status IN (\'SUBMITTED\', \'REVISED\')
                AND EXISTS (
                    SELECT 1
                    FROM call_for_proposal_evaluator cfe
                    WHERE cfe.call_for_proposal_id = a.call_for_proposal_id
                      AND cfe.evaluator_user_id = e.evaluator_id
                )
            JOIN user u ON u.id = e.evaluator_id
            LEFT JOIN (' . $evaluationScoreSubquery . ') es ON es.evaluation_id = e.id
            WHERE a.call_for_proposal_id = :call_id
            GROUP BY o.id, e.evaluator_id, u.last_name, u.first_name
            ORDER BY o.id ASC, u.last_name ASC, u.first_name ASC'
        );
        $organizationEvaluatorDetailsStmt->execute([':call_id' => $callId]);
        $organizationEvaluatorDetailsRows = $organizationEvaluatorDetailsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($organizationEvaluatorDetailsRows as $row) {
            $organizationId = (int) $row['organization_id'];
            if (!isset($organizationIdLookup[$organizationId])) {
                continue;
            }

            if (!isset($organizationEvaluatorDetails[$organizationId])) {
                $organizationEvaluatorDetails[$organizationId] = [];
            }

            $organizationEvaluatorDetails[$organizationId][] = [
                'evaluator_name' => (string) $row['evaluator_name'],
                'evaluator_total_score' => (float) $row['evaluator_total_score'],
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Graduatoria - <?php echo htmlspecialchars($call['title']); ?></title>
</head>
<body class="management-page">
    <?php include 'header.php'; ?>
    <main>
        <div class="hero">
            <div class="title">
                <h1>Graduatoria - <?php echo htmlspecialchars($call['title']); ?></h1>
            </div>

            <div class="content-container">
                <div class="content">
                    <div class="button-container">
                        <a href="index.php?open_gestione=1" class="page-button back-button">Indietro</a>
                        <a class="page-button" href="call_for_proposals.php">Torna ai bandi</a>
                    </div>

                    <?php if ($totalEvaluators === 0): ?>
                        <p>Non ci sono valutatori assegnati a questo bando. Aggiungi almeno un valutatore abilitato per visualizzare i risultati.</p>
                    <?php elseif (empty($organizations)): ?>
                        <p>Non sono ancora disponibili risultati per questo bando. Verifica che tutte le valutazioni siano state completate.</p>
                    <?php else: ?>
                        <div class="users-table-container">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>Posizione</th>
                                        <th>Ente</th>
                                        <th>Valutazioni ricevute</th>
                                        <th>Valutazioni forzate</th>
                                        <th>Punteggio medio</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $position = 1; ?>
                                    <?php foreach ($organizations as $organization): ?>
                                        <?php
                                            $organizationId = (int) $organization['organization_id'];
                                            $detailRowId = 'organization-detail-' . $organizationId;
                                            $organizationDetailRows = $organizationEvaluatorDetails[$organizationId] ?? [];
                                            $organizationAverageScore = number_format((float) $organization['average_score'], 2, ',', '.');
                                            $organizationEvaluatorCount = count($organizationDetailRows);
                                        ?>
                                        <tr>
                                            <td><?php echo $position++; ?></td>
                                            <td><?php echo htmlspecialchars($organization['organization_name']); ?></td>
                                            <td><?php echo htmlspecialchars((string) $organization['total_evaluations']); ?></td>
                                            <td><?php echo htmlspecialchars((string) $organization['forced_evaluations']); ?></td>
                                            <td><?php echo htmlspecialchars($organizationAverageScore); ?></td>
                                            <td>
                                                <div class="actions-cell actions-cell--single-row">
                                                    <button
                                                        type="button"
                                                        class="page-button secondary-button ranking-detail-toggle"
                                                        data-detail-row-id="<?php echo htmlspecialchars($detailRowId); ?>"
                                                        aria-controls="<?php echo htmlspecialchars($detailRowId); ?>"
                                                        aria-expanded="false"
                                                    >
                                                        Visualizza dettaglio
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr id="<?php echo htmlspecialchars($detailRowId); ?>" hidden>
                                            <td colspan="6">
                                                <div class="ranking-detail-panel" aria-live="polite">
                                                    <p class="ranking-detail-panel__meta">
                                                        <strong><?php echo htmlspecialchars($organization['organization_name']); ?></strong>
                                                        <span aria-hidden="true"> | </span>
                                                        Valutatori: <strong><?php echo htmlspecialchars((string) $organizationEvaluatorCount); ?></strong>
                                                    </p>

                                                    <?php if (empty($organizationDetailRows)): ?>
                                                        <p class="ranking-detail-empty">Non ci sono valutazioni da mostrare per questo ente.</p>
                                                    <?php else: ?>
                                                        <div class="ranking-detail-table-wrap">
                                                            <table class="ranking-detail-table">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Valutatore</th>
                                                                        <th>Punteggio totale assegnato</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($organizationDetailRows as $detailRow): ?>
                                                                        <tr>
                                                                            <td><?php echo htmlspecialchars($detailRow['evaluator_name']); ?></td>
                                                                            <td><?php echo htmlspecialchars(number_format((float) $detailRow['evaluator_total_score'], 2, ',', '.')); ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleButtons = document.querySelectorAll('.ranking-detail-toggle');
            const defaultOpenText = 'Visualizza dettaglio';
            const defaultCloseText = 'Nascondi dettaglio';

            const closeDetailRow = function (button, row) {
                row.setAttribute('hidden', 'hidden');
                button.setAttribute('aria-expanded', 'false');
                button.textContent = defaultOpenText;
            };

            const closeAllRowsExcept = function (currentTargetId) {
                toggleButtons.forEach(function (otherButton) {
                    const otherTargetId = otherButton.getAttribute('data-detail-row-id');
                    if (!otherTargetId || otherTargetId === currentTargetId) {
                        return;
                    }

                    const otherRow = document.getElementById(otherTargetId);
                    if (!otherRow || otherRow.hasAttribute('hidden')) {
                        return;
                    }

                    closeDetailRow(otherButton, otherRow);
                });
            };

            toggleButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetId = button.getAttribute('data-detail-row-id');
                    if (!targetId) {
                        return;
                    }

                    const detailRow = document.getElementById(targetId);
                    if (!detailRow) {
                        return;
                    }

                    const shouldExpand = detailRow.hasAttribute('hidden');
                    if (shouldExpand) {
                        closeAllRowsExcept(targetId);
                        detailRow.removeAttribute('hidden');
                        button.setAttribute('aria-expanded', 'true');
                        button.textContent = defaultCloseText;
                        return;
                    }

                    closeDetailRow(button, detailRow);
                });
            });
        });
    </script>
</body>
</html>
