<?php
/**
 * Modèle de base pour tous les modèles
 * Fournit les fonctionnalités communes de base de données
 */
abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $timestamps = true;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Récupération de tous les enregistrements
     */
    public function getAll($orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Recherche par ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $result = $this->db->fetch($sql, [$id]);
        
        return $result ? $this->hideFields($result) : null;
    }
    
    /**
     * Recherche avec conditions
     */
    public function findWhere($conditions, $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table} WHERE ";
        $params = [];
        $whereClauses = [];
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $whereClauses[] = "{$field} IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
        }
        
        $sql .= implode(' AND ', $whereClauses);
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $results = $this->db->fetchAll($sql, $params);
        return array_map([$this, 'hideFields'], $results);
    }
    
    /**
     * Recherche d'un seul enregistrement avec conditions
     */
    public function findOneWhere($conditions) {
        $results = $this->findWhere($conditions, null, 1);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Création d'un nouvel enregistrement
     */
    public function create($data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($data);
            return $this->db->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la création: " . $e->getMessage());
        }
    }
    
    /**
     * Mise à jour d'un enregistrement
     */
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $setClauses = [];
        foreach ($data as $field => $value) {
            $setClauses[] = "{$field} = :{$field}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE {$this->primaryKey} = :id";
        $data['id'] = $id;
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour: " . $e->getMessage());
        }
    }
    
    /**
     * Suppression d'un enregistrement
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        
        try {
            return $this->db->query($sql, [$id]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression: " . $e->getMessage());
        }
    }
    
    /**
     * Comptage des enregistrements
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $whereClauses = [];
            
            foreach ($conditions as $field => $value) {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $sql .= implode(' AND ', $whereClauses);
        }
        
        $result = $this->db->fetch($sql, $params);
        return (int)$result['count'];
    }
    
    /**
     * Vérification d'existence
     */
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
    
    /**
     * Pagination
     */
    public function paginate($page = 1, $perPage = 10, $conditions = [], $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        
        // Comptage total
        $total = $this->count($conditions);
        
        // Construction de la requête
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $whereClauses = [];
            
            foreach ($conditions as $field => $value) {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $sql .= implode(' AND ', $whereClauses);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT {$offset}, {$perPage}";
        
        $data = $this->db->fetchAll($sql, $params);
        $data = array_map([$this, 'hideFields'], $data);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
            ]
        ];
    }
    
    /**
     * Recherche textuelle
     */
    public function search($query, $fields, $limit = 10, $page = 1) {
        $offset = ($page - 1) * $limit;
        
        // Construction des conditions de recherche
        $searchConditions = [];
        $params = [];
        
        foreach ($fields as $field) {
            $searchConditions[] = "{$field} LIKE ?";
            $params[] = "%{$query}%";
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' OR ', $searchConditions);
        $sql .= " LIMIT {$offset}, {$limit}";
        
        $results = $this->db->fetchAll($sql, $params);
        return array_map([$this, 'hideFields'], $results);
    }
    
    /**
     * Exécution d'une requête personnalisée
     */
    public function query($sql, $params = []) {
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Exécution d'une requête avec un seul résultat
     */
    public function queryOne($sql, $params = []) {
        return $this->db->fetch($sql, $params);
    }
    
    /**
     * Démarrage d'une transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Validation d'une transaction
     */
    public function commit() {
        return $this->db->commit();
    }
    
    /**
     * Annulation d'une transaction
     */
    public function rollback() {
        return $this->db->rollback();
    }
    
    /**
     * Filtrage des champs fillable
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Masquage des champs sensibles
     */
    protected function hideFields($data) {
        if (empty($this->hidden) || !is_array($data)) {
            return $data;
        }
        
        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }
    
    /**
     * Validation des données avant sauvegarde
     */
    protected function validate($data, $rules = []) {
        if (empty($rules)) {
            return ['valid' => true, 'data' => $data, 'errors' => []];
        }
        
        $validator = new Validator();
        return $validator->validate($data, $rules);
    }
    
    /**
     * Récupération du nom de la table
     */
    public function getTable() {
        return $this->table;
    }
    
    /**
     * Récupération de la clé primaire
     */
    public function getPrimaryKey() {
        return $this->primaryKey;
    }
    
    /**
     * Récupération des enregistrements récents
     */
    public function getRecent($limit = 10, $orderBy = 'created_at DESC') {
        return $this->getAll($orderBy, $limit);
    }
    
    /**
     * Insertion en lot
     */
    public function bulkInsert($data) {
        if (empty($data)) {
            return false;
        }
        
        // Vérification que tous les enregistrements ont les mêmes champs
        $fields = array_keys($data[0]);
        foreach ($data as $record) {
            if (array_keys($record) !== $fields) {
                throw new Exception("Tous les enregistrements doivent avoir les mêmes champs");
            }
        }
        
        // Ajout des timestamps si nécessaire
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            foreach ($data as &$record) {
                $record['created_at'] = $now;
                $record['updated_at'] = $now;
            }
            $fields[] = 'created_at';
            $fields[] = 'updated_at';
        }
        
        // Construction de la requête
        $fieldsStr = implode(', ', $fields);
        $placeholders = '(' . str_repeat('?,', count($fields) - 1) . '?)';
        $valuesPlaceholders = str_repeat($placeholders . ',', count($data) - 1) . $placeholders;
        
        $sql = "INSERT INTO {$this->table} ({$fieldsStr}) VALUES {$valuesPlaceholders}";
        
        // Préparation des paramètres
        $params = [];
        foreach ($data as $record) {
            foreach ($fields as $field) {
                $params[] = $record[$field];
            }
        }
        
        try {
            return $this->db->query($sql, $params);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'insertion en lot: " . $e->getMessage());
        }
    }
    
    /**
     * Mise à jour en lot
     */
    public function bulkUpdate($conditions, $data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $setClauses = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $setClauses[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses);
        
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $whereClauses = [];
            
            foreach ($conditions as $field => $value) {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $sql .= implode(' AND ', $whereClauses);
        }
        
        try {
            return $this->db->query($sql, $params);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour en lot: " . $e->getMessage());
        }
    }
}