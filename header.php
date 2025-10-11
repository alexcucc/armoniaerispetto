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

                <div class="menu-separator"></div>

                <div class="nav-links-wrapper">
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
                        include_once 'db/common-db.php';
                        require_once 'RolePermissionManager.php';
                        $rolePermissionManager = new RolePermissionManager($pdo);
                        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
                            <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                            <li class="nav-item"><a class="nav-link" href="signup.php">Registrati</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="profile.php">Profilo</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                        <?php endif; ?>
                        <?php
                        $canEvaluationView = isset($_SESSION['user_id']) && $rolePermissionManager->userHasPermission(
                            $_SESSION['user_id'],
                            RolePermissionManager::$PERMISSIONS['EVALUATION_VIEW']
                        );
                        $canUserList = isset($_SESSION['user_id']) && $rolePermissionManager->userHasPermission(
                            $_SESSION['user_id'],
                            RolePermissionManager::$PERMISSIONS['USER_LIST']
                        );
                        $canOrganizationList = isset($_SESSION['user_id']) && $rolePermissionManager->userHasPermission(
                            $_SESSION['user_id'],
                            RolePermissionManager::$PERMISSIONS['ORGANIZATION_LIST']
                        );
                        $canEvaluatorList = isset($_SESSION['user_id']) && $rolePermissionManager->userHasPermission(
                            $_SESSION['user_id'],
                            RolePermissionManager::$PERMISSIONS['EVALUATOR_LIST']
                        );
                        $canSupervisorList = isset($_SESSION['user_id']) && $rolePermissionManager->userHasPermission(
                            $_SESSION['user_id'],
                            RolePermissionManager::$PERMISSIONS['SUPERVISOR_LIST']
                        );
                        $canCallForProposalList = isset($_SESSION['user_id']) && $rolePermissionManager->userHasPermission(
                            $_SESSION['user_id'],
                            RolePermissionManager::$PERMISSIONS['CALL_FOR_PROPOSAL_LIST']
                        );
                        $canApplicationList = isset($_SESSION['user_id']) && $rolePermissionManager->userHasPermission(
                            $_SESSION['user_id'],
                            RolePermissionManager::$PERMISSIONS['APPLICATION_LIST']
                        );
                        $canApplicationReview = isset($_SESSION['user_id']) && $rolePermissionManager->userHasPermission(
                            $_SESSION['user_id'],
                            RolePermissionManager::$PERMISSIONS['APPLICATION_REVIEW'],
                        );
                        if (
                            $canUserList ||
                            $canOrganizationList ||
                            $canEvaluatorList ||
                            $canSupervisorList ||
                            $canCallForProposalList ||
                            $canEvaluationView ||
                            $canApplicationList ||
                            $canApplicationReview
                        ): ?>
                            <li class="nav-item dropdown">
                                <a href="#" class="nav-link manage-toggle">Gestione</a>
                                <ul class="submenu">
                                    <?php if ($canUserList): ?>
                                        <li class="nav-item"><a class="nav-link" href="users.php">Utenti</a></li>
                                    <?php endif; ?>
                                    <?php if ($canEvaluatorList): ?>
                                        <li class="nav-item"><a class="nav-link" href="evaluators.php">Valutatori</a></li>
                                    <?php endif; ?>
                                    <?php if ($canCallForProposalList): ?>
                                        <li class="nav-item"><a class="nav-link" href="call_for_proposals.php">Bandi</a></li>
                                    <?php endif; ?>
                                    <?php if ($canOrganizationList): ?>
                                        <li class="nav-item"><a class="nav-link" href="organizations.php">Enti</a></li>
                                    <?php endif; ?>
                                    <?php if ($canApplicationReview): ?>
                                        <li class="nav-item"><a class="nav-link" href="supervisor_applications.php">Domande da revisionare</a></li>
                                    <?php endif; ?>
                                    <?php if ($canSupervisorList): ?>
                                        <li class="nav-item"><a class="nav-link" href="supervisors.php">Relatori</a></li>
                                    <?php endif; ?>
                                    <?php if ($canApplicationList): ?>
                                        <li class="nav-item"><a class="nav-link" href="applications.php">Risposte ai bandi</a></li>
                                    <?php endif; ?>
                                    <?php if ($canEvaluationView): ?>
                                        <li class="nav-item"><a class="nav-link" href="evaluations.php">Valutazioni</a></li>
                                    <?php endif; ?>

                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="menu-separator"></div>

                <div class="nav-item search-container">
                    <form class="search-form" action="ricerca.php" method="get">
                        <input type="search" 
                                name="q"
                                class="search-input" 
                                placeholder="Cerca nel sito"
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