<?php
session_start();

require_once 'db/common-db.php';
require_once 'RolePermissionManager.php';
$rolePermissionManager = new RolePermissionManager($pdo);

if (!isset($_SESSION['user_id']) || !$rolePermissionManager->userHasPermission($_SESSION['user_id'], RolePermissionManager::$PERMISSIONS['USER_LIST'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Vincitori bando corrente</title>
  </head>
  <body class="management-page management-page--scroll">
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Vincitori del bando corrente</h1>
        </div>
        <div class="content-container">
          <div class="content">
            <section>
              <h2>Vincitori</h2>
              <article>
                <h3>1. Bambini nel deserto</h3>
                <p>
                  La scelta della ONG nei Paesi in cui opera è orientata alla tutela dell’ambiente.<br> In Senegal, le attività includono
                  agroecologia, rimboschimento delle mangrovie e la ristrutturazione dell’eco-lodge di Keur Bamboung, quale
                  infrastruttura di supporto alla salvaguardia dell’Area Marina Protetta e risorsa di reddito per gli abitanti dei villaggi che
                  ne hanno la gestione.<br>
                  L’impegno comprende inoltre la promozione delle energie rinnovabili, il rafforzamento di una scuola di qualità e percorsi
                  di formazione professionale e inserimento lavorativo dei giovani, tra cui la scuola dei meccanici in Burkina Faso
                </p>
                <div class="winners-gallery" aria-label="Immagini del progetto Bambini nel deserto">
                  <figure class="winners-gallery__item">
                    <img src="images/bambini_deserto_officina.jpg" alt="Ragazzi con tute rosse al lavoro in una scuola di meccanica." loading="lazy">
                    <figcaption>Scuola dei meccanici in Burkina Faso.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/bambini_deserto_ecolodge.jpg" alt="Eco-lodge circondato dalla vegetazione lungo un corso d'acqua." loading="lazy">
                    <figcaption>Eco-lodge di Keur Bamboung.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/bambini_deserto_agroecologia.jpg" alt="Persone che seminano in un campo con tecniche di agroecologia." loading="lazy">
                    <figcaption>Attività di agroecologia.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/bambini_deserto_mangrovie.jpg" alt="Giovani mangrovie piantate in una zona paludosa." loading="lazy">
                    <figcaption>Rimboschimento delle mangrovie.</figcaption>
                  </figure>
                </div>
              </article>
              <article>
                <h3>2. Atena ODV</h3>
                <p>
                  Atena ODV, attraverso le attività del Centro di Recupero Animali Selvatici - CRAS di Rimini,
                  opera nelle province di Rimini e Forlì-Cesena e, in collaborazione con le istituzioni locali, anche
                  nella Repubblica di San Marino.<br> Il Centro si impegna nella protezione, nel recupero, nella cura e
                  nella riabilitazione della fauna selvatica, fino alla reimmissione in natura, accogliendo ogni anno
                  oltre 4.500 esemplari, con particolare attenzione alle specie più vulnerabili e di interesse
                  conservazionistico.<br> Al CRAS accedono ogni giorno decine di animali feriti o debilitati, spesso
                  vittime di traumi e patologie legati a cambiamenti climatici e ambientali e alle attività antropiche.<br>
                  Questo lavoro è reso possibile da una rete capillare di volontari, volontarie e cittadinanza attiva,
                  che operano in sinergia con veterinari esperti in fauna selvatica, in costante collaborazione con le
                  forze dell’ordine e grazie al sostegno decisivo di enti e istituzioni che scelgono di supportare il
                  CRAS, rafforzandone la capacità di intervento e contribuendo in modo concreto alla tutela di
                  ecosistemi e biodiversità
                </p>
                <div class="winners-gallery" aria-label="Immagini del Centro di Recupero Animali Selvatici di Rimini">
                  <figure class="winners-gallery__item">
                    <img src="images/atena_cura_cervo.jpg" alt="Volontario che presta cure a un giovane cervo ferito." loading="lazy">
                    <figcaption>Cure a un giovane cervo.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/atena_coniglio_allattamento.jpg" alt="Coniglietto in allattamento con biberon durante le cure." loading="lazy">
                    <figcaption>Allattamento di un coniglietto.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/atena_volpe_mascherina.jpg" alt="Volontari con dispositivi di protezione mentre assistono una volpe." loading="lazy">
                    <figcaption>Assistenza veterinaria a una volpe.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/atena_gufo_visita.jpg" alt="Gufo tenuto in sicurezza durante una visita veterinaria." loading="lazy">
                    <figcaption>Controllo sanitario su un gufo.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/atena_radiografia_cervo.png" alt="Cerva sottoposta a radiografia in sala diagnostica." loading="lazy">
                    <figcaption>Radiografia diagnostica.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/atena_soccorso_strada.jpg" alt="Soccorritrici prestano assistenza a un cervo lungo la strada." loading="lazy">
                    <figcaption>Soccorso su strada.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/atena_istrice_cure.jpg" alt="Volontaria tiene un istrice durante le cure." loading="lazy">
                    <figcaption>Accoglienza e cure di un istrice.</figcaption>
                  </figure>
                </div>
              </article>
              <article>
                <h3>3. Cras ODV</h3>
                <p>
                  Il centro di recupero animali selvatici di Cuneo è uno dei pochi centri in Italia
                  che si occupa indiscriminatamente di qualsiasi tipo di fauna autoctona e
                  esotica in difficoltà.<br> Forniamo servizio di pronto soccorso 24 ore su 24.
                  Per questo disponiamo di una sala operatoria di primo soccorso e di una sala
                  per prima degenza.<br> Il nostro scopo è quello di curare, guarire e reintrodurre gli
                  animali nel loro ambiente, quando possibile.<br> Dopo le cure, il settore tutela
                  Flora e Fauna della provincia di Cuneo individua un luogo protetto e idoneo al
                  rilascio. Sono inoltre molti i progetti portati avanti dal nostro centro, con
                  programmi di reintroduzione di specie gravemente minacciate da estinzione.<br>
                  Grazie al lavoro instancabile dei nostri volontari che ogni giorno si prendono
                  cura degli animali ospitati al centro, possiamo garantire a tutti i nostri ospiti
                  cure e assistenza, dalla pulizia delle gabbie all’alimentazione, ogni persona
                  rende possibile la sopravvivenza del centro e degli animali ospitati
                </p>
                <div class="winners-gallery" aria-label="Immagini del Centro di Recupero Animali Selvatici di Cuneo">
                  <figure class="winners-gallery__item">
                    <img src="images/cras_cucciolo_cervo.jpg" alt="Cucciolo di cervo in sala visite." loading="lazy">
                    <figcaption>Accoglienza di un cucciolo di cervo.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/cras_visita_scolaresca.jpg" alt="Gruppo di studenti in visita al centro con gli operatori." loading="lazy">
                    <figcaption>Educazione ambientale con le scuole.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/cras_intervento_chirurgico.jpg" alt="Equipe veterinaria durante un intervento chirurgico." loading="lazy">
                    <figcaption>Intervento chirurgico su fauna selvatica.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/cras_aquila_recupero.jpg" alt="Aquila in fase di recupero all'interno di una struttura." loading="lazy">
                    <figcaption>Recupero di un'aquila.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/cras_tasso.jpg" alt="Tasso ospitato al centro di recupero." loading="lazy">
                    <figcaption>Accoglienza di un tasso.</figcaption>
                  </figure>
                </div>
              </article>
            </section>
            <section>
              <h2>Premio</h2>
              <article>
                <h3>LAV</h3>
                <p>
                  Ogni giorno, la LAV, da quasi cinquant’anni, si dedica con passione a tessere una nuova storia di speranza e cambiamento per gli animali. Questa narrazione prende forma attraverso interventi che strappano le vittime allo sfruttamento, trasformando il dolore in salvezza.<br><br>
                  Il cuore pulsante è un'azione a 360 gradi: dai progetti di veterinaria sociale che proteggono il legame tra persone fragili e i loro compagni a quattro zampe, prevenendo l'abbandono, alle battaglie legali in prima linea contro maltrattamenti e crimini zoo mafiosi. Parallelamente, l'Unità di Emergenza garantisce soccorso immediato in scenari di crisi, siano essi conflitti o calamità naturali.<br><br>
                  L'impegno si eleva infine sul piano politico e istituzionale, agendo capillarmente dalle amministrazioni locali fino alla Commissione europea per forgiare strumenti normativi sempre più efficaci. Il cerchio si chiude con l'accoglienza alla Casa degli Animali LAV, un rifugio sicuro dove le esistenze spezzate degli animali possono finalmente trovare pace e dignità.
                </p>
              </article>
            </section>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
