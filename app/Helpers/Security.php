<?php
/**
 * Classe de sécurité pour l'application
 */
class Security {
    
    /**
     * Protection de base contre les attaques communes
     */
    public static function basicProtection() {
        // Protection contre les injections de headers
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            $maliciousPatterns = [
                '/\b(union|select|insert|update|delete|drop|create|alter)\b/i',
                '/script/i',
                '/<.*>/i'
            ];
            
            foreach ($maliciousPatterns as $pattern) {
                if (preg_match($pattern, $userAgent)) {
                    self::blockRequest('Malicious user agent detected');
                }
            }
        }
        
        // Protection contre les attaques par déni de service basique
        self::rateLimitCheck();
        
        // Nettoyage des données globales
        self::sanitizeGlobals();
    }
    
    /**
     * Génération d'un token CSRF
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
            time() - $_SESSION['csrf_token_time'] > ($_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Vérification du token CSRF
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        if (time() - $_SESSION['csrf_token_time'] > ($_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600)) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Hashage sécurisé des mots de passe
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Vérification des mots de passe
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Chiffrement symétrique des données sensibles
     */
    public static function encrypt($data) {
        $key = $_ENV['APP_KEY'] ?? 'bp2024_secure_key_32chars_min';
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Déchiffrement des données
     */
    public static function decrypt($encryptedData) {
        $key = $_ENV['APP_KEY'] ?? 'bp2024_secure_key_32chars_min';
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Nettoyage des chaînes de caractères
     */
    public static function sanitizeString($string) {
        $string = trim($string);
        $string = stripslashes($string);
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        return $string;
    }
    
    /**
     * Validation des emails
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Génération d'un identifiant unique sécurisé
     */
    public static function generateSecureId($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Limitation du débit des requêtes
     */
    private static function rateLimitCheck() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $maxAttempts = $_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5;
        $lockoutDuration = $_ENV['LOCKOUT_DURATION'] ?? 900; // 15 minutes
        
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        
        $now = time();
        $key = md5($ip);
        
        // Nettoyage des anciennes entrées
        foreach ($_SESSION['rate_limit'] as $k => $v) {
            if ($now - $v['time'] > $lockoutDuration) {
                unset($_SESSION['rate_limit'][$k]);
            }
        }
        
        // Vérification du nombre de tentatives
        if (isset($_SESSION['rate_limit'][$key])) {
            if ($_SESSION['rate_limit'][$key]['attempts'] >= $maxAttempts) {
                if ($now - $_SESSION['rate_limit'][$key]['time'] < $lockoutDuration) {
                    self::blockRequest('Too many requests');
                } else {
                    unset($_SESSION['rate_limit'][$key]);
                }
            }
        }
        
        // Enregistrement de la tentative
        if (!isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = ['attempts' => 0, 'time' => $now];
        }
        $_SESSION['rate_limit'][$key]['attempts']++;
        $_SESSION['rate_limit'][$key]['time'] = $now;
    }
    
    /**
     * Blocage d'une requête malveillante
     */
    private static function blockRequest($reason) {
        self::logSecurityEvent($reason);
        http_response_code(403);
        die('Access denied');
    }
    
    /**
     * Nettoyage des données globales
     */
    private static function sanitizeGlobals() {
        // Nettoyage récursif des données
        $sanitize = function($data) use (&$sanitize) {
            if (is_array($data)) {
                return array_map($sanitize, $data);
            }
            return is_string($data) ? self::sanitizeString($data) : $data;
        };
        
        $_GET = $sanitize($_GET);
        $_POST = $sanitize($_POST);
        $_COOKIE = $sanitize($_COOKIE);
    }
    
    /**
     * Logging des événements de sécurité
     */
    public static function logSecurityEvent($message) {
        $logFile = dirname(dirname(dirname(__FILE__))) . '/storage/logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $logMessage = "[{$timestamp}] SECURITY: {$message} - IP: {$ip} - URI: {$requestUri} - UA: {$userAgent}" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Génération d'un nonce pour les CSP
     */
    public static function generateNonce() {
        return base64_encode(random_bytes(16));
    }
    
    /**
     * Validation des données d'upload
     */
    public static function validateUpload($file) {
        $allowedExtensions = explode(',', $_ENV['ALLOWED_EXTENSIONS'] ?? 'jpg,jpeg,png,gif,webp,pdf');
        $maxFileSize = $_ENV['MAX_FILE_SIZE'] ?? 5242880; // 5MB
        
        // Vérification de la taille
        if ($file['size'] > $maxFileSize) {
            return ['valid' => false, 'error' => 'Fichier trop volumineux'];
        }
        
        // Vérification de l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return ['valid' => false, 'error' => 'Type de fichier non autorisé'];
        }
        
        // Vérification MIME
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!isset($allowedMimes[$extension]) || $mimeType !== $allowedMimes[$extension]) {
            return ['valid' => false, 'error' => 'Type MIME invalide'];
        }
        
        return ['valid' => true, 'error' => null];
    }
}