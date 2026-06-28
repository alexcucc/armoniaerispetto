<?php

declare(strict_types=1);

function evaluationGetCurrentModelVersion(): string
{
    return 'v4';
}

function evaluationIsLegacyModel(?string $modelVersion): bool
{
    return strtolower((string) $modelVersion) !== evaluationGetCurrentModelVersion();
}

function evaluationGetLegacyMaxTotalScoreRaw(): float
{
    return 2090.0;
}

function evaluationGetV4Definition(): array
{
    return [
        'max_total_score' => 200.0,
        'sections' => [
            'proposing_entity' => [
                'label' => 'Soggetto proponente',
                'type' => 'weighted',
                'table' => 'evaluation_v4_proposing_entity',
                'max' => 30,
                'criteria' => [
                    'general_information_score' => ['label' => 'Informazioni generali', 'weight' => 4, 'help' => "Ha un'identita chiara? Missione, reputazione e impatto sono dimostrabili?"],
                    'activities_consistency_score' => ['label' => 'Coerenza attivita svolte', 'weight' => 4, 'help' => 'Statuto, scopo sociale e track record recente sono coerenti tra loro?'],
                    'experience_score' => ['label' => 'Esperienza', 'weight' => 4, 'help' => 'Valutare anzianita, consolidamento operativo e riconoscibilita.'],
                    'organizational_management_score' => ['label' => 'Modalita organizzative e gestionali', 'weight' => 1, 'help' => 'Governance, organi dedicati, qualita organizzativa desumibile dalla proposta.'],
                    'budget_completeness_score' => ['label' => 'Bilancio correttezza e completezza', 'weight' => 3, 'help' => 'Bilancio completo, comprensibile e documentazione esaustiva.'],
                    'funding_sources_score' => ['label' => 'Bilancio fonti di finanziamento', 'weight' => 2, 'help' => 'Funding mix, peso raccolta fondi, diversificazione, equilibrio pubblico/privato.'],
                    'financial_soundness_score' => ['label' => 'Bilancio solidita finanziaria', 'weight' => 2, 'help' => 'Debiti sostenibili, equilibrio economico, trend ricavi, utile/perdita.'],
                    'organizational_structure_score' => ['label' => 'Struttura organizzativa', 'weight' => 3, 'help' => 'Struttura adeguata alla proposta, rapporto volontari/addetti.'],
                    'local_purpose_involvement_score' => ['label' => 'Finalita e coinvolgimento locale', 'weight' => 3, 'help' => 'Reputazione locale, partnership territoriali, evidenze oggettive.'],
                    'partnership_visibility_score' => ['label' => 'Partnership e visibilita', 'weight' => 4, 'help' => 'Network, universita, istituzioni, aziende, presenza web e social.'],
                ],
            ],
            'project' => [
                'label' => 'Progetto',
                'type' => 'weighted',
                'table' => 'evaluation_v4_project',
                'max' => 30,
                'criteria' => [
                    'needs_analysis_score' => ['label' => 'Identificazione dei bisogni e analisi dei problemi', 'weight' => 2, 'help' => 'Analisi completa, dettagliata, coerente e rispondente a un bisogno reale.'],
                    'objectives_consistency_score' => ['label' => 'Obiettivi coerenti con il bando', 'weight' => 6, 'help' => 'Obiettivi coerenti con il bando e monitoraggio dei risultati nel tempo.'],
                    'objectives_ambition_score' => ['label' => 'Obiettivi ambiziosi/ragionevoli/sensati', 'weight' => 4, 'help' => 'Ambiziosi ma concreti, non velleitari.'],
                    'objectives_feasibility_score' => ['label' => 'Obiettivi realizzabili nel contesto', 'weight' => 2, 'help' => 'Realizzabili rispetto a contesto e modalita attuativa.'],
                    'expected_results_score' => ['label' => 'Risultati attesi', 'weight' => 3, 'help' => 'Concreti e misurabili.'],
                    'activities_score' => ['label' => 'Attivita', 'weight' => 3, 'help' => 'Coerenti, chiare, dettagliate, realizzabili ed efficaci.'],
                    'local_purpose_score' => ['label' => 'Finalita locale', 'weight' => 2, 'help' => 'Quanto il progetto e legato al territorio.'],
                    'partnership_local_authorities_score' => ['label' => 'Partenariato e rapporti con autorita locali/nazionali', 'weight' => 2, 'help' => 'Partner e rapporti istituzionali sono un valore aggiunto.'],
                    'synergies_efficiency_score' => ['label' => 'Sinergie ed efficienze progettuali', 'weight' => 2, 'help' => 'Sinergie, recovery plan, riuso di esperienze o modelli efficaci.'],
                    'inefficiencies_score' => ['label' => 'Inefficienze progettuali', 'weight' => 1, 'help' => 'Solo criticita: sovrapposizioni, duplicazioni, ridondanze.', 'min' => -10, 'max' => 0],
                    'communication_visibility_score' => ['label' => 'Comunicazione e visibilita', 'weight' => 3, 'help' => 'Valorizza la collaborazione con Fondazione AR e le attivita media.'],
                ],
            ],
            'financial_plan' => [
                'label' => 'Piano finanziario',
                'type' => 'weighted',
                'table' => 'evaluation_v4_financial_plan',
                'max' => 15,
                'criteria' => [
                    'funding_limits_compliance_score' => ['label' => 'Rispetto dei valori limite del finanziamento', 'weight' => 2, 'help' => 'Solo criticita: se supera i limiti del bando, penalizzare.', 'min' => -10, 'max' => 0],
                    'budget_clarity_score' => ['label' => 'Completezza e chiarezza del budget', 'weight' => 3, 'help' => 'Budget chiaro e completo in tutte le sue parti.'],
                    'budget_consistency_score' => ['label' => 'Coerenza con obiettivi, risultati, impatto e cronogramma', 'weight' => 1, 'help' => 'Coerenza con obiettivi, risultati, impatto e cronoprogramma.'],
                    'cofinancing_score' => ['label' => 'Cofinanziamento', 'weight' => 2, 'help' => 'Percentuale adeguata e qualita/autorevolezza delle fonti.'],
                    'flexibility_score' => ['label' => 'Flessibilita', 'weight' => 3, 'help' => 'Capace di assorbire cambiamenti senza perdere efficacia.'],
                    'project_value_soundness_score' => ['label' => 'Solidita - valore del progetto', 'weight' => 2, 'help' => 'Rapporto con entrate, disponibilita e necessita reale di finanziamento.'],
                    'staff_cost_incidence_score' => ['label' => 'Incidenza spese per personale', 'weight' => 2, 'help' => 'Incidenza di manodopera e viaggi sul totale progetto.'],
                ],
            ],
            'qualitative_elements' => [
                'label' => 'Elementi qualitativi',
                'type' => 'weighted',
                'table' => 'evaluation_v4_qualitative_elements',
                'max' => 25,
                'criteria' => [
                    'new_project_score' => ['label' => 'Nuovo progetto', 'weight' => 3, 'help' => 'Nuova iniziativa o evoluzione sostanziale, non mera gestione corrente.'],
                    'long_term_impact_score' => ['label' => 'Impatto ed effetti di piu ampio e lungo termine', 'weight' => 5, 'help' => 'Potenziale sistemico nel lungo periodo.'],
                    'context_relevance_score' => ['label' => 'Pertinenza rispetto a bisogni e criticita del contesto', 'weight' => 3, 'help' => 'Coerenza con bisogni territoriali, priorita e politiche pubbliche.'],
                    'innovation_score' => ['label' => 'Innovativita del progetto', 'weight' => 5, 'help' => 'Uso di tecnologie, metodi o approcci nuovi.'],
                    'scientific_rigor_score' => ['label' => 'Rigore e validita scientifica', 'weight' => 6, 'help' => 'Evidenze scientifiche, fonti e collaborazioni di ricerca.'],
                    'replicability_scalability_score' => ['label' => 'Replicabilita e scalabilita', 'weight' => 3, 'help' => 'Adattabile e replicabile in altri contesti.'],
                ],
            ],
            'thematic_safeguard' => [
                'label' => 'Criteri tematici - Salvaguardia animali',
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_safeguard',
                'max' => 70,
                'criteria' => [
                    'habitat_safeguard_score' => ['label' => "Salvaguardia dell'habitat (flora e fauna)", 'help' => 'Sono previste azioni concrete e documentate per la salvaguardia dell habitat, della flora e della fauna?'],
                    'prevention_score' => ['label' => 'Prevenzione', 'help' => "Attribuire un voto solo alle voci che dalla domanda si desume si vogliano perseguire. Il voto valuta l'efficacia della proposta per il raggiungimento dell obiettivo."],
                    'legal_contrast_score' => ['label' => 'Contrasto ai maltrattamenti (giuridico/legale)', 'help' => 'Advocacy, rafforzamento giuridico e azioni per modificare leggi o regolamenti.'],
                    'liberation_actions_score' => ['label' => 'Contrasto ai maltrattamenti (azioni di liberazione)', 'help' => 'Azioni concrete per la liberazione fisica degli animali da luoghi di abuso, allevamenti intensivi o laboratori di sperimentazione.'],
                    'shelter_remedy_score' => ['label' => 'Rimedio accoglimento', 'help' => 'Creazione o sviluppo di canili, gattili, santuari o rifugi per animali domestici o da allevamento.'],
                    'protection_remedy_score' => ['label' => 'Rimedio protezione', 'help' => 'Creazione o co-partecipazione allo sviluppo di riserve e oasi.'],
                    'veterinary_rehabilitation_score' => ['label' => 'Rimedio cura e riabilitazione veterinaria', 'help' => 'Creazione o co-partecipazione allo sviluppo di CRAS per animali selvatici.'],
                    'relocation_remedy_score' => ['label' => 'Rimedio ricollocamento', 'help' => 'Azioni di ricollocamento di animali domestici o da allevamento presso terzi privati.'],
                    'species_focus_score' => ['label' => 'Focalizzazione su specifiche specie a rischio', 'help' => 'Il progetto e focalizzato in modo chiaro su una o piu specie a rischio e ne dimostra il bisogno specifico di tutela?'],
                    'facility_coparticipation_score' => ['label' => 'Co-partecipazione a sviluppo di riserve, oasi, CRAS ecc.', 'help' => 'E prevista una co-partecipazione concreta allo sviluppo o potenziamento di riserve, oasi, CRAS o strutture analoghe?'],
                    'multidisciplinary_sustainability_score' => ['label' => 'Sostenibilita multidisciplinare', 'help' => 'Ci sono garanzie di tenuta del progetto a livello istituzionale, ambientale, culturale ed economico?'],
                ],
            ],
            'thematic_repopulation' => [
                'label' => 'Criteri tematici - Ripopolamento',
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_repopulation',
                'max' => 70,
                'criteria' => [
                    'intervention_habitat_score' => ['label' => "Habitat dell'intervento", 'help' => "Adeguato? Pronto? Nella disponibilita dell'ente o del partenariato?"],
                    'threat_mitigation_strategy_score' => ['label' => 'Strategia di mitigazione delle minacce', 'help' => 'E presente? Completa? Adeguata?'],
                    'local_community_involvement_score' => ['label' => 'Coinvolgimento comunita locale', 'help' => 'La comunita locale e coinvolta? In quali fasi e con quali modalita?'],
                    'multidisciplinary_sustainability_score' => ['label' => 'Sostenibilita multidisciplinare', 'help' => 'Ci sono garanzie di tenuta del progetto a livello istituzionale, ambientale, culturale ed economico?'],
                ],
            ],
            'thematic_cohabitation' => [
                'label' => 'Criteri tematici - Coabitazione',
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_cohabitation',
                'max' => 70,
                'criteria' => [
                    'local_community_involvement_score' => ['label' => 'Coinvolgimento comunita locale', 'help' => 'E coinvolta? In che fasi? In che modo?'],
                    'biodiversity_integration_score' => ['label' => 'Tutela della biodiversita e integrazione della presenza animale', 'help' => 'E stato adeguatamente studiato l habitat, la biodiversita, gli ecosistemi e l impatto delle attivita previste?'],
                    'risk_reduction_strategy_score' => ['label' => 'Strategia di riduzione dei rischi', 'help' => 'E presente? Completa? Adeguata?'],
                    'circular_economy_support_score' => ['label' => "Sostegno allo sviluppo di un'economia circolare", 'help' => 'La proposta di sviluppo e adeguata, concreta e fattibile?'],
                    'multidisciplinary_sustainability_score' => ['label' => 'Sostenibilita multidisciplinare', 'help' => 'Ci sono garanzie di tenuta del progetto a livello istituzionale, ambientale, culturale ed economico?'],
                ],
            ],
            'thematic_community_support' => [
                'label' => 'Criteri tematici - Supporto di comunita',
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_community_support',
                'max' => 30,
                'criteria' => [
                    'systemic_development_score' => ['label' => 'Sviluppo sistemico di capacity building', 'help' => 'Il progetto prende in considerazione tutti gli ambiti rilevanti, educativi, economici e produttivi, per garantire buona riuscita?'],
                    'social_discrimination_contrast_score' => ['label' => 'Contrasto alle discriminazioni sociali', 'help' => 'Il bisogno e effettivo? Le risposte proposte sono concrete ed efficaci?'],
                    'habitat_safeguard_score' => ['label' => "Salvaguardia dell'habitat", 'help' => 'E prevista e adeguatamente documentata?'],
                    'multistakeholder_involvement_score' => ['label' => 'Coinvolgimento multistakeholder', 'help' => 'E previsto il coinvolgimento diretto di tutti gli stakeholder rilevanti?'],
                    'multidisciplinary_sustainability_score' => ['label' => 'Sostenibilita multidisciplinare', 'help' => 'Ci sono garanzie di tenuta del progetto a livello istituzionale, ambientale, culturale ed economico?'],
                ],
            ],
            'thematic_culture_education' => [
                'label' => 'Criteri tematici - Cultura, educazione, sensibilizzazione',
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_culture_education',
                'max' => 20,
                'criteria' => [
                    'dissemination_tools_score' => ['label' => 'Strumenti di disseminazione', 'help' => 'Quali e quanti sono gli strumenti di sensibilizzazione previsti? Sono i piu efficaci?'],
                    'advocacy_legal_strengthening_score' => ['label' => 'Advocacy e rafforzamento giuridico', 'help' => "E previsto? E razionale? Efficace? L'ente ha la forza per portare avanti questa attivita?"],
                    'innovation_degree_score' => ['label' => 'Grado di innovazione', 'help' => 'Utilizza nuovi approcci, metodologie o strumenti tecnologici?'],
                    'multistakeholder_involvement_score' => ['label' => 'Coinvolgimento multistakeholder', 'help' => 'E previsto il coinvolgimento diretto di tutti gli stakeholder rilevanti?'],
                    'multidisciplinary_sustainability_score' => ['label' => 'Sostenibilita multidisciplinare', 'help' => 'Ci sono garanzie di tenuta del progetto a livello istituzionale, ambientale, culturale ed economico?'],
                ],
            ],
        ],
    ];
}

