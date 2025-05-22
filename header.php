<header>
    <div class="header-content">
        <div class="title">
            <div class="logo-container">
                <a href="index.php" class="logo-link">
                    <img class="logo" src="images/logo.png" alt="Logo Fondazione Armonia e Rispetto"/>
                </a>
                <h1>Fondazione Armonia e Rispetto (ETS)</h1>
            </div>
            <div class="hamburger-container">
                <button class="hamburger">☰</button>
            </div>
        </div>


        <div class="navigation-menu-container">
            <nav class="navigation-menu">

                <div class="nav-links">
                    <ul class="nav-links">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="chi_siamo.php">Chi Siamo</a></li>
                        <li class="nav-item"><a class="nav-link" href="valori.php">Valori</a></li>
                        <li class="nav-item"><a class="nav-link" href="in_concreto.php">In Concreto</a></li>
                        <li class="nav-item"><a class="nav-link" href="progetti.php">Progetti</a></li>
                        <li class="nav-item"><a class="nav-link" href="eventi.php">Eventi</a></li>
                        <li class="nav-item"><a class="nav-link" href="contatti.php">Contatti</a></li>
                        <li class="nav-item"><a class="nav-link" href="bandi_e_finanziamenti.php">Bandi e Finanziamenti</a></li>
                        <li class="nav-item"><a class="nav-link" href="documenti.php">Documenti</a></li>
                        <li class="nav-item"><a class="nav-link" href="faq.php">FAQ</a></li>
                        <li class="nav-item"><a class="nav-link" href="dona_ora.php">Dona Ora</a></li>
                        <?php
                        session_start();
                        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
                            <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                            <li class="nav-item"><a class="nav-link" href="signup.php">Registrati</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="nav-item search-container">
                    <form class="search-form" action="ricerca.php" method="get">
                        <input type="search" 
                                name="q"
                                class="search-input" 
                                placeholder="Cerca nel sito..."
                                aria-label="Cerca nel sito"
                                required>
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </nav>
        </div>
    </div>
</header>