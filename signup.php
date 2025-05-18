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
              <label class="form-label required" for="username">Username</label>
              <input type="text" class="form-input" id="username" name="username" required>
            </div>
            <div class="form-group">
              <label class="form-label required" for="password">Password</label>
              <input type="password" class="form-input" id="password" name="password" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="phone">Numero di Telefono</label>
              <input type="tel" class="form-input" id="phone" name="phone">
            </div>
            <button type="submit" class="submit-btn">Registrati</button>
          </form>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
    <script>
      document.getElementById('signup-form').addEventListener('submit', async function(event) {
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