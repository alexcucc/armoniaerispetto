<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Presentazione della Domanda</title>
  </head>
  <body>
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Presentazione della Domanda</h1>
        </div>
        <div class="content-container">
          <div class="content">
            <?php
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            ?>
            <p>Per rispondere al bando e presentare una domanda di finanziamento è necessario scaricare e compilare i seguenti 4 documenti e prendere visione delle "Linee guida alla rendicontazione"</p>
            <p>I quattro moduli compilati dovranno essere inviati a <a href="mailto:segreteria@armoniaerispetto.it">segreteria@armoniaerispetto.it</a> assieme ad eventuali vostre integrazioni o proposte.</p>
            <p>Sempre allo stesso indirizzo email è possibile indirizzare richieste di informazioni o chiarimenti sul bando e su come parteciparvi.</p>
            <div class="button-container">
              <a href="documents/presentazione_domanda_bando.zip" class="page-button" download>
                Scarica file
              </a>
            </div>
            <?php
            } else {
            ?>
              <p>Per accedere a questa sezione, effettua il <a href="login.php">login</a> o <a href="signup.php">registrati</a>.</p>
              <?php
            }
            ?>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
