<?php
/**
 * Dashboard Controller
 * Handles dashboard and main statistics
 */
class DashboardController {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Show dashboard with statistics
     */
    public function index() {
        try {
            // Get statistics
            $stats = $this->getStatistics();
            
            // Prepare view data
            $data = [
                'title' => 'Tableau de Bord',
                'currentRoute' => 'dashboard',
                'stats' => $stats,
                'csrf_token' => CSRFMiddleware::generateToken(),
                'flash' => Session::getFlash()
            ];
            
            // Render view
            $this->render('dashboard/index', $data);
            
        } catch (Exception $e) {
            Session::setFlash('error', 'Erreur lors du chargement du tableau de bord.');
            header('Location: /');
            exit;
        }
    }
    
    /**
     * Get dashboard statistics
     */
    private function getStatistics() {
        $stats = [];
        
        try {
            // Total clients
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM clients WHERE deleted_at IS NULL");
            $stmt->execute();
            $stats['total_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Current month revenue
            $currentMonth = date('Y-m');
            $stmt = $this->db->prepare("
                SELECT 
                    COALESCE(SUM(amount), 0) as total_invoices,
                    COUNT(*) as invoice_count
                FROM transactions 
                WHERE type = 'invoice' 
                AND DATE_FORMAT(date, '%Y-%m') = ?
                AND deleted_at IS NULL
            ");
            $stmt->execute([$currentMonth]);
            $invoiceData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_returns
                FROM transactions 
                WHERE type = 'return' 
                AND DATE_FORMAT(date, '%Y-%m') = ?
                AND deleted_at IS NULL
            ");
            $stmt->execute([$currentMonth]);
            $returnData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stats['current_month'] = [
                'revenue' => $invoiceData['total_invoices'] - $returnData['total_returns'],
                'invoices' => $invoiceData['total_invoices'],
                'returns' => $returnData['total_returns'],
                'invoice_count' => $invoiceData['invoice_count']
            ];
            
            // Recent clients
            $stmt = $this->db->prepare("
                SELECT id, name, created_at 
                FROM clients 
                WHERE deleted_at IS NULL 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $stats['recent_clients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Recent transactions
            $stmt = $this->db->prepare("
                SELECT t.*, c.name as client_name 
                FROM transactions t 
                JOIN clients c ON t.client_id = c.id 
                WHERE t.deleted_at IS NULL 
                ORDER BY t.date DESC 
                LIMIT 10
            ");
            $stmt->execute();
            $stats['recent_transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Monthly trends (last 6 months)
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    SUM(CASE WHEN type = 'invoice' THEN amount ELSE 0 END) as invoices,
                    SUM(CASE WHEN type = 'return' THEN amount ELSE 0 END) as returns
                FROM transactions 
                WHERE date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                AND deleted_at IS NULL
                GROUP BY DATE_FORMAT(date, '%Y-%m')
                ORDER BY month DESC
            ");
            $stmt->execute();
            $stats['monthly_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // Return default stats on error
            $stats = [
                'total_clients' => 0,
                'current_month' => ['revenue' => 0, 'invoices' => 0, 'returns' => 0, 'invoice_count' => 0],
                'recent_clients' => [],
                'recent_transactions' => [],
                'monthly_trends' => []
            ];
        }
        
        return $stats;
    }
    
    /**
     * Render view with layout
     */
    private function render($view, $data = []) {
        // Extract data for view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewFile = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new Exception("View file not found: {$view}");
        }
        
        // Get view content
        $content = ob_get_clean();
        
        // Include layout
        include APP_PATH . '/Views/layouts/app.php';
    }
}