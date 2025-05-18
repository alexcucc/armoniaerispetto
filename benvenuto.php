<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
$userName = $_SESSION['user_name'] ?? 'Utente';
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <?php include 'common-head.php'; ?>
        <title>Benvenuto <?php echo htmlspecialchars($userName); ?></title>
    </head>
    <body>
        <?php include 'header.php'; ?>
        <main>
            <div class="hero">
                <div class="title">
                    <h1>Benvenuto nella tua area riservata</h1>
                </div>
                <div class="content-container">
                    <p>Qui puoi gestire il tuo profilo e accedere a contenuti esclusivi.</p>
                </div>
            </div>
        </main>
        <?php include 'footer.php'; ?>
    </body>
</html>