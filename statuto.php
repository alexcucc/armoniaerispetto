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
            <div class="pdf-container">
              <object
                data="documents/statuto.pdf"
                type="application/pdf"
                width="100%"
                height="800px">
                <p>
                  Il tuo browser non supporta la visualizzazione PDF.
                  <a href="documents/statuto.pdf" download>Scarica il PDF</a>
                </p>
              </object>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
