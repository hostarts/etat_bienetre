<?php
// 500 Error Page
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur Serveur - <?= $_ENV['APP_NAME'] ?? 'Bienetre Pharma' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .error-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 90%;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: #ff6b6b;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }
        .error-description {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .error-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-left: 4px solid #ff6b6b;
        }
        .error-details h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        .error-details pre {
            background: #fff;
            padding: 1rem;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.9rem;
            color: #333;
        }
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary {
            background: #ff6b6b;
            color: white;
        }
        .btn-primary:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e9ecef;
        }
        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        .error-icon {
            font-size: 4rem;
            color: #ff6b6b;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-code">500</div>
        <h1 class="error-title">Erreur Serveur</h1>
        <p class="error-description">
            Une erreur inattendue s'est produite sur le serveur. Nos équipes techniques ont été notifiées.
        </p>
        
        <?php if (isset($error) && ($_ENV['DEBUG'] ?? false) === 'true'): ?>
            <div class="error-details">
                <h3>Détails de l'erreur (Mode Debug):</h3>
                <pre><?= htmlspecialchars($error) ?></pre>
            </div>
        <?php endif; ?>
        
        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                <i class="fas fa-home"></i> Retour à l'Accueil
            </a>
            <button onclick="location.reload()" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Recharger la Page
            </button>
        </div>
    </div>
</body>
</html>