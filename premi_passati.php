<?php
session_start();

require_once 'db/common-db.php';
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Premi passati</title>
  </head>
  <body class="management-page management-page--scroll">
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Premi passati</h1>
        </div>
        <div class="content-container">
          <div class="content">
            <div class="button-container">
              <a href="premi_e_riconoscimenti.php?tab=passati" class="page-button back-button">Indietro</a>
            </div>
            <section>
              <h2>Premio</h2>
              <article>
                <h3>LAV</h3>
                <p>
                  Ogni giorno, la LAV, da quasi cinquant’anni, si dedica con passione a tessere una nuova storia di speranza e cambiamento per gli animali. Questa narrazione prende forma attraverso interventi che strappano le vittime allo sfruttamento, trasformando il dolore in salvezza.<br><br>
                  Il cuore pulsante è un'azione a 360 gradi: dai progetti di veterinaria sociale che proteggono il legame tra persone fragili e i loro compagni a quattro zampe, prevenendo l'abbandono, alle battaglie legali in prima linea contro maltrattamenti e crimini zoo mafiosi. Parallelamente, l'Unità di Emergenza garantisce soccorso immediato in scenari di crisi, siano essi conflitti o calamità naturali.<br><br>
                  L'impegno si eleva infine sul piano politico e istituzionale, agendo capillarmente dalle amministrazioni locali fino alla Commissione europea per forgiare strumenti normativi sempre più efficaci. Il cerchio si chiude con l'accoglienza alla Casa degli Animali LAV, un rifugio sicuro dove le esistenze spezzate degli animali possono finalmente trovare pace e dignità.
                </p>
                <div class="winners-gallery" aria-label="Immagini delle attività LAV">
                  <figure class="winners-gallery__item">
                    <img src="images/lav_cow.jpg" alt="Operatrice LAV accanto a una mucca in un fienile." loading="lazy">
                    <figcaption>Cura e relazione con gli animali in fattoria.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/lav_cani_famiglia.jpg" alt="Donna con tre cani in un trasportino durante una visita." loading="lazy">
                    <figcaption>Supporto alle famiglie con animali.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/lav_cane_recuperato.jpg" alt="Cane salvato riposa dopo un intervento di soccorso." loading="lazy">
                    <figcaption>Accoglienza e recupero.</figcaption>
                  </figure>
                  <figure class="winners-gallery__item">
                    <img src="images/lav_soccorso_cane.jpg" alt="Volontaria sorride accanto a un cane soccorso." loading="lazy">
                    <figcaption>Unità di emergenza sul territorio.</figcaption>
                  </figure>
                </div>
              </article>
            </section>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
