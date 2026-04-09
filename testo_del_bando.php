<?php
require_once 'db/common-db.php';

$callId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$callId) {
    header('Location: bandi.php');
    exit();
}

$stmt = $pdo->prepare('SELECT id, end_date, pdf_path FROM call_for_proposal WHERE id = :id');
$stmt->execute([':id' => $callId]);
$call = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$call) {
    header('Location: bandi.php');
    exit();
}

$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$endDate = (new DateTimeImmutable($call['end_date']))->format('Y-m-d');
$isExpired = $endDate < $today;
$backUrl = $isExpired ? 'bandi.php?tab=passati' : 'bandi.php?tab=attivi';
$baseDir = realpath('private/documents/call_for_proposals');
$realPdfPath = realpath(trim((string) ($call['pdf_path'] ?? '')));
$hasPdf = false;
if ($baseDir && $realPdfPath && is_file($realPdfPath)) {
    $normalizedBaseDir = rtrim(str_replace('\\', '/', $baseDir), '/');
    $normalizedRealPdfPath = str_replace('\\', '/', $realPdfPath);
    $hasPdf = strpos($normalizedRealPdfPath, $normalizedBaseDir . '/') === 0;
}
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
            <div class="button-container">
              <a href="<?php echo htmlspecialchars($backUrl); ?>" class="page-button back-button">
                Indietro
              </a>
            </div>
            <?php if ($hasPdf): ?>
            <object
              data="call_for_proposal_public_download.php?id=<?php echo urlencode((string) $callId); ?>#toolbar=0&navpanes=0&scrollbar=1&page=1"
              type="application/pdf"
              class="pdf-viewer"
              loading="lazy">
              <p>Il tuo browser non supporta la visualizzazione di PDF.</p>
            </object>
            <?php else: ?>
            <p>Il PDF di questo bando non è al momento disponibile.</p>
            <?php endif; ?>
            <?php if (!$isExpired): ?>
            <div class="button-container">
              <button onclick="window.location.href='presentazione_della_domanda.php?id=<?php echo urlencode((string) $callId); ?>';" class="page-button">
                Presentazione della risposta al bando
              </button>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
