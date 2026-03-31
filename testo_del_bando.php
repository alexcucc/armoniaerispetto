<?php
require_once 'db/common-db.php';

$callId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$callId) {
    header('Location: bandi.php');
    exit();
}

$stmt = $pdo->prepare('SELECT id, end_date FROM call_for_proposal WHERE id = :id');
$stmt->execute([':id' => $callId]);
$call = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$call) {
    header('Location: bandi.php');
    exit();
}

$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$endDate = (new DateTimeImmutable($call['end_date']))->format('Y-m-d');
$isExpired = $endDate < $today;
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Testo del Bando</title>
  </head>
  <body class="document-page">
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="content-container">
          <div class="content">
            <object 
              data="documents/bando.pdf#toolbar=0&navpanes=0&scrollbar=1&page=1" 
              type="application/pdf" 
              class="pdf-viewer"
              loading="lazy">
              <p>Il tuo browser non supporta la visualizzazione di PDF. Puoi scaricare il file usando il pulsante qui sotto.</p>
            </object>
            <div class="button-container">
              <a href="documents/bando.pdf" class="page-button" download>
                Scarica il bando
              </a>
              <?php if (!$isExpired): ?>
              <button onclick="window.location.href='presentazione_della_domanda.php';" class="page-button">
                Presentazione della risposta al bando
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
