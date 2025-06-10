<?php
// Fixed Login View
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - <?= $_ENV['APP_NAME'] ?? 'Bienetre Pharma' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="<?= $csrf_token ?? '' ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }
        .login-brand {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }
        .brand-logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            font-size: 3rem;
        }
        .brand-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .brand-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        .login-form-container {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-header h2 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        .form-header p {
            color: #666;
            font-size: 1rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 1;
        }
        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.3);
        }
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
            .login-brand {
                padding: 2rem;
                min-height: auto;
            }
            .brand-logo {
                width: 80px;
                height: 80px;
                font-size: 2rem;
                margin-bottom: 1rem;
            }
            .brand-title {
                font-size: 1.5rem;
            }
            .login-form-container {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-brand">
            <div class="brand-logo">
                <i class="fas fa-pills"></i>
            </div>
            <h1 class="brand-title"><?= $_ENV['APP_NAME'] ?? 'Bienetre Pharma' ?></h1>
            <p class="brand-subtitle">
                Système de gestion commerciale sécurisé pour optimiser vos opérations pharmaceutiques
            </p>
        </div>

        <div class="login-form-container">
            <div class="form-header">
                <h2>Connexion</h2>
                <p>Accédez à votre espace de gestion</p>
            </div>

            <?php if (isset($flash) && $flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="form-group">
                    <label for="username">Nom d'utilisateur ou Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control" 
                               placeholder="Entrez votre nom d'utilisateur"
                               required 
                               autocomplete="username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Entrez votre mot de passe"
                               required 
                               autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <span>Se connecter</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs requis.');
                return;
            }
            
            console.log('Login form submitted with username:', username);
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>