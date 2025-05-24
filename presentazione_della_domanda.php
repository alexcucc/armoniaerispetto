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
          <?php
          if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
          ?>
          <a href="documents/presentazione_domanda_bando.zip" class="page-button" download>
            Scarica file
          </a>
          <?php
          } else {
          ?>
            <p>Per accedere a questa sezione, effettua il <a href="login.php">login</a> o <a href="signup.php">registrati</a>.</p>
            <?php
          }
          ?>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
