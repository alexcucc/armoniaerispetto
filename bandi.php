<?php
require_once 'db/common-db.php';

$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$activeCalls = [];
$pastCalls = [];
$callsLoadError = null;
$requestedTab = isset($_GET['tab']) ? strtolower(trim((string) $_GET['tab'])) : '';
$selectedTab = $requestedTab === 'attivi' ? 'attivi' : 'passati';
$isPastTabActive = $selectedTab === 'passati';
$isActiveTabActive = $selectedTab === 'attivi';

try {
    $stmt = $pdo->query('SELECT id, title, description, start_date, end_date FROM call_for_proposal');
    $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($calls as $call) {
        $startDate = (new DateTimeImmutable($call['start_date']))->format('Y-m-d');
        $endDate = (new DateTimeImmutable($call['end_date']))->format('Y-m-d');

        $call['start_date_only'] = $startDate;
        $call['end_date_only'] = $endDate;

        if ($endDate < $today) {
            $pastCalls[] = $call;
            continue;
        }

        if ($startDate <= $today) {
            $activeCalls[] = $call;
        }
    }

    usort($activeCalls, function (array $first, array $second): int {
        $endDateComparison = strcmp($first['end_date_only'], $second['end_date_only']);
        if ($endDateComparison !== 0) {
            return $endDateComparison;
        }

        return strcasecmp($first['title'], $second['title']);
    });

    usort($pastCalls, function (array $first, array $second): int {
        $endDateComparison = strcmp($second['end_date_only'], $first['end_date_only']);
        if ($endDateComparison !== 0) {
            return $endDateComparison;
        }

        return strcasecmp($first['title'], $second['title']);
    });
} catch (Throwable $exception) {
    $callsLoadError = 'Al momento non è possibile caricare i bandi. Riprova più tardi.';
    error_log('Error loading calls for bandi.php: ' . $exception->getMessage());
}
?>
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
          <div class="content">
            <div class="button-container">
              <a href="bandi_e_finanziamenti.php" class="page-button back-button">Indietro</a>
            </div>
            <?php if ($callsLoadError !== null): ?>
              <p><?php echo htmlspecialchars($callsLoadError); ?></p>
            <?php else: ?>
              <div class="tab-container" role="region" aria-label="Elenco bandi" data-sync-query-param="tab">
                <div class="tab-buttons" role="tablist" aria-label="Filtri bandi">
                  <button class="tab-button tab-button-past<?php echo $isPastTabActive ? ' active' : ''; ?>" type="button" role="tab" aria-controls="bandi-passati" aria-selected="<?php echo $isPastTabActive ? 'true' : 'false'; ?>" data-tab-query-value="passati">Passati</button>
                  <button class="tab-button<?php echo $isActiveTabActive ? ' active' : ''; ?>" type="button" role="tab" aria-controls="bandi-attivi" aria-selected="<?php echo $isActiveTabActive ? 'true' : 'false'; ?>" data-tab-query-value="attivi">Attivi</button>
                </div>

                <section id="bandi-passati" class="tab-panel<?php echo $isPastTabActive ? ' active' : ''; ?>" role="tabpanel"<?php echo $isPastTabActive ? '' : ' hidden'; ?>>
                  <?php if ($pastCalls === []): ?>
                    <p>Nessun bando passato.</p>
                  <?php else: ?>
                    <?php foreach ($pastCalls as $call): ?>
                      <article class="call-item">
                        <div class="call-item__content">
                          <h2><?php echo htmlspecialchars($call['title']); ?></h2>
                          <p>
                            Dal <?php echo htmlspecialchars(date('d/m/Y', strtotime($call['start_date']))); ?>
                            al <?php echo htmlspecialchars(date('d/m/Y', strtotime($call['end_date']))); ?>
                          </p>
                          <?php if (trim((string) $call['description']) !== ''): ?>
                            <p><?php echo nl2br(htmlspecialchars($call['description'])); ?></p>
                          <?php endif; ?>
                        </div>
                        <div class="button-container button-container--right call-item__actions">
                          <a class="page-button" href="testo_del_bando.php?id=<?php echo urlencode((string) $call['id']); ?>">Apri bando</a>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </section>

                <section id="bandi-attivi" class="tab-panel<?php echo $isActiveTabActive ? ' active' : ''; ?>" role="tabpanel"<?php echo $isActiveTabActive ? '' : ' hidden'; ?>>
                  <?php if ($activeCalls === []): ?>
                    <p>Nessun bando attivo.</p>
                  <?php else: ?>
                    <?php foreach ($activeCalls as $call): ?>
                      <article class="call-item">
                        <div class="call-item__content">
                          <h2><?php echo htmlspecialchars($call['title']); ?></h2>
                          <p>
                            Dal <?php echo htmlspecialchars(date('d/m/Y', strtotime($call['start_date']))); ?>
                            al <?php echo htmlspecialchars(date('d/m/Y', strtotime($call['end_date']))); ?>
                          </p>
                          <?php if (trim((string) $call['description']) !== ''): ?>
                            <p><?php echo nl2br(htmlspecialchars($call['description'])); ?></p>
                          <?php endif; ?>
                        </div>
                        <div class="button-container button-container--right call-item__actions">
                          <a class="page-button" href="testo_del_bando.php?id=<?php echo urlencode((string) $call['id']); ?>">Apri bando</a>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </section>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
