<?php
/**
 * Modèle pour la gestion des clients
 */
class Client extends BaseModel {
    protected $table = 'clients';
    protected $fillable = ['name', 'address', 'phone', 'email'];
    
    /**
     * Règles de validation pour les clients
     */
    private $validationRules = [
        'name' => 'required|string|max:255',
        'address' => 'string|max:500',
        'phone' => 'string|max:20',
        'email' => 'email|max:100'
    ];
    
    /**
     * Création d'un nouveau client avec validation
     */
    public function create($data) {
        $validation = $this->validate($data, $this->validationRules);
        
        if (!$validation['valid']) {
            throw new Exception('Données invalides: ' . implode(', ', $validation['errors']));
        }
        
        // Vérification d'unicité du nom
        if ($this->existsByName($data['name'])) {
            throw new Exception('Un client avec ce nom existe déjà');
        }
        
        return parent::create($validation['data']);
    }
    
    /**
     * Mise à jour d'un client avec validation
     */
    public function update($id, $data) {
        $validation = $this->validate($data, $this->validationRules);
        
        if (!$validation['valid']) {
            throw new Exception('Données invalides: ' . implode(', ', $validation['errors']));
        }
        
        // Vérification d'unicité du nom (sauf pour le client actuel)
        if ($this->existsByName($data['name'], $id)) {
            throw new Exception('Un client avec ce nom existe déjà');
        }
        
        return parent::update($id, $validation['data']);
    }
    
