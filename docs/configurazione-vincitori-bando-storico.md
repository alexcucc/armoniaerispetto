# Configurazione Vincitori Del Bando Storico

Questo documento descrive la procedura completa per ricreare nel nuovo backoffice i vincitori che prima erano presenti nella vecchia pagina statica dei vincitori del bando.

Si riferisce al bando:

- `Bando Armonia e Rispetto I Edizione`

## Prerequisiti

Prima di iniziare, verificare tutte queste condizioni:

- Il codice applicativo è aggiornato con la nuova gestione vincitori.
- Sono state applicate le migration:
  - [V38__call_for_proposal_winners.sql](/abs/path/D:/Alessandro/Projects/Projects/fondazione-armonia-e-rispetto/fondazione-armonia-e-rispetto.github.io/db/migrations/V38__call_for_proposal_winners.sql)
  - [V39__call_for_proposal_winner_publication_status.sql](/abs/path/D:/Alessandro/Projects/Projects/fondazione-armonia-e-rispetto/fondazione-armonia-e-rispetto.github.io/db/migrations/V39__call_for_proposal_winner_publication_status.sql)
- Il bando `Bando Armonia e Rispetto I Edizione` esiste in `Gestione > Bandi`.
- Il bando è in stato `Chiuso`.
- Esistono tre candidature reali, appartenenti a quel bando, in stato `Convalida in definitiva`, corrispondenti a:
  - `Bambini nel deserto`
  - `Atena ODV`
  - `Cras ODV`
- Sono disponibili i file immagine storici usati nella vecchia pagina vincitori.

Se una delle tre candidature non esiste o non è in `Convalida in definitiva`, non sarà selezionabile nel form vincitori.

## Obiettivo Finale

Alla fine della procedura il bando dovrà avere:

- 3 vincitori configurati
- ordine pubblico corretto `1, 2, 3`
- titolo pubblico corretto per ciascun vincitore
- descrizione completa per ciascun vincitore
- galleria immagini corretta e ordinata
- stato finale `Pubblicato`

## Accesso Alla Pagina Corretta

1. Accedere al backoffice.
2. Aprire `Gestione > Bandi`.
3. Trovare `Bando Armonia e Rispetto I Edizione`.
4. Verificare che lo stato sia `Chiuso`.
5. Cliccare il pulsante `Configura vincitori`, `Vincitori in bozza` oppure `Vincitori pubblicati`, a seconda di ciò che compare.

## Regole Da Rispettare Durante Il Caricamento

- Tutti i vincitori vanno configurati nella stessa pagina.
- L’ordine dei vincitori deve essere:
  - `1` Bambini nel deserto
  - `2` Atena ODV
  - `3` Cras ODV
- Le immagini di ciascun vincitore devono essere caricate nell’ordine indicato sotto.
- Il pulsante corretto da usare durante il lavoro è `Salva bozza`.
- Solo al termine dei controlli finali va usato `Pubblica vincitori`.
- La bozza browser non sostituisce il salvataggio server:
  - `Salva bozza browser` salva solo nel browser
  - `Salva bozza` salva nel database ma non pubblica

## Procedura Operativa

### 1. Aprire La Schermata Vincitori

Una volta entrati nella pagina:

- controllare il riquadro `Stato pubblicazione`
- se il bando non deve ancora essere visibile pubblicamente, lasciarlo in `Bozza`
- usare `Salva bozza` durante tutto il caricamento

### 2. Inserire I Tre Vincitori

Se la pagina è vuota:

1. Cliccare `Aggiungi vincitore` fino ad avere tre schede.
2. Riordinarle con drag-and-drop oppure con i pulsanti `Su` e `Giu`.
3. Impostare le posizioni in questo ordine:
   - `1`
   - `2`
   - `3`

Se ci sono già schede presenti:

1. Riordinarle correttamente.
2. Controllare che non ci siano vincitori extra.
3. Se necessario, marcare i vincitori errati con `Elimina vincitore`.

## Configurazione Dettagliata Dei Vincitori

### Vincitore 1

Compilare la prima scheda così:

- `Candidatura`: selezionare `Bambini nel deserto`
- `Posizione`: `1`
- `Titolo pubblico`: `Bambini nel deserto`
- `Descrizione`:

```text
La scelta della ONG nei Paesi in cui opera è orientata alla tutela dell’ambiente.
In Senegal, le attività includono agroecologia, rimboschimento delle mangrovie e la ristrutturazione dell’eco-lodge di Keur Bamboung, quale infrastruttura di supporto alla salvaguardia dell’Area Marina Protetta e risorsa di reddito per gli abitanti dei villaggi che ne hanno la gestione.
L’impegno comprende inoltre la promozione delle energie rinnovabili, il rafforzamento di una scuola di qualità e percorsi di formazione professionale e inserimento lavorativo dei giovani, tra cui la scuola dei meccanici in Burkina Faso
```

