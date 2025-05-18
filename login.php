<!DOCTYPE html>
<html lang="it">
  <head>
    <?php include 'common-head.php';?>
    <title>Login</title>
  </head>
  <body>
    <?php include 'header.php';?>
    <main>
      <div class="hero">
        <div class="title">
          <h1>Login</h1>
        </div>
        <div class="content-container">
          <form action="login_handler.php" method="post">
            <div class="form-group">
              <label class="form-label required" for="login">Username o Email</label>
              <input type="text" class="form-input" id="login" name="login" required>
            </div>
            <div class="form-group">
              <label class="form-label required" for="password">Password</label>
              <input type="password" class="form-input" id="password" name="password" required>
            </div>
            <button type="submit" class="submit-btn">Login</button>
          </form>
        </div>
      </div>
    </main>
    <?php include 'footer.php';?>
  </body>
</html>