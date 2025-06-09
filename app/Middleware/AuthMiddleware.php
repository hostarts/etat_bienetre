<?php
/**
 * Authentication Middleware
 * Handles user authentication and authorization
 */
class AuthMiddleware {
    
    /**
     * Check if user is authenticated
     */
    public static function authenticate() {
        // For now, we always return true (no authentication system yet)
        // In a real application, you would check session/token here
        return true;
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
        // For now, all authenticated users have all permissions
        return self::authenticate();
    }
    
    /**
     * Require specific permission
     */
    public static function requirePermission($permission) {
        if (!self::hasPermission($permission)) {
            http_response_code(403);
            include dirname(__DIR__) . '/Views/errors/403.php';
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
            $_SESSION['redirect_after_login'] = $currentUrl;
        }
        
        header("Location: {$loginUrl}");
        exit;
    }
    
    /**
     * Get current user information
     */
    public static function getCurrentUser() {
        // For now, return a default admin user
        return [
            'id' => 1,
            'name' => 'Administrateur',
            'email' => 'admin@bienetre-pharma.dz',
            'role' => 'admin',
            'permissions' => ['*']
        ];
    }
    
    /**
     * Log user activity
     */
    public static function logActivity($action, $details = []) {
        $user = self::getCurrentUser();
        $logFile = dirname(dirname(dirname(__FILE__))) . '/storage/logs/user_activity.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $detailsJson = json_encode($details);
        
        $logMessage = "[{$timestamp}] USER: {$user['name']} ({$user['id']}) - ACTION: {$action} - IP: {$ip} - Details: {$detailsJson}" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check rate limiting for user actions
     */
    public static function checkRateLimit($action, $maxAttempts = 10, $timeWindow = 3600) {
        $user = self::getCurrentUser();
        $key = "rate_limit_{$action}_{$user['id']}";
        
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
}