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
$callOptionsStmt = $pdo->query('SELECT id, title FROM call_for_proposal ORDER BY title');
$callOptions = $callOptionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        
        // First name and last name are read-only and should not be updated
        $organization = trim(filter_input(INPUT_POST, 'organization', FILTER_UNSAFE_RAW));
        $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_UNSAFE_RAW));
        $new_password = trim(filter_input(INPUT_POST, 'new_password', FILTER_UNSAFE_RAW));
        $defaultCallInput = trim((string) (filter_input(INPUT_POST, 'default_call_for_proposal_id', FILTER_UNSAFE_RAW) ?? ''));
        $defaultCallId = null;

        if ($defaultCallInput !== '') {
            if (!ctype_digit($defaultCallInput) || (int) $defaultCallInput <= 0) {
                $message = "Il bando di default selezionato non è valido.";
            } else {
                $candidateCallId = (int) $defaultCallInput;
                $callCheckStmt = $pdo->prepare('SELECT 1 FROM call_for_proposal WHERE id = ?');
                $callCheckStmt->execute([$candidateCallId]);
                if (!$callCheckStmt->fetchColumn()) {
                    $message = "Il bando di default selezionato non esiste.";
                } else {
                    $defaultCallId = $candidateCallId;
                }
            }
        }

        if ($message !== '') {
            // Validation message already set above.
        } elseif (empty($phone)) {
            $message = "Il numero di telefono è obbligatorio.";
        } else {
            $sql = "UPDATE user SET organization = ?, phone = ?, default_call_for_proposal_id = ?";
            $params = [$organization, $phone, $defaultCallId];

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
}

// Get user data
$stmt = $pdo->prepare("SELECT first_name, last_name, email, organization, phone, default_call_for_proposal_id FROM user WHERE id = ?");
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
                    <input type="text" id="first_name" class="form-input" 
                           value="<?php echo htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'); ?>" readonly disabled>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="last_name">Cognome</label>
                    <input type="text" id="last_name" class="form-input" 
                           value="<?php echo htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8'); ?>" readonly disabled>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="organization">Organizzazione</label>
                    <input type="text" id="organization" name="organization" class="form-input"
                           value="<?php echo htmlspecialchars($user['organization'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="phone">Numero di Telefono</label>
                    <input type="tel" id="phone" name="phone" class="form-input"
                           value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="default_call_for_proposal_id">Bando di default</label>
                    <select id="default_call_for_proposal_id" name="default_call_for_proposal_id" class="form-input">
                        <option value="">Nessun bando di default</option>
                        <?php foreach ($callOptions as $callOption): ?>
                            <option
                                value="<?php echo (int) $callOption['id']; ?>"
                                <?php echo ((int) ($user['default_call_for_proposal_id'] ?? 0) === (int) $callOption['id']) ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($callOption['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
