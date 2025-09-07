<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once 'db/common-db.php';

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = trim(filter_input(INPUT_POST, 'first_name', FILTER_UNSAFE_RAW));
        $last_name = trim(filter_input(INPUT_POST, 'last_name', FILTER_UNSAFE_RAW));
        $organization = trim(filter_input(INPUT_POST, 'organization', FILTER_UNSAFE_RAW));
        $new_password = trim(filter_input(INPUT_POST, 'new_password', FILTER_UNSAFE_RAW));

        $sql = "UPDATE user SET first_name = ?, last_name = ?, organization = ?";
        $params = [$first_name, $last_name, $organization];

        if (!empty($new_password)) {
            $sql .= ", password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $message = "Profilo aggiornato con successo!";
        } else {
            $message = "Errore durante l'aggiornamento del profilo.";
        }
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT first_name, last_name, email, organization FROM user WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php'; ?>
    <title>Profilo Utente</title>
</head>
<body>
    <?php include 'header.php'; ?>

    <main>
        <div class="contact-form-container">
            <h2>Il Tuo Profilo</h2>
            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>

            <form class="contact-form" method="POST">
                <div class="form-group">
                    <label class="form-label required" for="first_name">Nome</label>
                    <input type="text" id="first_name" name="first_name" class="form-input" 
                           value="<?php echo htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="last_name">Cognome</label>
                    <input type="text" id="last_name" name="last_name" class="form-input" 
                           value="<?php echo htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="organization">Organizzazione</label>
                    <input type="text" id="organization" name="organization" class="form-input" 
                           value="<?php echo htmlspecialchars($user['organization'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" readonly disabled>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">Nuova Password (lascia vuoto per non modificare)</label>
                    <div class="password-container">
                        <input type="password" id="new_password" name="new_password" class="form-input">
                        <button type="button" class="toggle-password" aria-label="Mostra/Nascondi password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="update_profile" class="page-button">Aggiorna Profilo</button>
                </div>
            </form>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>