<?php
/**
 * Security Helper
 * Provides security-related functionality
 */
class Security {
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString($input) {
        if ($input === null) {
            return null;
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Validate email address
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Hash password using Argon2ID
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }
    
    /**
     * Verify password against hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure random ID
     */
    public static function generateSecureId($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = []) {
        $logFile = STORAGE_PATH . '/logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $detailsJson = json_encode($details);
        
        $logMessage = "[{$timestamp}] SECURITY: {$event} - IP: {$ip} - Details: {$detailsJson}" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check if IP is rate limited
     */
    public static function checkRateLimit($action, $maxAttempts = 10, $timeWindow = 3600) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit_{$action}_{$ip}";
        
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
            self::logSecurityEvent("Rate limit exceeded for {$action}", [
                'ip' => $ip,
                'attempts' => $data['count']
            ]);
            return false;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Clean input for SQL (additional layer)
     */
    public static function cleanInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'cleanInput'], $input);
        }
        
        return self::sanitizeString($input);
    }
    
    /**
     * Generate secure file name
     */
    public static function generateSecureFileName($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        
        return "{$timestamp}_{$random}" . ($extension ? ".{$extension}" : '');
    }
    
    /**
     * Check if request is from allowed referer
     */
    public static function validateReferer($allowedHosts = []) {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        if (empty($referer)) {
            return false;
        }
        
        $refererHost = parse_url($referer, PHP_URL_HOST);
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        
        // Allow same host by default
        if ($refererHost === $currentHost) {
            return true;
        }
        
        // Check allowed hosts
        return in_array($refererHost, $allowedHosts);
    }
}