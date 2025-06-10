<?php
/**
 * Session Management Helper
 * Provides secure session handling functionality with authentication support
 */
class Session {
    
    /**
     * Start session with security settings
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure session security
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerate session ID periodically
            self::regenerateId();
        }
    }
    
    /**
     * Regenerate session ID for security
     */
    public static function regenerateId() {
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Set a session value
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get a session value
     */
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Remove a session value
     */
    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * Check if session key exists
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Clear all session data
     */
    public static function clear() {
        self::start();
        session_unset();
    }
    
    /**
     * Destroy session completely
     */
    public static function destroy() {
        self::start();
        session_destroy();
        
        // Remove session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
    
    /**
     * Set flash message
     */
    public static function setFlash($type, $message) {
        self::set('flash_messages', [
            'type' => $type,
            'message' => $message,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Get and clear flash message
     */
    public static function getFlash() {
        $flash = self::get('flash_messages');
        if ($flash) {
            self::remove('flash_messages');
            return $flash;
        }
        return null;
    }
    
    /**
     * Set multiple flash messages
     */
    public static function setFlashBag($messages) {
        self::set('flash_bag', $messages);
    }
    
    /**
     * Get and clear flash bag
     */
    public static function getFlashBag() {
        $flashBag = self::get('flash_bag');
        if ($flashBag) {
            self::remove('flash_bag');
            return $flashBag;
        }
        return [];
    }
    
    // ==========================================
    // AUTHENTICATION METHODS - NEWLY ADDED
    // ==========================================
    
    /**
     * Store user data in session
     */
    public static function setUser($userData) {
        self::set('user', $userData);
        self::set('user_login_time', time());
    }
    
    /**
     * Get user data from session
     */
    public static function getUser() {
        return self::get('user');
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return self::has('user') && self::get('user') !== null;
    }
    
    /**
     * Log out user
     */
    public static function logout() {
        self::remove('user');
        self::remove('user_login_time');
        self::regenerateId();
    }
    
    /**
     * Get session fingerprint for security
     */
    public static function getFingerprint() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }
    
    /**
     * Validate session fingerprint
     */
    public static function validateFingerprint() {
        $storedFingerprint = self::get('fingerprint');
        $currentFingerprint = self::getFingerprint();
        
        if (!$storedFingerprint) {
            self::set('fingerprint', $currentFingerprint);
            return true;
        }
        
        return hash_equals($storedFingerprint, $currentFingerprint);
    }
    
    /**
     * Check session timeout
     */
    public static function checkTimeout($maxIdleTime = 7200) { // 2 hours default
        $lastActivity = self::get('last_activity');
        
        if ($lastActivity && (time() - $lastActivity) > $maxIdleTime) {
            self::destroy();
            return false;
        }
        
        self::set('last_activity', time());
        return true;
    }
    
    /**
     * Get user ID from session
     */
    public static function getUserId() {
        $user = self::getUser();
        return $user ? $user['id'] : null;
    }
    
    /**
     * Get user role from session
     */
    public static function getUserRole() {
        $user = self::getUser();
        return $user ? $user['role'] : null;
    }
    
    /**
     * Check if current user has specific role
     */
    public static function hasRole($role) {
        return self::getUserRole() === $role;
    }
    
    /**
     * Check if current user is admin
     */
    public static function isAdmin() {
        return self::hasRole('admin');
    }
}