<?php
/**
 * Modèle pour la gestion des transactions (factures et retours)
 */
class Transaction extends BaseModel {
    protected $table = 'transactions';
    protected $fillable = ['client_id', 'order_number', 'amount', 'transaction_date', 'month_year', 'type'];
    
    /**
     * Types de transactions
     */
    const TYPE_INVOICE = 'invoice';
    const TYPE_RETURN = 'return';
    
    /**
     * Création d'une facture
     */
    public function createInvoice($clientId, $orderNumber, $amount, $date, $monthYear) {
        // Validation des données
        $this->validateTransactionData($clientId, $orderNumber, $amount, $date);
        
        // Vérification d'unicité du numéro de commande pour ce client
        if ($this->orderNumberExists($orderNumber, $clientId)) {
            throw new Exception('Ce numéro de commande existe déjà pour ce client');
        }
        
        $data = [
            'client_id' => $clientId,
            'order_number' => $orderNumber,
            'amount' => abs($amount), // S'assurer que c'est positif
            'transaction_date' => $date,
            'month_year' => $monthYear,
            'type' => self::TYPE_INVOICE
        ];
        
        return $this->create($data);
    }
    
    /**
     * Création d'un retour
     */
    public function createReturn($clientId, $orderNumber, $amount, $date, $monthYear) {
        // Validation des données
        $this->validateTransactionData($clientId, $orderNumber, $amount, $date);
        
        $data = [
            'client_id' => $clientId,
            'order_number' => $orderNumber,
            'amount' => -abs($amount), // S'assurer que c'est négatif
            'transaction_date' => $date,
            'month_year' => $monthYear,
            'type' => self::TYPE_RETURN
        ];
        
        return $this->create($data);
    }
    
    /**
     * Récupération des transactions par client et mois
     */
    public function getByClientAndMonth($clientId, $monthYear) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE client_id = ? AND month_year = ? 
            ORDER BY transaction_date DESC, created_at DESC
        ";
        
