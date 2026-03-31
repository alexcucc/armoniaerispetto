<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Bandi e Finanziamenti</title>
  </head>
  <body class="bandi-finanziamenti-page">
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Bandi e Finanziamenti</h1>
        </div>
        <div class="content-container">
          <div class="content">
            <div class="button-container">
              <a href="index.php" class="page-button back-button">Indietro</a>
            </div>
            <div class="button-groups-container" aria-label="Sezioni bandi e finanziamenti">
              <div class="button-group">
                <a href="bandi.php?tab=attivi" class="page-button">Bandi</a>
                <div class="sub-button-container">
                  <a href="bandi.php?tab=attivi" class="page-button secondary sub-page-button">Bandi attivi</a>
                  <a href="bandi.php?tab=passati" class="page-button secondary sub-page-button">Bandi passati</a>
                </div>
              </div>
              <div class="button-group">
                <a href="premi_e_riconoscimenti.php?tab=attivi" class="page-button">Premi e Riconoscimenti</a>
                <div class="sub-button-container">
                  <a href="premi_e_riconoscimenti.php?tab=attivi" class="page-button secondary sub-page-button">Premi attivi</a>
                  <a href="premi_e_riconoscimenti.php?tab=passati" class="page-button secondary sub-page-button">Premi passati</a>
                </div>
              </div>
              <div class="button-group">
                <a href="finanziamenti_e_sussidi.php" class="page-button">Finanziamenti e Sussidi</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
