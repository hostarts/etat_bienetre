<?php
/**
 * Bienetre Pharma - Complete index.php with Auth Integration and Debug
 */

// STEP 1: Error reporting and constants FIRST
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', __DIR__);
define('STORAGE_PATH', ROOT_PATH . '/storage');

// STEP 2: Define URL helper functions for SUBDOMAIN
if (!function_exists('route_url')) {
    function route_url($path) {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path) {
        return '/public' . $path;
    }
}

// STEP 3: Debug logging function
function debug_log($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(PUBLIC_PATH . '/debug.log', $logMessage, FILE_APPEND | LOCK_EX);
}

// STEP 4: Load environment
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Africa/Algiers');

// STEP 5: Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// STEP 6: Autoloader
spl_autoload_register(function ($class) {
    $file = APP_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// STEP 7: Load helpers (NOW that APP_PATH is defined)
$helpers = ['Security', 'Database', 'Validator', 'FileUpload', 'Session'];
foreach ($helpers as $helper) {
    $file = APP_PATH . '/Helpers/' . $helper . '.php';
    if (file_exists($file)) {
        require_once $file;
        debug_log("Loaded helper: $helper");
    } else {
        debug_log("Helper not found: $helper at $file");
    }
}

// STEP 8: Load middleware (NOW that APP_PATH is defined)
$middleware = ['CSRFMiddleware', 'AuthMiddleware'];
foreach ($middleware as $mw) {
    $file = APP_PATH . '/Middleware/' . $mw . '.php';
    if (file_exists($file)) {
        require_once $file;
        debug_log("Loaded middleware: $mw");
    } else {
        debug_log("Middleware not found: $mw at $file");
    }
}

// STEP 9: Start session (NOW that Session class is loaded)
if (class_exists('Session')) {
    Session::start();
    debug_log("Session started using Session class");
} else {
    session_start();
    debug_log("Session started using native PHP session_start");
}

// STEP 10: Clean expired sessions periodically (if AuthMiddleware loaded)
if (class_exists('AuthMiddleware') && rand(1, 100) === 1) {
    AuthMiddleware::cleanExpiredSessions();
    debug_log("Cleaned expired sessions");
}

// STEP 11: Simple URL processing for SUBDOMAIN
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Remove /public from the beginning if present
if (strpos($requestUri, '/public') === 0) {
    $requestUri = substr($requestUri, 7);
}

$uri = trim(parse_url($requestUri, PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (empty($uri)) $uri = 'dashboard';

debug_log("=== REQUEST START ===");
debug_log("Original URI: " . $_SERVER['REQUEST_URI']);
debug_log("Processed URI: $uri");
debug_log("Method: $method");

// STEP 12: Define public routes that don't require authentication
$publicRoutes = ['login', 'logout'];
$isPublicRoute = in_array($uri, $publicRoutes);

debug_log("Is public route: " . ($isPublicRoute ? 'YES' : 'NO'));

try {
    // STEP 13: Authentication check for protected routes
    if (!$isPublicRoute && class_exists('AuthMiddleware')) {
        debug_log("Checking authentication for protected route");
        if (!AuthMiddleware::authenticate()) {
            debug_log("Authentication failed - redirecting to login");
            $loginUrl = '/login';
            if ($uri !== 'login') {
                if (class_exists('Session')) {
                    Session::set('redirect_after_login', $_SERVER['REQUEST_URI']);
                } else {
                    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
                }
                debug_log("Stored redirect URL: " . $_SERVER['REQUEST_URI']);
            }
            header("Location: $loginUrl");
            exit;
        }
        debug_log("Authentication successful");
    } else if (!$isPublicRoute) {
        debug_log("WARNING: AuthMiddleware not found - proceeding without authentication");
    }
    
    // STEP 14: CSRF protection for POST requests (except login)
    if ($method === 'POST' && $uri !== 'login') {
        debug_log("POST request detected - checking CSRF");
        if (class_exists('CSRFMiddleware')) {
            debug_log("CSRFMiddleware exists - validating token");
            CSRFMiddleware::requireValidToken();
            debug_log("CSRF token validation passed");
        } else {
            debug_log("WARNING: CSRFMiddleware not found - skipping CSRF validation");
        }
    } else if ($method === 'POST' && $uri === 'login') {
        debug_log("Login POST request - CSRF will be checked by AuthController");
    }
    
    // STEP 15: Route handling with authentication
    switch ($uri) {
        case 'login':
            debug_log("Routing to login");
            require_once APP_PATH . '/Controllers/AuthController.php';
            $authController = new AuthController();
            if ($method === 'POST') {
                debug_log("Processing login attempt");
                if (class_exists('CSRFMiddleware')) {
                    CSRFMiddleware::requireValidToken();
                }
                $authController->processLogin();
            } else {
                debug_log("Showing login form");
                $authController->showLogin();
            }
            break;
            
        case 'logout':
            debug_log("Processing logout");
            require_once APP_PATH . '/Controllers/AuthController.php';
            $authController = new AuthController();
            $authController->logout();
            break;
            
        case 'dashboard':
        case 'index':
        case '':
            debug_log("Routing to dashboard");
            if (class_exists('AuthMiddleware')) {
                AuthMiddleware::requireAuth();
                debug_log("Dashboard access authorized");
            }
            require_once APP_PATH . '/Controllers/DashboardController.php';
            (new DashboardController())->index();
            break;
            
        case 'clients':
            debug_log("=== STARTING CLIENTS ROUTE DEBUG ===");
            debug_log("Request URI: " . $_SERVER['REQUEST_URI']);
            debug_log("Request Method: $method");
            debug_log("APP_PATH: " . APP_PATH);
            
            // Check authentication and permissions
            if (class_exists('AuthMiddleware')) {
                AuthMiddleware::requireAuth();
                AuthMiddleware::requirePermission('view_clients');
                debug_log("Clients access authorized");
            }
            
            try {
                debug_log("About to check ClientController file existence");
                
                $controllerFile = APP_PATH . '/Controllers/ClientController.php';
                if (!file_exists($controllerFile)) {
                    debug_log("ERROR: ClientController file not found at: $controllerFile");
                    throw new Exception('ClientController file not found');
                }
                debug_log("SUCCESS: ClientController file exists at: $controllerFile");
                
                debug_log("About to require ClientController file");
                require_once $controllerFile;
                debug_log("SUCCESS: ClientController file loaded");
                
                debug_log("About to create ClientController instance");
                $controller = new ClientController();
                debug_log("SUCCESS: ClientController instance created");
                
                if ($method === 'POST') {
                    debug_log("POST method detected - calling store()");
                    debug_log("POST data: " . print_r($_POST, true));
                    $controller->store();
                    debug_log("SUCCESS: store() method completed");
                } else {
                    debug_log("GET method detected - calling index()");
                    
                    // Test database connection first
                    debug_log("Testing database connection");
                    try {
                        $db = Database::getInstance();
                        debug_log("SUCCESS: Database instance obtained");
                        
                        $stmt = $db->query("SELECT COUNT(*) as count FROM clients");
                        $count = $stmt->fetch()['count'];
                        debug_log("SUCCESS: Database query successful - found $count clients");
                    } catch (Exception $dbError) {
                        debug_log("ERROR: Database error - " . $dbError->getMessage());
                        debug_log("Database error file: " . $dbError->getFile());
                        debug_log("Database error line: " . $dbError->getLine());
                        throw $dbError;
                    }
                    
                    // Test view file
                    $viewFile = APP_PATH . '/Views/clients/index.php';
                    debug_log("Checking view file: $viewFile");
                    if (!file_exists($viewFile)) {
                        debug_log("ERROR: View file missing - $viewFile");
                        throw new Exception("View file missing: $viewFile");
                    } else {
                        debug_log("SUCCESS: View file exists - $viewFile");
                    }
                    
                    // Test layout file
                    $layoutFile = APP_PATH . '/Views/layouts/app.php';
                    debug_log("Checking layout file: $layoutFile");
                    if (!file_exists($layoutFile)) {
                        debug_log("ERROR: Layout file missing - $layoutFile");
                        throw new Exception("Layout file missing: $layoutFile");
                    } else {
                        debug_log("SUCCESS: Layout file exists - $layoutFile");
                    }
                    
                    // Now try to call the controller method
                    debug_log("About to call controller->index()");
                    $controller->index();
                    debug_log("SUCCESS: ClientController index() completed successfully");
                }
                
                debug_log("=== CLIENTS ROUTE DEBUG COMPLETED SUCCESSFULLY ===");
                
            } catch (Exception $e) {
                debug_log("EXCEPTION CAUGHT:");
                debug_log("Exception message: " . $e->getMessage());
                debug_log("Exception file: " . $e->getFile());
                debug_log("Exception line: " . $e->getLine());
                debug_log("Stack trace: " . $e->getTraceAsString());
                debug_log("=== END EXCEPTION DEBUG ===");
                
                // Show user-friendly error
                echo "<!DOCTYPE html><html><head><title>Debug Error</title></head><body>";
                echo "<h1>Client Controller Error</h1>";
                echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><strong>Debug log:</strong> <a href='/debug.log' target='_blank'>View debug.log</a></p>";
                echo "<p><strong>Check the debug log for detailed information</strong></p>";
                echo "<p><a href='/'>← Back to Dashboard</a></p>";
                echo "<hr>";
                echo "<h3>Quick Debug Info:</h3>";
                echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
                echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
                echo "</body></html>";
                exit;
            }
            break;
            
        case 'test-clients':
            echo "<!DOCTYPE html><html><head><title>Test Clients</title></head><body>";
            echo "<h1>Test Clients Page</h1>";
            try {
                $db = Database::getInstance();
                $stmt = $db->query("SELECT * FROM clients ORDER BY name LIMIT 10");
                $clients = $stmt->fetchAll();
                
                echo "<p>Found " . count($clients) . " clients:</p>";
                foreach($clients as $client) {
                    echo "<p>• " . htmlspecialchars($client['name']) . " - " . htmlspecialchars($client['phone']) . "</p>";
                }
            } catch (Exception $e) {
                echo "<p>Error: " . $e->getMessage() . "</p>";
            }
            echo "<p><a href='/'>← Back to Dashboard</a></p>";
            echo "</body></html>";
            break;
            
        default:
            debug_log("Processing dynamic route: $uri");
            $segments = explode('/', $uri);
            debug_log("Route segments: " . implode(', ', $segments));
            
            if ($segments[0] === 'clients' && isset($segments[1]) && !empty($segments[1])) {
                debug_log("Client-specific route detected: client ID = " . $segments[1]);
                
                // Check authentication and permissions for client routes
                if (class_exists('AuthMiddleware')) {
                    AuthMiddleware::requireAuth();
                    AuthMiddleware::requirePermission('view_clients');
                    debug_log("Client route access authorized");
                }
                
                require_once APP_PATH . '/Controllers/ClientController.php';
                $controller = new ClientController();
                
                if (isset($segments[2]) && $segments[2] === 'months') {
                    if (isset($segments[3]) && !empty($segments[3])) {
                        if (isset($segments[4]) && !empty($segments[4])) {
                            debug_log("Transaction route: " . $segments[4]);
                            
                            // Check transaction permissions
                            if (class_exists('AuthMiddleware')) {
                                AuthMiddleware::requirePermission('view_transactions');
                                debug_log("Transaction route access authorized");
                            }
                            
                            require_once APP_PATH . '/Controllers/TransactionController.php';
                            $transactionController = new TransactionController();
                            
                            switch ($segments[4]) {
                                case 'invoices':
                                    $method === 'POST' ? $transactionController->storeInvoice() : $transactionController->showMonth($segments[1], $segments[3]);
                                    break;
                                case 'returns':
                                    $method === 'POST' ? $transactionController->storeReturn() : $transactionController->showMonth($segments[1], $segments[3]);
                                    break;
                                case 'discount':
                                    $method === 'POST' ? $transactionController->updateDiscount() : $transactionController->showMonth($segments[1], $segments[3]);
                                    break;
                                default:
                                    debug_log("Unknown transaction action: " . $segments[4]);
                                    http_response_code(404);
                                    echo "404 - Transaction action not found";
                                    exit;
                            }
                        } else {
                            debug_log("Show month transactions");
                            if (class_exists('AuthMiddleware')) {
                                AuthMiddleware::requirePermission('view_transactions');
                            }
                            require_once APP_PATH . '/Controllers/TransactionController.php';
                            (new TransactionController())->showMonth($segments[1], $segments[3]);
                        }
                    } else {
                        debug_log("Show client months");
                        $controller->showMonths($segments[1]);
                    }
                } else if (!isset($segments[2]) || empty($segments[2])) {
                    debug_log("Show single client");
                    $controller->show($segments[1]);
                } else {
                    debug_log("Unknown client action: " . ($segments[2] ?? 'none'));
                    http_response_code(404);
                    echo "404 - Client action not found";
                    exit;
                }
            } else {
                debug_log("No matching route found for: $uri");
                http_response_code(404);
                echo "<!DOCTYPE html><html><head><title>404 - Page Not Found</title></head><body>";
                echo "<h1>404 - Page Not Found</h1>";
                echo "<p>The page you are looking for could not be found.</p>";
                echo "<p>URI: '" . htmlspecialchars($uri) . "'</p>";
                echo "<p><a href='/'>← Back to Dashboard</a></p>";
                echo "<hr>";
                echo "<h3>Debug Info:</h3>";
                echo "<p><strong>Original Request:</strong> " . htmlspecialchars($_SERVER['REQUEST_URI']) . "</p>";
                echo "<p><strong>Processed URI:</strong> " . htmlspecialchars($uri) . "</p>";
                echo "<p><strong>Method:</strong> " . htmlspecialchars($method) . "</p>";
                echo "<p><strong>Segments:</strong> " . htmlspecialchars(implode(', ', $segments)) . "</p>";
                echo "</body></html>";
                exit;
            }
    }
    
} catch (Exception $e) {
    debug_log("FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    debug_log("FATAL ERROR STACK TRACE: " . $e->getTraceAsString());
    
    error_log("BIENETRE ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>Server Error</title></head><body>";
    echo "<h1>Server Error</h1>";
    echo "<p>An error occurred. Please check the debug log for details.</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Debug log:</strong> <a href='/debug.log' target='_blank'>View debug.log</a></p>";
    echo "<p><a href='/'>← Back to Dashboard</a></p>";
    echo "</body></html>";
}

debug_log("=== REQUEST END ===");
?>