function evaluationGetV4Sections(): array
{
    return evaluationGetV4Definition()['sections'];
}

function evaluationGetV4EnabledSections(): array
{
    return evaluationGetV4Sections();
}


function evaluationGetV4FieldBounds(array $criterionDefinition): array
{
    return [
        'min' => array_key_exists('min', $criterionDefinition) ? (int) $criterionDefinition['min'] : 0,
        'max' => array_key_exists('max', $criterionDefinition) ? (int) $criterionDefinition['max'] : 10,
    ];
}

function evaluationV4GetCriterionNoteColumn(string $fieldName): string
{
    if (str_ends_with($fieldName, '_score')) {
        return substr($fieldName, 0, -6) . '_notes';
    }

    return $fieldName . '_notes';
}

function evaluationV4CreateEmptyData(): array
{
    $data = [];
    foreach (evaluationGetV4Sections() as $sectionKey => $sectionDefinition) {
        $data[$sectionKey] = [
            'scores' => [],
            'criterion_notes' => [],
        ];
        foreach ($sectionDefinition['criteria'] as $fieldName => $criterionDefinition) {
            $data[$sectionKey]['scores'][$fieldName] = null;
            $data[$sectionKey]['criterion_notes'][$fieldName] = '';
        }
    }
    return $data;
}

