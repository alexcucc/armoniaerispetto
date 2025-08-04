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
  $stmt = $pdo->prepare("SELECT u.organization FROM application a JOIN user u ON a.user_id = u.id WHERE a.id = :application_id");
  $stmt->execute([':application_id' => $application_id]);
  $entity_name = $stmt->fetchColumn();
  ?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php'; ?>
    <title>Invia la Valutazione</title>
  </head>
  <body>
    <?php include 'header.php'; ?>
    <main>
      <div class="contact-form-container" style="margin-top:2em;">
        <form id="evaluation-form" class="contact-form" action="evaluation_handler.php" method="post">
          <!-- Hidden fields -->
          <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
          <input type="hidden" name="evaluator_id" value="<?php echo $_SESSION['user_id']; ?>">

          <h2>Valutazione <?php echo htmlspecialchars($entity_name); ?></h2>
          <hr>
          <h3>Soggetto Proponente</h3>
          <div class="form-group">
            <label class="form-label required">Informazioni Generali</label>
            <input class="form-input" type="number" name="proposing_entity[general_information_score]" min="0" max="3" required>
            <small class="form-text">
              <ul>
                <li>Ha un'identità chiara?</li>
                <li>È in linea con il suo status giuridico?</li>
                <li>La mission è in linea con le attività realizzate?</li>
                <li>La sua reputazione e il suo impatto sono chiare e dimostrabili?</li>
              </ul>
              <small>(valori consentiti: da 0 a 3).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Esperienza</label>
            <input class="form-input" type="number" name="proposing_entity[experience_score]" min="0" max="4" required>
            <small class="form-text">
              <span>1 punto (0-3 anni di esperienza)</span>
              <ul>
                <li>Enti di recente costituzione</li>
                <li>Struttura ancora in fase di sviluppo</li>
                <li>Bassa esperienza gestionale e operativa</li>
              </ul>
              <span>2 punti (4-10 anni di esperienza)</span>
              <ul>
                <li>Organizzazione più consolidata</li>
                <li>Prime esperienze significative nella gestione di progetti e finanziamenti</li>
                <li>Maggiore riconoscibilità sul territorio</li>
              </ul>
              <span>3 punti (11-25 anni di esperienza)</span>
              <ul>
                <li>Solida esperienza nel settore</li>
                <li>Struttura organizzativa ben definita</li>
                <li>Possibile accesso a finanziamenti più strutturati e collaborazioni stabili</li>
              </ul>
              <span>4 punti (oltre 25 anni di esperienza)</span>
              <ul>
                <li>Lunga tradizione e radicamento sul territorio</li>
                <li>Struttura consolidata con una rete ampia di collaborazioni</li>
                <li>Forte riconoscimento istituzionale e sociale</li>
              </ul>
              <small>(valori consentiti: da 0 a 4).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Modalità organizzative, gestionali e di assunzione delle decisioni</label>
            <input class="form-input" type="number" name="proposing_entity[organizational_capacity_score]" min="0" max="4" required>
            <small>
              <ul>
                <li>Che tipo di governance ha l'ente?</li>
                <li>Quanto incide il volontariato?</li>
                <li>Valorizza il personale locale?</li>
                <li>Si tratta di una grande o piccola organizzazione?</li>
              </ul>
              <small>(valori consentiti: da 0 a 4).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Policy (welfare aziendale, gender equality, child safeguarding, politiche ambientali ecc.)</label>
            <input class="form-input" type="number" name="proposing_entity[policy_score]" min="0" max="4" required>
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
              <small>(valori consentiti: da 0 a 4).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Bilancio</label>
            <input class="form-input" type="number" name="proposing_entity[budget_score]" min="0" max="3" required>
            <small>
              <ul>
                <li>Come incide la raccolta fondi?</li>
                <li>Sono dotati di una strategia funding mix?</li>
                <li>Hanno debiti difficili da sostenere?</li>
                <li>Il bilancio è in crescita o in decrescita negli ultimi anni?</li>
              </ul>
              <small>(valori consentiti: da 0 a 3).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Finalità e coinvolgimento locale</label>
            <input class="form-input" type="number" name="proposing_entity[purpose_and_local_involvement_score]" min="0" max="4" required>
            <small>
              <ul>
                <li>Le attività e la mission sono in linea con un corretto sviluppo locale?</li>
                <li>Ha partnership locali?</li>
                <li>Vi è evidenza di una buona reputazione a livello locale?</li>
              </ul>
              <small>(valori consentiti: da 0 a 4).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Partnership e visibilità</label>
            <input class="form-input" type="number" name="proposing_entity[partnership_and_visibility_score]" min="0" max="4" required>
            <small>
              <ul>
                <li>Fa parte di network riconosciuti?</li>
                <li>Ha vinto dei premi?</li>
                <li>Ha partnership attive con università, istituzioni, aziende, altri ETS?</li>
              </ul>
              <small>(valori consentiti: da 0 a 4).</small>
            </small>
          </div>

          <hr>
          <h3>Progetto</h3>
          <div class="form-group">
            <label class="form-label required">Identificazione dei bisogni e analisi dei problemi</label>
            <input class="form-input" type="number" name="project[needs_identification_and_problem_analysis_score]" min="0" max="4" required>
            <small>
              <ul>
                <li>L'analisi è completa, sufficientemente dettagliata e coerente?</li>
                <li>Le fonti sono autorevoli?</li>
                <li>Risulta effettivamente rispondente a un bisogno emerso?</li>
              </ul>
              <small>(valori consentiti: da 0 a 4).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Aderenza alle finalità statutarie</label>
            <input class="form-input" type="number" name="project[adherence_to_statuary_purposes_score]" min="0" max="3" required>
            <small>
              <ul>
                <li>Il progetto è in linea con le finalità statutarie dell'ente?</li>
              </ul>
              <small>(valori consentiti: da 0 a 3).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Peso sociale (es. aiuto a fragili per cura animali)</label>
            <input class="form-input" type="number" name="project[social_weight_score]" min="0" max="2" required>
            <small>
              <ul>
                <li>Il progetto ha un impatto sociale positivo?</li>
              </ul>
              <small>(valori consentiti: da 0 a 2).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Obiettivi</label>
            <input class="form-input" type="number" name="project[objectives_score]" min="0" max="2" required>
            <small>
              <ul>
                <li>Sono coerenti?</li>
                <li>Sono realizzabili?</li>
              </ul>
              <small>(valori consentiti: da 0 a 2).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Risultati attesi</label>
            <input class="form-input" type="number" name="project[expected_results_score]" min="0" max="2" required>
            <small>
              <ul>
                <li>Sono concreti?</li>
                <li>Sono Misurabili?</li>
                <li>Sono Ambiziosi?</li>
              </ul>
              <small>(valori consentiti: da 0 a 2).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Attività</label>
            <input class="form-input" type="number" name="project[activity_score]" min="0" max="3" required>
            <small>
              <ul>
                <li>Sono coerenti?</li>
                <li>Sono Chiare?</li>
                <li>Sono Sufficientemente dettagliate?</li>
                <li>Sono Realizzabili?</li>
                <li>Sono Efficaci?</li>
              </ul>
              <small>(valori consentiti: da 0 a 3).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Finalità locale</label>
            <input class="form-input" type="number" name="project[local_purpose_score]" min="0" max="2" required>
            <small>
              <ul>
                <li>Il progetto ha una chiara finalità locale?</li>
              </ul>
              <small>(valori consentiti: da 0 a 2).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Partenariato e rapporti con autorità locali/nazionali</label>
            <input class="form-input" type="number" name="project[partnership_and_relations_with_local_authorities_score]" min="0" max="2" required>
            <small>
              <ul>
                <li>Il/i partner è/sono un valore aggiunto?</li>
                <li>Completano e/o arricchiscono il progetto?</li>
                <li>Permettono di raggiungere un maggior numero di beneficiari?</li>
                <li>I rapporti con le autorità locali sono sviluppati e fruttuosi?</li>
              </ul>
              <small>(valori consentiti: da 0 a 2).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Sinergie e inefficienze progettuali</label>
            <input class="form-input" type="number" name="project[synergies_and_design_inefficiencies_score]" min="0" max="3" required>
            <small>
              <ul>
                <li>È un progetto che condivide obiettivi, stakeholder, risorse, metodologie o deliverable con altri progetti precedenti o in corso?</li>
                <li>Presenta sinergie o sovrapposizioni nei risultati attesi con altri progetti?</li>
                <li>Risulta una duplicazione eccessiva di attività, obiettivi o output?</li>
                <li>Ripete processi già implementati altrove?</li>
              </ul>
              <small>(valori consentiti: da 0 a 3).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Comunicazione e visibilità</label>
            <input class="form-input" type="number" name="project[communication_and_visibility_score]" min="0" max="3" required>
            <small>
              <ul>
                <li>La proposta è in linea con le aspettative?</li>
                <li>Valorizza il progetto?</li>
                <li>Valorizza la collaborazione Ente - Fondazione AR?</li>
              </ul>
              <small>(valori consentiti: da 0 a 3).</small>
            </small>
          </div>

          <hr>
          <h3>Piano Finanziario</h3>
          <div class="form-group">
            <label class="form-label required">Completezza e chiarezza del budget</label>
            <input class="form-input" type="number" name="financial_plan[completeness_and_clarity_of_budget_score]" min="0" max="2" required>
            <small>
              <ul>
                <li>Il budget è chiaro e completo in tutte le sue parti?</li>
              </ul>
              <small>(valori consentiti: da 0 a 2).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Coerenza con obiettivi, risultati, impatto e cronogramma</label>
            <input class="form-input" type="number" name="financial_plan[consistency_with_objectives_score]" min="0" max="3" required>
            <small>
              <ul>
                <li>Il budget risulta coerente con gli obiettivi e i risultati del Progetto?</li>
                <li>Permette il rispetto del cronogramma e il raggiungimento dell'impatto atteso?</li>
              </ul>
              <small>(valori consentiti: da 0 a 3).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Cofinanziamento</label>
            <input class="form-input" type="number" name="financial_plan[cofinancing_score]" min="0" max="3" required>
            <small>
              <ul>
                <li>La percentuale del cofinanziamento è adeguata?</li>
                <li>Le fonti sono diversificate e autorevoli?</li>
              </ul>
              <small>(valori consentiti: da 0 a 3).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Flessibilità</label>
            <input class="form-input" type="number" name="financial_plan[flexibility_score]" min="0" max="2" required>
            <small>
              <ul>
                <li>Il budget è in grado di far fronte a eventuali cambiamenti di sviluppo progettuale senza variazioni onerose?</li>
              </ul>
              <small>(valori consentiti: da 0 a 2).</small>
            </small>
          </div>

          <hr>
          <h2>Elementi Qualitativi</h2>
          <div class="form-group">
            <label class="form-label required">L'impatto e gli effetti di più ampio e lungo termine prodotti dall’iniziativa in ragione del contesto di intervento</label>
            <input class="form-input" type="number" name="qualitative_elements[impact_score]" min="0" max="10" required>
            <small>
              <ul>
                <li>Il progetto ha la potenzialità di influire in maniera sistemica nel lungo periodo?</li>
                <li>Sono valutati i rischi di un "impatto negativo"?</li>
              </ul>
              <small>(valori consentiti: da 0 a 10).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Pertinenza del progetto rispetto ai bisogni e criticità specifiche del Paese, della Regione, del settore d’intervento, della sinergia con altri programmi</label>
            <input class="form-input" type="number" name="qualitative_elements[relevance_score]" min="0" max="10" required>
            <small>
              <ul>
                <li>Il progetto è in linea con i bisogni prioritari dell'area d'intervento?</li>
                <li>È rilevante rispetto alle criticità territoriali?</li>
                <li>È coerente con le politiche pubbliche e i relativi piani di sviluppo? È supportato dalle istituzioni?</li>
              </ul>
              <small>(valori consentiti: da 0 a 10).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Congruità del Progetto e della capacità operativa di realizzarla da parte del Soggetto Proponente</label>
            <input class="form-input" type="number" name="qualitative_elements[congruity_score]" min="0" max="10" required>
            <small>
              <ul>
                <li>Il progetto è coerente con le capacità e le risorse del soggetto proponente?</li>
              </ul>
              <small>(valori consentiti: da 0 a 10).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Innovatività del Progetto</label>
            <input class="form-input" type="number" name="qualitative_elements[innovation_score]" min="0" max="8" required>
            <small>
              <ul>
                <li>È previsto l'utilizzo di tecnologie o metodi e approcci nuovi per il raggiungimento degli obiettivi dichiarati?</li>
              </ul>
              <small>(valori consentiti: da 0 a 8).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Rigore e validità scientifica</label>
            <input class="form-input" type="number" name="qualitative_elements[rigor_and_scientific_validity_score]" min="0" max="8" required>
            <small>
              <ul>
                <li>La proposta è basata su evidenze scientifiche, opportunamente spiegate e con le fonti?</li>
              </ul>
              <small>(valori consentiti: da 0 a 8).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Replicabilità e scalabilità</label>
            <input class="form-input" type="number" name="qualitative_elements[replicability_and_scalability_score]" min="0" max="8" required>
            <small>
              <ul>
                <li>Il progetto può essere adattato e applicato in altri contesti?</li>
              </ul>
              <small>(valori consentiti: da 0 a 8).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Evidenza dello sviluppo progettuale in linea con un'equilibrata coabitazione uomo-animale che preveda adeguate misure di mitigazione ove necessario</label>
            <input class="form-input" type="number" name="qualitative_elements[cohabitation_evidence_score]" min="0" max="8" required>
            <small>
              <ul>
                <li>Il progetto ha valutato la compatibilità con una coabitazione uomo-animale?</li>
                <li>Sono previste azioni di tutela e di mitigazione dei rischi?</li>
              </ul>
              <small>(valori consentiti: da 0 a 8).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Partecipazione enti di ricerca e università</label>
            <input class="form-input" type="number" name="qualitative_elements[research_and_university_partnership_score]" min="0" max="8" required>
            <small>
              <ul>
                <li>È prevista la partecipazione di enti di ricerca?</li>
                <li>È/sono un valore aggiunto?</li>
                <li>Completano e/o arricchiscono il progetto?</li>
              </ul>
              <small>(valori consentiti: da 0 a 8).</small>
            </small>
          </div>

          <hr>
          <h3>Criteri Tematici - Ripopolamento</h3>
          <div class="form-group">
            <label class="form-label required">Habitat dell'intervento</label>
            <input class="form-input" type="number" name="thematic_repopulation[habitat_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto considera le caratteristiche ecologiche dell'habitat?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Strategia di mitigazione delle minacce</label>
            <input class="form-input" type="number" name="thematic_repopulation[threat_mitigation_strategy_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede misure per mitigare le minacce all'habitat?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Coinvolgimento comunità locale</label>
            <input class="form-input" type="number" name="thematic_repopulation[local_community_involvement_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto coinvolge attivamente la comunità locale?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)</label>
            <input class="form-input" type="number" name="thematic_repopulation[multidisciplinary_sustainability_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto considera le interconnessioni tra diversi ambiti (sociale, economico, ambientale)?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>

          <hr>
          <h3>Criteri Tematici - Salvaguardia</h3>
          <div class="form-group">
            <label class="form-label required">Approccio sistemico (prevenzione, contrasto, riabilitazione)</label>
            <input class="form-input" type="number" name="thematic_safeguard[systemic_approach_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto adotta un approccio sistemico per affrontare le problematiche ambientali?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Advocacy e rafforzamento giuridico</label>
            <input class="form-input" type="number" name="thematic_safeguard[advocacy_and_legal_strengthening_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto promuove l'advocacy e il rafforzamento giuridico per la tutela dell'ambiente?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Salvaguardia dell'habitat (flora e fauna)</label>
            <input class="form-input" type="number" name="thematic_safeguard[habitat_safeguard_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto contribuisce alla salvaguardia degli habitat naturali (flora e fauna)?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Compartecipazione a sviluppo di riserve, oasi, CRAS ecc.</label>
            <input class="form-input" type="number" name="thematic_safeguard[reservers_development_participation_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede la compartecipazione allo sviluppo di riserve, oasi, CRAS, ecc.?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Attività dedicate a specie cruciali e/o a rischio estinzione</label>
            <input class="form-input" type="number" name="thematic_safeguard[crucial_species_activities_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede attività dedicate a specie cruciali e/o a rischio estinzione?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Coinvolgimento multistakeholder (comunità locale, istituzioni, privato sociale)</label>
            <input class="form-input" type="number" name="thematic_safeguard[multistakeholder_involvement_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede il coinvolgimento di più attori (comunità locale, istituzioni, privato sociale)?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)</label>
            <input class="form-input" type="number" name="thematic_safeguard[multidisciplinary_sustainability_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede un approccio multidisciplinare per garantire la sostenibilità (istituzionale, ambientale, culturale, economica)?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>

          <hr>
          <h3>Criteri Tematici - Coabitazione</h3>
          <div class="form-group">
            <label class="form-label required">Strategia di riduzione dei rischi</label>
            <input class="form-input" type="number" name="thematic_cohabitation[risk_reduction_strategy_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede una strategia di riduzione dei rischi?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Tutela della biodiversità e integrazione della presenza animale  alle attività umane (es Rwanda)</label>
            <input class="form-input" type="number" name="thematic_cohabitation[biodiversity_protection_and_animal_integrity_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede attività dedicate a specie cruciali e/o a rischio estinzione?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Coinvolgimento comunità locale</label>
            <input class="form-input" type="number" name="thematic_cohabitation[local_community_involvement_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede il coinvolgimento della comunità locale?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Sostegno allo sviluppo di un'economia circolare per il sostentamento locale</label>
            <input class="form-input" type="number" name="thematic_cohabitation[circular_economy_development_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede il sostegno allo sviluppo di un'economia circolare per il sostentamento locale?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)</label>
            <input class="form-input" type="number" name="thematic_cohabitation[multidisciplinary_sustainability_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede un approccio multidisciplinare per la sostenibilità?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>

          <hr>
          <h3>Criteri Tematici - Supporto di comunità</h3>
          <div class="form-group">
            <label class="form-label required">Sviluppo sistemico  (educativo, economico, produttivo) di capacity buliding</label>
            <input class="form-input" type="number" name="thematic_community_support[systemic_development_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede un approccio sistemico per lo sviluppo della comunità?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Contrasto alle discriminazione sociali</label>
            <input class="form-input" type="number" name="thematic_community_support[social_discrimination_fighting_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede misure per contrastare le discriminazioni sociali?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Salvaguardia dell'habitat</label>
            <input class="form-input" type="number" name="thematic_community_support[habitat_protection_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede misure per la salvaguardia dell'habitat?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Coinvolgimento multistakeholder (comunità locale, istituzioni, privato sociale)</label>
            <input class="form-input" type="number" name="thematic_community_support[multistakeholder_involvement_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede un coinvolgimento attivo dei diversi attori sociali?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)</label>
            <input class="form-input" type="number" name="thematic_community_support[multidisciplinary_sustainability_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede un approccio multidisciplinare per la sostenibilità?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>

          <hr>
          <h3>Criteri Tematici - Cultura - Educazione - Sensibilizzazione</h3>
          <div class="form-group">
            <label class="form-label required">Strumenti di disseminazione</label>
            <input class="form-input" type="number" name="thematic_culture_education[dissemination_tools_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede strumenti di disseminazione efficaci?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Advocacy e rafforzamento giuridico</label>
            <input class="form-input" type="number" name="thematic_culture_education[advocacy_and_legal_strengthening_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede attività di advocacy e rafforzamento giuridico?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Grado di innovazione</label>
            <input class="form-input" type="number" name="thematic_culture_education[innovation_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede elementi innovativi?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Coinvolgimento multistakeholder (cittadinanza, istituzioni, centri di ricerca, agenzie educative)</label>
            <input class="form-input" type="number" name="thematic_culture_education[multistakeholder_involvement_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede un coinvolgimento attivo dei diversi attori sociali?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          <div class="form-group">
            <label class="form-label required">Sostenibilità multidisciplinare (istituzionale, ambientale, culturale, economica)</label>
            <input class="form-input" type="number" name="thematic_culture_education[multidisciplinary_sustainability_score]" min="0" max="100" required>
            <small>
              <ul>
                <li>Il progetto prevede un approccio multidisciplinare per la sostenibilità?</li>
              </ul>
              <small>(valori consentiti: da 0 a 100).</small>
            </small>
          </div>
          
          <div class="form-group" style="margin-top:2em;">
            <button class="submit-btn" type="submit">Invia Valutazione</button>
          </div>
        </form>
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
      document.getElementById('evaluation-form').addEventListener('submit', async function (e) {
      e.preventDefault();
      const form = this;
      const formData = new FormData(form);
      try {
        const response = await fetch(form.action, {
          method: form.method,
          body: formData
        });
        const data = await response.json();
        if(data.success) {
          const modal = document.getElementById('evaluation-success-modal');
          modal.style.display = 'block';
          let redirected = false;
          const goHome = () => {
            if (!redirected) {
              redirected = true;
              window.location.href = data.redirect || "index.php";
            }
          };
          document.getElementById('close-evaluation-modal').onclick = goHome;
          setTimeout(goHome, 2500);
        } else {
          alert(data.message || "Errore nell'invio della valutazione.");
        }
      } catch (error) {
        alert("Errore: " + error);
      }
    });
    </script>
  </body>
</html>