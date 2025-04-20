<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Contatti - Fondazione Armonia e Rispetto</title>
    <script type="module" src="main.js"></script>
    <script type="module" src="form.js"></script>
  </head>
  <body>
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>I nostri contatti</h1>
        </div>
        <div class="content-container">
          <div class="content-left">
            <img class="image" src="images/dogs.jpg">
          </div>
          <div class="content-right">
            <h2>Scrivici i tuoi commenti</h2>
            <div class="contact-form-container">
              <form id="contactForm" class="contact-form">
              
                  <div class="form-group">
                      <label class="form-label required" for="name">Nome</label>
                      <input type="text" class="form-input" id="name" name="name" required>
                  </div>
          
                  <div class="form-group">
                      <label class="form-label required" for="email">Email</label>
                      <input type="email" class="form-input" id="email" name="email" required>
                  </div>
          
                  <div class="form-group">
                      <label class="form-label" for="phone">Telefono</label>
                      <input type="tel" class="form-input" id="phone" name="phone">
                  </div>
          
                  <div class="form-group">
                      <label class="form-label required" for="message">Messaggio</label>
                      <textarea class="form-textarea" id="message" name="message" required></textarea>
                  </div>
          
                  <button type="submit" class="submit-btn">Invia Messaggio</button>
                  <p class="form-note">I campi contrassegnati con * sono obbligatori</p>
                  <div class="contact-form-result" id="contactFormResult"></div>
              </form>
            </div>
            <h2>Oppure contattaci via mail</h2>
            Segreteria: <a href="mailto:segreteria@armoniaerispetto.it">segreteria@armoniaerispetto.it</a>
            <br>
            Informazioni: <a href="mailto:info@armoniaerispetto.it">info@armoniaerispetto.it</a>
          </div>
        </div> 
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>
