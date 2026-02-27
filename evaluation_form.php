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
              'overall_score',
          ],
      ],
      'thematic_safeguard' => [
          'table'  => 'evaluation_thematic_criteria_safeguard',
          'fields' => [
              'overall_score',
          ],
      ],
      'thematic_cohabitation' => [
          'table'  => 'evaluation_thematic_criteria_cohabitation',
          'fields' => [
              'overall_score',
          ],
      ],
      'thematic_community_support' => [
          'table'  => 'evaluation_thematic_criteria_community_support',
          'fields' => [
              'overall_score',
          ],
      ],
      'thematic_culture_education' => [
          'table'  => 'evaluation_thematic_criteria_culture_education_awareness',
          'fields' => [
              'overall_score',
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
                      if ($score < 0) {
                          continue;
                      }

                      $score = max(0, min(10, $score));

                      $evaluationData[$sectionKey][$fieldName] = $score;
                  }
              }
          }
      }
  }

  $evaluationStatusLabels = [
      'SUBMITTED' => 'Inviata',
      'REVISED' => 'Revisionata',
      'DRAFT' => 'Bozza',
      'PENDING' => 'Da iniziare',
  ];
  $evaluationStatusNotes = [
      'SUBMITTED' => 'Valutazione inviata: puoi modificarla e reinviarla se necessario.',
      'REVISED' => 'Valutazione revisionata dopo un invio precedente.',
      'DRAFT' => 'Bozza salvata: puoi continuare a modificare e inviare quando vuoi.',
      'PENDING' => 'Valutazione non ancora iniziata: compila i punteggi e salva la bozza.',
  ];
  $displayStatusKey = $existingEvaluationId !== null ? ($existingEvaluationStatus ?? 'DRAFT') : 'PENDING';
  if (!isset($evaluationStatusLabels[$displayStatusKey])) {
      $displayStatusKey = 'PENDING';
  }
  $displayStatusLabel = $evaluationStatusLabels[$displayStatusKey] ?? $displayStatusKey;
  $displayStatusNote = $evaluationStatusNotes[$displayStatusKey] ?? 'Compila la valutazione e salva la bozza prima di inviarla.';
  $displayStatusClass = strtolower($displayStatusKey);

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

      echo '<input type="number" class="score-input" id="' . $inputIdAttr . '" name="' . $sanitizedName . '" aria-label="' . $ariaLabelAttr . '" min="0" max="10" step="1"' . $valueAttr . '>';
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
      if ($weight === null) {
          return;
      }

      renderWeightBadge($weight);
      echo '<span class="criteria-weighted-score" aria-live="polite">Voto pesato: 0</span>';
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
      .contact-form-container.evaluation-page {
        margin: 0.3rem 0 0.2rem;
        padding: 0.25rem 0.72rem 0.3rem;
      }

      .total-score-overlay {
        background-color: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 0.65rem;
        padding: 0.45rem 0.62rem;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
        font-weight: 600;
        font-size: 0.84rem;
        color: #1f2937;
        text-align: center;
      }

      .total-score-overlay__group {
        display: flex;
        flex-direction: column;
        gap: 0.05rem;
        padding: 0.08rem 0;
      }

      .total-score-overlay__group--thematic {
        margin-top: 0.06rem;
        padding-top: 0.25rem;
        border-top: 1px dashed #e5e7eb;
      }

      .total-score-overlay__label {
        display: block;
        font-size: 0.74rem;
        font-weight: 500;
        color: #4b5563;
        margin-bottom: 0.08rem;
        letter-spacing: 0.02em;
      }

      .total-score-overlay__value-row {
        display: inline-flex;
        align-items: baseline;
        justify-content: center;
        gap: 0.28rem;
      }

      .total-score-overlay__value {
        font-size: 1.1rem;
        color: #0c4a6e;
      }

      .total-score-overlay__separator {
        font-size: 0.95rem;
        color: #64748b;
      }

      .total-score-overlay__max {
        font-size: 0.94rem;
        color: #0f172a;
      }

      .evaluation-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.12rem;
      }

      .evaluation-header__main {
        grid-column: 2;
        min-width: 0;
        text-align: center;
      }

      .evaluation-header__title-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        flex-wrap: wrap;
      }

      .evaluation-header h2 {
        margin: 0;
        text-align: center;
        font-size: clamp(0.98rem, 1.45vw, 1.18rem);
        line-height: 1.2;
      }

      .evaluation-subject-name {
        margin: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
        padding: 0.16rem 0.55rem;
        border-radius: 9999px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1e3a8a;
        font-size: 0.84rem;
        font-weight: 700;
        line-height: 1.2;
      }

      .evaluation-status-panel {
        grid-column: 3;
        justify-self: end;
      }

      .evaluation-subject-name strong {
        font-weight: 800;
      }

      .evaluation-header .form-note {
        margin-top: 0.18rem;
        font-size: 0.76rem;
        line-height: 1.22;
        color: #64748b;
      }

      .evaluation-shell {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
      }

      .evaluation-layout {
        --evaluation-sidebar-width: 200px;
        --evaluation-layout-gap: 0.75rem;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(188px, var(--evaluation-sidebar-width));
        gap: var(--evaluation-layout-gap);
        align-items: start;
        flex: 1 1 auto;
        overflow: hidden;
      }

      .evaluation-content {
        min-width: 0;
        max-height: calc(100vh - 7.6rem);
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 0.18rem;
      }

      .evaluation-sidebar {
        position: sticky;
        top: calc(var(--header-height, 70px) + 0.2rem);
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        align-items: stretch;
        min-width: 188px;
        z-index: 1100;
      }

      .contact-form {
        padding-bottom: 0.25rem;
      }

      .evaluation-step {
        display: none;
      }

      .evaluation-step.active {
        display: block;
      }

      .evaluation-step h3 {
        margin: 0 0 0.22rem;
        transform: translateX(calc((var(--evaluation-sidebar-width) + var(--evaluation-layout-gap)) / 2));
        text-align: center;
        line-height: 1.2;
        font-size: 0.95rem;
      }

      .evaluation-step--proponent h3 {
        text-align: center;
        font-size: 1.02rem;
        color: #0f172a;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.45rem;
        padding: 0.25rem 0.45rem;
      }

      .evaluation-step .form-group + .form-group {
        margin-top: 0.3rem;
      }

      .evaluation-actions {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
        padding: 0.42rem 0.5rem;
        display: flex;
        flex-direction: column;
        flex-wrap: nowrap;
        gap: 0.35rem;
        justify-content: flex-start;
        align-items: stretch;
        border-radius: 0.55rem;
      }

      .evaluation-actions__nav,
      .evaluation-actions__main {
        display: flex;
        align-items: stretch;
        gap: 0.28rem;
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

      .evaluation-actions .submit-btn,
      .evaluation-actions .page-button {
        width: 100%;
        min-width: 7rem;
        padding: 0.34rem 0.52rem;
        font-size: 0.8rem;
      }

      .evaluation-actions .submit-btn {
        border-radius: 0.4rem;
      }

      .score-input {
        width: 100%;
        max-width: 72px;
        padding: 0.28rem 0.36rem;
        border-radius: 0.4rem;
        border: 1px solid #cbd5e1;
        font-weight: 600;
        font-size: 0.84rem;
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
        padding: 0.03rem 0.3rem;
        margin-left: 0.22rem;
        border-radius: 9999px;
        background: #ecfeff;
        color: #0ea5e9;
        font-weight: 700;
        font-size: 0.64rem;
        border: 1px solid #bae6fd;
      }

      .criteria-weighted-score {
        display: inline-flex;
        align-items: center;
        padding: 0.03rem 0.3rem;
        margin-left: 0.2rem;
        border-radius: 9999px;
        background: #fef3c7;
        color: #b45309;
        font-weight: 700;
        font-size: 0.64rem;
        border: 1px solid #fde68a;
      }

      .section-weight-badge {
        margin-left: 0.45rem;
      }

      .criteria-row {
        display: flex;
        align-items: flex-start;
        gap: 0.34rem;
        flex-wrap: wrap;
      }

      .criteria-row__label {
        flex: 1 1 220px;
        min-width: 0;
      }

      .criteria-row__input {
        flex: 0 0 74px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
      }

      .criteria-row__weight {
        flex: 1 1 150px;
        display: flex;
        align-items: center;
        gap: 0.18rem;
        flex-wrap: wrap;
      }

      .criteria-label {
        display: inline-flex;
        align-items: flex-start;
        gap: 0.2rem;
        margin: 0;
        line-height: 1.18;
        font-size: 0.8rem;
      }

      .criteria-info-toggle {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        border-radius: 0.4rem;
        padding: 0.18rem 0.42rem;
        font-weight: 700;
        font-size: 0.68rem;
        cursor: pointer;
      }

      .criteria-info-toggle:hover {
        background: #bae6fd;
      }

      .criteria-info-content {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.34rem 0.48rem;
        font-size: 0.75rem;
        color: #0f172a;
        margin-top: 0.22rem;
      }

      .criteria-info-text ul {
        margin: 0.15rem 0 0.1rem 0.9rem;
      }

      .evaluation-step h3:focus {
        outline: 2px solid #0ea5e9;
        outline-offset: 3px;
        border-radius: 0.25rem;
      }

      @media (max-width: 1000px) {
        .evaluation-header {
          grid-template-columns: 1fr;
        }

        .evaluation-header__main,
        .evaluation-status-panel {
          grid-column: 1;
        }

        .evaluation-status-panel {
          justify-self: stretch;
        }

        .evaluation-layout {
          grid-template-columns: 1fr;
          gap: 0.35rem;
          overflow: visible;
        }

        .evaluation-step h3 {
          transform: none;
        }

        .evaluation-sidebar {
          order: -1;
          position: sticky;
          top: calc(var(--header-height, 70px) + 0.2rem);
          min-width: 0;
          z-index: 1200;
        }

        .evaluation-content {
          max-height: calc(100vh - 11.8rem);
          overflow-y: auto;
          padding-right: 0.08rem;
        }

        .evaluation-header .form-note,
        .evaluation-status-panel__note {
          display: none;
        }
      }

      @media (max-height: 820px) and (min-width: 1001px) {
        .evaluation-header .form-note,
        .evaluation-status-panel__note {
          display: none;
        }

        .evaluation-content {
          max-height: calc(100vh - 6.9rem);
        }
      }

      @media (max-width: 768px) {
        .contact-form-container.evaluation-page {
          padding: 0.2rem 0.45rem 0.24rem;
        }

        .evaluation-subject-name {
          max-width: 100%;
        }

        .total-score-overlay {
          width: 100%;
          max-width: none;
        }
      }

      @media (max-width: 640px) {
        .criteria-row__label,
        .criteria-row__input,
        .criteria-row__weight {
          flex: 1 1 100%;
        }

        .criteria-row__input {
          justify-content: flex-start;
        }

        .score-input {
          max-width: 100%;
        }

        .total-score-overlay__value {
          font-size: 1rem;
        }

        .total-score-overlay__max {
          font-size: 0.9rem;
        }

        .evaluation-content {
          max-height: calc(100vh - 12.8rem);
        }
      }
    </style>
  </head>
  <body>
    <?php include 'header.php'; ?>
    <main>
      <div class="contact-form-container evaluation-page">
        <div class="evaluation-shell">
        <div class="button-container">
          <a href="evaluations.php" class="page-button back-button evaluation-actions__back-link">Indietro</a>
        </div>
        <form id="evaluation-form" class="contact-form" action="evaluation_handler.php" method="post">
          <!-- Hidden fields -->
          <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
          <input type="hidden" name="evaluator_id" value="<?php echo $_SESSION['user_id']; ?>">
          <?php if ($existingEvaluationId !== null): ?>
            <input type="hidden" name="evaluation_id" value="<?php echo $existingEvaluationId; ?>">
          <?php endif; ?>
          <div class="evaluation-header">
            <div class="evaluation-header__main">
              <div class="evaluation-header__title-row">
                <h2>Valutazione progetto</h2>
                <p class="evaluation-subject-name">Ente: <strong><?php echo htmlspecialchars($entity_name); ?></strong></p>
              </div>
              <p class="form-note">Tutte le valutazioni utilizzano una scala da 0 (livello minimo) a 10 (livello massimo). Inserisci il punteggio desiderato nel campo numerico accanto a ciascun criterio.</p>
            </div>
            <div class="evaluation-status-panel evaluation-status-panel--<?php echo htmlspecialchars($displayStatusClass); ?>">
              <span class="evaluation-status-panel__label">Stato valutazione</span>
              <span class="evaluation-status-badge evaluation-status-badge--<?php echo htmlspecialchars($displayStatusClass); ?>">
                <?php echo htmlspecialchars($displayStatusLabel); ?>
              </span>
              <p class="evaluation-status-panel__note"><?php echo htmlspecialchars($displayStatusNote); ?></p>
            </div>
          </div>

          <div class="evaluation-layout">
            <div class="evaluation-content">
          <div class="evaluation-step evaluation-step--proponent active" data-step-index="0">
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
              <p class="form-note">Utilizza la scala 0-10 considerando questi riferimenti:</p>
              <ul>
                <li><strong>0-3:</strong> Enti di recente costituzione con struttura in fase di sviluppo e scarsa esperienza gestionale.</li>
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
              <label class="form-label">Voci della sottosezione</label>
              <small>
                <ul>
                  <li>Habitat dell'intervento</li>
                  <li>Strategia di mitigazione delle minacce</li>
                  <li>Coinvolgimento comunità locale</li>
                  <li>Sostenibilità multidisciplinare</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Valutazione complessiva della sottosezione</label>
              <?php renderScoreInput('thematic_repopulation[overall_score]', 'Valutazione complessiva - Ripopolamento', $evaluationData['thematic_repopulation']['overall_score'] ?? null); ?>
            </div>
          </div>

          <div class="evaluation-step" data-step-index="5">
            <h3>Criteri Tematici - Salvaguardia <?php renderSectionWeightBadge($sectionWeightMultipliers, 'thematic_safeguard'); ?></h3>
            <div class="form-group">
              <label class="form-label">Voci della sottosezione</label>
              <small>
                <ul>
                  <li>Approccio sistemico (prevenzione, contrasto, riabilitazione)</li>
                  <li>Advocacy e rafforzamento giuridico</li>
                  <li>Salvaguardia dell'habitat (flora e fauna)</li>
                  <li>Compartecipazione a sviluppo di riserve, oasi, CRAS ecc.</li>
                  <li>Attività dedicate a specie cruciali e/o a rischio estinzione</li>
                  <li>Coinvolgimento multistakeholder</li>
                  <li>Sostenibilità multidisciplinare</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Valutazione complessiva della sottosezione</label>
              <?php renderScoreInput('thematic_safeguard[overall_score]', 'Valutazione complessiva - Salvaguardia', $evaluationData['thematic_safeguard']['overall_score'] ?? null); ?>
            </div>
          </div>

          <div class="evaluation-step" data-step-index="6">
            <h3>Criteri Tematici - Coabitazione <?php renderSectionWeightBadge($sectionWeightMultipliers, 'thematic_cohabitation'); ?></h3>
            <div class="form-group">
              <label class="form-label">Voci della sottosezione</label>
              <small>
                <ul>
                  <li>Strategia di riduzione dei rischi</li>
                  <li>Tutela della biodiversità e integrazione della presenza animale alle attività umane</li>
                  <li>Coinvolgimento comunità locale</li>
                  <li>Sostegno allo sviluppo di un'economia circolare per il sostentamento locale</li>
                  <li>Sostenibilità multidisciplinare</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Valutazione complessiva della sottosezione</label>
              <?php renderScoreInput('thematic_cohabitation[overall_score]', 'Valutazione complessiva - Coabitazione', $evaluationData['thematic_cohabitation']['overall_score'] ?? null); ?>
            </div>
          </div>
          <div class="evaluation-step" data-step-index="7">
            <h3>Criteri Tematici - Supporto di comunità <?php renderSectionWeightBadge($sectionWeightMultipliers, 'thematic_community_support'); ?></h3>
            <div class="form-group">
              <label class="form-label">Voci della sottosezione</label>
              <small>
                <ul>
                  <li>Sviluppo sistemico (educativo, economico, produttivo) di capacity building</li>
                  <li>Contrasto alle discriminazioni sociali</li>
                  <li>Salvaguardia dell'habitat</li>
                  <li>Coinvolgimento multistakeholder</li>
                  <li>Sostenibilità multidisciplinare</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Valutazione complessiva della sottosezione</label>
              <?php renderScoreInput('thematic_community_support[overall_score]', 'Valutazione complessiva - Supporto di comunità', $evaluationData['thematic_community_support']['overall_score'] ?? null); ?>
            </div>
          </div>
          <div class="evaluation-step" data-step-index="8">
            <h3>Criteri Tematici - Cultura - Educazione - Sensibilizzazione <?php renderSectionWeightBadge($sectionWeightMultipliers, 'thematic_culture_education'); ?></h3>
            <div class="form-group">
              <label class="form-label">Voci della sottosezione</label>
              <small>
                <ul>
                  <li>Strumenti di disseminazione</li>
                  <li>Advocacy e rafforzamento giuridico</li>
                  <li>Grado di innovazione</li>
                  <li>Coinvolgimento multistakeholder</li>
                  <li>Sostenibilità multidisciplinare</li>
                </ul>
              </small>
            </div>
            <div class="form-group">
              <label class="form-label required">Valutazione complessiva della sottosezione</label>
              <?php renderScoreInput('thematic_culture_education[overall_score]', 'Valutazione complessiva - Cultura - Educazione - Sensibilizzazione', $evaluationData['thematic_culture_education']['overall_score'] ?? null); ?>
            </div>
          </div>
        </div>
          <div class="evaluation-sidebar" aria-label="Punteggi e azioni di navigazione">
          <div class="total-score-overlay" role="status" aria-live="polite">
            <div class="total-score-overlay__group">
              <span class="total-score-overlay__label">Totale punteggio / Totale max</span>
              <span class="total-score-overlay__value-row">
                <span class="total-score-overlay__value" id="total-score-value">0</span>
                <span class="total-score-overlay__separator">/</span>
                <span class="total-score-overlay__max" id="total-score-max-value">0</span>
              </span>
            </div>
            <div class="total-score-overlay__group">
              <span class="total-score-overlay__label">Totale sezione corrente / Max sezione corrente</span>
              <span class="total-score-overlay__value-row">
                <span class="total-score-overlay__value" id="section-score-value">0</span>
                <span class="total-score-overlay__separator">/</span>
                <span class="total-score-overlay__max" id="section-score-max-value">0</span>
              </span>
            </div>
            <div class="total-score-overlay__group total-score-overlay__group--thematic" id="thematic-score-group" hidden>
              <span class="total-score-overlay__label">Totale criteri tematici / Max criteri tematici</span>
              <span class="total-score-overlay__value-row">
                <span class="total-score-overlay__value" id="thematic-score-value">0</span>
                <span class="total-score-overlay__separator">/</span>
                <span class="total-score-overlay__max" id="thematic-score-max-value">0</span>
              </span>
            </div>
          </div>
          <div class="evaluation-actions" aria-label="Navigazione e azioni di salvataggio">
            <div class="evaluation-actions__nav">
              <button type="button" class="page-button" id="previous-step-button">Sezione precedente</button>
              <button type="button" class="page-button" id="next-step-button">Sezione successiva</button>
            </div>
            <div class="evaluation-actions__main">
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
        const totalScoreMaxElement = document.getElementById('total-score-max-value');
        const sectionScoreElement = document.getElementById('section-score-value');
        const sectionScoreMaxElement = document.getElementById('section-score-max-value');
        const thematicScoreGroupElement = document.getElementById('thematic-score-group');
        const thematicScoreElement = document.getElementById('thematic-score-value');
        const thematicScoreMaxElement = document.getElementById('thematic-score-max-value');
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
        const sectionLabels = {
          proposing_entity: 'Soggetto Proponente',
          project: 'Progetto',
          financial_plan: 'Piano Finanziario',
          qualitative_elements: 'Elementi Qualitativi',
          thematic_repopulation: 'Criteri Tematici - Ripopolamento',
          thematic_safeguard: 'Criteri Tematici - Salvaguardia',
          thematic_cohabitation: 'Criteri Tematici - Coabitazione',
          thematic_community_support: 'Criteri Tematici - Supporto di comunità',
          thematic_culture_education: 'Criteri Tematici - Cultura - Educazione - Sensibilizzazione',
        };
        const thematicDisplayMaxScore = 70;
        const thematicWeightTotal = sectionKeys.reduce((total, sectionKey) => {
          if (typeof sectionKey !== 'string' || !sectionKey.startsWith('thematic_')) {
            return total;
          }

          return total + (sectionWeightMultipliers[sectionKey] ?? 0);
        }, 0);
        const thematicSectionScaleFactor = thematicWeightTotal > 0
          ? thematicDisplayMaxScore / thematicWeightTotal
          : 0;
        const incompleteMessage = 'valutazione incompleta: valutazione non inviabile';
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

          return Math.min(Math.max(value, 0), 10);
        };

        const enforceInputBounds = (input) => {
          if (!input) {
            return;
          }

          const rawValue = (input.value || '').trim();
          if (rawValue === '') {
            input.value = '';
            return;
          }

          if (!/^\d+$/.test(rawValue)) {
            input.value = '';
            return;
          }

          const parsedValue = Number.parseInt(rawValue, 10);
          const clampedValue = clampScore(parsedValue);

          if (clampedValue === null) {
            input.value = '';
            return;
          }

          const normalizedValue = clampedValue.toString();
          if (input.value !== normalizedValue) {
            input.value = normalizedValue;
          }
        };

        const formatScore = (score) => {
          if (!Number.isFinite(score)) {
            return '0';
          }

          return Number.isInteger(score) ? score.toString() : score.toFixed(2);
        };

        const getSectionKey = (index) => sectionKeys[index] || null;
        const isThematicStep = (index) => {
          const sectionKey = getSectionKey(index);
          return typeof sectionKey === 'string' && sectionKey.startsWith('thematic_');
        };

        const extractFieldName = (inputName) => {
          if (typeof inputName !== 'string') {
            return null;
          }

          const match = inputName.match(/\[([^\]]+)\]$/);
          return match ? match[1] : null;
        };

        const extractSectionName = (inputName) => {
          if (typeof inputName !== 'string') {
            return null;
          }

          const match = inputName.match(/^([^\[]+)\[/);
          return match ? match[1] : null;
        };

        const getCriterionWeight = (input) => {
          if (!input) {
            return null;
          }

          const sectionKey = extractSectionName(input.name);
          const fieldName = extractFieldName(input.name);
          if (!sectionKey || !fieldName) {
            return null;
          }

          const sectionWeights = criterionWeights[sectionKey];
          if (!sectionWeights) {
            return null;
          }

          const weight = sectionWeights[fieldName];
          return Number.isFinite(weight) ? weight : null;
        };

        const calculateWeightedCriterionScore = (input) => {
          const weight = getCriterionWeight(input);
          if (weight === null) {
            return null;
          }

          const value = clampScore(Number.parseInt(input.value, 10));
          if (value === null) {
            return 0;
          }

          const scoreScale = 10;
          return (value * weight) / scoreScale;
        };

        const updateCriterionWeightedScore = (input) => {
          if (!input) {
            return;
          }

          const group = input.closest('.form-group');
          if (!group) {
            return;
          }

          const weightedScoreBadge = group.querySelector('.criteria-weighted-score');
          if (!weightedScoreBadge) {
            return;
          }

          const weightedScore = calculateWeightedCriterionScore(input);
          const displayValue = weightedScore === null ? '0' : formatScore(weightedScore);
          weightedScoreBadge.textContent = `Voto pesato: ${displayValue}`;
        };

        const updateAllCriterionWeightedScores = () => {
          const scoreInputs = form ? form.querySelectorAll('input.score-input') : [];
          scoreInputs.forEach((input) => updateCriterionWeightedScore(input));
        };

        const calculateWeightedSectionScore = (sectionIndex) => {
          const sectionElement = stepElements[sectionIndex];

          if (!sectionElement) {
            return 0;
          }

          const inputs = Array.from(sectionElement.querySelectorAll('input.score-input'));
          const sectionKey = getSectionKey(sectionIndex);
          const scoreScale = 10;
          const multiplier = sectionWeightMultipliers[sectionKey] ?? 1;
          const thematicScaledSectionWeight = isThematicStep(sectionIndex)
            ? multiplier * thematicSectionScaleFactor
            : null;
          const thematicCriterionWeight = thematicScaledSectionWeight !== null && inputs.length > 0
            ? thematicScaledSectionWeight / inputs.length
            : null;

          let sectionTotal = 0;

          inputs.forEach((input) => {
            const value = clampScore(Number.parseInt(input.value, 10));
            if (value === null) {
              return;
            }

            if (thematicCriterionWeight !== null) {
              sectionTotal += (value * thematicCriterionWeight) / scoreScale;
              return;
            }

            const fieldName = extractFieldName(input.name);
            const weight = sectionKey && fieldName && criterionWeights[sectionKey]
              ? (criterionWeights[sectionKey][fieldName] ?? 1)
              : 1;

            sectionTotal += (value * weight) / scoreScale;
          });

          if (thematicScaledSectionWeight !== null) {
            return sectionTotal;
          }

          return sectionTotal * multiplier;
        };

        const calculateWeightedSectionMaxScore = (sectionIndex) => {
          const sectionElement = stepElements[sectionIndex];

          if (!sectionElement) {
            return 0;
          }

          const inputs = Array.from(sectionElement.querySelectorAll('input.score-input'));
          const sectionKey = getSectionKey(sectionIndex);
          const multiplier = sectionWeightMultipliers[sectionKey] ?? 1;
          if (isThematicStep(sectionIndex)) {
            const thematicScaledSectionWeight = multiplier * thematicSectionScaleFactor;
            return inputs.length > 0 ? thematicScaledSectionWeight : 0;
          }

          let sectionMax = 0;

          inputs.forEach((input) => {
            const fieldName = extractFieldName(input.name);
            const weight = sectionKey && fieldName && criterionWeights[sectionKey]
              ? (criterionWeights[sectionKey][fieldName] ?? 1)
              : 1;

            sectionMax += weight;
          });

          return sectionMax * multiplier;
        };

        const calculateTotalMaxScore = () => {
          let totalMax = 0;
          stepElements.forEach((_, index) => {
            totalMax += calculateWeightedSectionMaxScore(index);
          });

          return totalMax;
        };

        const calculateThematicTotalScore = () => {
          let total = 0;
          stepElements.forEach((_, index) => {
            if (!isThematicStep(index)) {
              return;
            }

            total += calculateWeightedSectionScore(index);
          });

          return total;
        };

        const calculateThematicMaxScore = () => {
          let totalMax = 0;
          stepElements.forEach((_, index) => {
            if (!isThematicStep(index)) {
              return;
            }

            totalMax += calculateWeightedSectionMaxScore(index);
          });

          return totalMax;
        };

        const calculateThematicScore = () => {
          if (!thematicScoreElement || !thematicScoreMaxElement) {
            return;
          }

          const thematicRawScore = calculateThematicTotalScore();
          const thematicRawMax = calculateThematicMaxScore();

          thematicScoreElement.textContent = formatScore(thematicRawScore);
          thematicScoreMaxElement.textContent = formatScore(thematicRawMax);
        };

        const updateThematicCounterVisibility = () => {
          if (!thematicScoreGroupElement) {
            return;
          }

          thematicScoreGroupElement.hidden = !isThematicStep(activeStepIndex);
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

          if (totalScoreMaxElement) {
            totalScoreMaxElement.textContent = formatScore(calculateTotalMaxScore());
          }

          calculateThematicScore();
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
          const weightedSectionMaxScore = calculateWeightedSectionMaxScore(activeStepIndex);

          sectionScoreElement.textContent = formatScore(weightedSectionScore);

          if (sectionScoreMaxElement) {
            sectionScoreMaxElement.textContent = formatScore(weightedSectionMaxScore);
          }
        }

        const transformCriteriaLayout = () => {
          const groups = Array.from(document.querySelectorAll('.form-group'));

          groups.forEach((group) => {
            const label = group.querySelector(':scope > label');
            const input = group.querySelector(':scope > input.score-input');
            const description = group.querySelector(':scope > small');
            const weightBadge = label ? label.querySelector('.criteria-weight-badge') : null;
            const weightedScoreBadge = label ? label.querySelector('.criteria-weighted-score') : null;

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
            if (weightedScoreBadge) {
              weightedScoreBadge.remove();
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
            if (weightedScoreBadge) {
              weightWrapper.appendChild(weightedScoreBadge);
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

        const scrollToPageTop = () => {
          const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';

          if (evaluationContent) {
            if (typeof evaluationContent.scrollTo === 'function') {
              evaluationContent.scrollTo({ top: 0, behavior });
            } else {
              evaluationContent.scrollTop = 0;
            }
          }

          window.scrollTo({
            top: 0,
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

        const isScoreMissing = (input) => {
          if (!input) {
            return false;
          }

          return (input.value || '').trim() === '';
        };

        const getSectionLabel = (index) => {
          const sectionKey = getSectionKey(index);
          if (sectionKey && sectionLabels[sectionKey]) {
            return sectionLabels[sectionKey];
          }

          return `Sezione ${index + 1}`;
        };

        const getIncompleteSectionLabels = () => {
          const missingSectionLabels = [];

          stepElements.forEach((step, index) => {
            const inputs = getStepScoreInputs(step);
            const hasMissingScores = inputs.some((input) => isScoreMissing(input));

            if (hasMissingScores) {
              missingSectionLabels.push(getSectionLabel(index));
            }
          });

          return missingSectionLabels;
        };

        const buildIncompleteSectionsMessage = (missingSectionLabels) => {
          if (!Array.isArray(missingSectionLabels) || missingSectionLabels.length === 0) {
            return incompleteMessage;
          }

          return `${incompleteMessage}\nSezioni non completate:\n- ${missingSectionLabels.join('\n- ')}`;
        };

        const updateNavigationState = () => {
          if (!nextStepButton || !previousStepButton || stepElements.length === 0) {
            return;
          }

          previousStepButton.disabled = activeStepIndex <= 0;
          const isLastStep = activeStepIndex >= stepElements.length - 1;
          nextStepButton.disabled = isLastStep;
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
          updateThematicCounterVisibility();

          calculateSectionScore();

          if (forceScroll) {
            const activeStepElement = stepElements[activeStepIndex];
            focusStepHeading(activeStepElement);
            scrollToPageTop();
          }
        };

        const attemptStepChange = (targetIndex) => {
          if (targetIndex === activeStepIndex) {
            return;
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
        updateAllCriterionWeightedScores();

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
              updateCriterionWeightedScore(input);
            });

            enforceInputBounds(input);
            updateCriterionWeightedScore(input);
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

          const missingSectionLabels = getIncompleteSectionLabels();
          if (missingSectionLabels.length > 0) {
            alert(buildIncompleteSectionsMessage(missingSectionLabels));
            return;
          }

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
