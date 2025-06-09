<?php
/**
 * Configuration principale de l'application
 */
class App {
    private $controller;
    private $method;
    private $params = [];
    
    public function __construct() {
        $this->loadEnvironment();
        $this->setupErrorHandling();
        $this->parseUrl();
    }
    
    /**
     * Chargement des variables d'environnement
     */
    private function loadEnvironment() {
        $envFile = dirname(dirname(__DIR__)) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') !== 0 && strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
        
        // Configuration par défaut
        $_ENV['APP_NAME'] = $_ENV['APP_NAME'] ?? 'Bienetre Pharma';
        $_ENV['DEBUG'] = $_ENV['DEBUG'] ?? 'false';
        $_ENV['TIMEZONE'] = $_ENV['TIMEZONE'] ?? 'Africa/Algiers';
        
        date_default_timezone_set($_ENV['TIMEZONE']);
    }
    
    /**
     * Configuration de la gestion d'erreurs
     */
    private function setupErrorHandling() {
        if ($_ENV['DEBUG'] === 'false') {
            error_reporting(0);
            ini_set('display_errors', 0);
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }
    }
    
    /**
     * Parse de l'URL pour déterminer le routage
     */
    private function parseUrl() {
        $url = $_GET['url'] ?? '';
        $url = rtrim($url, '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $url = explode('/', $url);
        
        // Détermination du contrôleur
        $this->controller = !empty($url[0]) ? $url[0] : 'dashboard';
        $this->method = $url[1] ?? 'index';
        $this->params = array_slice($url, 2);
        
        // Conversion en noms de classes
        $this->controller = ucfirst($this->controller) . 'Controller';
    }
    
    /**
     * Exécution de l'application
     */
    public function run() {
        // Auto-chargement des classes
        $this->autoload();
        
        // Vérification de l'existence du contrôleur
        $controllerFile = dirname(__DIR__) . '/Controllers/' . $this->controller . '.php';
        
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            
            if (class_exists($this->controller)) {
                $controller = new $this->controller();
                
                if (method_exists($controller, $this->method)) {
                    call_user_func_array([$controller, $this->method], $this->params);
                } else {
                    $this->show404();
                }
            } else {
                $this->show404();
            }
        } else {
            $this->show404();
        }
    }
    
    /**
     * Auto-chargement des classes
     */
    private function autoload() {
        spl_autoload_register(function($className) {
            $directories = [
                dirname(__DIR__) . '/Controllers/',
                dirname(__DIR__) . '/Models/',
                dirname(__DIR__) . '/Middleware/',
                dirname(__DIR__) . '/Helpers/',
                dirname(__DIR__) . '/Config/'
            ];
            
            foreach ($directories as $directory) {
                $file = $directory . $className . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    break;
                }
            }
        });
    }
    
    /**
     * Affichage de la page 404
     */
    private function show404() {
        http_response_code(404);
        require_once dirname(__DIR__) . '/Views/errors/404.php';
    }
}