<?php
require_once 'db/common-db.php';
require_once 'call_for_proposal_winner_utils.php';

$callId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$callId || !callForProposalWinnersTableExists($pdo)) {
    header('Location: bandi.php?tab=passati');
    exit();
}

$publicationStatusSelect = callForProposalWinnerPublicationStatusColumnExists($pdo)
    ? ', winner_publication_status'
    : ', "PUBLISHED" AS winner_publication_status';

$callStmt = $pdo->prepare('SELECT id, title, status' . $publicationStatusSelect . ' FROM call_for_proposal WHERE id = :id');
$callStmt->execute([':id' => $callId]);
$call = $callStmt->fetch(PDO::FETCH_ASSOC);

if (
    !$call
    || ($call['status'] ?? '') !== 'CLOSED'
    || ($call['winner_publication_status'] ?? 'DRAFT') !== 'PUBLISHED'
) {
    header('Location: bandi.php?tab=passati');
    exit();
}

$winnersStmt = $pdo->prepare(
    'SELECT w.id, w.display_order, w.public_title, w.description, '
    . 'a.project_name, o.name AS organization_name '
    . 'FROM call_for_proposal_winner w '
    . 'JOIN application a ON a.id = w.application_id '
    . 'JOIN organization o ON o.id = a.organization_id '
    . 'WHERE w.call_for_proposal_id = :call_id '
    . 'ORDER BY w.display_order ASC, w.id ASC'
);
$winnersStmt->execute([':call_id' => $callId]);
$dbWinners = $winnersStmt->fetchAll(PDO::FETCH_ASSOC);

if ($dbWinners === []) {
    header('Location: bandi.php?tab=passati');
    exit();
}

$winnerIds = array_map(static fn (array $winner): int => (int) $winner['id'], $dbWinners);
$imagesByWinnerId = [];
if ($winnerIds !== []) {
    $imagesStmt = $pdo->prepare(
        'SELECT id, winner_id, alt_text, caption '
        . 'FROM call_for_proposal_winner_image '
        . 'WHERE winner_id IN ('
        . implode(',', array_fill(0, count($winnerIds), '?'))
        . ') ORDER BY winner_id ASC, display_order ASC, id ASC'
    );
    $imagesStmt->execute($winnerIds);
    foreach ($imagesStmt->fetchAll(PDO::FETCH_ASSOC) as $image) {
        $winnerId = (int) $image['winner_id'];
        if (!isset($imagesByWinnerId[$winnerId])) {
            $imagesByWinnerId[$winnerId] = [];
        }

        $imagesByWinnerId[$winnerId][] = [
            'src' => 'call_for_proposal_winner_image.php?id=' . urlencode((string) $image['id']),
            'alt_text' => (string) $image['alt_text'],
            'caption' => (string) ($image['caption'] ?? ''),
        ];
    }
}

$winners = [];
foreach ($dbWinners as $winner) {
    $winners[] = [
        'display_order' => (int) $winner['display_order'],
        'public_title' => (string) $winner['public_title'],
        'organization_name' => (string) $winner['organization_name'],
        'project_name' => (string) $winner['project_name'],
        'description' => (string) $winner['description'],
        'images' => $imagesByWinnerId[(int) $winner['id']] ?? [],
    ];
}

$pageTitle = (string) $call['title'];
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
  </head>
  <body class="management-page management-page--scroll">
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        </div>
        <div class="content-container">
          <div class="content">
            <div class="button-container">
              <a href="bandi.php?tab=passati" class="page-button back-button">Indietro</a>
            </div>
            <section>
              <h2>Vincitori</h2>
              <?php foreach ($winners as $winner): ?>
                <article>
                  <h3><?php echo htmlspecialchars((string) $winner['display_order']); ?>. <?php echo htmlspecialchars($winner['public_title']); ?></h3>
                  <?php if (trim((string) ($winner['organization_name'] ?? '')) !== '' || trim((string) ($winner['project_name'] ?? '')) !== ''): ?>
                    <p class="winner-card__meta">
                      <?php if (trim((string) ($winner['organization_name'] ?? '')) !== ''): ?>
                        <strong>Ente:</strong> <?php echo htmlspecialchars((string) $winner['organization_name']); ?><br>
                      <?php endif; ?>
                      <?php if (trim((string) ($winner['project_name'] ?? '')) !== ''): ?>
                        <strong>Progetto:</strong> <?php echo htmlspecialchars((string) $winner['project_name']); ?>
                      <?php endif; ?>
                    </p>
                  <?php endif; ?>
                  <p><?php echo nl2br(htmlspecialchars($winner['description'])); ?></p>
                  <?php if (!empty($winner['images'])): ?>
                    <div class="winners-gallery" aria-label="Immagini di <?php echo htmlspecialchars($winner['public_title']); ?>">
                      <?php foreach ($winner['images'] as $image): ?>
                        <figure class="winners-gallery__item">
                          <img src="<?php echo htmlspecialchars((string) $image['src']); ?>" alt="<?php echo htmlspecialchars((string) $image['alt_text']); ?>" loading="lazy">
                          <?php if (trim((string) ($image['caption'] ?? '')) !== ''): ?>
                            <figcaption><?php echo htmlspecialchars((string) $image['caption']); ?></figcaption>
                          <?php endif; ?>
                        </figure>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </section>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
