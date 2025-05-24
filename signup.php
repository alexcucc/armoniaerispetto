<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Registrazione</title>
  </head>
  <body>
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Registrati</h1>
        </div>
        <div class="content-container">
          <form id="signup-form" action="signup_handler.php" method="post">
            <div id="error-message"></div>
            <div class="form-group">
              <label class="form-label required" for="first_name">Nome</label>
              <input type="text" class="form-input" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
              <label class="form-label required" for="last_name">Cognome</label>
              <input type="text" class="form-input" id="last_name" name="last_name" required>
            </div>
            <div class="form-group">
              <label class="form-label required" for="email">Email</label>
              <input type="email" class="form-input" id="email" name="email" required>
            </div>
            <div class="form-group">
              <label class="form-label required" for="password">Password</label>
              <div class="password-container">
                <input type="password" class="form-input" id="password" name="password" required minlength="6" title="La password deve contenere almeno 6 caratteri.">
                <button type="button" class="toggle-password" aria-label="Mostra/Nascondi password">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label required" for="password_verify">Conferma Password</label>
              <div class="password-container">
                <input type="password" class="form-input" id="password_verify" name="password_verify" required minlength="6" title="La password deve contenere almeno 6 caratteri.">
                <button type="button" class="toggle-password" aria-label="Mostra/Nascondi password">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="phone">Numero di Telefono</label>
              <input type="tel" class="form-input" id="phone" name="phone" required>
            </div>
            <button type="submit" class="submit-btn">Registrati</button>
          </form>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
    <script>

      // Toggle password visibility for all password fields
      document.querySelectorAll('.toggle-password').forEach(button => {
          button.addEventListener('click', function() {
              const passwordInput = this.parentElement.querySelector('input');
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
      });

      // Handle form submission
      document.getElementById('signup-form').addEventListener('submit', async function(event) {
          event.preventDefault();
          const errorMessageDiv = document.getElementById('error-message');
          const password = document.getElementById('password').value;
          const passwordVerify = document.getElementById('password_verify').value;
          
          if (password !== passwordVerify) {
              errorMessageDiv.textContent = 'Le password non corrispondono.';
              errorMessageDiv.style.display = 'block';
              return;
          }
          
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
                  // Redirect upon successful signup
                  window.location.href = data.redirect || 'index.php';
              } else {
                  // Display error message
                  errorMessageDiv.textContent = data.message || 'Signup fallito. Per favore riprova.';
                  errorMessageDiv.style.display = 'block';
              }
          } catch (error) {
              errorMessageDiv.textContent = 'Si è verificato un errore. Per favore riprova più tardi.';
              errorMessageDiv.style.display = 'block';
              console.error('Signup error:', error);
          }
      });
    </script>
  </body>
</html>