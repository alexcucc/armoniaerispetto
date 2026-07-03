<?php

declare(strict_types=1);

function evaluationGetCurrentModelVersion(): string
{
    return 'v5';
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
        'thematic_display_max_score' => 100.0,
        'sections' => [
            'proposing_entity' => [
                'label' => 'Soggetto proponente',
                'type' => 'weighted',
                'table' => 'evaluation_v4_proposing_entity',
                'max' => 30,
                'criteria' => [
                    'general_information_score' => [
                        'label' => 'Informazioni generali',
                        'weight' => 4,
                        'help' => "Ha un'identità chiara? Il suo scopo sociale è ben identificato nello statuto? (es. obiettivi non siano troppo eterogenei tra di loro)\" La mission è in linea con le attività realizzate? La sua reputazione e il suo impatto sono chiari e dimostrabili? il convalidatore cerca evidenze sul web e le riporta sulla checklist.",
                    ],
                    'activities_consistency_score' => [
                        'label' => 'Coerenza attività svolte',
                        'weight' => 4,
                        'help' => "- Lo statuto è coerente rispetto al bando?\n- Il track record degli ultimi due anni è in linea con quanto previsto dallo statuto e dallo scopo sociale? (es. di incoerenza= nello statuto aiuto ai carcerati e attività realizzate negli ultimi due anni sono volte alla sensibilizz animali. Il convalidatore ne dà evidanza nella checklist)",
                    ],
                    'experience_score' => [
                        'label' => 'Esperienza',
                        'weight' => 4,
                        'help' => "2,5 punti (0-3 anni di esperienza)\nEnti di recente costituzione\nStruttura ancora in fase di sviluppo\nBassa esperienza gestionale e operativa\n5 punti (4-10 anni di esperienza)\nOrganizzazione più consolidata\nPrime esperienze significative nella gestione di progetti e finanziamenti\nMaggiore riconoscibilità sul territorio\n7,5 punti (11-25 anni di esperienza)\nSolida esperienza nel settore\nStruttura organizzativa ben definita\nPossibile accesso a finanziamenti più strutturati e collaborazioni stabili\n10 punti (oltre 25 anni di esperienza)\nLunga tradizione e radicamento sul territorio\nStruttura consolidata con una rete ampia di collaborazioni\nForte riconoscimento istituzionale e sociale",
                    ],
                    'organizational_management_score' => [
                        'label' => 'Modalità organizzative e gestionali',
                        'weight' => 1,
                        'help' => "Che tipo di governance ha l'ente? Sono presenti organi specifici per funzioni dedicate (es. comitato scientifico, tecnico, amministrativo)? Se si = + punti\nQuanto incide il volontariato? Più volontari rispetto al totale degli addetti comportano un punteggio maggiore.\nLa capacità organizzativa è da dedurre da come è formulata la proposta al bando. Pertanto, il livello qualitativo dell'organizzazione è desumibile dalla documentazione fornita nella domanda e nei documenti correlati (budget, cronoprogramma, ecc.).",
                    ],
                    'budget_completeness_score' => [
                        'label' => 'Bilancio correttezza e completezza',
                        'weight' => 3,
                        'help' => "Il bilancio è completo e comprensibile?\nI documenti sono esaustivi?",
                    ],
                    'funding_sources_score' => [
                        'label' => 'Bilancio fonti di finanziamento',
                        'weight' => 2,
                        'help' => "Quanto pesa la raccolta fondi rispetto alle entrate totali dell'organizzazione? (es. sotto il 10% = 0 punti; sopra l'80% = 10 punti).\nLa raccolta fondi è ben articolata su più fonti (funding mix)? (monofinanziatore = 0 punti; maggiore diversificazione delle fonti (es. eventi, quote associative, 5x1000, donazioni, sponsorizzazioni, ecc.) = punteggio più alto.\nQual è il peso dei finanziatori pubblici e quello dei finanziatori privati? Attribuire un punteggio maggiore a una percentuale più elevata di finanziamenti privati.\nCi sono ricavi per prestazioni (SCS) ? In questo caso punteggio più basso",
                    ],
                    'financial_soundness_score' => [
                        'label' => 'Bilancio solidità finanziaria',
                        'weight' => 2,
                        'help' => "Hanno debiti difficili da sostenere? (più debito = - punti).\nI ricavi (fonti) sono in crescita o in decrescita negli ultimi anni? (se in crescita = + punti)\nIl bilancio è in equilibrio? Chiude in utile o in perdita? Se chiude sistematicamente in perdita voto negativo.",
                    ],
                    'organizational_structure_score' => [
                        'label' => 'Struttura organizzativa',
                        'weight' => 3,
                        'help' => "La loro struttura organizzativa è adeguata con quanto da loro proposto ?\nIl numero di volontari rispetto al totale addetti (dato che si reperisce dalla domanda) è elevato ? (se si + punti)",
                    ],
                    'local_purpose_involvement_score' => [
                        'label' => 'Finalità e coinvolgimento locale',
                        'weight' => 3,
                        'help' => "Ha partnership locali? In caso affermativo, attribuire un punteggio maggiore.\nVi è evidenza di una buona reputazione a livello locale? In caso affermativo, attribuire un punteggio maggiore.\nIl convalidatore deve ricercare evidenze a supporto di tale valutazione e riportarle nella scheda di analisi.\nHa partnership locali? In caso affermativo, attribuire un punteggio maggiore",
                    ],
                    'partnership_visibility_score' => [
                        'label' => 'Partnership e visibilità',
                        'weight' => 4,
                        'help' => "Fa parte di network riconosciuti ? Ha partnership attive con università, istituzioni, aziende, altri ETS ? Ha un sito WEB ? E' presente sui social ? In caso affermativo, attribuire un punteggio maggiore.",
                    ],
                ],
            ],
            'project' => [
                'label' => 'Progetto',
                'type' => 'weighted',
                'table' => 'evaluation_v4_project',
                'max' => 30,
                'criteria' => [
                    'needs_analysis_score' => [
                        'label' => 'Identificazione dei bisogni e analisi dei problemi',
                        'weight' => 2,
                        'help' => "L'analisi è completa, sufficientemente dettagliata e coerente?\nRisulta effettivamente rispondente a un bisogno emerso?",
                    ],
                    'objectives_consistency_score' => [
                        'label' => 'Obiettivi coerenti con il bando',
                        'weight' => 6,
                        'help' => "Gli obiettivi del progetto sono coerenti rispetto al bando?\nE' previsto un monitoraggio dei risultati nel tempo ?",
                    ],
                    'objectives_ambition_score' => [
                        'label' => 'Obiettivi ambiziosi/ragionevoli/sensati',
                        'weight' => 4,
                        'help' => "Gli obiettivi sono ambiziosi ma contemporaneamente ragionevoli/sensati? (es. non sono campati per aria/non sono sogni, sono concreti e non velleitari)",
                    ],
                    'objectives_feasibility_score' => [
                        'label' => 'Obiettivi realizzabili nel contesto',
                        'weight' => 2,
                        'help' => "Gli obiettivi sono realizzabili per via del contesto e secondo la modalità attuativa esposta?",
                    ],
                    'expected_results_score' => [
                        'label' => 'Risultati attesi',
                        'weight' => 3,
                        'help' => "Sono concreti e misurabili? In caso contrario, attribuire un punteggio basso.",
                    ],
                    'activities_score' => [
                        'label' => 'Attività',
                        'weight' => 3,
                        'help' => "Sono coerenti rispetto all'obiettivo dichiarato?\nChiare?\nSufficientemente dettagliate?\nRealizzabili?\nEfficaci? (Le azioni permettono di raggiungere l'obiettivo dichiarato?)",
                    ],
                    'local_purpose_score' => [
                        'label' => 'Finalità locale',
                        'weight' => 2,
                        'help' => "Il progetto ha una chiara finalità locale? (più è legato al territorio più è alto il punteggio)",
                    ],
                    'partnership_local_authorities_score' => [
                        'label' => 'Partenariato e rapporti con autorità locali/nazionali',
                        'weight' => 2,
                        'help' => "Il/i partner è/sono un valore aggiunto? Completano e/o arricchiscono il progetto?\nPermettono di raggiungere un maggior numero di beneficiari?\nI rapporti con le autorità locali sono sviluppati e fruttuosi?\nPiù partenariato = + punti",
                    ],
                    'synergies_efficiency_score' => [
                        'label' => 'Sinergie ed efficienze progettuali',
                        'weight' => 2,
                        'help' => "Progetto integrato o rindondante\nE' un progetto che condivide obiettivi, stakeholder, risorse, metodologie o deliverable con altri progetti precedenti o in corso? Se si = + punti\nPresenta sinergie con gli stessi? Se si = + punti\nSono previsti recovery plan (gestione insuccessi del progetto) nel caso in cui il progetto andasse male? Se si = + punti\nReplica progetti di successo già realizzati altrove? Se si = + punti",
                    ],
                    'inefficiencies_score' => [
                        'label' => 'Inefficienze progettuali (voto solo negativo)',
                        'weight' => 1,
                        'min' => -10,
                        'max' => 0,
                        'help' => "Progetto integrato o rindondante\nSono previste sovrapposizioni nei risultati attesi con altri progetti? Se si meno punti\nRisulta una duplicazione eccessiva di attività, obiettivi o output ? Se si= - punti\nIl voto può essere solo negativo",
                    ],
                    'communication_visibility_score' => [
                        'label' => 'Comunicazione e visibilità',
                        'weight' => 3,
                        'help' => "Valorizza la collaborazione Ente - Fondazione AR?\nSono previste attività comunicative su media? Queste attività comunicative danno risalto alla Fondazione AR ?\nPer ogni si =+ punti",
                    ],
                ],
            ],
            'financial_plan' => [
                'label' => 'Piano Finanziario',
                'type' => 'weighted',
                'table' => 'evaluation_v4_financial_plan',
                'max' => 15,
                'criteria' => [
                    'funding_limits_compliance_score' => [
                        'label' => 'Rispetto dei valori limite del finanziamento (voto solo negativo)',
                        'weight' => 2,
                        'min' => -10,
                        'max' => 0,
                        'help' => "L' importo richiesto resta entro i limiti di finanziamento indicati nel bando? Se no voto negativo",
                    ],
                    'budget_clarity_score' => [
                        'label' => 'Completezza e chiarezza del budget',
                        'weight' => 3,
                        'help' => "Il budget è chiaro e completo in tutte le sue parti?",
                    ],
                    'budget_consistency_score' => [
                        'label' => 'Coerenza con obiettivi, risultati, impatto e cronogramma',
                        'weight' => 1,
                        'help' => "Il budget risulta coerente con gli obiettivi e i risultati del Progetto?\nPermette il rispetto del cronoprogramma e il raggiungimento dell'impatto atteso?",
                    ],
                    'cofinancing_score' => [
                        'label' => 'Cofinanziamento',
                        'weight' => 2,
                        'help' => "La percentuale del cofinanziamento proprio o di terzi è adeguata ? + elevata la % = + punti\nLe fonti di cofinanziamento sono autorevoli? Più sono autorevoli (es. grosse fondazioni o entri pubblici maggiori) più elevato il punteggio (i cofinanziatori sono indicati sulla domanda)",
                    ],
                    'flexibility_score' => [
                        'label' => 'Flessibilità',
                        'weight' => 3,
                        'help' => "il budget è in grado di far fronte a eventuali cambiamenti progettuali in corso d'opera senza perdere efficacia ? Più è flessibile = + punti",
                    ],
                    'project_value_soundness_score' => [
                        'label' => 'Solidità – valore del progetto',
                        'weight' => 2,
                        'help' => "L'importo del progetto vale + del 25% delle entrate dell'ultimo bilancio? Non si può chiedere per il progetto + di 1/4 delle entrare dell'ultimo bilancio. Altrimenti voto negativo\nSe il contributo è troppo basso (meno dell 1% del loro volume di affari) allora voto basso.\nSe il contributo è molto basso in relazione alle disponibilità liquide (disponibilità finanziarie) signfica poca necessità di finanziamento e quindi = - punti.\nIl totale di questa voce non può essere negativo",
                    ],
                    'staff_cost_incidence_score' => [
                        'label' => 'Incidenza spese per personale',
                        'weight' => 2,
                        'help' => "Incidenza manodopera + spese viaggio rispetto al totale progetto. Se la % è elevata voto basso",
                    ],
                ],
            ],
            'qualitative_elements' => [
                'label' => 'Elementi Qualitativi',
                'type' => 'weighted',
                'table' => 'evaluation_v4_qualitative_elements',
                'max' => 25,
                'criteria' => [
                    'new_project_score' => [
                        'label' => 'Nuovo progetto',
                        'weight' => 3,
                        'help' => "Si tratta di un nuovo progetto o di iniziative manutentive o per gestione corrente ? Se non è nuovo progetto o implementazione di esistente = - punti.",
                    ],
                    'long_term_impact_score' => [
                        'label' => "L'impatto e gli effetti di più ampio e lungo termine prodotti dall’iniziativa in ragione del contesto di intervento",
                        'weight' => 5,
                        'help' => "Il progetto ha la potenzialità di influire in maniera sistemica nel lungo periodo?",
                    ],
                    'context_relevance_score' => [
                        'label' => "Pertinenza del progetto rispetto ai bisogni e criticità specifiche del Paese, della Regione, del settore d’intervento, della sinergia con altri programmi",
                        'weight' => 3,
                        'help' => "Il progetto è in linea con i bisogni prioritari dell'area d'intervento? È rilevante rispetto all criticità territoriali? È coerente con le politiche pubbliche e i relativi piani di sviluppo? È supportato dalle istituzioni?",
                    ],
                    'innovation_score' => [
                        'label' => 'Innovatività del Progetto',
                        'weight' => 5,
                        'help' => "è previsto l'utilizzo di tecnologie o metodi e approcci nuovi per il raggiungimento degli obiettivi dichiarati ? Es. droni, IA, sensori satellitari, collari GPS, riconoscimento immagini...",
                    ],
                    'scientific_rigor_score' => [
                        'label' => 'Rigore e validità scientifica',
                        'weight' => 6,
                        'help' => "la proposta è basata su evidenze scientifiche, opportunamente spiegate e con le fonti?\nCi sono partenariati o collaborazioni dichiarate con qualche ente di ricerca ?",
                    ],
                    'replicability_scalability_score' => [
                        'label' => 'Replicabilità e scalabilità',
                        'weight' => 3,
                        'help' => "Il progetto può essere adattato e applicato in altri contesti?",
                    ],
                ],
            ],
            'thematic_safeguard' => [
                'label' => 'SALVAGUARDIA ANIMALI',
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_safeguard',
                'max' => 70,
                'description' => "Per salvaguardia animale si intendono quelle azioni rivolte a Prevenzione, Accoglienza, Riparo, Cura e Difesa, Contrasto ai maltrattamenti, agli abusi, all'abbandono, allo sfruttamento e alla crudeltà, che sono qui dettagliate.\nAttribuire un voto solo alle voci che dalla domanda si desume si vogliano perseguire. Il voto valuta l'efficacia della proposta per il raggiungimento dell' obiettivo (+ o – bene)",
                'criteria' => [
                    'prevention_operational_tactical_score' => [
                        'label' => 'Prevenzione (operativa – tattica)',
                        'weight' => 6,
                        'help' => "Scoprire e denunciare casi di maltrattamenti e/o abusi con uso di stampa e media",
                    ],
                    'legal_contrast_score' => [
                        'label' => 'Contrasto ai maltrattamenti (giuridico/legale)',
                        'weight' => 8,
                        'help' => "Azioni legali contro casi specifici, querele, segnalazioni e denunce alla magistratura e agli enti pubblici preposti, supporto a procedimenti,",
                    ],
                    'liberation_actions_score' => [
                        'label' => 'Contrasto ai maltrattamenti (azioni di liberazione)',
                        'weight' => 16,
                        'help' => "Azioni concrete per la liberazione fisica degli animali dai luoghi di abuso (allevamenti intensivi, laboratori per sperimentazioni). Interventi diretti di recupero, sequestro, salvataggio e sottrazione degli animali da tali situazioni",
                    ],
                    'shelter_remedy_score' => [
                        'label' => 'Rimedio accoglimento',
                        'weight' => 8,
                        'help' => "Creazione o sviluppo di canili e gattili o santuari e rifugi per domestici o di allevamento Capacità di offrire ricovero temporaneo o permanente agli animali recuperati o tutelati Capacità di garantire adeguatezza delle cure mediche e dei percorsi riabilitativi dopo il recupero/accoglimento",
                    ],
                    'recovery_remedy_score' => [
                        'label' => 'Rimedio recupero',
                        'weight' => 8,
                        'help' => "Recupero animali feriti, recupero sequestrati, salvataggio animali in natura",
                    ],
                    'protection_remedy_score' => [
                        'label' => 'Rimedio protezione',
                        'weight' => 10,
                        'help' => "Creazione o Co-partecipazione a sviluppo di CRAS per selvatici",
                    ],
                    'veterinary_rehabilitation_score' => [
                        'label' => 'Rimedio cura e riabilitazione veterinaria',
                        'weight' => 8,
                        'help' => "Fornitura di cure e interventi di riabilitazione resi in CRAS o cliniche o centri dedicati",
                    ],
                    'relocation_remedy_score' => [
                        'label' => 'Rimedio ricollocamento',
                        'weight' => 6,
                        'help' => "Azioni di ricollocamento di animali sia domestici che da allevamento presso terzi privati. Capacità di favorire adozioni, reinserimento in natura o trasferimento in strutture adeguate",
                    ],
                ],
            ],
            'thematic_conservation_species_habitat' => [
                'label' => 'CONSERVAZIONE SPECIE ED HABITAT',
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_conservation_species_habitat',
                'max' => 60,
                'description' => "Considerando la valenza strategica degli habitat e della biodiversità è prevista questa sezione a parte nella quale sono valutate le componenti specifiche di questa azione.\nQuesta sezione va compilata solo per i progetti specifici di ripopolamento, difesa degli habitat, reintroduzioni. La valutazione delle strategie di mitigazione delle minacce e coinvolgimento della comunità locale sono previste in altra sezione",
                'criteria' => [
                    'habitat_protection_score' => [
                        'label' => 'Protezione habitat',
                        'weight' => 9,
                        'help' => "Ogni azione volta a difendere l'ecosistema/ambiente in cui l'animale vive : es. riforestazione, protezione zone umide, tutela nidi, recupero ecosistemi e corridoi ecologici\nCapacità in generale del progetto di preservare o ripristinare ecosistemi, biodiversità e condizioni ambientali favorevoli alle specie.",
                    ],
                    'reserves_oases_coparticipation_score' => [
                        'label' => 'Co-partecipazione a sviluppo di riserve e oasi',
                        'weight' => 10,
                        'help' => "Creazione o Co-partecipazione a sviluppo di riserve o oasi naturali",
                    ],
                    'repopulation_score' => [
                        'label' => 'Ripopolamento',
                        'weight' => 14,
                        'help' => "Attuazione o sostegno a progetti di ripopolamento di specie in via di estinzione o a rischio di",
                    ],
                    'reintroductions_score' => [
                        'label' => 'Reintroduzioni',
                        'weight' => 14,
                        'help' => "Attuazione o sostegno a progetti di reintroduzione di animali in aree originariamente autoctone",
                    ],
                    'monitoring_research_census_score' => [
                        'label' => 'Monitoraggio, ricerca e censimento',
                        'weight' => 5,
                        'help' => "Attività svolte per fini di pura conoscenza o finalizzate a progetti di ripopolamento e reintroduz.",
                    ],
                    'rare_species_conservation_score' => [
                        'label' => 'Conservazione specie rare',
                        'weight' => 8,
                        'help' => "Se il progetto è specificamente dedicato alla difesa di specie rare",
                    ],
                ],
            ],
            'thematic_anthropic_threat_reduction' => [
                'label' => 'RIDUZIONE MINACCIA ANTROPICA',
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_anthropic_threat_reduction',
                'max' => 40,
                'description' => "In questa sezione vengono valutate le modalità con cui il progetto si propone di ridurre la minaccia derivante dall' antropizzazione\nQuesta sezione va compilata se nella domanda sono presenti temi specifici per la riduzione/gestione di questa minaccia",
                'criteria' => [
                    'anti_poaching_illegal_traffic_score' => [
                        'label' => "Lotta al bracconaggio es al traffico illegale di animali",
                        'weight' => 8,
                        'help' => '',
                    ],
                    'infrastructure_impact_mitigation_score' => [
                        'label' => 'Mitigazione impatto infrastrutture',
                        'weight' => 6,
                        'help' => "Protezione degli animali dall' impatto dei manufatti umani (strade, ferrovie, elettrovie, palazzi etc)",
                    ],
                    'roadkill_prevention_score' => [
                        'label' => 'Prevenzione investimenti stradali',
                        'weight' => 5,
                        'help' => "Se il progetto è specificamente dedicato a proteggere gli animali dagli investimenti stradali",
                    ],
                    'electrocution_protection_score' => [
                        'label' => 'Protezione elettrocuzione elettrodotti',
                        'weight' => 5,
                        'help' => "Se il progetto è specificamente dedicato a proteggere gli animali dal rischio di folgorazione",
                    ],
                    'accidental_capture_prevention_score' => [
                        'label' => 'Prevenzione cattura accidentali',
                        'weight' => 8,
                        'help' => "Se il progetto è dedicato a ridurre il rischio di catture non volute (es. delfini nelle reti a strascico)",
                    ],
                    'pollution_fight_score' => [
                        'label' => "Lotta all'inquinamento",
                        'weight' => 8,
                        'help' => "Se il progetto vuole contrastare l'inquinamento nocivo per gli animali (dell' aria, acustico, luminoso)",
                    ],
                ],
            ],
            'thematic_cohabitation' => [
                'label' => 'COABITAZIONE',
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_cohabitation',
                'max' => 50,
                'description' => "In questa sezione vengono valutate le modalità con cui il progetto si propone di facilitare e rendere proficua la coabitazione uomo - animale.\nQuesta sezione va compilata se nella domanda sono presenti temi specifici per la coabitazione o il ripopolamento o la reintroduzione in ambiti abitati e con potenziali conflitti di interesse con le attività umane",
                'criteria' => [
                    'local_community_mediation_score' => [
                        'label' => 'Coinvolgimento/mediazione comunità locale',
                        'weight' => 5,
                        'help' => "La comunità locale è protagonista del progetto o semplice destinataria? (ad esempio: esiste co-progettazione con la popolazione, partenariati locali formalizzati o iniziative di sensibilizzazione?) (esempio lupo- pastori con gregge)",
                    ],
                    'biodiversity_integration_human_activities_score' => [
                        'label' => 'Tutela della biodiversità e integrazione della presenza animale alle attività umane (es Rwanda)',
                        'weight' => 18,
                        'help' => "E' stato sviluppata la capacità di integrare la presenza della fauna nelle attività agricole, turistiche, culturali o produttive? Sono previsti benefici condivisi derivanti dalla conservazione? Esempio parco Virunga Rwanda)",
                    ],
                    'human_animal_conflict_risk_reduction_score' => [
                        'label' => 'Riduzione del rischio di conflitto uomo/animale',
                        'weight' => 13,
                        'help' => "Il progetto riduce concretamente i rischi per uomini e animali? Esistono misure preventive documentate, sistemi di allerta e monitoraggio, strumenti di compensazione dei danni?\nEs. sono previste Recinzioni, servizi di guardania, indennizzi, gestione grandi carnivori, gestione ungulati",
                    ],
                    'circular_economy_local_support_score' => [
                        'label' => "Sostegno allo sviluppo di un'economia circolare per il sostentamento locale",
                        'weight' => 10,
                        'help' => "La tutela della fauna genera valore economico per il territorio in termini di filiere locali, ecoturismo, produzioni sostenibili oppure occupazione collegata alla conservazione?\nLa proposta di sviluppo è adeguata, concreta e fattibile?",
                    ],
                    'multidisciplinary_overall_strength_score' => [
                        'label' => "Forza d'insieme multidisciplinare (istituzionale, ambientale, culturale, economica)",
                        'weight' => 4,
                        'help' => "Ci sono garanzie di tenuta del progetto a livello istituzionale/ambientale/culturale/economico?",
                    ],
                ],
            ],
            'thematic_community_support' => [
                'label' => "SUPPORTO DI COMUNITA'",
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_community_support',
                'max' => 70,
                'description' => "La Fondazione AR ha nel proprio statuto anche il supporto al sostentamento ed allo sviluppo di comunità.\nQuesta sezione va compilata se nella domanda sono presenti temi specifici per lo sviluppo e/o il sostentamento oppure se il bando pur prevedendo altri temi più animalisti ha delle implicazioni che riguardano le Comunità (sia Italiane che dei paesi meno sviluppati)",
                'criteria' => [
                    'systemic_capacity_building_score' => [
                        'label' => 'Sviluppo sistemico (educativo, economico, produttivo) di capacity buliding',
                        'weight' => 30,
                        'help' => "Per capacy buiding si intende il trasferimento di competenze e capacità atte a rendere autonoma la popolazione su specifici fattori di sostentamento e sviluppo\nIl progetto prende in considerazioni tutte gli ambiti (educativo, economico, produttivo) per garanzia di buona riuscita?",
                    ],
                    'social_discrimination_contrast_score' => [
                        'label' => 'Contrasto alle discriminazione sociali',
                        'weight' => 20,
                        'help' => "Questa voce si riferisce al contrasto ai gap sociali di sviluppo, quali, fame, povertà ma anche alle discriminazioni sociali (di minoranze, di disabilità,.. etc)\nIl bisogno è effettivo? Le risposte concrete ed efficaci?",
                    ],
                    'multistakeholder_context_attention_score' => [
                        'label' => "Attenzione all'ambito : coinvolgimento multistakeholder (comunità locale, istituzioni, privato sociale)",
                        'weight' => 10,
                        'help' => "E' previsto il coinvolgimento diretto di tutti gli stakeholder coinvolti ?",
                    ],
                    'territorial_networks_partnerships_score' => [
                        'label' => 'Capacità di creare reti territoriali e partenariati stabili',
                        'weight' => 10,
                        'help' => "Capacità organizzativa e istituzionale del territorio: coinvolgimento diretto di tutti gli stakeholder nel costruire una rete che continuerà a lavorare insieme anche dopo la fine del finanziamento",
                    ],
                ],
            ],
            'thematic_culture_education' => [
                'label' => 'CULTURA - EDUCAZIONE – SENSIBILIZZAZIONE – ADVOCACY',
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_culture_education',
                'max' => 50,
                'description' => "La diffusione di una cultura pro-animali, specie nelle giovani generazioni, è un presupposto importante per cambiare l'atteggiamento ed i comportamenti verso gli animali, così come lo sono tutti gli interventi e le proposte di intervento per migliorare le leggi e norme che riguardano gli animali\nQuesta sezione va compilata se nella domanda sono presenti temi specifici per la diffusione di una cultura dell' armonia e del rispetto, oppure se il bando pur prevedendo altri temi più animalisti ha delle implicazioni culturali: informative, educative o di sensibilizzazione o di advocacy",
                'criteria' => [
                    'education_and_culture_score' => [
                        'label' => 'Educazione e cultura',
                        'weight' => 20,
                        'help' => "Il progetto si occupa di educazione scolastica pro animali,campagne informative e formazione ?",
                    ],
                    'advocacy_governance_politics_score' => [
                        'label' => 'Advocacy Governance e Politica',
                        'weight' => 15,
                        'help' => "Il progetto si occupa di studi normativi, proposte legislative, proposte di linee guida, supporto enti pubblici ?",
                    ],
                    'scientific_research_score' => [
                        'label' => 'Ricerca scientifica',
                        'weight' => 8,
                        'help' => "Il progetto si occupa di monitoraggio, etologia, epidemiologia, genetica, tecnologie di conservazione, sistemi di censimento ?",
                    ],
                    'involvement_breadth_score' => [
                        'label' => 'Ampiezza del coinvolgimento (cittadinanza, istituzioni, centri di ricerca, agenzie educative)',
                        'weight' => 7,
                        'help' => "Quanto ampio è previsto sia il coinvolgimento diretto di tutti gli stakeholder ?",
                    ],
                ],
            ],
            'thematic_weight_depth' => [
                'label' => "PESO E PROFONDITA' DELL' INTERVENTO",
                'type' => 'thematic',
                'table' => 'evaluation_v4_thematic_weight_depth',
                'max' => 60,
                'description' => "Questa importante sezione si occupa di classificare gli interventi in base alla sistematicità del cambiamento che producono.\nESEMPIO\nLivello 1 – Alleviare una sofferenza\nSalvare un cane ferito.\nBeneficio enorme, ma circoscritto.\nLivello 2 – Eliminare una causa\nSterilizzare una colonia felina.\nSi evita che il problema si ripresenti.\nLivello 3 – Modificare un sistema\nRealizzare corridoi ecologici.\nIl beneficio riguarda migliaia di animali nel tempo.\nLivello 4 – Cambiare la cultura\nEducazione, formazione, nuove norme.\nIl beneficio può durare decenni.",
                'criteria' => [
                    'scale_score' => [
                        'label' => 'Scala',
                        'weight' => 20,
                        'help' => "Quanti animali, specie o ecosistemi coinvolge?",
                    ],
                    'depth_score' => [
                        'label' => 'Profondità',
                        'weight' => 20,
                        'help' => "Riduce un sintomo, elimina una causa o cambia un sistema?",
                    ],
                    'duration_score' => [
                        'label' => 'Durata',
                        'weight' => 20,
                        'help' => "Gli effetti sono temporanei o permanenti?",
                    ],
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
    $weightedScore = 0.0;
    $hasScores = false;

    foreach ($sectionDefinition['criteria'] as $fieldName => $criterionDefinition) {
        $score = $scores[$fieldName] ?? null;
        if ($score === null || !is_numeric($score)) {
            continue;
        }
        $hasScores = true;
        $weightedScore += (((float) $score) * (float) ($criterionDefinition['weight'] ?? 0)) / 10;
    }

    return [
        'weighted_score' => $hasScores ? evaluationV4Round($weightedScore) : null,
        'has_scores' => $hasScores,
    ];
}

function evaluationV4CalculateTotals(array $data): array
{
    $definition = evaluationGetV4Definition();
    $sections = $definition['sections'];
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
        $thematicTotal = min((float) ($definition['thematic_display_max_score'] ?? 100.0), $thematicTotal);
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
