<?php
/**
 * Contrôleur pour le tableau de bord
 */
class DashboardController extends BaseController {
    private $clientModel;
    private $transactionModel;
    
    public function __construct() {
        parent::__construct();
        $this->clientModel = new Client();
        $this->transactionModel = new Transaction();
    }
    
    /**
     * Page d'accueil du tableau de bord
     */
    public function index() {
        $this->requireAuth();
        
        try {
            // Récupération des statistiques globales
            $stats = $this->getGlobalStats();
            
            // Récupération des clients récents
            $recentClients = $this->clientModel->getRecent(5);
            
            // Récupération des transactions récentes
            $recentTransactions = $this->transactionModel->getRecent(10);
            
            // Données pour les graphiques
            $monthlyData = $this->getMonthlyData();
            $topClients = $this->getTopClients();
            
            $this->logAction('dashboard_view');
            
            $this->render('dashboard/index', [
                'stats' => $stats,
                'recent_clients' => $recentClients,
                'recent_transactions' => $recentTransactions,
                'monthly_data' => $monthlyData,
                'top_clients' => $topClients,
                'flash' => $this->getFlashMessage()
            ]);
            
        } catch (Exception $e) {
            $this->error('Erreur lors du chargement du tableau de bord: ' . $e->getMessage());
        }
    }
    
