<?php
/**
 * Client Controller
 * Handles client management operations
 */
class ClientController {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Show all clients with pagination
     */
    public function index() {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_ENV['ITEMS_PER_PAGE'] ?? 12);
            $offset = ($page - 1) * $perPage;
            
            // Get total count
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM clients WHERE deleted_at IS NULL");
            $stmt->execute();
            $totalClients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get clients for current page
            $stmt = $this->db->prepare("
                SELECT * FROM clients 
                WHERE deleted_at IS NULL 
                ORDER BY name ASC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$perPage, $offset]);
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
            
            $data = [
                'title' => 'Gestion Clients',
                'currentRoute' => 'clients',
                'clients' => $clients,
                'pagination' => $pagination,
                'csrf_token' => CSRFMiddleware::generateToken(),
                'flash' => Session::getFlash()
            ];
            
            $this->render('clients/index', $data);
            
        } catch (Exception $e) {
            Session::setFlash('error', 'Erreur lors du chargement des clients.');
            header('Location: /dashboard');
            exit;
        }
    }
    
    /**
     * Show single client
     */
    public function show($clientId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$clientId]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$client) {
                http_response_code(404);
                require_once APP_PATH . '/Views/errors/404.php';
                return;
            }
            
            // Get client statistics
            $stats = $this->getClientStats($clientId);
            
            $data = [
                'title' => 'Client - ' . $client['name'],
                'currentRoute' => 'clients/' . $clientId,
                'client' => $client,
                'currentClient' => $client,
                'stats' => $stats,
                'csrf_token' => CSRFMiddleware::generateToken(),
                'flash' => Session::getFlash()
            ];
            
            $this->render('clients/show', $data);
            
        } catch (Exception $e) {
            Session::setFlash('error', 'Erreur lors du chargement du client.');
            header('Location: /clients');
            exit;
        }
    }
    
    /**
     * Show client months
     */
    public function showMonths($clientId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$clientId]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$client) {
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
            
            $data = [
                'title' => 'Mois d\'Activité - ' . $client['name'],
                'currentRoute' => 'clients/' . $clientId . '/months',
                'client' => $client,
                'currentClient' => $client,
                'months' => $monthsWithDiscounts,
                'csrf_token' => CSRFMiddleware::generateToken(),
                'flash' => Session::getFlash()
            ];
            
            $this->render('clients/months', $data);
            
        } catch (Exception $e) {
            Session::setFlash('error', 'Erreur lors du chargement des mois.');
            header('Location: /clients/' . $clientId);
            exit;
        }
    }
    
    /**
     * Store new client
     */
    public function store() {
        try {
            // Validate input
            $validator = new Validator();
            $rules = [
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:500',
                'phone' => 'required|phone',
                'email' => 'email'
            ];
            
            $validation = $validator->validate($_POST, $rules);
            if (!$validation['valid']) {
                Session::setFlashBag(['error' => $validation['errors']]);
                header('Location: /clients');
                exit;
            }
            
            // Check for duplicate name
            $stmt = $this->db->prepare("SELECT id FROM clients WHERE name = ? AND deleted_at IS NULL");
            $stmt->execute([Security::sanitizeString($_POST['name'])]);
            if ($stmt->fetch()) {
                Session::setFlash('error', 'Un client avec ce nom existe déjà.');
                header('Location: /clients');
                exit;
            }
            
            // Insert client
            $stmt = $this->db->prepare("
                INSERT INTO clients (name, address, phone, email, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                Security::sanitizeString($_POST['name']),
                Security::sanitizeString($_POST['address']),
                Security::sanitizeString($_POST['phone']),
                Security::sanitizeString($_POST['email'] ?? null)
            ]);
            
            Session::setFlash('success', 'Client ajouté avec succès.');
            header('Location: /clients');
            exit;
            
        } catch (Exception $e) {
            Session::setFlash('error', 'Erreur lors de l\'ajout du client.');
            header('Location: /clients');
            exit;
        }
    }
    
    /**
     * Get client statistics
     */
    private function getClientStats($clientId) {
        $stats = [];
        
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
            
            $stats['net_total'] = $stats['total_invoices'] - $stats['total_returns'];
            
        } catch (Exception $e) {
            $stats = [
                'total_invoices' => 0,
                'total_returns' => 0,
                'invoice_count' => 0,
                'return_count' => 0,
                'net_total' => 0
            ];
        }
        
        return $stats;
    }
    
    /**
     * Render view with layout
     */
    private function render($view, $data = []) {
        extract($data);
        ob_start();
        
        $viewFile = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new Exception("View file not found: {$view}");
        }
        
        $content = ob_get_clean();
        include APP_PATH . '/Views/layouts/app.php';
    }
}
