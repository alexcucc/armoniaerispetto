<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Bandi</title>
  </head>
  <body>
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Bandi</h1>
        </div>
        <div class="content-container">
          <?php
          if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
          ?>
            <button onclick="window.location.href='testo_del_bando.php';" class="page-button">Testo del Bando</button>
            <button onclick="window.location.href='presentazione_della_domanda.php';" class="page-button">Presentazione della Domanda</button>
            <button onclick="window.location.href='linee_guida.php';" class="page-button">Linee Guida</button>
          <?php
          } else {
          ?>
            <p>Per accedere a questa sezione, effettua il <a href="login.php">login</a> o <a href="register.php">registrati</a>.</p>
          <?php
          }
          ?>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