    /**
     * API pour les statistiques en temps réel
     */
    public function stats() {
        $this->requireAuth();
        
        try {
            $stats = $this->getGlobalStats();
            $this->json($stats);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * API pour les données des graphiques
     */
    public function chartData($type = 'monthly') {
        $this->requireAuth();
        
        try {
            switch ($type) {
                case 'monthly':
                    $data = $this->getMonthlyData();
                    break;
                case 'clients':
                    $data = $this->getTopClients();
                    break;
                case 'revenue':
                    $data = $this->getRevenueData();
                    break;
                default:
                    throw new Exception('Type de graphique invalide');
            }
            
            $this->json($data);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Récupération des statistiques globales
     */
    private function getGlobalStats() {
        $sql = "
            SELECT 
                COUNT(DISTINCT client_id) as total_clients,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_revenue,
                COUNT(*) as total_transactions,
                SUM(CASE WHEN amount < 0 THEN 1 ELSE 0 END) as total_returns,
                AVG(CASE WHEN amount > 0 THEN amount END) as avg_transaction,
                COUNT(DISTINCT month_year) as active_months
            FROM transactions
        ";
        
        $stats = $this->db->fetch($sql);
        
        // Statistiques additionnelles
        $todayStats = $this->getTodayStats();
        $monthStats = $this->getCurrentMonthStats();
        
        return [
            'total_clients' => (int)$stats['total_clients'],
            'total_revenue' => (float)$stats['total_revenue'],
            'total_transactions' => (int)$stats['total_transactions'],
            'total_returns' => (int)$stats['total_returns'],
            'avg_transaction' => (float)$stats['avg_transaction'],
            'active_months' => (int)$stats['active_months'],
            'today' => $todayStats,
            'this_month' => $monthStats
        ];
    }
    
    /**
     * Statistiques du jour
     */
    private function getTodayStats() {
        $today = date('Y-m-d');
        
        $sql = "
            SELECT 
                COUNT(*) as transactions_today,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as revenue_today,
                COUNT(DISTINCT client_id) as active_clients_today
            FROM transactions 
            WHERE DATE(transaction_date) = ?
        ";
        
        return $this->db->fetch($sql, [$today]);
    }
    
    /**
     * Statistiques du mois en cours
     */
    private function getCurrentMonthStats() {
        $currentMonth = date('Y-m');
        
        $sql = "
            SELECT 
                COUNT(*) as transactions_month,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as revenue_month,
                COUNT(DISTINCT client_id) as active_clients_month,
                SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) as returns_month
            FROM transactions 
            WHERE month_year = ?
        ";
        
        return $this->db->fetch($sql, [$currentMonth]);
    }
    
    /**
     * Données mensuelles pour les graphiques
     */
    private function getMonthlyData() {
        $sql = "
            SELECT 
                month_year,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as revenue,
                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as returns,
                COUNT(*) as transactions,
                COUNT(DISTINCT client_id) as active_clients
            FROM transactions 
            WHERE month_year >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m')
            GROUP BY month_year 
            ORDER BY month_year ASC
        ";
        
        $data = $this->db->fetchAll($sql);
        
        // Formatage pour les graphiques
        $formatted = [
            'labels' => [],
            'revenue' => [],
            'returns' => [],
            'transactions' => [],
            'clients' => []
        ];
        
        foreach ($data as $row) {
            $formatted['labels'][] = date('M Y', strtotime($row['month_year'] . '-01'));
            $formatted['revenue'][] = (float)$row['revenue'];
            $formatted['returns'][] = (float)$row['returns'];
            $formatted['transactions'][] = (int)$row['transactions'];
            $formatted['clients'][] = (int)$row['active_clients'];
        }
        
        return $formatted;
    }
    
    /**
     * Top clients par chiffre d'affaires
     */
    private function getTopClients() {
        $sql = "
            SELECT 
                c.name,
                SUM(t.amount) as total_amount,
                COUNT(t.id) as total_transactions,
                COUNT(DISTINCT t.month_year) as active_months,
                MAX(t.transaction_date) as last_transaction
            FROM clients c
            INNER JOIN transactions t ON c.id = t.client_id
            GROUP BY c.id, c.name
            HAVING total_amount > 0
            ORDER BY total_amount DESC
            LIMIT 10
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Évolution du chiffre d'affaires
     */
    private function getRevenueData() {
        $sql = "
            SELECT 
                DATE(transaction_date) as date,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as daily_revenue
            FROM transactions 
            WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(transaction_date) 
            ORDER BY date ASC
        ";
        
        $data = $this->db->fetchAll($sql);
        
        $formatted = [
            'labels' => [],
            'revenue' => []
        ];
        
        foreach ($data as $row) {
            $formatted['labels'][] = date('d/m', strtotime($row['date']));
            $formatted['revenue'][] = (float)$row['daily_revenue'];
        }
        
        return $formatted;
    }
    
    /**
     * Recherche rapide
     */
    public function search() {
        $this->requireAuth();
        
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            $this->json(['results' => []]);
        }
        
        try {
            // Recherche dans les clients
            $clients = $this->clientModel->search($query, 5);
            
            // Recherche dans les transactions
            $transactions = $this->transactionModel->search($query, 5);
            
            $results = [
                'clients' => $clients,
                'transactions' => $transactions
            ];
            
            $this->json(['results' => $results]);
            
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Export des données
     */
    public function export($type = 'csv') {
        $this->requireAuth();
        
        try {
            switch ($type) {
                case 'csv':
                    $this->exportCSV();
                    break;
                case 'pdf':
                    $this->exportPDF();
                    break;
                default:
                    throw new Exception('Format d\'export invalide');
            }
        } catch (Exception $e) {
            $this->error('Erreur lors de l\'export: ' . $e->getMessage());
        }
    }
    
    /**
     * Export CSV
     */
    private function exportCSV() {
        $stats = $this->getGlobalStats();
        $clients = $this->clientModel->getAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=dashboard_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Statistiques Globales']);
        fputcsv($output, ['Total Clients', $stats['total_clients']]);
        fputcsv($output, ['Chiffre Affaires', $stats['total_revenue']]);
        fputcsv($output, ['Total Transactions', $stats['total_transactions']]);
        
        fclose($output);
        exit;
    }
    
    /**
     * Export PDF (placeholder)
     */
    private function exportPDF() {
        // À implémenter avec une librairie PDF
        throw new Exception('Export PDF non encore implémenté');
    }
}