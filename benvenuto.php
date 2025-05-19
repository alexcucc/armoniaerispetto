<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <?php include 'common-head.php'; ?>
        <title>Benvenuto</title>
    </head>
    <body>
        <?php include 'header.php'; ?>
        <main>
            <div class="hero">
                <div class="title">
                    <h1>Benvenuto <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h1>
                </div>
                <div class="content-container">
                    <div class="content">
                        <button onclick="window.location.href='index.php';" class="page-button">Home</button>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'footer.php'; ?>
    </body>
</html>