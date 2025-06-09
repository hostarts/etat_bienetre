<?php
/**
 * Bienetre Pharma - Main Entry Point
 * Front Controller for the application
 */

// Error reporting for development
if (($_ENV['DEBUG'] ?? 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Define application constants
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', __DIR__);
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Load environment configuration
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
        }
    }
}

// Set timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Africa/Algiers');

// Start session with security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Simple autoloader for application classes
spl_autoload_register(function ($class) {
    $file = APP_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load core helpers
$helpers = [
    'Security',
    'Database', 
    'Validator',
    'FileUpload',
    'Session'
];

foreach ($helpers as $helper) {
    $helperFile = APP_PATH . '/Helpers/' . $helper . '.php';
    if (file_exists($helperFile)) {
        require_once $helperFile;
    }
}

// Load middleware
$middleware = [
    'CSRFMiddleware',
    'AuthMiddleware'
];

foreach ($middleware as $mw) {
    $mwFile = APP_PATH . '/Middleware/' . $mw . '.php';
    if (file_exists($mwFile)) {
        require_once $mwFile;
    }
}

// Start session
Session::start();

// Get the requested URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Remove query string from URI
$uri = parse_url($requestUri, PHP_URL_PATH);

// Remove leading/trailing slashes and convert to lowercase
$uri = trim($uri, '/');
$uri = strtolower($uri);

// Simple routing
try {
    // Security checks
    if ($requestMethod === 'POST') {
        CSRFMiddleware::requireValidToken();
    }
    
    // Basic rate limiting
    if (!AuthMiddleware::checkRateLimit('general', 100, 3600)) {
        http_response_code(429);
        die('Too many requests. Please try again later.');
    }
    
    // Route handling
    switch ($uri) {
        case '':
        case 'dashboard':
            require_once APP_PATH . '/Controllers/DashboardController.php';
            $controller = new DashboardController();
            $controller->index();
            break;
            
        case 'clients':
            require_once APP_PATH . '/Controllers/ClientController.php';
            $controller = new ClientController();
            if ($requestMethod === 'POST') {
                $controller->store();
            } else {
                $controller->index();
            }
            break;
            
        default:
            // Handle dynamic routes like /clients/{id} or /clients/{id}/months/{month}
            $segments = explode('/', $uri);
            
            if ($segments[0] === 'clients' && isset($segments[1])) {
                require_once APP_PATH . '/Controllers/ClientController.php';
                $controller = new ClientController();
                
                if (isset($segments[2]) && $segments[2] === 'months') {
                    if (isset($segments[3])) {
                        // /clients/{id}/months/{month}
                        if (isset($segments[4])) {
                            // Handle transactions: /clients/{id}/months/{month}/invoices or /clients/{id}/months/{month}/returns
                            require_once APP_PATH . '/Controllers/TransactionController.php';
                            $transactionController = new TransactionController();
                            
                            switch ($segments[4]) {
                                case 'invoices':
                                    if ($requestMethod === 'POST') {
                                        $transactionController->storeInvoice();
                                    }
                                    break;
                                case 'returns':
                                    if ($requestMethod === 'POST') {
                                        $transactionController->storeReturn();
                                    }
                                    break;
                                case 'discount':
                                    if ($requestMethod === 'POST') {
                                        $transactionController->updateDiscount();
                                    }
                                    break;
                            }
                        } else {
                            // Show month transactions
                            require_once APP_PATH . '/Controllers/TransactionController.php';
                            $transactionController = new TransactionController();
                            $transactionController->showMonth($segments[1], $segments[3]);
                        }
                    } else {
                        // Show client months
                        $controller->showMonths($segments[1]);
                    }
                } else {
                    // Show single client
                    $controller->show($segments[1]);
                }
            } else {
                // 404 Not Found
                http_response_code(404);
                require_once APP_PATH . '/Views/errors/404.php';
                exit;
            }
            break;
    }

} catch (Exception $e) {
    // Log the error
    $logFile = STORAGE_PATH . '/logs/app.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $errorMessage = "[{$timestamp}] ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
    file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);
    
    // Show error page
    http_response_code(500);
    $error = ($_ENV['DEBUG'] ?? 'false') === 'true' ? $e->getMessage() : null;
    require_once APP_PATH . '/Views/errors/500.php';
}
