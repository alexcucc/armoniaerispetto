<?php
require_once 'db/common-db.php';

$callId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$callId) {
    header('Location: bandi.php?tab=attivi');
    exit();
}

$stmt = $pdo->prepare('SELECT id, title FROM call_for_proposal WHERE id = :id');
$stmt->execute([':id' => $callId]);
$call = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$call) {
    header('Location: bandi.php?tab=attivi');
    exit();
}

$zipPath = 'private/documents/call_for_proposals/' . $callId . '/application_documents.zip';
$zipExists = is_file($zipPath);
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Presentazione della Risposta al Bando</title>
  </head>
  <body>
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Presentazione della Risposta al Bando</h1>
        </div>
        <div class="content-container">
          <div class="content">
            <?php
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            ?>
            <p><strong>Bando:</strong> <?php echo htmlspecialchars($call['title']); ?></p>
            <p>Per rispondere al bando e presentare una domanda di finanziamento è necessario scaricare e compilare i documenti richiesti e prendere visione delle "Linee guida alla rendicontazione".</p>
            <p>I moduli compilati dovranno essere inviati a <a href="mailto:segreteria@armoniaerispetto.it">segreteria@armoniaerispetto.it</a> assieme ad eventuali vostre integrazioni o proposte.</p>
            <p>Sempre allo stesso indirizzo email è possibile indirizzare richieste di informazioni o chiarimenti sul bando e su come parteciparvi.</p>
            <?php if ($zipExists): ?>
            <div class="button-container">
              <a href="call_for_proposal_application_documents_download.php?id=<?php echo urlencode((string) $callId); ?>" class="page-button">
                Scarica file
              </a>
            </div>
            <?php else: ?>
            <p>I documenti per la presentazione della domanda non sono ancora disponibili per questo bando.</p>
            <?php endif; ?>
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