function evaluationV4LoadData(PDO $pdo, int $evaluationId): array
{
    $data = evaluationV4CreateEmptyData();
    foreach (evaluationGetV4Sections() as $sectionKey => $sectionDefinition) {
        $fields = array_keys($sectionDefinition['criteria']);
        $noteColumns = array_map('evaluationV4GetCriterionNoteColumn', $fields);
        $columns = implode(', ', array_merge($fields, $noteColumns));
        $stmt = $pdo->prepare("SELECT {$columns} FROM {$sectionDefinition['table']} WHERE evaluation_id = :evaluation_id LIMIT 1");
        $stmt->execute([':evaluation_id' => $evaluationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            continue;
        }

        foreach ($fields as $fieldName) {
            $noteColumn = evaluationV4GetCriterionNoteColumn($fieldName);
            $data[$sectionKey]['criterion_notes'][$fieldName] = (string) ($row[$noteColumn] ?? '');
            if (!array_key_exists($fieldName, $row) || $row[$fieldName] === null || !is_numeric($row[$fieldName])) {
                continue;
            }
            $data[$sectionKey]['scores'][$fieldName] = (int) $row[$fieldName];
        }
    }
    return $data;
}


function evaluationV4Round(float $value): float
{
    return round($value, 2);
}

function evaluationV4CalculateSection(array $sectionDefinition, array $sectionData): array
{
    $scores = $sectionData['scores'] ?? [];
    if (($sectionDefinition['type'] ?? '') === 'thematic') {
        $values = [];
        foreach ($sectionDefinition['criteria'] as $fieldName => $criterionDefinition) {
            $score = $scores[$fieldName] ?? null;
            if ($score === null || !is_numeric($score)) {
                continue;
            }
            $values[] = (float) $score;
        }

        $averageScore = null;
        $weightedScore = null;
        if ($values !== []) {
            $averageScore = evaluationV4Round(array_sum($values) / count($values));
            $weightedScore = evaluationV4Round(($averageScore / 10) * (float) $sectionDefinition['max']);
        }

        return [
            'average_score' => $averageScore,
            'weighted_score' => $weightedScore,
            'has_scores' => $values !== [],
        ];
    }

    $weightedScore = 0.0;
    $hasScores = false;
    foreach ($sectionDefinition['criteria'] as $fieldName => $criterionDefinition) {
        $score = $scores[$fieldName] ?? null;
        if ($score === null || !is_numeric($score)) {
            continue;
        }
        $hasScores = true;
        $weightedScore += (((float) $score) * (float) $criterionDefinition['weight']) / 10;
    }

    return [
        'weighted_score' => $hasScores ? evaluationV4Round($weightedScore) : null,
        'has_scores' => $hasScores,
    ];
}

function evaluationV4CalculateTotals(array $data): array
{
    $sections = evaluationGetV4EnabledSections();
    $results = [];
    $thematicTotal = 0.0;
    $hasThematicScore = false;
    $overallTotal = 0.0;
    $hasOverallScore = false;

    foreach ($sections as $sectionKey => $sectionDefinition) {
        $sectionResult = evaluationV4CalculateSection($sectionDefinition, $data[$sectionKey] ?? []);
        $results[$sectionKey] = $sectionResult;

        if (($sectionDefinition['type'] ?? '') === 'thematic') {
            if ($sectionResult['weighted_score'] !== null) {
                $hasThematicScore = true;
                $thematicTotal += (float) $sectionResult['weighted_score'];
            }
            continue;
        }

        if ($sectionResult['weighted_score'] !== null) {
            $hasOverallScore = true;
            $overallTotal += (float) $sectionResult['weighted_score'];
        }
    }

    if ($hasThematicScore) {
        $thematicTotal = min(100.0, $thematicTotal);
        $overallTotal += $thematicTotal;
        $hasOverallScore = true;
    } else {
        $thematicTotal = null;
    }

    return [
        'sections' => $results,
        'thematic_total' => $thematicTotal !== null ? evaluationV4Round((float) $thematicTotal) : null,
        'overall_total' => $hasOverallScore ? evaluationV4Round($overallTotal) : null,
    ];
}

function evaluationV4FormatScore($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    $numeric = (float) $value;
    if (abs($numeric - round($numeric)) < 0.00001) {
        return (string) ((int) round($numeric));
    }

    return number_format($numeric, 2, ',', '.');
}

function evaluationGetForcedWeightedMaxScoreForModel(?string $modelVersion): float
{
    return evaluationIsLegacyModel($modelVersion) ? evaluationGetLegacyMaxTotalScoreRaw() : evaluationGetV4Definition()['max_total_score'];
}




