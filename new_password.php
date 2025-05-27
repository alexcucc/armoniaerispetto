<?php
session_start();
include_once 'db/common-db.php';

$token = $_GET['token'] ?? '';
$valid = false;

if ($token) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = :token AND used = 0 LIMIT 1");
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reset && strtotime($reset['expires_at']) > time()) {
            $valid = true;
        }
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php';?>
    <title>Nuova Password</title>
</head>
<body>
    <?php include 'header.php';?>
    <main>
        <div class="hero">
            <div class="title">
                <h1>Imposta Nuova Password</h1>
            </div>
            <div class="content-container">
                <?php if ($valid): ?>
                <form id="new-password-form" action="update_password_handler.php" method="post">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div id="error-message" style="color: red;"></div>
                    <div class="form-group">
                        <label class="form-label required" for="password">Nuova Password</label>
                        <div class="password-container">
                            <input type="password" class="form-input" id="password" name="password" required>
                            <button type="button" class="toggle-password" aria-label="Mostra/Nascondi password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label required" for="confirm_password">Conferma Password</label>
                        <div class="password-container">
                            <input type="password" class="form-input" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="toggle-password" aria-label="Mostra/Nascondi password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn">Aggiorna Password</button>
                </form>
                <?php else: ?>
                <div class="error-message">
                    Il link per il reset della password non è valido o è scaduto.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include 'footer.php';?>
    <script>
        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Form submission
        document.getElementById('new-password-form')?.addEventListener('submit', async function(event) {
            event.preventDefault();
            const errorMessageDiv = document.getElementById('error-message');
            errorMessageDiv.style.display = 'none';
            
            if (this.password.value !== this.confirm_password.value) {
                errorMessageDiv.textContent = 'Le password non coincidono';
                errorMessageDiv.style.display = 'block';
                return;
            }

            const formData = new FormData(this);
            try {
                const response = await fetch(this.action, {
                    method: this.method,
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    window.location.href = 'login.php?message=password_updated';
                } else {
                    errorMessageDiv.textContent = data.message;
                    errorMessageDiv.style.display = 'block';
                }
            } catch (error) {
                errorMessageDiv.textContent = 'Si è verificato un errore. Per favore riprova più tardi.';
                errorMessageDiv.style.display = 'block';
                console.error('Password update error:', error);
            }
        });
    </script>
</body>
</html>