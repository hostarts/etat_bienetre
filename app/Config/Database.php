<?php
/**
 * Configuration et gestion de la base de données
 * Pattern Singleton pour une seule connexion
 */
class Database {
    private static $instance = null;
    private $pdo;
    private $host;
    private $dbname;
    private $username;
    private $password;
    
    /**
     * Constructeur privé pour Singleton
     */
    private function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->dbname = $_ENV['DB_NAME'] ?? 'bienetre_pharma';
        $this->username = $_ENV['DB_USER'] ?? 'bienetre_user';
        $this->password = $_ENV['DB_PASS'] ?? 'N3*WsMh),,8&gI=A';
        
        $this->connect();
    }
    
    /**
     * Connexion à la base de données
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            $this->logError('Database connection failed: ' . $e->getMessage());
            throw new Exception('Erreur de connexion à la base de données');
        }
    }
    
    /**
     * Récupération de l'instance unique
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Récupération de la connexion PDO
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Exécution d'une requête préparée
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError('Query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Erreur lors de l\'exécution de la requête');
        }
    }
    
    /**
     * Récupération de tous les résultats
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupération d'un seul résultat
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Insertion et récupération de l'ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Démarrage d'une transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Validation d'une transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Annulation d'une transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Vérification de la santé de la connexion
     */
    public function isHealthy() {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Logging des erreurs
     */
    private function logError($message) {
        $logFile = dirname(dirname(dirname(__FILE__))) . '/storage/logs/database.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] ERROR: {$message}" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Empêcher le clonage
     */
    private function __clone() {}
    
    /**
     * Empêcher la désérialisation
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}