#### Immagini Vincitore 1

Caricare le immagini in questo ordine:

1. File `images/bambini_deserto_officina.jpg`
   - ordine `1`
   - alt text `Ragazzi con tute rosse al lavoro in una scuola di meccanica.`
   - didascalia `Scuola dei meccanici in Burkina Faso.`
2. File `images/bambini_deserto_ecolodge.jpg`
   - ordine `2`
   - alt text `Eco-lodge circondato dalla vegetazione lungo un corso d'acqua.`
   - didascalia `Eco-lodge di Keur Bamboung.`
3. File `images/bambini_deserto_agroecologia.jpg`
   - ordine `3`
   - alt text `Persone che seminano in un campo con tecniche di agroecologia.`
   - didascalia `Attività di agroecologia.`
4. File `images/bambini_deserto_mangrovie.jpg`
   - ordine `4`
   - alt text `Giovani mangrovie piantate in una zona paludosa.`
   - didascalia `Rimboschimento delle mangrovie.`

### Vincitore 2

Compilare la seconda scheda così:

- `Candidatura`: selezionare `Atena ODV`
- `Posizione`: `2`
- `Titolo pubblico`: `Atena ODV`
- `Descrizione`:

```text
Atena ODV, attraverso le attività del Centro di Recupero Animali Selvatici - CRAS di Rimini, opera nelle province di Rimini e Forlì-Cesena e, in collaborazione con le istituzioni locali, anche nella Repubblica di San Marino.
Il Centro si impegna nella protezione, nel recupero, nella cura e nella riabilitazione della fauna selvatica, fino alla reimmissione in natura, accogliendo ogni anno oltre 4.500 esemplari, con particolare attenzione alle specie più vulnerabili e di interesse conservazionistico.
Al CRAS accedono ogni giorno decine di animali feriti o debilitati, spesso vittime di traumi e patologie legati a cambiamenti climatici e ambientali e alle attività antropiche.
Questo lavoro è reso possibile da una rete capillare di volontari, volontarie e cittadinanza attiva, che operano in sinergia con veterinari esperti in fauna selvatica, in costante collaborazione con le forze dell’ordine e grazie al sostegno decisivo di enti e istituzioni che scelgono di supportare il CRAS, rafforzandone la capacità di intervento e contribuendo in modo concreto alla tutela di ecosistemi e biodiversità
```

#### Immagini Vincitore 2

Caricare le immagini in questo ordine:

1. File `images/atena_cura_cervo.jpg`
   - ordine `1`
   - alt text `Volontario che presta cure a un giovane cervo ferito.`
   - didascalia `Cure a un giovane cervo.`
2. File `images/atena_coniglio_allattamento.jpg`
   - ordine `2`
   - alt text `Coniglietto in allattamento con biberon durante le cure.`
   - didascalia `Allattamento di un coniglietto.`
3. File `images/atena_volpe_mascherina.jpg`
   - ordine `3`
   - alt text `Volontari con dispositivi di protezione mentre assistono una volpe.`
   - didascalia `Assistenza veterinaria a una volpe.`
4. File `images/atena_gufo_visita.jpg`
   - ordine `4`
   - alt text `Gufo tenuto in sicurezza durante una visita veterinaria.`
   - didascalia `Controllo sanitario su un gufo.`
5. File `images/atena_radiografia_cervo.png`
   - ordine `5`
   - alt text `Cerva sottoposta a radiografia in sala diagnostica.`
   - didascalia `Radiografia diagnostica.`
6. File `images/atena_soccorso_strada.jpg`
   - ordine `6`
   - alt text `Soccorritrici prestano assistenza a un cervo lungo la strada.`
   - didascalia `Soccorso su strada.`
7. File `images/atena_istrice_cure.jpg`
   - ordine `7`
   - alt text `Volontaria tiene un istrice durante le cure.`
   - didascalia `Accoglienza e cure di un istrice.`

### Vincitore 3

Compilare la terza scheda così:

- `Candidatura`: selezionare `Cras ODV`
- `Posizione`: `3`
- `Titolo pubblico`: `Cras ODV`
- `Descrizione`:

```text
Il centro di recupero animali selvatici di Cuneo è uno dei pochi centri in Italia che si occupa indiscriminatamente di qualsiasi tipo di fauna autoctona e esotica in difficoltà.
Forniamo servizio di pronto soccorso 24 ore su 24. Per questo disponiamo di una sala operatoria di primo soccorso e di una sala per prima degenza.
Il nostro scopo è quello di curare, guarire e reintrodurre gli animali nel loro ambiente, quando possibile.
Dopo le cure, il settore tutela Flora e Fauna della provincia di Cuneo individua un luogo protetto e idoneo al rilascio. Sono inoltre molti i progetti portati avanti dal nostro centro, con programmi di reintroduzione di specie gravemente minacciate da estinzione.
Grazie al lavoro instancabile dei nostri volontari che ogni giorno si prendono cura degli animali ospitati al centro, possiamo garantire a tutti i nostri ospiti cure e assistenza, dalla pulizia delle gabbie all’alimentazione, ogni persona rende possibile la sopravvivenza del centro e degli animali ospitati
```

