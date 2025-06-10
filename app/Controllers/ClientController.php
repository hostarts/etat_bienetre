<?php
/**
 * Client Controller - COMPLETE FIXED VERSION
 * Handles client management operations with full debugging and error handling
 */
class ClientController {
    
    private $db;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
            error_log("ClientController: Database connection established");
        } catch (Exception $e) {
            error_log("ClientController: Database connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Show all clients with pagination
     */
    public function index() {
        error_log("ClientController::index() - START");
        
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_ENV['ITEMS_PER_PAGE'] ?? 12);
            $offset = ($page - 1) * $perPage;
            
            error_log("ClientController::index() - Pagination: page=$page, perPage=$perPage, offset=$offset");
            
            // Get total count
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM clients WHERE deleted_at IS NULL");
            $stmt->execute();
            $totalClients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            error_log("ClientController::index() - Total clients: $totalClients");
            
            // Get clients for current page
            $stmt = $this->db->prepare("
                SELECT * FROM clients 
                WHERE deleted_at IS NULL 
                ORDER BY name ASC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$perPage, $offset]);
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("ClientController::index() - Fetched " . count($clients) . " clients");
            
            // Calculate pagination
            $totalPages = ceil($totalClients / $perPage);
            $pagination = [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total' => $totalClients,
                'per_page' => $perPage,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $totalClients)
            ];
            
            // Start session and generate CSRF token
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $csrf_token = CSRFMiddleware::generateToken();
            error_log("ClientController::index() - CSRF token generated: $csrf_token");
            
            // Get flash message
            $flash = Session::getFlash();
            if ($flash) {
                error_log("ClientController::index() - Flash message: " . $flash['type'] . " - " . $flash['message']);
            }
            
            $data = [
                'title' => 'Gestion Clients',
                'currentRoute' => 'clients',
                'clients' => $clients,
                'pagination' => $pagination,
                'csrf_token' => $csrf_token,
                'flash' => $flash
            ];
            
            error_log("ClientController::index() - Rendering view with " . count($data) . " data items");
            $this->render('clients/index', $data);
            
            error_log("ClientController::index() - COMPLETED");
            
        } catch (Exception $e) {
            error_log("ClientController::index() - ERROR: " . $e->getMessage());
            error_log("ClientController::index() - Stack trace: " . $e->getTraceAsString());
            Session::setFlash('error', 'Erreur lors du chargement des clients.');
            header('Location: /dashboard');
            exit;
        }
    }
    
    /**
     * Store new client - COMPLETE FIXED VERSION
     */
    public function store() {
        error_log("=== ClientController::store() - START ===");
        error_log("ClientController::store() - Request method: " . $_SERVER['REQUEST_METHOD']);
        error_log("ClientController::store() - POST data: " . print_r($_POST, true));
        
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        error_log("ClientController::store() - Session ID: " . session_id());
        error_log("ClientController::store() - Session data: " . print_r($_SESSION, true));
        
        try {
            // Check if this is actually a POST request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log("ClientController::store() - ERROR: Not a POST request, method: " . $_SERVER['REQUEST_METHOD']);
                Session::setFlash('error', 'Méthode de requête invalide.');
                header('Location: /clients');
                exit;
            }
            
            // CSRF Token Validation with detailed logging
            $submittedToken = $_POST['csrf_token'] ?? '';
            $sessionToken = $_SESSION['csrf_token'] ?? '';
            
            error_log("ClientController::store() - CSRF Validation:");
            error_log("  - Submitted token: '$submittedToken'");
            error_log("  - Session token: '$sessionToken'");
            error_log("  - Tokens match: " . ($submittedToken === $sessionToken ? 'YES' : 'NO'));
            
            if (!CSRFMiddleware::verifyToken($submittedToken)) {
                error_log("ClientController::store() - ERROR: CSRF token verification failed");
                Session::setFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
                header('Location: /clients');
                exit;
            }
            
            error_log("ClientController::store() - CSRF token validation successful");
            
            // Extract and validate form data
            $name = trim($_POST['name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            error_log("ClientController::store() - Form data extracted:");
            error_log("  - Name: '$name'");
            error_log("  - Address: '$address'");
            error_log("  - Phone: '$phone'");
            error_log("  - Email: '$email'");
            
            // Validation
            $errors = [];
            
            if (empty($name)) {
                $errors[] = 'Le nom du client est requis.';
            } elseif (strlen($name) > 255) {
                $errors[] = 'Le nom du client ne peut pas dépasser 255 caractères.';
            }
            
            if (empty($address)) {
                $errors[] = 'L\'adresse du client est requise.';
            } elseif (strlen($address) > 1000) {
                $errors[] = 'L\'adresse ne peut pas dépasser 1000 caractères.';
            }
            
            if (empty($phone)) {
                $errors[] = 'Le téléphone du client est requis.';
            } elseif (!preg_match('/^(\+213|0)[567]\d{8}$/', $phone)) {
                $errors[] = 'Le numéro de téléphone n\'est pas valide (format algérien attendu).';
            }
            
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'L\'adresse email n\'est pas valide.';
            }
            
            if (!empty($errors)) {
                error_log("ClientController::store() - Validation errors: " . implode(', ', $errors));
                Session::setFlash('error', implode(' ', $errors));
                header('Location: /clients');
                exit;
            }
            
            error_log("ClientController::store() - Validation passed");
            
            // Check for duplicate name
            error_log("ClientController::store() - Checking for duplicate client name");
            $stmt = $this->db->prepare("SELECT id FROM clients WHERE name = ? AND deleted_at IS NULL");
            $stmt->execute([$name]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                error_log("ClientController::store() - ERROR: Duplicate client name found, ID: " . $existing['id']);
                Session::setFlash('error', 'Un client avec ce nom existe déjà.');
                header('Location: /clients');
                exit;
            }
            
            error_log("ClientController::store() - No duplicate found, proceeding with insertion");
            
            // Insert client
            $stmt = $this->db->prepare("
                INSERT INTO clients (name, address, phone, email, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $insertData = [$name, $address, $phone, $email];
            error_log("ClientController::store() - Executing insert with data: " . print_r($insertData, true));
            
            $result = $stmt->execute($insertData);
            
            if ($result) {
                $clientId = $this->db->lastInsertId();
                error_log("ClientController::store() - SUCCESS: Client created with ID: $clientId");
                
                // Verify insertion by fetching the created client
                $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ?");
                $stmt->execute([$clientId]);
                $createdClient = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($createdClient) {
                    error_log("ClientController::store() - Verification: Client successfully inserted: " . print_r($createdClient, true));
                    Session::setFlash('success', "Client '{$name}' ajouté avec succès.");
                } else {
                    error_log("ClientController::store() - WARNING: Client insertion seemed successful but verification failed");
                    Session::setFlash('warning', 'Client ajouté mais vérification échouée.');
                }
            } else {
                error_log("ClientController::store() - ERROR: Failed to insert client");
                error_log("ClientController::store() - PDO Error Info: " . print_r($stmt->errorInfo(), true));
                Session::setFlash('error', 'Erreur lors de l\'ajout du client.');
            }
            
            error_log("ClientController::store() - Redirecting to /clients");
            header('Location: /clients');
            exit;
            
        } catch (PDOException $e) {
            error_log("ClientController::store() - PDO Exception: " . $e->getMessage());
            error_log("ClientController::store() - PDO Error Code: " . $e->getCode());
            error_log("ClientController::store() - Stack trace: " . $e->getTraceAsString());
            Session::setFlash('error', 'Erreur de base de données lors de l\'ajout du client.');
            header('Location: /clients');
            exit;
        } catch (Exception $e) {
            error_log("ClientController::store() - General Exception: " . $e->getMessage());
            error_log("ClientController::store() - Stack trace: " . $e->getTraceAsString());
            Session::setFlash('error', 'Erreur inattendue lors de l\'ajout du client.');
            header('Location: /clients');
            exit;
        }
    }
    
    /**
     * Show single client
     */
    public function show($clientId) {
        error_log("ClientController::show() - START with clientId: $clientId");
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$clientId]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$client) {
                error_log("ClientController::show() - ERROR: Client not found with ID: $clientId");
                http_response_code(404);
                require_once APP_PATH . '/Views/errors/404.php';
                return;
            }
            
            error_log("ClientController::show() - Client found: " . $client['name']);
            
            // Get client statistics
            $stats = $this->getClientStats($clientId);
            error_log("ClientController::show() - Stats calculated: " . print_r($stats, true));
            
            // Generate CSRF token
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $csrf_token = CSRFMiddleware::generateToken();
            
            $data = [
                'title' => 'Client - ' . $client['name'],
                'currentRoute' => 'clients/' . $clientId,
                'client' => $client,
                'currentClient' => $client,
                'stats' => $stats,
                'csrf_token' => $csrf_token,
                'flash' => Session::getFlash()
            ];
            
            $this->render('clients/show', $data);
            error_log("ClientController::show() - COMPLETED");
            
        } catch (Exception $e) {
            error_log("ClientController::show() - ERROR: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors du chargement du client.');
            header('Location: /clients');
            exit;
        }
    }
    
    /**
     * Show client months
     */
    public function showMonths($clientId) {
        error_log("ClientController::showMonths() - START with clientId: $clientId");
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$clientId]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$client) {
                error_log("ClientController::showMonths() - ERROR: Client not found with ID: $clientId");
                http_response_code(404);
                require_once APP_PATH . '/Views/errors/404.php';
                return;
            }
            
            // Get months with transactions
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month_year,
                    SUM(CASE WHEN type = 'invoice' THEN amount ELSE 0 END) as total_invoices,
                    SUM(CASE WHEN type = 'return' THEN amount ELSE 0 END) as total_returns,
                    COUNT(CASE WHEN type = 'invoice' THEN 1 END) as invoice_count,
                    COUNT(CASE WHEN type = 'return' THEN 1 END) as return_count
                FROM transactions 
                WHERE client_id = ? AND deleted_at IS NULL
                GROUP BY DATE_FORMAT(date, '%Y-%m')
                ORDER BY month_year DESC
            ");
            $stmt->execute([$clientId]);
            $months = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("ClientController::showMonths() - Found " . count($months) . " months with transactions");
            
            // Get discounts for each month
            $monthsWithDiscounts = [];
            foreach ($months as $month) {
                $stmt = $this->db->prepare("
                    SELECT discount_percent 
                    FROM client_discounts 
                    WHERE client_id = ? AND month_year = ?
                ");
                $stmt->execute([$clientId, $month['month_year']]);
                $discount = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $month['discount_percent'] = $discount['discount_percent'] ?? 0;
                $subtotal = $month['total_invoices'] - $month['total_returns'];
                $discountAmount = $subtotal * ($month['discount_percent'] / 100);
                $month['final_total'] = $subtotal - $discountAmount;
                
                $monthsWithDiscounts[] = $month;
            }
            
            // Generate CSRF token
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $csrf_token = CSRFMiddleware::generateToken();
            
            $data = [
                'title' => 'Mois d\'Activité - ' . $client['name'],
                'currentRoute' => 'clients/' . $clientId . '/months',
                'client' => $client,
                'currentClient' => $client,
                'months' => $monthsWithDiscounts,
                'csrf_token' => $csrf_token,
                'flash' => Session::getFlash()
            ];
            
            $this->render('clients/months', $data);
            error_log("ClientController::showMonths() - COMPLETED");
            
        } catch (Exception $e) {
            error_log("ClientController::showMonths() - ERROR: " . $e->getMessage());
            Session::setFlash('error', 'Erreur lors du chargement des mois.');
            header('Location: /clients/' . $clientId);
            exit;
        }
    }
    
    /**
     * Get client statistics
     */
    private function getClientStats($clientId) {
        error_log("ClientController::getClientStats() - START for clientId: $clientId");
        
        try {
            // Total transactions
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN type = 'invoice' THEN amount ELSE 0 END) as total_invoices,
                    SUM(CASE WHEN type = 'return' THEN amount ELSE 0 END) as total_returns,
                    COUNT(CASE WHEN type = 'invoice' THEN 1 END) as invoice_count,
                    COUNT(CASE WHEN type = 'return' THEN 1 END) as return_count
                FROM transactions 
                WHERE client_id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$clientId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Ensure numeric values
            $stats['total_invoices'] = floatval($stats['total_invoices'] ?? 0);
            $stats['total_returns'] = floatval($stats['total_returns'] ?? 0);
            $stats['invoice_count'] = intval($stats['invoice_count'] ?? 0);
            $stats['return_count'] = intval($stats['return_count'] ?? 0);
            $stats['net_total'] = $stats['total_invoices'] - $stats['total_returns'];
            
            error_log("ClientController::getClientStats() - Stats: " . print_r($stats, true));
            return $stats;
            
        } catch (Exception $e) {
            error_log("ClientController::getClientStats() - ERROR: " . $e->getMessage());
            return [
                'total_invoices' => 0,
                'total_returns' => 0,
                'invoice_count' => 0,
                'return_count' => 0,
                'net_total' => 0
            ];
        }
    }
    
    /**
     * Render view with layout - Enhanced with debugging
     */
    private function render($view, $data = []) {
        error_log("ClientController::render() - START");
        error_log("ClientController::render() - View: $view");
        error_log("ClientController::render() - Data keys: " . implode(', ', array_keys($data)));
        
        try {
            // Extract data for view
            extract($data);
            error_log("ClientController::render() - Data extracted successfully");
            
            // Start output buffering
            ob_start();
            error_log("ClientController::render() - Output buffering started");
            
            // Include the view file
            $viewFile = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
            error_log("ClientController::render() - View file path: $viewFile");
            
            if (file_exists($viewFile)) {
                error_log("ClientController::render() - View file exists, including...");
                include $viewFile;
                error_log("ClientController::render() - View file included successfully");
            } else {
                error_log("ClientController::render() - ERROR: View file not found: $viewFile");
                throw new Exception("View file not found: {$view}");
            }
            
            // Get view content
            $content = ob_get_clean();
            error_log("ClientController::render() - Content captured, length: " . strlen($content));
            
            // Include layout
            $layoutFile = APP_PATH . '/Views/layouts/app.php';
            error_log("ClientController::render() - Layout file: $layoutFile");
            
            if (file_exists($layoutFile)) {
                include $layoutFile;
                error_log("ClientController::render() - Layout included successfully");
            } else {
                error_log("ClientController::render() - ERROR: Layout file not found: $layoutFile");
                echo $content; // Fallback to just content
            }
            
            error_log("ClientController::render() - COMPLETED");
            
        } catch (Exception $e) {
            error_log("ClientController::render() - ERROR: " . $e->getMessage());
            error_log("ClientController::render() - Stack trace: " . $e->getTraceAsString());
            
            // Clean output buffer if still active
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Show error page
            http_response_code(500);
            echo "<h1>Error</h1><p>Unable to render view: {$view}</p>";
            if (($_ENV['DEBUG'] ?? 'false') === 'true') {
                echo "<p>Error: " . $e->getMessage() . "</p>";
            }
        }
    }
}