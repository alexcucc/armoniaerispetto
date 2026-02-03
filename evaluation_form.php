<?php 
  session_start();
  if (!isset($_SESSION['user_id'])) {
      header("Location: login.php");
      exit;
  }
  if (!isset($_GET['application_id'])) {
    exit("Error: application_id not set.");
  }
  $application_id = intval($_GET['application_id']);

  include_once 'db/common-db.php';
  include_once 'RolePermissionManager.php';
  $rolePermissionManager = new RolePermissionManager($pdo);
  if ($rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['EVALUATION_CREATE']) === false) {
      header("Location: index.php");
      exit;
  }
  
  // Query to fetch the organization name of the proponent
  $stmt = $pdo->prepare(
      "SELECT o.name AS organization_name, a.status, c.status AS call_status FROM application a "
      . "LEFT JOIN organization o ON a.organization_id = o.id "
      . "JOIN call_for_proposal c ON a.call_for_proposal_id = c.id "
      . "WHERE a.id = :application_id"
  );
  $stmt->execute([':application_id' => $application_id]);
  $applicationInfo = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$applicationInfo) {
      $_SESSION['evaluation_error'] = 'Risposta al bando non trovata.';
      header('Location: evaluations.php');
      exit;
  }

  if (($applicationInfo['call_status'] ?? null) === 'CLOSED') {
      $_SESSION['evaluation_error'] = 'Il bando è chiuso e non è più possibile valutare.';
      header('Location: evaluations.php');
      exit;
  }

  if (($applicationInfo['status'] ?? '') !== 'FINAL_VALIDATION') {
      $_SESSION['evaluation_error'] = 'È possibile valutare solo le risposte in stato "Convalida in definitiva".';
      header('Location: evaluations.php');
      exit;
  }

  $entity_name = $applicationInfo['organization_name'] ?? '';
  if ($entity_name === '') {
      $entity_name = 'Soggetto proponente';
  }

  $sectionDefinitions = [
      'proposing_entity' => [
          'table'  => 'evaluation_proposing_entity',
          'fields' => [
              'general_information_score',
              'experience_score',
              'organizational_capacity_score',
              'policy_score',
              'budget_score',
              'purpose_and_local_involvement_score',
              'partnership_and_visibility_score',
          ],
      ],
      'project' => [
          'table'  => 'evaluation_project',
          'fields' => [
              'needs_identification_and_problem_analysis_score',
              'adherence_to_statuary_purposes_score',
              'social_weight_score',
              'objectives_score',
              'expected_results_score',
              'activity_score',
              'local_purpose_score',
              'partnership_and_relations_with_local_authorities_score',
              'synergies_and_design_inefficiencies_score',
              'communication_and_visibility_score',
          ],
      ],
      'financial_plan' => [
          'table'  => 'evaluation_financial_plan',
          'fields' => [
              'completeness_and_clarity_of_budget_score',
              'consistency_with_objectives_score',
              'cofinancing_score',
              'flexibility_score',
          ],
      ],
      'qualitative_elements' => [
          'table'  => 'evaluation_qualitative_elements',
          'fields' => [
              'impact_score',
              'relevance_score',
              'congruity_score',
              'innovation_score',
              'rigor_and_scientific_validity_score',
              'replicability_and_scalability_score',
              'cohabitation_evidence_score',
              'research_and_university_partnership_score',
          ],
      ],
      'thematic_repopulation' => [
          'table'  => 'evaluation_thematic_criteria_repopulation',
          'fields' => [
              'habitat_score',
              'threat_mitigation_strategy_score',
              'local_community_involvement_score',
              'multidisciplinary_sustainability_score',
          ],
      ],
      'thematic_safeguard' => [
          'table'  => 'evaluation_thematic_criteria_safeguard',
          'fields' => [
              'systemic_approach_score',
              'advocacy_and_legal_strengthening_score',
              'habitat_safeguard_score',
              'reservers_development_participation_score',
              'crucial_species_activities_score',
              'multistakeholder_involvement_score',
              'multidisciplinary_sustainability_score',
          ],
      ],
      'thematic_cohabitation' => [
          'table'  => 'evaluation_thematic_criteria_cohabitation',
          'fields' => [
              'risk_reduction_strategy_score',
              'biodiversity_protection_and_animal_integrity_score',
              'local_community_involvement_score',
              'circular_economy_development_score',
              'multidisciplinary_sustainability_score',
          ],
      ],
      'thematic_community_support' => [
          'table'  => 'evaluation_thematic_criteria_community_support',
          'fields' => [
              'systemic_development_score',
              'social_discrimination_fighting_score',
              'habitat_protection_score',
              'multistakeholder_involvement_score',
              'multidisciplinary_sustainability_score',
          ],
      ],
      'thematic_culture_education' => [
          'table'  => 'evaluation_thematic_criteria_culture_education_awareness',
          'fields' => [
              'dissemination_tools_score',
              'advocacy_and_legal_strengthening_score',
              'innovation_score',
              'multistakeholder_involvement_score',
              'multidisciplinary_sustainability_score',
          ],
      ],
  ];

  // Pesature allineate al calcolo complessivo in call_for_proposal_results.php
  $criterionWeights = [
      'proposing_entity' => [
          'general_information_score'                    => 2,
          'experience_score'                            => 4,
          'organizational_capacity_score'               => 4,
          'policy_score'                                => 4,
          'budget_score'                                => 3,
          'purpose_and_local_involvement_score'         => 4,
          'partnership_and_visibility_score'            => 4,
      ],
      'project' => [
          'needs_identification_and_problem_analysis_score'       => 3,
          'adherence_to_statuary_purposes_score'                  => 3,
          'social_weight_score'                                   => 2,
          'objectives_score'                                      => 2,
          'expected_results_score'                                => 2,
          'activity_score'                                        => 3,
          'local_purpose_score'                                   => 2,
          'partnership_and_relations_with_local_authorities_score'=> 2,
          'synergies_and_design_inefficiencies_score'             => 3,
          'communication_and_visibility_score'                    => 3,
      ],
      'financial_plan' => [
          'completeness_and_clarity_of_budget_score' => 3,
          'consistency_with_objectives_score'        => 3,
          'cofinancing_score'                        => 2,
          'flexibility_score'                        => 2,
      ],
      'qualitative_elements' => [
          'impact_score'                           => 5,
          'relevance_score'                        => 6,
          'congruity_score'                        => 4,
          'innovation_score'                       => 3,
          'rigor_and_scientific_validity_score'    => 6,
          'replicability_and_scalability_score'    => 4,
          'cohabitation_evidence_score'            => 6,
          'research_and_university_partnership_score' => 6,
      ],
  ];

  // Pesi applicati al punteggio totale delle sezioni tematiche
  $sectionWeightMultipliers = [
      'thematic_repopulation'      => 35,
      'thematic_safeguard'         => 35,
      'thematic_cohabitation'      => 20,
      'thematic_community_support' => 9,
      'thematic_culture_education' => 10,
  ];

  $evaluationData = [];
  foreach ($sectionDefinitions as $sectionKey => $definition) {
      $evaluationData[$sectionKey] = array_fill_keys($definition['fields'], null);
  }

  $existingEvaluationId = null;
  $existingEvaluationStatus = null;

  $existingEvaluationStmt = $pdo->prepare(
      'SELECT id, status FROM evaluation WHERE application_id = :application_id AND evaluator_id = :evaluator_id LIMIT 1'
  );
  $existingEvaluationStmt->execute([
      ':application_id' => $application_id,
      ':evaluator_id' => $_SESSION['user_id'],
  ]);
  $existingEvaluation = $existingEvaluationStmt->fetch(PDO::FETCH_ASSOC) ?: null;

  if ($existingEvaluation !== null) {
      $existingEvaluationId = (int) $existingEvaluation['id'];
      $existingEvaluationStatus = $existingEvaluation['status'];

      if ($existingEvaluationStatus === 'SUBMITTED') {
          $_SESSION['evaluation_error'] = 'La valutazione è già stata inviata e non può essere modificata.';
          header('Location: evaluations.php');
          exit;
      }

      foreach ($sectionDefinitions as $sectionKey => $definition) {
          $columns = implode(', ', $definition['fields']);
          $sectionStmt = $pdo->prepare("SELECT {$columns} FROM {$definition['table']} WHERE evaluation_id = :evaluation_id LIMIT 1");
          $sectionStmt->execute([':evaluation_id' => $existingEvaluationId]);
          $sectionData = $sectionStmt->fetch(PDO::FETCH_ASSOC);
          if ($sectionData) {
              foreach ($definition['fields'] as $fieldName) {
                  if (array_key_exists($fieldName, $sectionData)) {
                      $rawValue = $sectionData[$fieldName];
                      if ($rawValue === null) {
                          continue;
                      }

                      $score = (int) $rawValue;
                      if ($score < 1) {
                          continue;
                      }

                      $evaluationData[$sectionKey][$fieldName] = $score;
                  }
              }
          }
      }
  }

  function renderScoreInput(string $name, string $ariaLabel, ?int $selected = null): void
  {
      $sanitizedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
      $inputId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) . '_score_input';
      $inputIdAttr = htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8');
      $ariaLabelAttr = htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8');

      $valueAttr = '';
      if ($selected !== null) {
          $valueAttr = ' value="' . (int) $selected . '"';
      }

      echo '<input type="number" class="score-input" id="' . $inputIdAttr . '" name="' . $sanitizedName . '" aria-label="' . $ariaLabelAttr . '" min="1" max="10" step="1"' . $valueAttr . '>';
  }

  function renderWeightBadge(?int $weight): void
  {
      if ($weight === null) {
          return;
      }

      echo '<span class="criteria-weight-badge" aria-label="Peso ' . htmlspecialchars((string) $weight, ENT_QUOTES, 'UTF-8') . '">Peso: ' . (int) $weight . '</span>';
  }

  function renderCriterionWeightBadge(array $weights, string $sectionKey, string $fieldName): void
  {
      $weight = $weights[$sectionKey][$fieldName] ?? null;
      renderWeightBadge($weight);
  }

  function renderSectionWeightBadge(array $sectionWeights, string $sectionKey): void
  {
      $weight = $sectionWeights[$sectionKey] ?? null;
      if ($weight === null) {
          return;
      }

      echo '<span class="section-weight-badge">Peso sezione: ' . (int) $weight . '</span>';
  }
  ?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php'; ?>
    <title>Invia la Valutazione</title>
    <style>
      .total-score-overlay {
        background-color: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 0.65rem;
        padding: 0.7rem 0.85rem;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
        font-weight: 600;
        font-size: 0.95rem;
        color: #1f2937;
        text-align: center;
      }

      .total-score-overlay__group {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
        padding: 0.15rem 0;
      }

      .total-score-overlay__label {
        display: block;
        font-size: 0.82rem;
        font-weight: 500;
        color: #4b5563;
        margin-bottom: 0.25rem;
        letter-spacing: 0.02em;
      }

      .total-score-overlay__value {
        font-size: 1.4rem;
        color: #0c4a6e;
      }

      @media (max-width: 768px) {
        .total-score-overlay {
          width: 100%;
          max-width: none;
        }
      }

      .evaluation-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
      }

      .evaluation-shell {
        display: flex;
        flex-direction: column;
      }

      .evaluation-layout {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 1.5rem;
        align-items: start;
        flex: 1 1 auto;
        overflow: hidden;
      }

      .evaluation-content {
        min-width: 0;
        max-height: calc(100vh - 14rem);
        overflow-y: auto;
        padding-right: 0.35rem;
      }

      .evaluation-sidebar {
        position: sticky;
        top: 6.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        align-items: stretch;
        min-width: 240px;
        z-index: 1100;
      }

      .contact-form {
        padding-bottom: 1.5rem;
      }

      .evaluation-step {
        display: none;
      }

      .evaluation-step.active {
        display: block;
      }

      .evaluation-actions {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #e5e7eb;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.16);
        padding: 0.85rem 0.95rem;
        display: flex;
        flex-direction: column;
        flex-wrap: nowrap;
        gap: 0.65rem;
        justify-content: flex-start;
        align-items: stretch;
        border-radius: 0.75rem;
      }

      .evaluation-actions__nav,
      .evaluation-actions__main {
        display: flex;
        align-items: stretch;
        gap: 0.55rem;
        flex-direction: column;
        justify-content: flex-start;
      }

      .evaluation-actions__nav .page-button {
        background: #0ea5e9;
        color: #fff;
        border: none;
        padding: 0.55rem 0.85rem;
        border-radius: 0.4rem;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.92rem;
      }

      .evaluation-actions__nav .page-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }

      .evaluation-actions__back-link {
        color: #0f172a;
        text-decoration: none;
        font-weight: 600;
      }

      .evaluation-actions .submit-btn,
      .evaluation-actions .page-button {
        width: 100%;
        min-width: 8rem;
        padding: 0.55rem 0.9rem;
        font-size: 0.95rem;
      }

      .evaluation-actions .back-button {
        width: auto;
        min-width: 7rem;
        padding: 0.55rem 0.9rem;
        font-size: 0.95rem;
        align-self: flex-start;
        width: -webkit-fill-available;
      }

      .evaluation-actions .submit-btn {
        border-radius: 0.4rem;
      }

      @media (max-width: 640px) {
        .evaluation-layout {
          grid-template-columns: 1fr;
          overflow: visible;
        }

        .evaluation-sidebar {
          position: relative;
          top: auto;
        }

        .evaluation-content {
          max-height: none;
          overflow: visible;
        }
      }

      .score-input {
        width: 100%;
        max-width: 140px;
        padding: 0.55rem 0.65rem;
        border-radius: 0.4rem;
        border: 1px solid #cbd5e1;
        font-weight: 600;
      }

      .score-input:focus {
        outline: 2px solid #0ea5e9;
        outline-offset: 1px;
        border-color: #0ea5e9;
      }

      .criteria-weight-badge,
      .section-weight-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.5rem;
        margin-left: 0.4rem;
        border-radius: 9999px;
        background: #ecfeff;
        color: #0ea5e9;
        font-weight: 700;
        font-size: 0.78rem;
        border: 1px solid #bae6fd;
      }

      .section-weight-badge {
        margin-left: 0.6rem;
      }

      .criteria-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
      }

      .criteria-row__label {
        flex: 1 1 320px;
      }

      .criteria-row__input {
        flex: 0 0 150px;
        display: flex;
        align-items: center;
      }

      .criteria-row__weight {
        flex: 0 0 110px;
        display: flex;
        align-items: center;
      }

      .criteria-label {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin: 0;
      }

      .criteria-info-toggle {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        border-radius: 0.4rem;
        padding: 0.35rem 0.65rem;
        font-weight: 700;
        cursor: pointer;
      }

      .criteria-info-toggle:hover {
        background: #bae6fd;
      }

      .criteria-info-content {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.65rem 0.8rem;
        font-size: 0.92rem;
        color: #0f172a;
        margin-top: 0.45rem;
      }

      .criteria-info-text ul {
        margin: 0.3rem 0 0.2rem 1.2rem;
      }

      .evaluation-step h3:focus {
        outline: 2px solid #0ea5e9;
        outline-offset: 3px;
        border-radius: 0.25rem;
      }
    </style>
  </head>
  <body>
    <?php include 'header.php'; ?>
    <main>
      <div class="contact-form-container" style="margin-top:2em;">
        <div class="evaluation-shell">
        <form id="evaluation-form" class="contact-form" action="evaluation_handler.php" method="post">
          <!-- Hidden fields -->
          <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
          <input type="hidden" name="evaluator_id" value="<?php echo $_SESSION['user_id']; ?>">
          <?php if ($existingEvaluationId !== null): ?>
            <input type="hidden" name="evaluation_id" value="<?php echo $existingEvaluationId; ?>">
          <?php endif; ?>
          <div class="evaluation-header">
            <div>
              <h2>Valutazione <?php echo htmlspecialchars($entity_name); ?></h2>
              <p class="form-note">Tutte le valutazioni utilizzano una scala da 1 (livello minimo) a 10 (livello massimo). Inserisci il punteggio desiderato nel campo numerico accanto a ciascun criterio.</p>
              <?php if ($existingEvaluationId !== null): ?>
                <p class="form-note"><strong>Stato corrente:</strong> bozza modificabile.</p>
              <?php endif; ?>
            </div>
          </div>

          <div class="evaluation-layout">
            <div class="evaluation-content">
          <div class="evaluation-step active" data-step-index="0">
            <h3>Soggetto Proponente</h3>
          <div class="form-group">
            <label class="form-label required">Informazioni Generali <?php renderCriterionWeightBadge($criterionWeights, 'proposing_entity', 'general_information_score'); ?></label>
            <?php renderScoreInput('proposing_entity[general_information_score]', 'Informazioni Generali', $evaluationData['proposing_entity']['general_information_score']); ?>
            <small class="form-text">
              <ul>
                <li>Ha un'identità chiara?</li>
                <li>È in linea con il suo status giuridico?</li>
                <li>La mission è in linea con le attività realizzate?</li>
                <li>La sua reputazione e il suo impatto sono chiare e dimostrabili?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Esperienza <?php renderCriterionWeightBadge($criterionWeights, 'proposing_entity', 'experience_score'); ?></label>
            <?php renderScoreInput('proposing_entity[experience_score]', 'Esperienza', $evaluationData['proposing_entity']['experience_score']); ?>
            <small class="form-text">
              <p class="form-note">Utilizza la scala 1-10 considerando questi riferimenti:</p>
              <ul>
                <li><strong>1-3:</strong> Enti di recente costituzione con struttura in fase di sviluppo e scarsa esperienza gestionale.</li>
                <li><strong>4-6:</strong> Organizzazioni consolidate con prime esperienze significative e crescente riconoscibilità.</li>
                <li><strong>7-8:</strong> Enti con solida esperienza, struttura organizzativa definita e collaborazioni stabili.</li>
                <li><strong>9-10:</strong> Lunga tradizione, ampia rete di collaborazioni e forte riconoscimento istituzionale e sociale.</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Modalità organizzative, gestionali e di assunzione delle decisioni <?php renderCriterionWeightBadge($criterionWeights, 'proposing_entity', 'organizational_capacity_score'); ?></label>
            <?php renderScoreInput('proposing_entity[organizational_capacity_score]', 'Modalità organizzative, gestionali e di assunzione delle decisioni', $evaluationData['proposing_entity']['organizational_capacity_score']); ?>
            <small>
              <ul>
                <li>Che tipo di governance ha l'ente?</li>
                <li>Quanto incide il volontariato?</li>
                <li>Valorizza il personale locale?</li>
                <li>Si tratta di una grande o piccola organizzazione?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Policy (welfare aziendale, gender equality, child safeguarding, politiche ambientali ecc.) <?php renderCriterionWeightBadge($criterionWeights, 'proposing_entity', 'policy_score'); ?></label>
            <?php renderScoreInput('proposing_entity[policy_score]', 'Policy (welfare aziendale, gender equality, child safeguarding, politiche ambientali ecc.)', $evaluationData['proposing_entity']['policy_score']); ?>
            <small>
              <ul>
                <li>Esiste un codice etico?</li>
                <li>Esistono regolamenti interni?</li>
                <li>Politiche di inclusione?</li>
                <li>Politiche ambientali?</li>
                <li>Meccanismi di whistleblowing?</li>
                <li>Procedure di autovalutazione?</li>
                <li>L'ente risulta trasparente?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Bilancio <?php renderCriterionWeightBadge($criterionWeights, 'proposing_entity', 'budget_score'); ?></label>
            <?php renderScoreInput('proposing_entity[budget_score]', 'Bilancio', $evaluationData['proposing_entity']['budget_score']); ?>
            <small>
              <ul>
                <li>Come incide la raccolta fondi?</li>
                <li>Sono dotati di una strategia funding mix?</li>
                <li>Hanno debiti difficili da sostenere?</li>
                <li>Il bilancio è in crescita o in decrescita negli ultimi anni?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Finalità e coinvolgimento locale <?php renderCriterionWeightBadge($criterionWeights, 'proposing_entity', 'purpose_and_local_involvement_score'); ?></label>
            <?php renderScoreInput('proposing_entity[purpose_and_local_involvement_score]', 'Finalità e coinvolgimento locale', $evaluationData['proposing_entity']['purpose_and_local_involvement_score']); ?>
            <small>
              <ul>
                <li>Le attività e la mission sono in linea con un corretto sviluppo locale?</li>
                <li>Ha partnership locali?</li>
                <li>Vi è evidenza di una buona reputazione a livello locale?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Partnership e visibilità <?php renderCriterionWeightBadge($criterionWeights, 'proposing_entity', 'partnership_and_visibility_score'); ?></label>
            <?php renderScoreInput('proposing_entity[partnership_and_visibility_score]', 'Partnership e visibilità', $evaluationData['proposing_entity']['partnership_and_visibility_score']); ?>
            <small>
              <ul>
                <li>Fa parte di network riconosciuti?</li>
                <li>Ha vinto dei premi?</li>
                <li>Ha partnership attive con università, istituzioni, aziende, altri ETS?</li>
              </ul>
            </small>
          </div>

          </div>

          <div class="evaluation-step" data-step-index="1">
            <h3>Progetto</h3>
          <div class="form-group">
            <label class="form-label required">Identificazione dei bisogni e analisi dei problemi <?php renderCriterionWeightBadge($criterionWeights, 'project', 'needs_identification_and_problem_analysis_score'); ?></label>
            <?php renderScoreInput('project[needs_identification_and_problem_analysis_score]', 'Identificazione dei bisogni e analisi dei problemi', $evaluationData['project']['needs_identification_and_problem_analysis_score']); ?>
            <small>
              <ul>
                <li>L'analisi è completa, sufficientemente dettagliata e coerente?</li>
                <li>Le fonti sono autorevoli?</li>
                <li>Risulta effettivamente rispondente a un bisogno emerso?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Aderenza alle finalità statutarie <?php renderCriterionWeightBadge($criterionWeights, 'project', 'adherence_to_statuary_purposes_score'); ?></label>
            <?php renderScoreInput('project[adherence_to_statuary_purposes_score]', 'Aderenza alle finalità statutarie', $evaluationData['project']['adherence_to_statuary_purposes_score']); ?>
            <small>
              <ul>
                <li>Il progetto è in linea con le finalità statutarie dell'ente?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Peso sociale (es. aiuto a fragili per cura animali) <?php renderCriterionWeightBadge($criterionWeights, 'project', 'social_weight_score'); ?></label>
            <?php renderScoreInput('project[social_weight_score]', 'Peso sociale (es. aiuto a fragili per cura animali)', $evaluationData['project']['social_weight_score']); ?>
            <small>
              <ul>
                <li>Il progetto ha un impatto sociale positivo?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Obiettivi <?php renderCriterionWeightBadge($criterionWeights, 'project', 'objectives_score'); ?></label>
            <?php renderScoreInput('project[objectives_score]', 'Obiettivi', $evaluationData['project']['objectives_score']); ?>
            <small>
              <ul>
                <li>Sono coerenti?</li>
                <li>Sono realizzabili?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Risultati attesi <?php renderCriterionWeightBadge($criterionWeights, 'project', 'expected_results_score'); ?></label>
            <?php renderScoreInput('project[expected_results_score]', 'Risultati attesi', $evaluationData['project']['expected_results_score']); ?>
            <small>
              <ul>
                <li>Sono concreti?</li>
                <li>Sono Misurabili?</li>
                <li>Sono Ambiziosi?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Attività <?php renderCriterionWeightBadge($criterionWeights, 'project', 'activity_score'); ?></label>
            <?php renderScoreInput('project[activity_score]', 'Attività', $evaluationData['project']['activity_score']); ?>
            <small>
              <ul>
                <li>Sono coerenti?</li>
                <li>Sono Chiare?</li>
                <li>Sono Sufficientemente dettagliate?</li>
                <li>Sono Realizzabili?</li>
                <li>Sono Efficaci?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Finalità locale <?php renderCriterionWeightBadge($criterionWeights, 'project', 'local_purpose_score'); ?></label>
            <?php renderScoreInput('project[local_purpose_score]', 'Finalità locale', $evaluationData['project']['local_purpose_score']); ?>
            <small>
              <ul>
                <li>Il progetto ha una chiara finalità locale?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Partenariato e rapporti con autorità locali/nazionali <?php renderCriterionWeightBadge($criterionWeights, 'project', 'partnership_and_relations_with_local_authorities_score'); ?></label>
            <?php renderScoreInput('project[partnership_and_relations_with_local_authorities_score]', 'Partenariato e rapporti con autorità locali/nazionali', $evaluationData['project']['partnership_and_relations_with_local_authorities_score']); ?>
            <small>
              <ul>
                <li>Il/i partner è/sono un valore aggiunto?</li>
                <li>Completano e/o arricchiscono il progetto?</li>
                <li>Permettono di raggiungere un maggior numero di beneficiari?</li>
                <li>I rapporti con le autorità locali sono sviluppati e fruttuosi?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Sinergie e inefficienze progettuali <?php renderCriterionWeightBadge($criterionWeights, 'project', 'synergies_and_design_inefficiencies_score'); ?></label>
            <?php renderScoreInput('project[synergies_and_design_inefficiencies_score]', 'Sinergie e inefficienze progettuali', $evaluationData['project']['synergies_and_design_inefficiencies_score']); ?>
            <small>
              <ul>
                <li>È un progetto che condivide obiettivi, stakeholder, risorse, metodologie o deliverable con altri progetti precedenti o in corso?</li>
                <li>Presenta sinergie o sovrapposizioni nei risultati attesi con altri progetti?</li>
                <li>Risulta una duplicazione eccessiva di attività, obiettivi o output?</li>
                <li>Ripete processi già implementati altrove?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Comunicazione e visibilità <?php renderCriterionWeightBadge($criterionWeights, 'project', 'communication_and_visibility_score'); ?></label>
            <?php renderScoreInput('project[communication_and_visibility_score]', 'Comunicazione e visibilità', $evaluationData['project']['communication_and_visibility_score']); ?>
            <small>
              <ul>
                <li>La proposta è in linea con le aspettative?</li>
                <li>Valorizza il progetto?</li>
                <li>Valorizza la collaborazione Ente - Fondazione AR?</li>
              </ul>
            </small>
          </div>

          </div>

          <div class="evaluation-step" data-step-index="2">
            <h3>Piano Finanziario</h3>
          <div class="form-group">
            <label class="form-label required">Completezza e chiarezza del budget <?php renderCriterionWeightBadge($criterionWeights, 'financial_plan', 'completeness_and_clarity_of_budget_score'); ?></label>
            <?php renderScoreInput('financial_plan[completeness_and_clarity_of_budget_score]', 'Completezza e chiarezza del budget', $evaluationData['financial_plan']['completeness_and_clarity_of_budget_score']); ?>
            <small>
              <ul>
                <li>Il budget è chiaro e completo in tutte le sue parti?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Coerenza con obiettivi, risultati, impatto e cronogramma <?php renderCriterionWeightBadge($criterionWeights, 'financial_plan', 'consistency_with_objectives_score'); ?></label>
            <?php renderScoreInput('financial_plan[consistency_with_objectives_score]', 'Coerenza con obiettivi, risultati, impatto e cronogramma', $evaluationData['financial_plan']['consistency_with_objectives_score']); ?>
            <small>
              <ul>
                <li>Il budget risulta coerente con gli obiettivi e i risultati del Progetto?</li>
                <li>Permette il rispetto del cronogramma e il raggiungimento dell'impatto atteso?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Cofinanziamento <?php renderCriterionWeightBadge($criterionWeights, 'financial_plan', 'cofinancing_score'); ?></label>
            <?php renderScoreInput('financial_plan[cofinancing_score]', 'Cofinanziamento', $evaluationData['financial_plan']['cofinancing_score']); ?>
            <small>
              <ul>
                <li>La percentuale del cofinanziamento è adeguata?</li>
                <li>Le fonti sono diversificate e autorevoli?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Flessibilità <?php renderCriterionWeightBadge($criterionWeights, 'financial_plan', 'flexibility_score'); ?></label>
            <?php renderScoreInput('financial_plan[flexibility_score]', 'Flessibilità', $evaluationData['financial_plan']['flexibility_score']); ?>
            <small>
              <ul>
                <li>Il budget è in grado di far fronte a eventuali cambiamenti di sviluppo progettuale senza variazioni onerose?</li>
              </ul>
            </small>
          </div>

          </div>

          <div class="evaluation-step" data-step-index="3">
            <h3>Elementi Qualitativi</h3>
          <div class="form-group">
            <label class="form-label required">L'impatto e gli effetti di più ampio e lungo termine prodotti dall’iniziativa in ragione del contesto di intervento <?php renderCriterionWeightBadge($criterionWeights, 'qualitative_elements', 'impact_score'); ?></label>
            <?php renderScoreInput('qualitative_elements[impact_score]', 'L\'impatto e gli effetti di più ampio e lungo termine prodotti dall’iniziativa in ragione del contesto di intervento', $evaluationData['qualitative_elements']['impact_score']); ?>
            <small>
              <ul>
                <li>Il progetto ha la potenzialità di influire in maniera sistemica nel lungo periodo?</li>
                <li>Sono valutati i rischi di un "impatto negativo"?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Pertinenza del progetto rispetto ai bisogni e criticità specifiche del Paese, della Regione, del settore d’intervento, della sinergia con altri programmi <?php renderCriterionWeightBadge($criterionWeights, 'qualitative_elements', 'relevance_score'); ?></label>
            <?php renderScoreInput('qualitative_elements[relevance_score]', 'Pertinenza del progetto rispetto ai bisogni e criticità specifiche del Paese, della Regione, del settore d’intervento, della sinergia con altri programmi', $evaluationData['qualitative_elements']['relevance_score']); ?>
            <small>
              <ul>
                <li>Il progetto è in linea con i bisogni prioritari dell'area d'intervento?</li>
                <li>È rilevante rispetto alle criticità territoriali?</li>
                <li>È coerente con le politiche pubbliche e i relativi piani di sviluppo? È supportato dalle istituzioni?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Congruità del Progetto e della capacità operativa di realizzarla da parte del Soggetto Proponente <?php renderCriterionWeightBadge($criterionWeights, 'qualitative_elements', 'congruity_score'); ?></label>
            <?php renderScoreInput('qualitative_elements[congruity_score]', 'Congruità del Progetto e della capacità operativa di realizzarla da parte del Soggetto Proponente', $evaluationData['qualitative_elements']['congruity_score']); ?>
            <small>
              <ul>
                <li>Il progetto è coerente con le capacità e le risorse del soggetto proponente?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Innovatività del Progetto <?php renderCriterionWeightBadge($criterionWeights, 'qualitative_elements', 'innovation_score'); ?></label>
            <?php renderScoreInput('qualitative_elements[innovation_score]', 'Innovatività del Progetto', $evaluationData['qualitative_elements']['innovation_score']); ?>
            <small>
              <ul>
                <li>È previsto l'utilizzo di tecnologie o metodi e approcci nuovi per il raggiungimento degli obiettivi dichiarati?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Rigore e validità scientifica <?php renderCriterionWeightBadge($criterionWeights, 'qualitative_elements', 'rigor_and_scientific_validity_score'); ?></label>
            <?php renderScoreInput('qualitative_elements[rigor_and_scientific_validity_score]', 'Rigore e validità scientifica', $evaluationData['qualitative_elements']['rigor_and_scientific_validity_score']); ?>
            <small>
              <ul>
                <li>La proposta è basata su evidenze scientifiche, opportunamente spiegate e con le fonti?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Replicabilità e scalabilità <?php renderCriterionWeightBadge($criterionWeights, 'qualitative_elements', 'replicability_and_scalability_score'); ?></label>
            <?php renderScoreInput('qualitative_elements[replicability_and_scalability_score]', 'Replicabilità e scalabilità', $evaluationData['qualitative_elements']['replicability_and_scalability_score']); ?>
            <small>
              <ul>
                <li>Il progetto può essere adattato e applicato in altri contesti?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Evidenza dello sviluppo progettuale in linea con un'equilibrata coabitazione uomo-animale che preveda adeguate misure di mitigazione ove necessario <?php renderCriterionWeightBadge($criterionWeights, 'qualitative_elements', 'cohabitation_evidence_score'); ?></label>
            <?php renderScoreInput('qualitative_elements[cohabitation_evidence_score]', 'Evidenza dello sviluppo progettuale in linea con un\'equilibrata coabitazione uomo-animale che preveda adeguate misure di mitigazione ove necessario', $evaluationData['qualitative_elements']['cohabitation_evidence_score']); ?>
            <small>
              <ul>
                <li>Il progetto ha valutato la compatibilità con una coabitazione uomo-animale?</li>
                <li>Sono previste azioni di tutela e di mitigazione dei rischi?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Partecipazione enti di ricerca e università <?php renderCriterionWeightBadge($criterionWeights, 'qualitative_elements', 'research_and_university_partnership_score'); ?></label>
            <?php renderScoreInput('qualitative_elements[research_and_university_partnership_score]', 'Partecipazione enti di ricerca e università', $evaluationData['qualitative_elements']['research_and_university_partnership_score']); ?>
            <small>
              <ul>
                <li>È prevista la partecipazione di enti di ricerca?</li>
                <li>È/sono un valore aggiunto?</li>
                <li>Completano e/o arricchiscono il progetto?</li>
              </ul>
            </small>
          </div>

          </div>

          <div class="evaluation-step" data-step-index="4">
            <h3>Criteri Tematici - Ripopolamento <?php renderSectionWeightBadge($sectionWeightMultipliers, 'thematic_repopulation'); ?></h3>
          <div class="form-group">
            <label class="form-label required">Habitat dell'intervento</label>
            <?php renderScoreInput('thematic_repopulation[habitat_score]', 'Habitat dell\'intervento', $evaluationData['thematic_repopulation']['habitat_score']); ?>
            <small>
              <ul>
                <li>Il progetto considera le caratteristiche ecologiche dell'habitat?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Strategia di mitigazione delle minacce</label>
            <?php renderScoreInput('thematic_repopulation[threat_mitigation_strategy_score]', 'Strategia di mitigazione delle minacce', $evaluationData['thematic_repopulation']['threat_mitigation_strategy_score']); ?>
            <small>
              <ul>
                <li>Il progetto prevede misure per mitigare le minacce all'habitat?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Coinvolgimento comunità locale</label>
            <?php renderScoreInput('thematic_repopulation[local_community_involvement_score]', 'Coinvolgimento comunità locale', $evaluationData['thematic_repopulation']['local_community_involvement_score']); ?>
            <small>
              <ul>
                <li>Il progetto coinvolge attivamente la comunità locale?</li>
              </ul>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)</label>
            <?php renderScoreInput('thematic_repopulation[multidisciplinary_sustainability_score]', 'Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)', $evaluationData['thematic_repopulation']['multidisciplinary_sustainability_score']); ?>
            <small>
              <ul>
                <li>Il progetto considera le interconnessioni tra diversi ambiti (sociale, economico, ambientale)?</li>
              </ul>
            </small>
          </div>

          </div>

          <div class="evaluation-step" data-step-index="5">
            <h3>Criteri Tematici - Salvaguardia <?php renderSectionWeightBadge($sectionWeightMultipliers, 'thematic_safeguard'); ?></h3>
            <div class="form-group">
              <label class="form-label required">Approccio sistemico (prevenzione, contrasto, riabilitazione)</label>
              <?php renderScoreInput('thematic_safeguard[systemic_approach_score]', 'Approccio sistemico (prevenzione, contrasto, riabilitazione)', $evaluationData['thematic_safeguard']['systemic_approach_score']); ?>
              <small>
                <ul>
                  <li>Il progetto adotta un approccio sistemico per affrontare le problematiche ambientali?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Advocacy e rafforzamento giuridico</label>
              <?php renderScoreInput('thematic_safeguard[advocacy_and_legal_strengthening_score]', 'Advocacy e rafforzamento giuridico', $evaluationData['thematic_safeguard']['advocacy_and_legal_strengthening_score']); ?>
              <small>
                <ul>
                  <li>Il progetto promuove l'advocacy e il rafforzamento giuridico per la tutela dell'ambiente?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Salvaguardia dell'habitat (flora e fauna)</label>
              <?php renderScoreInput('thematic_safeguard[habitat_safeguard_score]', 'Salvaguardia dell\'habitat (flora e fauna)', $evaluationData['thematic_safeguard']['habitat_safeguard_score']); ?>
              <small>
                <ul>
                  <li>Il progetto contribuisce alla salvaguardia degli habitat naturali (flora e fauna)?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Compartecipazione a sviluppo di riserve, oasi, CRAS ecc.</label>
              <?php renderScoreInput('thematic_safeguard[reservers_development_participation_score]', 'Compartecipazione a sviluppo di riserve, oasi, CRAS ecc.', $evaluationData['thematic_safeguard']['reservers_development_participation_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede la compartecipazione allo sviluppo di riserve, oasi, CRAS, ecc.?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Attività dedicate a specie cruciali e/o a rischio estinzione</label>
              <?php renderScoreInput('thematic_safeguard[crucial_species_activities_score]', 'Attività dedicate a specie cruciali e/o a rischio estinzione', $evaluationData['thematic_safeguard']['crucial_species_activities_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede attività dedicate a specie cruciali e/o a rischio estinzione?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Coinvolgimento multistakeholder (comunità locale, istituzioni, privato sociale)</label>
              <?php renderScoreInput('thematic_safeguard[multistakeholder_involvement_score]', 'Coinvolgimento multistakeholder (comunità locale, istituzioni, privato sociale)', $evaluationData['thematic_safeguard']['multistakeholder_involvement_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede il coinvolgimento di più attori (comunità locale, istituzioni, privato sociale)?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)</label>
              <?php renderScoreInput('thematic_safeguard[multidisciplinary_sustainability_score]', 'Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)', $evaluationData['thematic_safeguard']['multidisciplinary_sustainability_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede un approccio multidisciplinare per garantire la sostenibilità (istituzionale, ambientale, culturale, economica)?</li>
                </ul>
              </small>
            </div>
          </div>

          <div class="evaluation-step" data-step-index="6">
            <h3>Criteri Tematici - Coabitazione <?php renderSectionWeightBadge($sectionWeightMultipliers, 'thematic_cohabitation'); ?></h3>
            <div class="form-group">
              <label class="form-label required">Strategia di riduzione dei rischi</label>
              <?php renderScoreInput('thematic_cohabitation[risk_reduction_strategy_score]', 'Strategia di riduzione dei rischi', $evaluationData['thematic_cohabitation']['risk_reduction_strategy_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede una strategia di riduzione dei rischi?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Tutela della biodiversità e integrazione della presenza animale  alle attività umane (es Rwanda)</label>
              <?php renderScoreInput('thematic_cohabitation[biodiversity_protection_and_animal_integrity_score]', 'Tutela della biodiversità e integrazione della presenza animale alle attività umane (es Rwanda)', $evaluationData['thematic_cohabitation']['biodiversity_protection_and_animal_integrity_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede attività dedicate a specie cruciali e/o a rischio estinzione?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Coinvolgimento comunità locale</label>
              <?php renderScoreInput('thematic_cohabitation[local_community_involvement_score]', 'Coinvolgimento comunità locale', $evaluationData['thematic_cohabitation']['local_community_involvement_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede il coinvolgimento della comunità locale?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Sostegno allo sviluppo di un'economia circolare per il sostentamento locale</label>
              <?php renderScoreInput('thematic_cohabitation[circular_economy_development_score]', 'Sostegno allo sviluppo di un\'economia circolare per il sostentamento locale', $evaluationData['thematic_cohabitation']['circular_economy_development_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede il sostegno allo sviluppo di un'economia circolare per il sostentamento locale?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)</label>
              <?php renderScoreInput('thematic_cohabitation[multidisciplinary_sustainability_score]', 'Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)', $evaluationData['thematic_cohabitation']['multidisciplinary_sustainability_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede un approccio multidisciplinare per la sostenibilità?</li>
                </ul>
              </small>
            </div>
          </div>
          <div class="evaluation-step" data-step-index="7">
            <h3>Criteri Tematici - Supporto di comunità <?php renderSectionWeightBadge($sectionWeightMultipliers, 'thematic_community_support'); ?></h3>
            <div class="form-group">
              <label class="form-label required">Sviluppo sistemico  (educativo, economico, produttivo) di capacity buliding</label>
              <?php renderScoreInput('thematic_community_support[systemic_development_score]', 'Sviluppo sistemico (educativo, economico, produttivo) di capacity buliding', $evaluationData['thematic_community_support']['systemic_development_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede un approccio sistemico per lo sviluppo della comunità?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Contrasto alle discriminazione sociali</label>
              <?php renderScoreInput('thematic_community_support[social_discrimination_fighting_score]', 'Contrasto alle discriminazione sociali', $evaluationData['thematic_community_support']['social_discrimination_fighting_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede misure per contrastare le discriminazioni sociali?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Salvaguardia dell'habitat</label>
              <?php renderScoreInput('thematic_community_support[habitat_protection_score]', 'Salvaguardia dell\'habitat', $evaluationData['thematic_community_support']['habitat_protection_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede misure per la salvaguardia dell'habitat?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Coinvolgimento multistakeholder (comunità locale, istituzioni, privato sociale)</label>
              <?php renderScoreInput('thematic_community_support[multistakeholder_involvement_score]', 'Coinvolgimento multistakeholder (comunità locale, istituzioni, privato sociale)', $evaluationData['thematic_community_support']['multistakeholder_involvement_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede un coinvolgimento attivo dei diversi attori sociali?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)</label>
              <?php renderScoreInput('thematic_community_support[multidisciplinary_sustainability_score]', 'Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)', $evaluationData['thematic_community_support']['multidisciplinary_sustainability_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede un approccio multidisciplinare per la sostenibilità?</li>
                </ul>
              </small>
            </div>
          </div>
          <div class="evaluation-step" data-step-index="8">
            <h3>Criteri Tematici - Cultura - Educazione - Sensibilizzazione <?php renderSectionWeightBadge($sectionWeightMultipliers, 'thematic_culture_education'); ?></h3>
            <div class="form-group">
              <label class="form-label required">Strumenti di disseminazione</label>
              <?php renderScoreInput('thematic_culture_education[dissemination_tools_score]', 'Strumenti di disseminazione', $evaluationData['thematic_culture_education']['dissemination_tools_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede strumenti di disseminazione efficaci?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Advocacy e rafforzamento giuridico</label>
              <?php renderScoreInput('thematic_culture_education[advocacy_and_legal_strengthening_score]', 'Advocacy e rafforzamento giuridico', $evaluationData['thematic_culture_education']['advocacy_and_legal_strengthening_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede attività di advocacy e rafforzamento giuridico?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Grado di innovazione</label>
              <?php renderScoreInput('thematic_culture_education[innovation_score]', 'Grado di innovazione', $evaluationData['thematic_culture_education']['innovation_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede elementi innovativi?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Coinvolgimento multistakeholder (cittadinanza, istituzioni, centri di ricerca, agenzie educative)</label>
              <?php renderScoreInput('thematic_culture_education[multistakeholder_involvement_score]', 'Coinvolgimento multistakeholder (cittadinanza, istituzioni, centri di ricerca, agenzie educative)', $evaluationData['thematic_culture_education']['multistakeholder_involvement_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede un coinvolgimento attivo dei diversi attori sociali?</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)</label>
              <?php renderScoreInput('thematic_culture_education[multidisciplinary_sustainability_score]', 'Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)', $evaluationData['thematic_culture_education']['multidisciplinary_sustainability_score']); ?>
              <small>
                <ul>
                  <li>Il progetto prevede un approccio multidisciplinare per la sostenibilità?</li>
                </ul>
              </small>
            </div>
          </div>
        </div>
          <div class="evaluation-sidebar" aria-label="Punteggi e azioni di navigazione">
          <div class="total-score-overlay" role="status" aria-live="polite">
            <div class="total-score-overlay__group">
              <span class="total-score-overlay__label">Totale punteggio</span>
              <span class="total-score-overlay__value" id="total-score-value">0</span>
            </div>
            <div class="total-score-overlay__group">
              <span class="total-score-overlay__label">Totale sezione corrente</span>
              <span class="total-score-overlay__value" id="section-score-value">0</span>
            </div>
          </div>
          <div class="evaluation-actions" aria-label="Navigazione e azioni di salvataggio">
            <div class="evaluation-actions__nav">
              <button type="button" class="page-button" id="previous-step-button">Sezione precedente</button>
              <button type="button" class="page-button" id="next-step-button">Sezione successiva</button>
            </div>
            <div class="evaluation-actions__main">
              <a href="evaluations.php" class="page-button back-button evaluation-actions__back-link">Indietro</a>
              <button class="submit-btn secondary-button" type="submit" name="action" value="save">Salva bozza</button>
              <button class="submit-btn" type="submit" name="action" value="submit">Invia Valutazione</button>
            </div>
          </div>
            </div>
          </div>
        </form>
        </div>
      </div>
    </main>
    <?php include 'footer.php'; ?>
    <div id="evaluation-success-modal" class="evaluation-success-modal" style="display: none;">
      <div class="evaluation-success-modal-content">
        <div class="evaluation-success-modal-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2>Valutazione inviata!</h2>
        <p>Grazie per la tua valutazione. Verrai reindirizzato tra pochi secondi.</p>
        <button id="close-evaluation-modal" class="submit-btn">Vai subito</button>
      </div>
    </div>
    <script>
      (function () {
        const form = document.getElementById('evaluation-form');
        const modal = document.getElementById('evaluation-success-modal');
        const closeButton = document.getElementById('close-evaluation-modal');
        const totalScoreElement = document.getElementById('total-score-value');
        const sectionScoreElement = document.getElementById('section-score-value');
        const stepElements = Array.from(document.querySelectorAll('.evaluation-step'));
        const evaluationContent = document.querySelector('.evaluation-content');
        const nextStepButton = document.getElementById('next-step-button');
        const previousStepButton = document.getElementById('previous-step-button');
        const backLink = document.querySelector('.evaluation-actions__back-link');
        const criterionWeights = <?php echo json_encode($criterionWeights, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        const sectionWeightMultipliers = <?php echo json_encode($sectionWeightMultipliers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        const sectionKeys = [
          'proposing_entity',
          'project',
          'financial_plan',
          'qualitative_elements',
          'thematic_repopulation',
          'thematic_safeguard',
          'thematic_cohabitation',
          'thematic_community_support',
          'thematic_culture_education',
        ];
        let activeStepIndex = 0;
        let hasUnsavedChanges = false;

        const markUnsavedChanges = () => {
          hasUnsavedChanges = true;
        };

        const resetUnsavedChanges = () => {
          hasUnsavedChanges = false;
        };

        const handleBackNavigation = (event) => {
          if (!hasUnsavedChanges) {
            return;
          }

          const shouldLeave = window.confirm('Hai modificato la valutazione. Uscendo senza salvare perderai le modifiche. Vuoi continuare?');

          if (!shouldLeave) {
            event.preventDefault();
          }
        };

        const clampScore = (value) => {
          if (Number.isNaN(value)) {
            return null;
          }

          return Math.min(Math.max(value, 1), 10);
        };

        const enforceInputBounds = (input) => {
          if (!input) {
            return;
          }

          const rawValue = Number.parseInt(input.value, 10);

          if (Number.isNaN(rawValue)) {
            input.value = '';
            return;
          }

          const clampedValue = clampScore(rawValue);

          if (clampedValue === null) {
            input.value = '';
            return;
          }

          if (clampedValue !== rawValue) {
            input.value = clampedValue;
          }
        };

        const formatScore = (score) => {
          if (!Number.isFinite(score)) {
            return '0';
          }

          return Number.isInteger(score) ? score.toString() : score.toFixed(2);
        };

        const getSectionKey = (index) => sectionKeys[index] || null;

        const extractFieldName = (inputName) => {
          if (typeof inputName !== 'string') {
            return null;
          }

          const match = inputName.match(/\[([^\]]+)\]$/);
          return match ? match[1] : null;
        };

        const calculateWeightedSectionScore = (sectionIndex) => {
          const sectionElement = stepElements[sectionIndex];

          if (!sectionElement) {
            return 0;
          }

          const inputs = Array.from(sectionElement.querySelectorAll('input.score-input'));
          const sectionKey = getSectionKey(sectionIndex);
          const multiplier = sectionWeightMultipliers[sectionKey] ?? 1;
          const scoreScale = 10;

          let sectionTotal = 0;

          inputs.forEach((input) => {
            const value = clampScore(Number.parseInt(input.value, 10));
            if (value === null) {
              return;
            }

            const fieldName = extractFieldName(input.name);
            const weight = sectionKey && fieldName && criterionWeights[sectionKey]
              ? (criterionWeights[sectionKey][fieldName] ?? 1)
              : 1;

            sectionTotal += (value * weight) / scoreScale;
          });

          return sectionTotal * multiplier;
        };

        function calculateTotalScore() {
          if (!form || !totalScoreElement) {
            return;
          }

          let total = 0;
          stepElements.forEach((_, index) => {
            total += calculateWeightedSectionScore(index);
          });

          totalScoreElement.textContent = formatScore(total);
        }

        function calculateSectionScore() {
          if (!form || !sectionScoreElement || stepElements.length === 0) {
            return;
          }

          const currentStep = stepElements[activeStepIndex];
          if (!currentStep) {
            sectionScoreElement.textContent = '0';
            return;
          }

          const weightedSectionScore = calculateWeightedSectionScore(activeStepIndex);

          sectionScoreElement.textContent = formatScore(weightedSectionScore);
        }

        const transformCriteriaLayout = () => {
          const groups = Array.from(document.querySelectorAll('.form-group'));

          groups.forEach((group) => {
            const label = group.querySelector(':scope > label');
            const input = group.querySelector(':scope > input.score-input');
            const description = group.querySelector(':scope > small');
            const weightBadge = label ? label.querySelector('.criteria-weight-badge') : null;

            if (!label || !input) {
              return;
            }

            const row = document.createElement('div');
            row.className = 'criteria-row';

            const labelWrapper = document.createElement('div');
            labelWrapper.className = 'criteria-row__label';
            label.classList.add('criteria-label');
            if (weightBadge) {
              weightBadge.remove();
            }
            labelWrapper.appendChild(label);

            const inputWrapper = document.createElement('div');
            inputWrapper.className = 'criteria-row__input';
            inputWrapper.appendChild(input);

            const weightWrapper = document.createElement('div');
            weightWrapper.className = 'criteria-row__weight';
            if (weightBadge) {
              weightWrapper.appendChild(weightBadge);
            }

            row.appendChild(labelWrapper);
            row.appendChild(inputWrapper);
            row.appendChild(weightWrapper);

            const hasDescription = Boolean(description);
            let infoContent = null;
            if (hasDescription && description) {
              description.classList.remove('criteria-inline-info');
              description.classList.add('criteria-info-text');

              const infoButton = document.createElement('button');
              infoButton.type = 'button';
              infoButton.className = 'criteria-info-toggle';
              infoButton.textContent = 'Info';
              infoButton.setAttribute('aria-expanded', 'false');

              infoContent = document.createElement('div');
              infoContent.className = 'criteria-info-content';
              infoContent.hidden = true;
              infoContent.appendChild(description);

              infoButton.addEventListener('click', () => {
                const isHidden = infoContent?.hidden ?? true;
                if (infoContent) {
                  infoContent.hidden = !isHidden;
                }

                infoButton.setAttribute('aria-expanded', String(!isHidden));
              });

              row.appendChild(infoButton);
            }

            group.innerHTML = '';
            group.appendChild(row);

            if (infoContent) {
              group.appendChild(infoContent);
            }
          });
        };

        const scrollToStep = (stepElement) => {
          if (!stepElement) {
            return;
          }

          const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';

          if (evaluationContent && evaluationContent.scrollHeight > evaluationContent.clientHeight) {
            const containerRect = evaluationContent.getBoundingClientRect();
            const stepRect = stepElement.getBoundingClientRect();
            const topPosition = (stepRect.top - containerRect.top) + evaluationContent.scrollTop;
            const top = Math.max(0, topPosition);

            if (typeof evaluationContent.scrollTo === 'function') {
              evaluationContent.scrollTo({ top, behavior });
            } else {
              evaluationContent.scrollTop = top;
            }

            return;
          }

          const offset = 90;
          const topPosition = stepElement.getBoundingClientRect().top + window.scrollY - offset;

          window.scrollTo({
            top: topPosition < 0 ? 0 : topPosition,
            behavior
          });
        };

        const focusStepHeading = (stepElement) => {
          if (!stepElement) {
            return;
          }

          const heading = stepElement.querySelector('h3');
          if (!heading) {
            return;
          }

          if (!heading.hasAttribute('tabindex')) {
            heading.setAttribute('tabindex', '-1');
          }

          try {
            heading.focus({ preventScroll: true });
          } catch (error) {
            heading.focus();
          }
        };

        const getStepScoreInputs = (stepElement) => {
          if (!stepElement) {
            return [];
          }

          return Array.from(stepElement.querySelectorAll('input.score-input'));
        };

        const isScoreValueValid = (input) => {
          if (!input) {
            return true;
          }

          const rawValue = (input.value || '').trim();
          if (rawValue === '') {
            return false;
          }

          const numericValue = Number(rawValue);
          return Number.isFinite(numericValue)
            && Number.isInteger(numericValue)
            && numericValue >= 1
            && numericValue <= 10;
        };

        const isStepComplete = (stepElement) => {
          const inputs = getStepScoreInputs(stepElement);
          if (inputs.length === 0) {
            return true;
          }

          return inputs.every((input) => isScoreValueValid(input));
        };

        const updateNavigationState = () => {
          if (!nextStepButton || !previousStepButton || stepElements.length === 0) {
            return;
          }

          previousStepButton.disabled = activeStepIndex <= 0;
          const isLastStep = activeStepIndex >= stepElements.length - 1;
          const currentStep = stepElements[activeStepIndex] || null;
          nextStepButton.disabled = isLastStep || !isStepComplete(currentStep);
        };

        const isStepValid = (stepElement) => {
          if (!stepElement) {
            return true;
          }

          const inputs = getStepScoreInputs(stepElement);
          for (const input of inputs) {
            if (isScoreValueValid(input)) {
              input.setCustomValidity('');
              continue;
            }

            const message = (input.value || '').trim() === ''
              ? 'Compila tutti i punteggi della sezione per continuare.'
              : 'Inserisci un punteggio valido (1-10) per continuare.';

            input.setCustomValidity(message);
            input.reportValidity();
            input.setCustomValidity('');
            return false;
          }

          return true;
        };

        const setActiveStep = (targetIndex, { forceScroll = true } = {}) => {
          if (stepElements.length === 0 || targetIndex < 0 || targetIndex >= stepElements.length) {
            return;
          }

          activeStepIndex = targetIndex;

          stepElements.forEach((step, index) => {
            step.classList.toggle('active', index === activeStepIndex);
          });

          updateNavigationState();

          calculateSectionScore();

          if (forceScroll) {
            const activeStepElement = stepElements[activeStepIndex];
            focusStepHeading(activeStepElement);
            scrollToStep(activeStepElement);
          }
        };

        const attemptStepChange = (targetIndex) => {
          if (targetIndex === activeStepIndex) {
            return;
          }

          if (targetIndex > activeStepIndex) {
            const currentStep = stepElements[activeStepIndex];
            if (!isStepValid(currentStep)) {
              return;
            }
          }

          setActiveStep(targetIndex);
        };

        if (nextStepButton) {
          nextStepButton.addEventListener('click', () => attemptStepChange(activeStepIndex + 1));
        }

        if (previousStepButton) {
          previousStepButton.addEventListener('click', () => attemptStepChange(activeStepIndex - 1));
        }

        transformCriteriaLayout();

        if (stepElements.length > 0) {
          setActiveStep(0, { forceScroll: false });
        }

        if (form && totalScoreElement) {
          const formFields = form.querySelectorAll('input, textarea, select');

          formFields.forEach((field) => {
            field.addEventListener('input', markUnsavedChanges);
            field.addEventListener('change', markUnsavedChanges);
          });

          const scoreInputs = form.querySelectorAll('input.score-input');
          scoreInputs.forEach((input) => {
            input.addEventListener('input', () => {
              enforceInputBounds(input);
              calculateTotalScore();
              calculateSectionScore();
              updateNavigationState();
            });

            enforceInputBounds(input);
          });

          calculateTotalScore();
          calculateSectionScore();
        }

        if (backLink) {
          backLink.addEventListener('click', handleBackNavigation);
        }

        form.addEventListener('submit', async function (event) {
          const submitter = event.submitter || null;
          const actionValue = submitter ? submitter.value : null;

          if (actionValue !== 'submit') {
            resetUnsavedChanges();
            return;
          }

          event.preventDefault();

          const formData = new FormData(form);
          if (submitter && submitter.name) {
            formData.set(submitter.name, submitter.value);
          }

          try {
            const actionUrl = form.getAttribute('action') || window.location.href;
            const response = await fetch(actionUrl, {
              method: form.method,
              body: formData,
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            });
            const data = await response.json();

            if (data.success) {
              resetUnsavedChanges();
              modal.style.display = 'block';
              let redirected = false;
              const goHome = () => {
                if (!redirected) {
                  redirected = true;
                  window.location.href = data.redirect || 'evaluations.php';
                }
              };

              closeButton.onclick = goHome;
              setTimeout(goHome, 2500);
            } else {
              alert(data.message || "Errore nell'invio della valutazione.");
            }
          } catch (error) {
            alert('Errore: ' + error);
          }
        });
      })();
    </script>
  </body>
</html>