        return $this->db->fetchAll($sql, [$clientId, $monthYear]);
    }
    
    /**
     * Calcul du total pour un client et un mois
     */
    public function getClientMonthTotal($clientId, $monthYear) {
        $sql = "
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM {$this->table} 
            WHERE client_id = ? AND month_year = ?
        ";
        
        $result = $this->db->fetch($sql, [$clientId, $monthYear]);
        return (float)$result['total'];
    }
    
    /**
     * Comptage des transactions pour un client et un mois
     */
    public function getClientMonthTransactionCount($clientId, $monthYear) {
        $sql = "
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE client_id = ? AND month_year = ?
        ";
        
        $result = $this->db->fetch($sql, [$clientId, $monthYear]);
        return (int)$result['count'];
    }
    
    /**
     * Total des factures pour un client et un mois
     */
    public function getClientMonthInvoicesTotal($clientId, $monthYear) {
        $sql = "
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM {$this->table} 
            WHERE client_id = ? AND month_year = ? AND amount > 0
        ";
        
        $result = $this->db->fetch($sql, [$clientId, $monthYear]);
        return (float)$result['total'];
    }
    
    /**
     * Total des retours pour un client et un mois
     */
    public function getClientMonthReturnsTotal($clientId, $monthYear) {
        $sql = "
            SELECT COALESCE(ABS(SUM(amount)), 0) as total 
            FROM {$this->table} 
            WHERE client_id = ? AND month_year = ? AND amount < 0
        ";
        
        $result = $this->db->fetch($sql, [$clientId, $monthYear]);
        return (float)$result['total'];
    }
    
    /**
     * Récupération des mois d'activité d'un client avec pagination
     */
    public function getClientMonths($clientId, $page = 1, $perPage = 12) {
        $offset = ($page - 1) * $perPage;
        
        // Comptage total des mois
        $countSql = "
            SELECT COUNT(DISTINCT month_year) as total 
            FROM {$this->table} 
            WHERE client_id = ?
        ";
        $totalResult = $this->db->fetch($countSql, [$clientId]);
        $total = (int)$totalResult['total'];
        
        // Récupération des mois avec totaux
        $sql = "
            SELECT 
                month_year,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as invoices_total,
                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as returns_total,
                MIN(transaction_date) as first_transaction,
                MAX(transaction_date) as last_transaction
            FROM {$this->table} 
            WHERE client_id = ?
            GROUP BY month_year 
            ORDER BY month_year DESC
            LIMIT {$offset}, {$perPage}
        ";
        
        $data = $this->db->fetchAll($sql, [$clientId]);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }
    
    /**
     * Vérification d'existence d'un numéro de commande
     */
    public function orderNumberExists($orderNumber, $clientId = null, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE order_number = ?";
        $params = [$orderNumber];
        
        if ($clientId) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * Statistiques globales des transactions
     */
    public function getGlobalStats() {
        $sql = "
            SELECT 
                COUNT(DISTINCT client_id) as total_clients,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_revenue,
                COUNT(*) as total_transactions,
                SUM(CASE WHEN amount < 0 THEN 1 ELSE 0 END) as total_returns,
                AVG(CASE WHEN amount > 0 THEN amount END) as avg_transaction,
                COUNT(DISTINCT month_year) as active_months,
                MIN(transaction_date) as first_transaction,
                MAX(transaction_date) as last_transaction
            FROM {$this->table}
        ";
        
        return $this->db->fetch($sql);
    }
    
    /**
     * Statistiques d'un client
     */
    public function getClientStats($clientId) {
        $sql = "
            SELECT 
                COUNT(DISTINCT month_year) as total_months,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_invoices,
                SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) as total_returns,
                SUM(amount) as total_balance,
                COUNT(*) as total_transactions,
                AVG(CASE WHEN amount > 0 THEN amount END) as avg_invoice,
                MIN(transaction_date) as first_transaction,
                MAX(transaction_date) as last_transaction
            FROM {$this->table} 
            WHERE client_id = ?
        ";
        
        return $this->db->fetch($sql, [$clientId]);
    }
    
    /**
     * Transactions récentes d'un client
     */
    public function getClientRecentTransactions($clientId, $limit = 10) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE client_id = ? 
            ORDER BY transaction_date DESC, created_at DESC 
            LIMIT {$limit}
        ";
        
        return $this->db->fetchAll($sql, [$clientId]);
    }
    
    /**
     * Mois d'activité d'un client (liste simple)
     */
    public function getClientActiveMonths($clientId) {
        $sql = "
            SELECT DISTINCT month_year
            FROM {$this->table} 
            WHERE client_id = ? 
            ORDER BY month_year DESC
        ";
        
        return $this->db->fetchAll($sql, [$clientId]);
    }
    
    /**
     * Vérification si un client a des transactions
     */
    public function clientHasTransactions($clientId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE client_id = ?";
        $result = $this->db->fetch($sql, [$clientId]);
        return $result['count'] > 0;
    }
    
    /**
     * Recherche dans les transactions
     */
    public function search($query, $limit = 10) {
        $sql = "
            SELECT t.*, c.name as client_name
            FROM {$this->table} t
            INNER JOIN clients c ON t.client_id = c.id
            WHERE t.order_number LIKE ? 
               OR c.name LIKE ?
               OR t.amount LIKE ?
            ORDER BY t.transaction_date DESC
            LIMIT {$limit}
        ";
        
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    /**
     * Transactions par période
     */
    public function getByPeriod($startDate, $endDate, $clientId = null) {
        $sql = "
            SELECT t.*, c.name as client_name
            FROM {$this->table} t
            INNER JOIN clients c ON t.client_id = c.id
            WHERE t.transaction_date BETWEEN ? AND ?
        ";
        
        $params = [$startDate, $endDate];
        
        if ($clientId) {
            $sql .= " AND t.client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY t.transaction_date DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Données mensuelles pour graphiques
     */
    public function getMonthlyData($months = 12) {
        $sql = "
            SELECT 
                month_year,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as revenue,
                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as returns,
                COUNT(*) as transactions,
                COUNT(DISTINCT client_id) as active_clients
            FROM {$this->table} 
            WHERE month_year >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL {$months} MONTH), '%Y-%m')
            GROUP BY month_year 
            ORDER BY month_year ASC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Top des numéros de commande (pour détecter les doublons)
     */
    public function getDuplicateOrderNumbers() {
        $sql = "
            SELECT 
                order_number,
                COUNT(*) as count,
                GROUP_CONCAT(DISTINCT client_id) as client_ids
            FROM {$this->table}
            GROUP BY order_number
            HAVING COUNT(*) > 1
            ORDER BY count DESC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Validation des données de transaction
     */
    private function validateTransactionData($clientId, $orderNumber, $amount, $date) {
        $errors = [];
        
        // Validation du client
        if (empty($clientId) || !is_numeric($clientId)) {
            $errors[] = 'ID client invalide';
        }
        
        // Validation du numéro de commande
        if (empty($orderNumber)) {
            $errors[] = 'Numéro de commande requis';
        } elseif (strlen($orderNumber) > 100) {
            $errors[] = 'Numéro de commande trop long';
        }
        
        // Validation du montant
        if (!is_numeric($amount) || $amount <= 0) {
            $errors[] = 'Montant invalide';
        }
        
        // Validation de la date
        if (empty($date)) {
            $errors[] = 'Date requise';
        } else {
            $dateObj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
                $errors[] = 'Format de date invalide';
            }
        }
        
        if (!empty($errors)) {
            throw new Exception('Données invalides: ' . implode(', ', $errors));
        }
    }
    
    /**
     * Calcul des totaux par type
     */
    public function getTotalsByType($clientId = null, $monthYear = null) {
        $sql = "
            SELECT 
                type,
                COUNT(*) as count,
                SUM(ABS(amount)) as total_amount
            FROM {$this->table}
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($clientId) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        if ($monthYear) {
            $sql .= " AND month_year = ?";
            $params[] = $monthYear;
        }
        
        $sql .= " GROUP BY type";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Nettoyage des transactions orphelines
     */
    public function cleanOrphanTransactions() {
        $sql = "
            DELETE t FROM {$this->table} t
            LEFT JOIN clients c ON t.client_id = c.id
            WHERE c.id IS NULL
        ";
        
        return $this->db->query($sql);
    }
    
    /**
     * Archivage des anciennes transactions
     */
    public function archiveOldTransactions($months = 24) {
        // Cette méthode pourrait déplacer les anciennes transactions vers une table d'archive
        // Pour l'instant, on se contente de les marquer
        $cutoffDate = date('Y-m-d', strtotime("-{$months} months"));
        
        $sql = "
            UPDATE {$this->table} 
            SET archived = 1 
            WHERE transaction_date < ? AND archived = 0
        ";
        
        return $this->db->query($sql, [$cutoffDate]);
    }
    
    /**
     * Récupération des transactions récentes globales
     */
    public function getRecent($limit = 10) {
        $sql = "
            SELECT t.*, c.name as client_name
            FROM {$this->table} t
            INNER JOIN clients c ON t.client_id = c.id
            ORDER BY t.created_at DESC
            LIMIT {$limit}
        ";
        
        return $this->db->fetchAll($sql);
    }
}