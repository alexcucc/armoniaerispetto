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
              data="documents/bando.pdf" 
              type="application/pdf" 
              class="pdf-viewer">
            </object>
            <a href="documents/bando.pdf" class="page-button" download>
              Scarica file
            </a>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
