<?php
$requestedTab = isset($_GET['tab']) ? strtolower(trim((string) $_GET['tab'])) : '';
$selectedTab = $requestedTab === 'attivi' ? 'attivi' : 'passati';
$isPastTabActive = $selectedTab === 'passati';
$isActiveTabActive = $selectedTab === 'attivi';
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Premi e Riconoscimenti</title>
  </head>
  <body>
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Premi e Riconoscimenti</h1>
        </div>
        <div class="content-container">
          <div class="content">
            <div class="tab-container" role="region" aria-label="Elenco premi e riconoscimenti" data-sync-query-param="tab">
              <div class="tab-buttons" role="tablist" aria-label="Filtri premi e riconoscimenti">
                <button class="tab-button tab-button-past<?php echo $isPastTabActive ? ' active' : ''; ?>" type="button" role="tab" aria-controls="premi-passati" aria-selected="<?php echo $isPastTabActive ? 'true' : 'false'; ?>" data-tab-query-value="passati">Passati</button>
                <button class="tab-button<?php echo $isActiveTabActive ? ' active' : ''; ?>" type="button" role="tab" aria-controls="premi-attivi" aria-selected="<?php echo $isActiveTabActive ? 'true' : 'false'; ?>" data-tab-query-value="attivi">Attivi</button>
              </div>

              <section id="premi-passati" class="tab-panel<?php echo $isPastTabActive ? ' active' : ''; ?>" role="tabpanel"<?php echo $isPastTabActive ? '' : ' hidden'; ?>>
                <p>Nessun premio o riconoscimento passato disponibile al momento.</p>
              </section>

              <section id="premi-attivi" class="tab-panel<?php echo $isActiveTabActive ? ' active' : ''; ?>" role="tabpanel"<?php echo $isActiveTabActive ? '' : ' hidden'; ?>>
                <p>Nessun premio o riconoscimento attivo al momento.</p>
              </section>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