    /**
     * Vérification d'existence par nom
     */
    public function existsByName($name, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE name = ?";
        $params = [$name];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * Recherche de clients
     */
    public function search($query, $limit = 10, $page = 1) {
        $searchFields = ['name', 'address', 'phone', 'email'];
        return parent::search($query, $searchFields, $limit, $page);
    }
    
    /**
     * Récupération de tous les clients avec leurs statistiques
     */
    public function getAllWithStats() {
        $sql = "
            SELECT 
                c.*,
                COALESCE(t.total_transactions, 0) as total_transactions,
                COALESCE(t.total_amount, 0) as total_amount,
                COALESCE(t.total_invoices, 0) as total_invoices,
                COALESCE(t.total_returns, 0) as total_returns,
                t.last_transaction,
                t.first_transaction,
                COALESCE(t.active_months, 0) as active_months
            FROM {$this->table} c
            LEFT JOIN (
                SELECT 
                    client_id,
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_invoices,
                    SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_returns,
                    MAX(transaction_date) as last_transaction,
                    MIN(transaction_date) as first_transaction,
                    COUNT(DISTINCT month_year) as active_months
                FROM transactions 
                GROUP BY client_id
            ) t ON c.id = t.client_id
            ORDER BY c.name
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Récupération d'un client avec ses statistiques
     */
    public function findByIdWithStats($id) {
        $sql = "
            SELECT 
                c.*,
                COALESCE(t.total_transactions, 0) as total_transactions,
                COALESCE(t.total_amount, 0) as total_amount,
                COALESCE(t.total_invoices, 0) as total_invoices,
                COALESCE(t.total_returns, 0) as total_returns,
                t.last_transaction,
                t.first_transaction,
                COALESCE(t.active_months, 0) as active_months,
                COALESCE(t.avg_transaction, 0) as avg_transaction
            FROM {$this->table} c
            LEFT JOIN (
                SELECT 
                    client_id,
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_invoices,
                    SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_returns,
                    MAX(transaction_date) as last_transaction,
                    MIN(transaction_date) as first_transaction,
                    COUNT(DISTINCT month_year) as active_months,
                    AVG(CASE WHEN amount > 0 THEN amount END) as avg_transaction
                FROM transactions 
                WHERE client_id = ?
                GROUP BY client_id
            ) t ON c.id = t.client_id
            WHERE c.id = ?
        ";
        
        return $this->db->fetch($sql, [$id, $id]);
    }
    
    /**
     * Récupération des clients les plus actifs
     */
    public function getTopClients($limit = 10, $orderBy = 'total_amount') {
        $validOrderBy = ['total_amount', 'total_transactions', 'active_months', 'last_transaction'];
        
        if (!in_array($orderBy, $validOrderBy)) {
            $orderBy = 'total_amount';
        }
        
        $sql = "
            SELECT 
                c.*,
                t.total_transactions,
                t.total_amount,
                t.last_transaction,
                t.active_months
            FROM {$this->table} c
            INNER JOIN (
                SELECT 
                    client_id,
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount,
                    MAX(transaction_date) as last_transaction,
                    COUNT(DISTINCT month_year) as active_months
                FROM transactions 
                GROUP BY client_id
                HAVING total_amount > 0
            ) t ON c.id = t.client_id
            ORDER BY t.{$orderBy} DESC
            LIMIT {$limit}
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Recherche pour autocomplétion
     */
    public function searchForAutocomplete($query, $limit = 10) {
        $sql = "
            SELECT id, name, address, phone
            FROM {$this->table}
            WHERE name LIKE ? OR address LIKE ? OR phone LIKE ?
            ORDER BY name
            LIMIT {$limit}
        ";
        
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    /**
     * Récupération des clients actifs (avec transactions récentes)
     */
    public function getActiveClients($days = 30, $limit = null) {
        $sql = "
            SELECT DISTINCT c.*
            FROM {$this->table} c
            INNER JOIN transactions t ON c.id = t.client_id
            WHERE t.transaction_date >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            ORDER BY c.name
        ";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Récupération des clients inactifs
     */
    public function getInactiveClients($days = 90) {
        $sql = "
            SELECT c.*
            FROM {$this->table} c
            LEFT JOIN transactions t ON c.id = t.client_id
            WHERE t.client_id IS NULL 
               OR t.transaction_date < DATE_SUB(NOW(), INTERVAL {$days} DAY)
            GROUP BY c.id
            ORDER BY c.name
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Statistiques globales des clients
     */
    public function getGlobalStats() {
        $sql = "
            SELECT 
                COUNT(*) as total_clients,
                COUNT(CASE WHEN t.client_id IS NOT NULL THEN 1 END) as clients_with_transactions,
                COUNT(CASE WHEN t.last_transaction >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_clients_30d,
                COUNT(CASE WHEN t.last_transaction >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 END) as active_clients_90d,
                AVG(t.total_amount) as avg_client_value,
                MAX(t.total_amount) as max_client_value
            FROM {$this->table} c
            LEFT JOIN (
                SELECT 
                    client_id,
                    SUM(amount) as total_amount,
                    MAX(transaction_date) as last_transaction
                FROM transactions 
                GROUP BY client_id
            ) t ON c.id = t.client_id
        ";
        
        return $this->db->fetch($sql);
    }
    
    /**
     * Clients par mois d'inscription
     */
    public function getClientsByMonth($months = 12) {
        $sql = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as clients_count
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$months} MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Vérification si un client a des transactions
     */
    public function hasTransactions($clientId) {
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE client_id = ?";
        $result = $this->db->fetch($sql, [$clientId]);
        return $result['count'] > 0;
    }
    
    /**
     * Suppression d'un client avec vérifications
     */
    public function delete($id) {
        // Vérification que le client existe
        $client = $this->findById($id);
        if (!$client) {
            throw new Exception('Client non trouvé');
        }
        
        // Vérification qu'il n'y a pas de transactions
        if ($this->hasTransactions($id)) {
            throw new Exception('Impossible de supprimer un client ayant des transactions');
        }
        
        return parent::delete($id);
    }
    
    /**
     * Exportation des données client
     */
    public function exportData($clientId) {
        $client = $this->findByIdWithStats($clientId);
        
        if (!$client) {
            throw new Exception('Client non trouvé');
        }
        
        // Récupération des transactions
        $transactionsSql = "
            SELECT * FROM transactions 
            WHERE client_id = ? 
            ORDER BY transaction_date DESC
        ";
        $transactions = $this->db->fetchAll($transactionsSql, [$clientId]);
        
        // Récupération des remises
        $discountsSql = "
            SELECT * FROM month_discounts 
            WHERE client_id = ? 
            ORDER BY month_year DESC
        ";
        $discounts = $this->db->fetchAll($discountsSql, [$clientId]);
        
        return [
            'client' => $client,
            'transactions' => $transactions,
            'discounts' => $discounts,
            'export_date' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Fusion de deux clients
     */
    public function mergeClients($keepClientId, $mergeClientId) {
        $keepClient = $this->findById($keepClientId);
        $mergeClient = $this->findById($mergeClientId);
        
        if (!$keepClient || !$mergeClient) {
            throw new Exception('Un des clients n\'existe pas');
        }
        
        try {
            $this->beginTransaction();
            
            // Transfert des transactions
            $this->db->query(
                "UPDATE transactions SET client_id = ? WHERE client_id = ?",
                [$keepClientId, $mergeClientId]
            );
            
            // Transfert des remises
            $this->db->query(
                "UPDATE month_discounts SET client_id = ? WHERE client_id = ? 
                 AND NOT EXISTS (
                     SELECT 1 FROM month_discounts md2 
                     WHERE md2.client_id = ? AND md2.month_year = month_discounts.month_year
                 )",
                [$keepClientId, $mergeClientId, $keepClientId]
            );
            
            // Suppression des remises en double
            $this->db->query(
                "DELETE FROM month_discounts WHERE client_id = ?",
                [$mergeClientId]
            );
            
            // Suppression du client fusionné
            $this->delete($mergeClientId);
            
            $this->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            throw new Exception('Erreur lors de la fusion: ' . $e->getMessage());
        }
    }
    
    /**
     * Validation des données spécifique aux clients
     */
    protected function validateClientData($data) {
        $errors = [];
        
        // Validation du nom (obligatoire et unique)
        if (empty($data['name'])) {
            $errors['name'] = 'Le nom est obligatoire';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'Le nom ne peut pas dépasser 255 caractères';
        }
        
        // Validation de l'email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide';
        }
        
        // Validation du téléphone (format algérien)
        if (!empty($data['phone'])) {
            $phonePattern = '/^(\+213|0)?[1-9][0-9]{8}$/';
            if (!preg_match($phonePattern, $data['phone'])) {
                $errors['phone'] = 'Le numéro de téléphone n\'est pas valide';
            }
        }
        
        return $errors;
    }
}