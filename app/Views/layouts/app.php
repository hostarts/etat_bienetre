<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? htmlspecialchars($title) . ' - ' : '' ?><?= $_ENV['APP_NAME'] ?? 'Bienetre Pharma' ?></title>
    <meta name="description" content="<?= isset($description) ? htmlspecialchars($description) : 'Système de gestion pour Bienetre Pharma' ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="/assets/css/app.css">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= $csrf_token ?? '' ?>">
</head>
<body class="<?= isset($bodyClass) ? htmlspecialchars($bodyClass) : '' ?>">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Chargement...</p>
        </div>
    </div>

    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Navigation -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="/assets/images/logo_bienetre.webp" alt="Bienetre Pharma" class="sidebar-logo">
            <div class="sidebar-title">
                <h1><?= $_ENV['APP_NAME'] ?? 'Bienetre Pharma' ?></h1>
                <small>Gestion Commerciale</small>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="/dashboard" class="menu-item <?= ($currentRoute ?? '') === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Tableau de Bord</span>
            </a>
            
            <a href="/clients" class="menu-item <?= strpos($currentRoute ?? '', 'clients') === 0 ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Gestion Clients</span>
            </a>
            
            <?php if (isset($currentClient)): ?>
                <div class="menu-section">
                    <div class="menu-section-title">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($currentClient['name']) ?>
                    </div>
                    <a href="/clients/<?= $currentClient['id'] ?>" class="menu-item sub-item">
                        <i class="fas fa-info-circle"></i>
                        <span>Détails Client</span>
                    </a>
                    <a href="/clients/<?= $currentClient['id'] ?>/months" class="menu-item sub-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Mois d'Activité</span>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if (isset($currentClient) && isset($currentMonth)): ?>
                <div class="menu-section">
                    <div class="menu-section-title">
                        <i class="fas fa-calendar"></i> <?= $currentMonth ?>
                    </div>
                    <a href="/clients/<?= $currentClient['id'] ?>/months/<?= $currentMonth ?>" class="menu-item sub-item active">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Factures</span>
                    </a>
                    <button onclick="window.print()" class="menu-item sub-item print-btn">
                        <i class="fas fa-print"></i>
                        <span>Imprimer État</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span>Administrateur</span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Flash Messages -->
        <?php if (isset($flash) && $flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>" id="flashMessage">
                <div class="alert-content">
                    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Page Content -->
        <div class="page-content">
            <?= $content ?>
        </div>
    </main>

    <!-- Modals Container -->
    <div id="modalsContainer"></div>

    <!-- Main JavaScript -->
    <script src="/assets/js/app.js"></script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline scripts -->
    <?php if (isset($inlineScripts)): ?>
        <script>
            <?= $inlineScripts ?>
        </script>
    <?php endif; ?>
</body>
</html>