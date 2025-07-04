<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Testo del Bando</title>
  </head>
  <body>
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Testo del Bando</h1>
        </div>
        <div class="content-container">
          <div class="content">
            <object 
              data="documents/bando.pdf#toolbar=0&navpanes=0&scrollbar=1&view=FitH&page=1" 
              type="application/pdf" 
              class="pdf-viewer"
              loading="lazy">
              <p>Il tuo browser non supporta la visualizzazione di PDF. Puoi scaricare il file usando il pulsante qui sotto.</p>
            </object>
            <div class="button-container">
              <a href="documents/bando.pdf" class="page-button" download>
                Scarica il bando
              </a>
              <button onclick="window.location.href='presentazione_della_domanda.php';" class="page-button">
                Presentazione della domanda
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
