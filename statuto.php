<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Statuto</title>
  </head>
  <body>
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Statuto</h1>
        </div>
        <div class="content-container">
          <div class="content">
            <object
              data="documents/statuto.pdf"
              type="application/pdf"
              class="pdf-viewer">
              <p>Il tuo browser non supporta la visualizzazione di PDF. Puoi scaricare il file usando il pulsante qui sotto.</p>
            </object>
            <div class="button-container">
              <a href="documents/statuto.pdf" class="page-button" download>
                Scarica file
              </a>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
