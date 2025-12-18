<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Login</title>
  </head>
  <body>
    <?php include 'header.php';?>
    <main class="login-page">
      <div class="hero login-hero">
        <div class="title">
          <h1>Login</h1>
        </div>
        <div class="content-container">
          <div class="login-card">
            <form id="login-form" action="login_handler.php" method="post">
              <div id="error-message" role="alert"></div>
              <div class="form-group">
                <label class="form-label required" for="login">Email</label>
                <input type="text" class="form-input" id="login" name="login" required>
              </div>
              <div class="form-group">
                <label class="form-label required" for="password">Password</label>
                <div class="password-container">
                  <input type="password" class="form-input" id="password" name="password" required>
                  <button type="button" class="toggle-password" aria-label="Mostra/Nascondi password">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
              <button type="submit" class="submit-btn">Login</button>
              <div class="forgot-password">
                  <a href="reset-password.php">Password dimenticata?</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
    <div id="login-success-modal" class="login-success-modal">
      <div class="login-success-modal-content">
        <div class="login-success-modal-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2>Login effettuato!</h2>
        <p>Verrai reindirizzato alla home tra pochi secondi.</p>
        <button id="close-success-modal" class="submit-btn">Vai subito</button>
      </div>
    </div>
    <script>

      // Toggle password visibility
      document.querySelector('.toggle-password').addEventListener('click', function() {
          const passwordInput = document.getElementById('password');
          const icon = this.querySelector('i');
          
          if (passwordInput.type === 'password') {
              passwordInput.type = 'text';
              icon.classList.remove('fa-eye');
              icon.classList.add('fa-eye-slash');
          } else {
              passwordInput.type = 'password';
              icon.classList.remove('fa-eye-slash');
              icon.classList.add('fa-eye');
          }
      });

      // Handle form submission
      document.getElementById('login-form').addEventListener('submit', async function(event) {
          event.preventDefault();
          const errorMessageDiv = document.getElementById('error-message');
          errorMessageDiv.style.display = 'none';
          errorMessageDiv.textContent = '';
          const formData = new FormData(this);
          try {
              const response = await fetch(this.action, {
                  method: this.method,
                  body: formData
              });
              const data = await response.json();
              if (data.success) {
                  // Show modal
                  const modal = document.getElementById('login-success-modal');
                  modal.style.display = 'block';
                  // Redirect after 2.5 seconds or on button click
                  let redirected = false;
                  const goHome = () => {
                    if (!redirected) {
                      redirected = true;
                      window.location.href = data.redirect || 'index.php';
                    }
                  };
                  document.getElementById('close-success-modal').onclick = goHome;
                  setTimeout(goHome, 2000);
              } else {
                  // Display error message
                  errorMessageDiv.textContent = data.message || 'Login fallito. Per favore riprova.';
                  errorMessageDiv.style.display = 'block';
              }
          } catch (error) {
              errorMessageDiv.textContent = 'Si è verificato un errore. Per favore riprova più tardi.';
              errorMessageDiv.style.display = 'block';
              console.error('Login error:', error);
          }
      });
    </script>
  </body>
</html>