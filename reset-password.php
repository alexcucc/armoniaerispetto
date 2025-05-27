<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'common-head.php';?>
    <title>Reimposta Password</title>
</head>
<body>
    <?php include 'header.php';?>
    <main>
        <div class="hero">
            <div class="title">
                <h1>Reimposta Password</h1>
            </div>
            <div class="content-container">
                <form id="reset-password-form" action="reset_password_handler.php" method="post">
                    <div id="error-message" style="color: red;"></div>
                    <div id="success-message" style="color: green;"></div>
                    <div class="form-group">
                        <label class="form-label required" for="email">Email</label>
                        <input type="email" class="form-input" id="email" name="email" required>
                    </div>
                    <button type="submit" class="submit-btn">Invia Link di Reset</button>
                </form>
            </div>
        </div>
    </main>
    <?php include 'footer.php';?>
    <script>
        document.getElementById('reset-password-form').addEventListener('submit', async function(event) {
            event.preventDefault();
            const errorMessageDiv = document.getElementById('error-message');
            const successMessageDiv = document.getElementById('success-message');
            errorMessageDiv.style.display = 'none';
            successMessageDiv.style.display = 'none';
            
            const formData = new FormData(this);
            try {
                const response = await fetch(this.action, {
                    method: this.method,
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    successMessageDiv.textContent = data.message;
                    successMessageDiv.style.display = 'block';
                    this.reset();
                } else {
                    errorMessageDiv.textContent = data.message;
                    errorMessageDiv.style.display = 'block';
                }
            } catch (error) {
                errorMessageDiv.textContent = 'Si è verificato un errore. Per favore riprova più tardi.';
                errorMessageDiv.style.display = 'block';
                console.error('Reset password error:', error);
            }
        });
    </script>
</body>
</html>