#### Immagini Vincitore 3

Caricare le immagini in questo ordine:

1. File `images/cras_cucciolo_cervo.jpg`
   - ordine `1`
   - alt text `Cucciolo di cervo in sala visite.`
   - didascalia `Accoglienza di un cucciolo di cervo.`
2. File `images/cras_visita_scolaresca.jpg`
   - ordine `2`
   - alt text `Gruppo di studenti in visita al centro con gli operatori.`
   - didascalia `Educazione ambientale con le scuole.`
3. File `images/cras_intervento_chirurgico.jpg`
   - ordine `3`
   - alt text `Equipe veterinaria durante un intervento chirurgico.`
   - didascalia `Intervento chirurgico su fauna selvatica.`
4. File `images/cras_aquila_recupero.jpg`
   - ordine `4`
   - alt text `Aquila in fase di recupero all'interno di una struttura.`
   - didascalia `Recupero di un'aquila.`
5. File `images/cras_tasso.jpg`
   - ordine `5`
   - alt text `Tasso ospitato al centro di recupero.`
   - didascalia `Accoglienza di un tasso.`

## Salvataggio Consigliato

Ordine consigliato di lavoro:

1. Inserire i tre vincitori con titolo, candidatura, posizione e descrizione.
2. Cliccare `Salva bozza`.
3. Rientrare nella pagina se necessario e verificare che i tre vincitori siano presenti.
4. Caricare tutte le immagini rispettando l’ordine indicato.
5. Cliccare di nuovo `Salva bozza`.
6. Usare `Anteprima pubblica` solo per controllo finale, quando pronto per la pubblicazione.
7. Quando tutto è corretto, cliccare `Pubblica vincitori`.

## Controlli Finali Obbligatori

Prima di pubblicare, verificare:

- il numero totale dei vincitori è `3`
- l’ordine è `1, 2, 3`
- i titoli pubblici sono corretti
- le candidature selezionate corrispondono ai tre vincitori storici
- i testi coincidono con quelli della vecchia pagina
- le immagini sono tutte presenti
- l’ordine delle immagini è corretto
- tutte le immagini hanno `alt text`
- tutte le didascalie corrispondono alla vecchia pagina

## Verifica Dopo La Pubblicazione

Dopo avere cliccato `Pubblica vincitori`:

1. Aprire `Gestione > Bandi`.
2. Verificare che sul bando compaia `Vincitori pubblicati`.
3. Aprire `Anteprima pubblica`.
4. Controllare che la pagina pubblica mostri:
   - il titolo del bando corretto
   - i tre vincitori nell’ordine corretto
   - testo completo corretto
   - immagini corrette
5. Aprire anche il frontend pubblico in `Bandi > Passati`.
6. Verificare la presenza del pulsante `Visualizza vincitori`.
7. Aprire la pagina pubblica dei vincitori e rifare l’ultimo controllo visivo.

## Errori Più Comuni

- Il vincitore non compare tra le candidature selezionabili:
  - la candidatura non appartiene a quel bando
  - la candidatura non è in `Convalida in definitiva`
- Il pulsante pubblico non compare:
  - il bando non è `Chiuso`
  - i vincitori sono ancora in `Bozza`
  - non ci sono vincitori salvati correttamente
- L’upload immagine non viene mantenuto dopo un ripristino bozza browser:
  - è previsto, i file vanno riselezionati
- L’ordine immagini si altera:
  - usare il drag-and-drop o i pulsanti `Su` / `Giu`
  - ricontrollare i valori ordine prima del salvataggio finale

## Riferimenti

- Pagina admin bandi: [call_for_proposals.php](/abs/path/D:/Alessandro/Projects/Projects/fondazione-armonia-e-rispetto/fondazione-armonia-e-rispetto.github.io/call_for_proposals.php)
- Pagina gestione vincitori: [call_for_proposal_winners_manage.php](/abs/path/D:/Alessandro/Projects/Projects/fondazione-armonia-e-rispetto/fondazione-armonia-e-rispetto.github.io/call_for_proposal_winners_manage.php)
- Pagina pubblica vincitori: [call_for_proposal_winners.php](/abs/path/D:/Alessandro/Projects/Projects/fondazione-armonia-e-rispetto/fondazione-armonia-e-rispetto.github.io/call_for_proposal_winners.php)
