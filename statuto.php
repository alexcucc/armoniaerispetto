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
                height="100%">
                <div class="mobile-pdf-notice">
                  <div class="pdf-options">
                      <a href="documents/statuto.pdf" class="pdf-button" download>
                          <span class="icon">📥</span>
                          Scarica PDF
                      </a>
                      <a href="documents/statuto.pdf" class="pdf-button" target="_blank">
                          <span class="icon">👁️</span>
                          Visualizza PDF
                      </a>
                  </div>
                </div>
              </object>          
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
