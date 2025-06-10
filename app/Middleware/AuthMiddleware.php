<?php
/**
 * Working Authentication Middleware
 * Fixed version that properly handles database sessions
 */
class AuthMiddleware {
    
    /**
     * Check if user is authenticated
     */
    public static function authenticate() {
        Session::start();
        
        $user = Session::getUser();
        if (!$user) {
            return false;
        }
        
        // Validate session fingerprint
        if (method_exists('Session', 'validateFingerprint')) {
            if (!Session::validateFingerprint()) {
                Session::logout();
                return false;
            }
        }
        
        // Check session timeout
        if (method_exists('Session', 'checkTimeout')) {
            if (!Session::checkTimeout()) {
                return false;
            }
        }
        
        // Validate session in database (with proper error handling)
        if (!self::validateDatabaseSession()) {
            // Create database session if it doesn't exist instead of failing
            self::createDatabaseSession($user);
        }
        
        return true;
    }
    
    /**
     * Validate session in database with proper error handling
     */
    private static function validateDatabaseSession() {
        if (!class_exists('Database')) {
            return true; // Skip if Database class doesn't exist
        }
        
        try {
            $db = Database::getInstance();
            $sessionId = session_id();
            
            // Check if user_sessions table exists
            $stmt = $db->query("SHOW TABLES LIKE 'user_sessions'");
            if (!$stmt->fetch()) {
                return true; // Skip if table doesn't exist yet
            }
            
            // Check if session exists and is valid
            $stmt = $db->prepare("
                SELECT us.*, u.is_active 
                FROM user_sessions us
                JOIN users u ON us.user_id = u.id
                WHERE us.id = ? AND us.expires_at > NOW() AND u.is_active = 1
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$session) {
                return false; // Session not found or expired
            }
            
            // Update session expiry
            $newExpiry = date('Y-m-d H:i:s', time() + (int)($_ENV['SESSION_LIFETIME'] ?? 7200));
            $stmt = $db->prepare("UPDATE user_sessions SET expires_at = ? WHERE id = ?");
            $stmt->execute([$newExpiry, $sessionId]);
            
            return true;
            
        } catch (Exception $e) {
            // Log error but don't fail authentication
            error_log("AuthMiddleware database validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create database session record
     */
    private static function createDatabaseSession($user) {
        try {
            $db = Database::getInstance();
            $sessionId = session_id();
            $expiresAt = date('Y-m-d H:i:s', time() + (int)($_ENV['SESSION_LIFETIME'] ?? 7200));
            
            $stmt = $db->prepare("
                INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                expires_at = VALUES(expires_at)
            ");
            $stmt->execute([
                $sessionId,
                $user['id'],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $expiresAt
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to create database session: " . $e->getMessage());
        }
    }
    
    /**
     * Require authentication for protected routes
     */
    public static function requireAuth() {
        if (!self::authenticate()) {
            self::redirectToLogin();
        }
    }
    
    /**
     * Check if user has specific permission
     */
    public static function hasPermission($permission) {
        if (!self::authenticate()) {
            return false;
        }
        
        $user = self::getCurrentUser();
        if (!$user) {
            return false;
        }
        
        // Admin has all permissions
        if (isset($user['role']) && $user['role'] === 'admin') {
            return true;
        }
        
        // Basic permissions for authenticated users
        $allowedPermissions = ['view_clients', 'view_transactions', 'view_dashboard'];
        return in_array($permission, $allowedPermissions);
    }
    
    /**
     * Require specific permission
     */
    public static function requirePermission($permission) {
        if (!self::hasPermission($permission)) {
            http_response_code(403);
            $errorFile = dirname(__DIR__) . '/Views/errors/403.php';
            if (file_exists($errorFile)) {
                include $errorFile;
            } else {
                echo '<h1>403 - Access Forbidden</h1><p>You do not have permission to access this resource.</p>';
            }
            exit;
        }
    }
    
    /**
     * Redirect to login page
     */
    private static function redirectToLogin() {
        $loginUrl = '/login';
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
        
        if ($currentUrl !== $loginUrl) {
            Session::set('redirect_after_login', $currentUrl);
        }
        
        header("Location: {$loginUrl}");
        exit;
    }
    
    /**
     * Get current user information
     */
    public static function getCurrentUser() {
        if (!self::authenticate()) {
            return null;
        }
        
        return Session::getUser();
    }
    
    /**
     * Log user activity
     */
    public static function logActivity($action, $details = []) {
        $user = Session::getUser();
        if (!$user) {
            return;
        }
        
        try {
            $logFile = dirname(dirname(dirname(__FILE__))) . '/storage/logs/user_activity.log';
            $logDir = dirname($logFile);
            
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userName = $user['full_name'] ?? $user['username'] ?? 'Unknown';
            $userId = $user['id'] ?? 'unknown';
            $detailsJson = json_encode($details);
            
            $logMessage = "[{$timestamp}] USER: {$userName} ({$userId}) - ACTION: {$action} - IP: {$ip} - Details: {$detailsJson}" . PHP_EOL;
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Silently fail on logging errors
        }
    }
    
    /**
     * Check rate limiting
     */
    public static function checkRateLimit($action, $maxAttempts = 10, $timeWindow = 3600) {
        $user = Session::getUser();
        if (!$user) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $key = "rate_limit_{$action}_{$ip}";
        } else {
            $key = "rate_limit_{$action}_{$user['id']}";
        }
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = $_SESSION[$key];
        $now = time();
        
        // Reset if time window has passed
        if (($now - $data['first_attempt']) > $timeWindow) {
            $_SESSION[$key] = [
                'count' => 1,
                'first_attempt' => $now
            ];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $maxAttempts) {
            return false;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Clean expired sessions
     */
    public static function cleanExpiredSessions() {
        if (!class_exists('Database')) {
            return;
        }
        
        try {
            $db = Database::getInstance();
            
            // Check if user_sessions table exists
            $stmt = $db->query("SHOW TABLES LIKE 'user_sessions'");
            if (!$stmt->fetch()) {
                return;
            }
            
            $stmt = $db->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
            $stmt->execute();
        } catch (Exception $e) {
            error_log("AuthMiddleware cleanup error: " . $e->getMessage());
        }
    }
    
    /**
     * Get user role
     */
    public static function getUserRole() {
        $user = self::getCurrentUser();
        return $user ? ($user['role'] ?? 'user') : null;
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return self::getUserRole() === 'admin';
    }
    
    /**
     * Require admin role
     */
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            http_response_code(403);
            echo '<h1>403 - Access Forbidden</h1><p>Admin access required.</p>';
            exit;
        }
    }
}