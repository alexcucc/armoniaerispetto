<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';

$rolePermissionManager = new RolePermissionManager($pdo);

if (
    !isset($_SESSION['user_id'])
    || !$rolePermissionManager->userHasPermission(
        $_SESSION['user_id'],
        RolePermissionManager::$PERMISSIONS['EVALUATION_VIEW']
    )
) {
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

$totalEvaluatorsStmt = $pdo->query('SELECT COUNT(*) FROM evaluator');
$totalEvaluators = (int) $totalEvaluatorsStmt->fetchColumn();

$organizations = [];
if ($totalEvaluators > 0) {
    $organizationsStmt = $pdo->prepare(
        'SELECT
            o.id AS organization_id,
            o.name AS organization_name,
            COUNT(DISTINCT a.id) AS applications_count,
            COUNT(e.id) AS total_evaluations,
            COALESCE(SUM(es.total_overall_score), 0) AS total_score
        FROM application a
        JOIN organization o ON a.organization_id = o.id
        LEFT JOIN evaluation e ON e.application_id = a.id
        LEFT JOIN (
            SELECT
                ev.id AS evaluation_id,
                COALESCE(efp.weighted_score, 0)
                + COALESCE(ep.weighted_score, 0)
                + COALESCE(epe.weighted_score, 0)
                + COALESCE(eq.weighted_score, 0)
                + COALESCE(etc_cohab.weighted_score, 0)
                + COALESCE(etc_community.weighted_score, 0)
                + COALESCE(etc_culture.weighted_score, 0)
                + COALESCE(etc_repopulation.weighted_score, 0)
                + COALESCE(etc_safeguard.weighted_score, 0) AS total_overall_score
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
        ) es ON es.evaluation_id = e.id
        WHERE a.call_for_proposal_id = :call_id
        GROUP BY o.id, o.name
        HAVING COUNT(e.id) = COUNT(DISTINCT a.id) * :total_evaluators
        ORDER BY total_score DESC, organization_name ASC'
    );

    $organizationsStmt->execute([
        ':call_id' => $callId,
        ':total_evaluators' => $totalEvaluators,
    ]);
    $organizations = $organizationsStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Graduatoria - <?php echo htmlspecialchars($call['title']); ?></title>
</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <div class="hero">
            <div class="title">
                <h1>Graduatoria - <?php echo htmlspecialchars($call['title']); ?></h1>
            </div>

            <div class="content-container">
                <div class="content">
                    <div class="button-container">
                        <a href="javascript:history.back()" class="page-button back-button">Indietro</a>
                        <a class="page-button" href="call_for_proposals.php">Torna ai bandi</a>
                    </div>

                    <?php if ($totalEvaluators === 0): ?>
                        <p>Non ci sono valutatori registrati. Aggiungi almeno un valutatore per visualizzare i risultati.</p>
                    <?php elseif (empty($organizations)): ?>
                        <p>Non sono ancora disponibili risultati per questo bando. Verifica che tutte le valutazioni siano state completate.</p>
                    <?php else: ?>
                        <div class="users-table-container">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>Posizione</th>
                                        <th>Ente</th>
                                        <th>Risposte al bando presentate</th>
                                        <th>Valutazioni ricevute</th>
                                        <th>Punteggio totale</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $position = 1; ?>
                                    <?php foreach ($organizations as $organization): ?>
                                        <tr>
                                            <td><?php echo $position++; ?></td>
                                            <td><?php echo htmlspecialchars($organization['organization_name']); ?></td>
                                            <td><?php echo htmlspecialchars((string) $organization['applications_count']); ?></td>
                                            <td><?php echo htmlspecialchars((string) $organization['total_evaluations']); ?></td>
                                            <td><?php echo htmlspecialchars(number_format((float) $organization['total_score'], 0, ',', '.')); ?></td>
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
</body>
</html>
